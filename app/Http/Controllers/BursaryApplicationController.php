<?php

namespace App\Http\Controllers;

use App\Mail\BursaryApplicationReceipt;
use App\Mail\BursaryApplicationSubmitted;
use App\Models\Bursary;
use App\Models\BursaryApplication;
use App\Models\BursaryApplicationDocument;
use App\Models\BursaryDocumentRequirement;
use App\Models\UserApplicationDocument;
use App\Models\UserApplicationProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BursaryApplicationController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const ACADEMIC_RECORD_KEYS = [
        'academic_transcript',
        'grade_12_marks',
        'grade_11_marks',
        'matric_certificate',
    ];

    public function store(Request $request, Bursary $bursary): RedirectResponse
    {
        $user = $request->user();
        $providerEmail = $bursary->applicationProviderEmail();
        $providerPostalAddress = $bursary->applicationProviderPostalAddress();
        $isEmailSubmission = $bursary->isEmailSubmission();
        $isPostalSubmission = $bursary->isPostalSubmission();
        $canUseEmailSubmission = $isEmailSubmission && filter_var($providerEmail, FILTER_VALIDATE_EMAIL);
        $deliveryType = $canUseEmailSubmission ? 'email' : ($isPostalSubmission ? 'postal' : 'email');

        if (! Schema::hasTable('bursary_applications') || ! Schema::hasTable('bursary_application_documents')) {
            return back()->withErrors([
                'application' => 'Bursary applications are being prepared. Please run the latest migrations and try again.',
            ]);
        }

        if (! $isEmailSubmission && ! $isPostalSubmission) {
            return back()->withErrors([
                'application' => 'This bursary is not ready for Chamu applications yet.',
            ]);
        }

        if ($deliveryType === 'email' && ! $canUseEmailSubmission) {
            return back()->withErrors([
                'application' => 'This bursary is not ready for Chamu email applications yet.',
            ]);
        }

        $requirements = Schema::hasTable('bursary_document_requirements')
            ? $bursary->documentRequirements()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
            : collect();

        if ($requirements->isEmpty()) {
            $requirements = BursaryDocumentRequirement::defaultEmailSubmissionRequirements();
        }

        $applicationProfile = Schema::hasTable('user_application_profiles')
            ? UserApplicationProfile::query()->where('user_id', $user->id)->first()
            : null;
        $selectedSavedDocumentIds = collect($request->input('saved_documents', []))
            ->flatten()
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();
        $selectedSavedDocuments = Schema::hasTable('user_application_documents') && $selectedSavedDocumentIds->isNotEmpty()
            ? UserApplicationDocument::query()
                ->where('user_id', $user->id)
                ->whereIn('id', $selectedSavedDocumentIds)
                ->get()
            : collect();
        $selectedSavedDocumentsByKey = $selectedSavedDocuments->groupBy('document_key');

        $rules = [
            'applicant_phone' => ['nullable', 'string', 'max:40'],
            'applicant_postal_address' => ['nullable', 'string', 'max:1200'],
            'study_level' => ['nullable', 'string', 'max:80'],
            'institution' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'current_year' => ['nullable', 'string', 'max:80'],
            'funding_need' => ['nullable', 'string', 'max:1200'],
            'household_income' => ['nullable', 'string', 'max:255'],
            'sassa_recipient' => ['nullable', 'boolean'],
            'special_circumstances' => ['nullable', 'array'],
            'special_circumstances.*' => ['string', 'in:disability,vulnerable_child'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['nullable', 'array'],
            'documents.*.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'saved_documents' => ['nullable', 'array'],
            'saved_documents.*' => ['nullable', 'array'],
            'saved_documents.*.*' => ['integer'],
            'consent' => ['accepted'],
        ];

        $validator = Validator::make($request->all(), $rules, [
            'applicant_postal_address.required' => 'Add the postal or return address Chamu should keep with this application.',
            'consent.accepted' => $deliveryType === 'postal'
                ? 'Confirm that you understand this bursary must be printed and submitted by post or hand delivery.'
                : 'Confirm that Chamu may email this application on your behalf.',
            'documents.*.*.mimes' => 'Documents must be PDF, JPG, PNG, DOC, or DOCX files.',
            'documents.*.*.max' => 'Each document may not be larger than 10MB.',
        ]);

        $validator->after(function ($validator) use ($applicationProfile, $deliveryType, $request, $requirements, $selectedSavedDocuments, $selectedSavedDocumentsByKey): void {
            if (
                $deliveryType === 'postal'
                && trim((string) $request->input('applicant_postal_address', $applicationProfile?->applicant_postal_address ?? '')) === ''
            ) {
                $validator->errors()->add('applicant_postal_address', 'Add the postal or return address Chamu should keep with this application.');
            }

            $selectedSavedDocuments->each(function (UserApplicationDocument $document) use ($validator): void {
                if (! Storage::disk($document->storage_disk)->exists($document->path)) {
                    $validator->errors()->add("documents.{$document->document_key}", $document->label.' is saved on your profile, but the file is missing. Please upload it again.');
                }
            });

            $requirements
                ->where('is_required', true)
                ->whereNull('requirement_group')
                ->each(function ($requirement) use ($request, $selectedSavedDocumentsByKey, $validator): void {
                    if (! $this->hasDocumentUpload($request, $requirement->key) && $selectedSavedDocumentsByKey->get($requirement->key, collect())->isEmpty()) {
                        $validator->errors()->add("documents.{$requirement->key}", $requirement->label.' is required.');
                    }
                });

            $academicRequirements = $requirements->where('requirement_group', 'academic_record');

            if ($academicRequirements->isNotEmpty()) {
                $hasAcademicRecord = collect(self::ACADEMIC_RECORD_KEYS)
                    ->contains(fn (string $key): bool => $this->hasDocumentUpload($request, $key) || $selectedSavedDocumentsByKey->get($key, collect())->isNotEmpty());

                if (! $hasAcademicRecord) {
                    $validator->errors()->add('documents.academic_record', 'Upload at least one academic record, transcript, Grade 12 marks, Grade 11 marks, or matric certificate.');
                }
            }
        });

        $data = $validator->validate();
        $requirementsByKey = $requirements->keyBy('key');
        $profileValue = fn (string $key): mixed => filled($data[$key] ?? null)
            ? $data[$key]
            : ($applicationProfile?->{$key} ?? null);
        $specialCircumstances = $data['special_circumstances'] ?? $applicationProfile?->special_circumstances ?? [];
        $sassaRecipient = array_key_exists('sassa_recipient', $data)
            ? (bool) $data['sassa_recipient']
            : (bool) ($applicationProfile?->sassa_recipient ?? false);

        $application = DB::transaction(function () use ($bursary, $deliveryType, $profileValue, $providerEmail, $providerPostalAddress, $request, $requirementsByKey, $sassaRecipient, $selectedSavedDocuments, $specialCircumstances, $user): BursaryApplication {
            $application = BursaryApplication::create([
                'user_id' => $user->id,
                'bursary_id' => $bursary->id,
                'status' => 'pending',
                'delivery_type' => $deliveryType,
                'provider_email' => $providerEmail ?: '',
                'provider_postal_address' => $providerPostalAddress,
                'applicant_name' => $user->name ?: trim($user->first_name.' '.$user->last_name),
                'applicant_email' => $user->email,
                'applicant_phone' => $profileValue('applicant_phone'),
                'applicant_postal_address' => $profileValue('applicant_postal_address'),
                'study_level' => $profileValue('study_level'),
                'institution' => $profileValue('institution'),
                'qualification' => $profileValue('qualification'),
                'current_year' => $profileValue('current_year'),
                'funding_need' => $profileValue('funding_need'),
                'household_income' => $profileValue('household_income'),
                'sassa_recipient' => $sassaRecipient,
                'special_circumstances' => array_values($specialCircumstances),
                'metadata' => [
                    'delivery_type' => $deliveryType,
                    'bursary_title' => $bursary->title,
                    'company_name' => $bursary->company?->name,
                    'source_url' => $bursary->source_url,
                    'supporting_documents' => $bursary->supporting_documents ?? [],
                    'used_application_profile' => $selectedSavedDocuments->isNotEmpty(),
                ],
            ]);

            foreach ($selectedSavedDocuments as $savedDocument) {
                $requirement = $requirementsByKey->get($savedDocument->document_key);
                $path = $this->copySavedDocument($application, $savedDocument);

                BursaryApplicationDocument::create([
                    'bursary_application_id' => $application->id,
                    'bursary_document_requirement_id' => $requirement->id ?? null,
                    'document_key' => $savedDocument->document_key,
                    'original_name' => $savedDocument->original_name,
                    'storage_disk' => $savedDocument->storage_disk,
                    'path' => $path,
                    'mime_type' => $savedDocument->mime_type,
                    'size' => $savedDocument->size,
                ]);
            }

            foreach ((array) $request->file('documents', []) as $documentKey => $files) {
                if (! $requirementsByKey->has($documentKey)) {
                    continue;
                }

                foreach ($this->normaliseFiles($files) as $file) {
                    $requirement = $requirementsByKey->get($documentKey);
                    $path = $this->storeDocument($application, $documentKey, $file);

                    BursaryApplicationDocument::create([
                        'bursary_application_id' => $application->id,
                        'bursary_document_requirement_id' => $requirement->id ?? null,
                        'document_key' => $documentKey,
                        'original_name' => $file->getClientOriginalName(),
                        'storage_disk' => 'local',
                        'path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }

            return $application->load(['bursary.company', 'documents.requirement', 'user']);
        });

        try {
            if ($deliveryType === 'email') {
                Mail::to($providerEmail)->send(new BursaryApplicationSubmitted($application));
            }

            $application->forceFill([
                'status' => $deliveryType === 'postal' ? 'postal_ready' : 'submitted',
                'submitted_at' => now(),
            ])->save();

            Mail::to($application->applicant_email)->send(new BursaryApplicationReceipt(
                $application->fresh(['bursary.company', 'documents.requirement', 'user'])
            ));

            $application->forceFill([
                'receipt_sent_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $application->forceFill([
                'status' => 'failed',
                'metadata' => array_merge($application->metadata ?? [], [
                    'mail_error' => $exception->getMessage(),
                ]),
            ])->save();

            report($exception);

            return back()
                ->withInput($request->except('documents'))
                ->withErrors(['application' => $deliveryType === 'postal'
                    ? 'We saved your postal pack, but the receipt email could not be sent. Please try again.'
                    : 'We saved your application, but the email could not be sent. Please try again.']);
        }

        return redirect()
            ->route('bursaries.show', $bursary)
            ->with('status', $deliveryType === 'postal'
                ? 'Chamu prepared your postal bursary pack. Print and submit it to the provider.'
                : 'Chamu sent your bursary application and emailed you a receipt.');
    }

    private function hasDocumentUpload(Request $request, string $key): bool
    {
        return collect($this->normaliseFiles($request->file("documents.{$key}")))
            ->contains(fn (UploadedFile $file): bool => $file->isValid());
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function normaliseFiles(mixed $files): array
    {
        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return collect($files)
            ->filter(fn ($file): bool => $file instanceof UploadedFile)
            ->values()
            ->all();
    }

    private function storeDocument(BursaryApplication $application, string $documentKey, UploadedFile $file): string
    {
        $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: $documentKey;
        $extension = $file->getClientOriginalExtension();
        $filename = $documentKey.'-'.$basename.'-'.Str::random(8).($extension ? '.'.$extension : '');

        return $file->storeAs('bursary-applications/'.$application->id, $filename, 'local');
    }

    private function copySavedDocument(BursaryApplication $application, UserApplicationDocument $document): string
    {
        $disk = Storage::disk($document->storage_disk);

        if (! $disk->exists($document->path)) {
            throw new RuntimeException('A saved application document could not be found. Please upload it again.');
        }

        $basename = Str::slug(pathinfo($document->original_name, PATHINFO_FILENAME)) ?: $document->document_key;
        $extension = pathinfo($document->original_name, PATHINFO_EXTENSION);
        $filename = $document->document_key.'-'.$basename.'-saved-'.Str::random(8).($extension ? '.'.$extension : '');
        $path = 'bursary-applications/'.$application->id.'/'.$filename;

        if (! $disk->copy($document->path, $path)) {
            throw new RuntimeException('A saved application document could not be attached. Please upload it again.');
        }

        return $path;
    }

}
