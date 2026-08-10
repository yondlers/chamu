<?php

namespace App\Services;

use App\Models\TutorApplication;
use App\Models\University;
use App\Models\User;
use App\Models\UserApplicationDocument;
use App\Models\UserApplicationProfile;
use App\Models\UserSubjectResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SharedApplicationProfile
{
    public function prefillTutorDraft(User $user, TutorApplication $application): TutorApplication
    {
        if ($application->isSubmitted() || ! Schema::hasTable('user_application_profiles')) {
            return $application;
        }

        $profile = $user->applicationProfile
            ?? UserApplicationProfile::query()->where('user_id', $user->id)->first();

        if (blank($application->province_id) && filled($user->province_id)) {
            $application->province_id = $user->province_id;
        }

        if (blank($application->phone) && filled($profile?->applicant_phone)) {
            $application->phone = $profile->applicant_phone;
        }

        if (blank($application->whatsapp) && filled($profile?->applicant_phone)) {
            $application->whatsapp = $profile->applicant_phone;
            $application->whatsapp_same_as_phone = true;
        }

        if (blank($application->street) && filled($profile?->applicant_postal_address)) {
            $lines = preg_split('/\R+/', trim((string) $profile->applicant_postal_address)) ?: [];
            $application->street = Str::limit(trim((string) ($lines[0] ?? $profile->applicant_postal_address)), 255, '');

            if (blank($application->city) && isset($lines[1])) {
                $application->city = Str::limit(trim((string) $lines[1]), 120, '');
            }
        }

        if (
            blank($application->university_id)
            && blank($application->university_other)
            && blank($application->university)
            && filled($profile?->institution)
        ) {
            $matchedUniversity = University::query()
                ->where(function ($query) use ($profile): void {
                    $query->where('name', $profile->institution)
                        ->orWhere('abbreviation', $profile->institution);
                })
                ->first(['id', 'name']);

            if ($matchedUniversity) {
                $application->university_id = $matchedUniversity->id;
                $application->university = $matchedUniversity->name;
            } else {
                $application->university_other = $profile->institution;
                $application->university = $profile->institution;
            }

            $application->attended_university = true;
        }

        if (
            blank($application->qualification_id)
            && blank($application->qualification_other)
            && blank($application->programme)
            && filled($profile?->qualification)
        ) {
            $application->qualification_other = $profile->qualification;
            $application->programme = $profile->qualification;
        }

        if ($application->isDirty()) {
            $application->save();
        }

        if ($application->marks()->doesntExist()) {
            $this->seedTutorMarksFromLearnerResults($user, $application);
        }

        return $application->fresh();
    }

    public function syncFromTutor(User $user, TutorApplication $application): void
    {
        if (filled($application->province_id) && (int) $user->province_id !== (int) $application->province_id) {
            $user->forceFill(['province_id' => $application->province_id])->save();
        }

        if (! Schema::hasTable('user_application_profiles')) {
            return;
        }

        $application->loadMissing(['selectedUniversity:id,name', 'selectedQualification:id,name']);

        $institution = $application->selectedUniversity?->name
            ?: ($application->university ?: $application->university_other);
        $qualification = $application->selectedQualification?->name
            ?: ($application->programme ?: $application->qualification_other);
        $postalAddress = collect([$application->street, $application->city])
            ->filter(fn ($value) => filled($value))
            ->implode("\n");

        $attributes = [];

        if (filled($application->phone)) {
            $attributes['applicant_phone'] = $application->phone;
        }

        if (filled($postalAddress)) {
            $attributes['applicant_postal_address'] = $postalAddress;
        }

        if (filled($institution)) {
            $attributes['institution'] = $institution;
        }

        if (filled($qualification)) {
            $attributes['qualification'] = $qualification;
        }

        if ($attributes === []) {
            return;
        }

        UserApplicationProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            $attributes
        );
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<string, array<int, UploadedFile>>  $uploadedDocuments
     */
    public function syncFromBursary(User $user, array $fields, array $uploadedDocuments = []): void
    {
        if (Schema::hasTable('user_application_profiles')) {
            $attributes = [];

            foreach ([
                'applicant_phone',
                'applicant_postal_address',
                'study_level',
                'institution',
                'qualification',
                'current_year',
                'funding_need',
                'household_income',
            ] as $key) {
                if (array_key_exists($key, $fields) && filled($fields[$key])) {
                    $attributes[$key] = $fields[$key];
                }
            }

            if (array_key_exists('sassa_recipient', $fields)) {
                $attributes['sassa_recipient'] = (bool) $fields['sassa_recipient'];
            }

            if (array_key_exists('special_circumstances', $fields) && is_array($fields['special_circumstances'])) {
                $attributes['special_circumstances'] = array_values($fields['special_circumstances']);
            }

            if ($attributes !== []) {
                UserApplicationProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    $attributes
                );
            }
        }

        if (! Schema::hasTable('user_application_documents') || $uploadedDocuments === []) {
            return;
        }

        $definitions = UserApplicationDocument::definitions()->keyBy('key');

        foreach ($uploadedDocuments as $documentKey => $files) {
            if (! $definitions->has($documentKey)) {
                continue;
            }

            $definition = $definitions->get($documentKey);
            $validFiles = collect($files)
                ->filter(fn ($file): bool => $file instanceof UploadedFile && $file->isValid())
                ->values();

            if ($validFiles->isEmpty()) {
                continue;
            }

            if (! $definition->accepts_multiple) {
                UserApplicationDocument::query()
                    ->where('user_id', $user->id)
                    ->where('document_key', $documentKey)
                    ->get()
                    ->each(function (UserApplicationDocument $document): void {
                        Storage::disk($document->storage_disk)->delete($document->path);
                        $document->delete();
                    });
            }

            foreach ($validFiles as $file) {
                $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: $documentKey;
                $extension = $file->getClientOriginalExtension();
                $filename = $documentKey.'-'.$basename.'-'.Str::random(8).($extension ? '.'.$extension : '');
                $path = 'application-profiles/'.$user->id.'/'.$filename;
                $contents = @file_get_contents($file->getRealPath() ?: $file->getPathname());

                if ($contents === false) {
                    continue;
                }

                Storage::disk('local')->put($path, $contents);

                UserApplicationDocument::query()->create([
                    'user_id' => $user->id,
                    'document_key' => $documentKey,
                    'label' => $definition->label,
                    'original_name' => $file->getClientOriginalName(),
                    'storage_disk' => 'local',
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }
    }

    private function seedTutorMarksFromLearnerResults(User $user, TutorApplication $application): void
    {
        if (! Schema::hasTable('user_subject_results') || ! Schema::hasTable('tutor_marks')) {
            return;
        }

        $results = UserSubjectResult::query()
            ->with('subject:id,name')
            ->where('user_id', $user->id)
            ->whereNotNull('mark')
            ->orderByDesc('term_id')
            ->orderByDesc('id')
            ->get()
            ->unique('subject_id')
            ->take(12)
            ->values();

        foreach ($results as $index => $result) {
            $application->marks()->create([
                'subject_id' => $result->subject_id,
                'subject_other' => $result->subject_id ? null : ($result->subject?->name),
                'mark' => (int) $result->mark,
                'level' => 'high_school',
                'sort_order' => $index + 1,
            ]);
        }
    }
}
