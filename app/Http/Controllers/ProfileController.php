<?php

namespace App\Http\Controllers;

use App\Models\UserApplicationDocument;
use App\Models\UserApplicationProfile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $showTutorEntryPoints = (bool) config('features.show_tutor_entry_points', false);

        $userTypes = DB::table('user_types')
            ->select('id', 'name')
            ->whereIn('name', ['pupil', 'student', 'tutor', 'teacher', 'parent'])
            ->orderByRaw("case name when 'pupil' then 1 when 'student' then 2 when 'tutor' then 3 when 'teacher' then 4 when 'parent' then 5 else 6 end")
            ->get();

        $curriculums = DB::table('curriculums')
            ->select('id', 'name', 'abbreviation')
            ->when(Schema::hasColumn('curriculums', 'is_live'), fn ($query) => $query->where('is_live', true))
            ->orderBy('abbreviation')
            ->get();

        $grades = DB::table('grades')
            ->select('id', 'curriculum_id', 'name', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        $provinces = DB::table('provinces')
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        if (Schema::hasTable('user_roles')) {
            $user->loadMissing(['roles:id,name', 'userType:id,name']);
        } else {
            $user->loadMissing(['userType:id,name']);
        }

        return view('profile.edit', [
            'user' => $user,
            'userTypes' => $userTypes,
            'selectedRoles' => $user->roleNames()
                ->intersect(['pupil', 'student', 'tutor'])
                ->values()
                ->all(),
            'visibleProfileRoleNames' => $showTutorEntryPoints ? ['pupil', 'student', 'tutor'] : ['pupil', 'student'],
            'showTutorEntryPoints' => $showTutorEntryPoints,
            'curriculums' => $curriculums,
            'grades' => $grades,
            'provinces' => $provinces,
        ]);

    }

    public function update(Request $request)
    {
        $user = $request->user();
        $showTutorEntryPoints = (bool) config('features.show_tutor_entry_points', false);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username,'.$user->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(['pupil', 'student', 'tutor', 'teacher', 'parent'])],
            'user_type_id' => ['nullable', 'exists:user_types,id'],
            'curriculum_id' => ['nullable', 'exists:curriculums,id'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
        ]);

        $roles = collect($data['roles'])
            ->map(fn ($role) => strtolower((string) $role))
            ->unique()
            ->values();

        if (! $showTutorEntryPoints) {
            $roles = $user->hasRole('tutor')
                ? $roles->push('tutor')
                : $roles->reject(fn ($role) => $role === 'tutor')->values();
        }

        foreach (['teacher', 'parent'] as $extraRole) {
            if ($user->hasRole($extraRole)) {
                $roles->push($extraRole);
            }
        }

        $roles = $roles->unique()->values();

        $primaryType = null;
        if (! empty($data['user_type_id'])) {
            $primaryType = DB::table('user_types')
                ->where('id', $data['user_type_id'])
                ->whereIn('name', $roles->all())
                ->first(['id', 'name']);
        }

        if ($primaryType === null) {
            $preferredOrder = ['pupil', 'student', 'tutor', 'teacher', 'parent'];
            $primaryName = collect($preferredOrder)->first(fn ($role) => $roles->contains($role)) ?? $roles->first();
            $primaryType = DB::table('user_types')
                ->where('name', $primaryName)
                ->first(['id', 'name']);
        }

        if ($primaryType === null) {
            return back()
                ->withErrors(['roles' => 'Choose at least one valid account role.'])
                ->withInput();
        }

        $isPupil = $roles->contains('pupil');

        if ($isPupil && empty($data['curriculum_id'])) {
            return back()
                ->withErrors(['curriculum_id' => 'Choose your curriculum for a high school pupil account.'])
                ->withInput();
        }

        $user->forceFill([
            'user_type_id' => $primaryType->id,
            'curriculum_id' => $isPupil ? ($data['curriculum_id'] ?? $user->curriculum_id) : $user->curriculum_id,
            'grade_id' => $isPupil ? ($data['grade_id'] ?? $user->grade_id) : $user->grade_id,
            'province_id' => $data['province_id'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'username' => $data['username'],
            'email' => $data['email'],
            'name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
        ])->save();

        $user->syncRoles($roles->all(), $primaryType->name);

        return redirect()
            ->route('profile.edit')
            ->with('status', $showTutorEntryPoints
                ? 'Profile updated. Shared application details stay available across Pupil, Student, and Tutor.'
                : 'Profile updated. Shared application details stay available across Pupil and Student.');

    }

    public function application(Request $request)
    {
        abort_unless(
            Schema::hasTable('user_application_profiles') && Schema::hasTable('user_application_documents'),
            404
        );

        $user = $request->user();
        $applicationProfile = UserApplicationProfile::firstOrNew(['user_id' => $user->id]);
        $documentDefinitions = UserApplicationDocument::definitions();
        $savedDocuments = UserApplicationDocument::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->filter(fn (UserApplicationDocument $document): bool => $document->existsOnDisk())
            ->groupBy('document_key');

        return view('profile.application', [
            'user' => $user,
            'applicationProfile' => $applicationProfile,
            'documentDefinitions' => $documentDefinitions,
            'savedDocuments' => $savedDocuments,
        ]);

    }

    public function updateApplication(Request $request)
    {
        abort_unless(
            Schema::hasTable('user_application_profiles') && Schema::hasTable('user_application_documents'),
            404
        );

        $user = $request->user();
        $documentDefinitions = UserApplicationDocument::definitions();
        $documentKeys = $documentDefinitions->pluck('key')->all();
        $academicDocumentKeys = $documentDefinitions
            ->where('requirement_group', 'academic_record')
            ->pluck('key')
            ->all();
        $existingDocuments = UserApplicationDocument::query()
            ->where('user_id', $user->id)
            ->get()
            ->filter(fn (UserApplicationDocument $document): bool => $document->existsOnDisk())
            ->groupBy('document_key');

        $normaliseFiles = function (mixed $files): array {
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
        };

        $hasNewDocument = fn (string $key): bool => collect($normaliseFiles($request->file("documents.{$key}")))
            ->contains(fn ($file): bool => $file->isValid());

        $validator = Validator::make($request->all(), [
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
        ], [
            'documents.*.*.mimes' => 'Documents must be PDF, JPG, PNG, DOC, or DOCX files.',
            'documents.*.*.max' => 'Each document may not be larger than 10MB.',
        ]);

        $validator->after(function ($validator) use ($academicDocumentKeys, $documentDefinitions, $existingDocuments, $hasNewDocument): void {
            $documentDefinitions
                ->where('is_required', true)
                ->each(function ($definition) use ($existingDocuments, $hasNewDocument, $validator): void {
                    if ($existingDocuments->get($definition->key, collect())->isEmpty() && ! $hasNewDocument($definition->key)) {
                        $validator->errors()->add("documents.{$definition->key}", $definition->label.' is required.');
                    }
                });

            $hasAcademicDocument = collect($academicDocumentKeys)->contains(function (string $key) use ($existingDocuments, $hasNewDocument): bool {
                return $existingDocuments->get($key, collect())->isNotEmpty() || $hasNewDocument($key);
            });

            if (! $hasAcademicDocument) {
                $validator->errors()->add('documents.academic_record', 'Upload at least one academic record, transcript, Grade 12 marks, Grade 11 marks, or matric certificate.');
            }
        });

        $data = $validator->validate();

        UserApplicationProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'applicant_phone' => $data['applicant_phone'] ?? null,
                'applicant_postal_address' => $data['applicant_postal_address'] ?? null,
                'study_level' => $data['study_level'] ?? null,
                'institution' => $data['institution'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'current_year' => $data['current_year'] ?? null,
                'funding_need' => $data['funding_need'] ?? null,
                'household_income' => $data['household_income'] ?? null,
                'sassa_recipient' => (bool) ($data['sassa_recipient'] ?? false),
                'special_circumstances' => array_values($data['special_circumstances'] ?? []),
            ]
        );

        $definitionsByKey = $documentDefinitions->keyBy('key');

        foreach ((array) $request->file('documents', []) as $documentKey => $files) {
            if (! in_array($documentKey, $documentKeys, true)) {
                continue;
            }

            $definition = $definitionsByKey->get($documentKey);
            $uploadedFiles = $normaliseFiles($files);

            if ($uploadedFiles === []) {
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

            foreach ($uploadedFiles as $file) {
                $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: $documentKey;
                $extension = $file->getClientOriginalExtension();
                $filename = $documentKey.'-'.$basename.'-'.Str::random(8).($extension ? '.'.$extension : '');
                $path = $file->storeAs('application-profiles/'.$user->id, $filename, 'local');

                UserApplicationDocument::create([
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

        return redirect()
            ->route('profile.application')
            ->with('status', 'Application profile saved.');

    }
}
