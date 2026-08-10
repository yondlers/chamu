<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Province;
use App\Models\Qualification;
use App\Models\Subject;
use App\Models\TutorApplication;
use App\Models\TutorApplicationSubject;
use App\Models\TutorAvailability;
use App\Models\TutorMark;
use App\Models\TutorStatus;
use App\Models\University;
use App\Models\User;
use App\Services\SharedApplicationProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TutorApplicationController extends Controller
{
    public function welcome(Request $request)
    {
        $application = $this->findOrCreateDraft($request->user());

        if ($application->isSubmitted()) {
            return redirect()->route('tutor.application.coming-soon');
        }

        return view('tutor.welcome', [
            'user' => $request->user(),
            'application' => $application,
        ]);
    }

    public function show(Request $request)
    {
        $application = $this->findOrCreateDraft($request->user());

        if ($application->isSubmitted()) {
            return redirect()->route('tutor.application.coming-soon');
        }

        $step = max(1, min(TutorApplication::TOTAL_STEPS, $request->integer('step') ?: (int) $application->current_step));

        $application->load([
            'subjects.subject',
            'marks.subject',
            'availabilities',
            'province',
            'selectedUniversity',
            'selectedQualification',
            'tutorStatus',
        ]);

        return view('tutor.application', [
            'user' => $request->user(),
            'application' => $application,
            'step' => $step,
            'steps' => $this->steps(),
            'provinces' => Province::query()->select('id', 'name')->orderBy('name')->get(),
            'subjectOptions' => $this->subjectOptions(),
            'languageOptions' => $this->languageOptions(),
            'syllabusOptions' => $this->syllabusOptions(),
            'levelOptions' => $this->levelOptions(),
            'teachingModeOptions' => $this->teachingModeOptions(),
            'heardFromOptions' => $this->heardFromOptions(),
            'dayOptions' => $this->dayOptions(),
            'universitiesUrl' => route('tutor.lookups.universities'),
            'qualificationsUrl' => route('tutor.lookups.qualifications'),
            'subjectsUrl' => route('tutor.lookups.subjects'),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $application = $this->findOrCreateDraft($user);

        if ($application->isSubmitted()) {
            return redirect()->route('tutor.application.coming-soon');
        }

        $step = max(1, min(TutorApplication::TOTAL_STEPS, $request->integer('step', 1)));
        $action = $request->input('action', 'continue');
        $soft = $action === 'exit';

        $data = $this->validateStep($request, $application, $step, $soft);

        DB::transaction(function () use ($request, $application, $user, $step, $action, $data) {
            if ($step === 1 && $request->hasFile('profile_image')) {
                if ($application->profile_image_path) {
                    Storage::disk('public')->delete($application->profile_image_path);
                }

                $data['profile_image_path'] = $request->file('profile_image')
                    ->store('tutor-profiles/'.$user->id, 'public');
            }

            if ($step === 3 && array_key_exists('marks', $data)) {
                if (! empty($data['marks'])) {
                    $this->syncMarks($application, $data['marks']);
                }
                unset($data['marks']);
            }

            if ($step === 4 && array_key_exists('subjects', $data)) {
                if (! empty($data['subjects'])) {
                    $this->syncSubjects($application, $data['subjects']);
                }
                unset($data['subjects']);
            }

            if ($step === 5 && array_key_exists('availabilities', $data)) {
                if (! empty($data['availabilities'])) {
                    $this->syncAvailabilities($application, $data['availabilities']);
                }
                unset($data['availabilities']);
            }

            if ($step === 6 && ($data['whatsapp_same_as_phone'] ?? false)) {
                $data['whatsapp'] = $data['phone'] ?? $application->phone;
            }

            $nextStep = match ($action) {
                'exit' => $step,
                'submit' => TutorApplication::TOTAL_STEPS,
                default => min(TutorApplication::TOTAL_STEPS, $step + 1),
            };

            $application->fill(array_merge($data, [
                'current_step' => $nextStep,
            ]));

            if ($action === 'submit') {
                $pendingStatus = TutorStatus::query()->where('name', TutorStatus::PENDING)->first();

                $application->status = TutorApplication::STATUS_SUBMITTED;
                $application->submitted_at = now();
                $application->accept_terms = true;
                $application->tutor_status_id = $pendingStatus?->id;
            }

            $application->save();

            app(SharedApplicationProfile::class)->syncFromTutor($user, $application);

            if ($action === 'submit') {
                $user->addRole('tutor');
            }

            AuditLog::query()->create([
                'name' => $action === 'submit' ? 'Tutor application submitted' : 'Tutor application saved',
                'description' => $action === 'submit'
                    ? $user->name.' submitted a tutor application.'
                    : $user->name.' saved tutor application step '.$step.'.',
                'user_id' => $user->id,
                'event' => $action === 'submit' ? 'tutor.application.submitted' : 'tutor.application.saved',
                'auditable_type' => TutorApplication::class,
                'auditable_id' => $application->id,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                'url' => $request->fullUrl(),
                'metadata' => [
                    'step' => $step,
                    'action' => $action,
                    'subject_count' => $application->subjects()->count(),
                    'mark_count' => $application->marks()->count(),
                    'availability_count' => $application->availabilities()->count(),
                    'tutor_status_id' => $application->tutor_status_id,
                ],
            ]);
        });

        if ($action === 'submit') {
            return redirect()
                ->route('tutor.application.coming-soon')
                ->with('status', 'Application received. We will let you know when the Tutor section is ready.');
        }

        if ($action === 'exit') {
            return redirect()
                ->route('dashboard.index')
                ->with('status', 'Your tutor application was saved. You can continue later.');
        }

        return redirect()
            ->route('tutor.application.show', ['step' => min(TutorApplication::TOTAL_STEPS, $step + 1)])
            ->with('status', 'Progress saved.');
    }

    public function comingSoon(Request $request)
    {
        $application = TutorApplication::query()
            ->with(['tutorStatus', 'reviews' => fn ($query) => $query->where('is_visible', true)])
            ->where('user_id', $request->user()->id)
            ->first();

        if ($application === null || ! $application->isSubmitted()) {
            return redirect()->route('tutor.application.welcome');
        }

        return view('tutor.coming-soon', [
            'user' => $request->user(),
            'application' => $application,
        ]);
    }

    public function searchUniversities(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        $universities = University::query()
            ->select('id', 'name', 'abbreviation')
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', '%'.$term.'%')
                        ->orWhere('abbreviation', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->limit(25)
            ->get()
            ->map(fn (University $university) => [
                'id' => $university->id,
                'label' => $university->abbreviation
                    ? $university->name.' ('.$university->abbreviation.')'
                    : $university->name,
            ]);

        return response()->json([
            'results' => $universities,
            'other' => ['id' => 'other', 'label' => 'Other (type the name)'],
        ]);
    }

    public function searchQualifications(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $universityId = $request->integer('university_id') ?: null;

        $qualifications = Qualification::query()
            ->select('id', 'name', 'university_id', 'abbreviation')
            ->when($universityId, fn ($query) => $query->where('university_id', $universityId))
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', '%'.$term.'%')
                        ->orWhere('abbreviation', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->limit(40)
            ->get()
            ->map(fn (Qualification $qualification) => [
                'id' => $qualification->id,
                'label' => $qualification->abbreviation
                    ? $qualification->name.' ('.$qualification->abbreviation.')'
                    : $qualification->name,
            ]);

        return response()->json([
            'results' => $qualifications,
            'other' => ['id' => 'other', 'label' => 'Other (type the course name)'],
        ]);
    }

    public function searchSubjects(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        $subjects = Subject::query()
            ->selectRaw('MIN(id) as id, name')
            ->when($term !== '', fn ($query) => $query->where('name', 'like', '%'.$term.'%'))
            ->groupBy('name')
            ->orderBy('name')
            ->limit(40)
            ->get()
            ->map(fn ($subject) => [
                'id' => (int) $subject->id,
                'label' => $subject->name,
            ]);

        return response()->json([
            'results' => $subjects,
            'other' => ['id' => 'other', 'label' => 'Other (type the subject)'],
        ]);
    }

    private function findOrCreateDraft(User $user): TutorApplication
    {
        $application = TutorApplication::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'status' => TutorApplication::STATUS_DRAFT,
                'current_step' => 1,
                'whatsapp_same_as_phone' => true,
                'languages' => [],
                'teaching_modes' => ['online'],
                'province_id' => $user->province_id,
            ]
        );

        return app(SharedApplicationProfile::class)->prefillTutorDraft($user, $application);
    }

    private function validateStep(Request $request, TutorApplication $application, int $step, bool $soft): array
    {
        $required = $soft ? ['nullable'] : ['required'];
        $arrayRequired = $soft ? ['nullable', 'array'] : ['required', 'array', 'min:1'];

        $data = match ($step) {
            1 => $request->validate([
                'headline' => array_merge($required, ['string', 'max:120']),
                'gender' => ['nullable', 'string', Rule::in(['female', 'male', 'non-binary', 'prefer_not_to_say'])],
                'street' => ['nullable', 'string', 'max:255'],
                'city' => array_merge($required, ['string', 'max:120']),
                'province_id' => array_merge($required, ['exists:provinces,id']),
                'languages' => $arrayRequired,
                'languages.*' => ['string', 'max:80'],
                'profile_image' => [
                    Rule::requiredIf(fn () => ! $soft && blank($application->profile_image_path)),
                    'nullable',
                    'image',
                    'max:5120',
                ],
            ]),
            2 => $this->validateEducationStep($request, $soft),
            3 => $request->validate([
                'marks' => $arrayRequired,
                'marks.*.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
                'marks.*.subject_other' => ['nullable', 'string', 'max:120'],
                'marks.*.mark' => array_merge($required, ['integer', 'min:0', 'max:100']),
                'marks.*.year' => ['nullable', 'integer', 'min:1980', 'max:'.((int) date('Y') + 1)],
                'marks.*.level' => ['nullable', 'string', Rule::in(array_keys($this->levelOptions()))],
            ]),
            4 => $request->validate([
                'subjects' => $arrayRequired,
                'subjects.*.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
                'subjects.*.subject_other' => ['nullable', 'string', 'max:120'],
                'subjects.*.hourly_rate' => array_merge($required, ['numeric', 'min:50', 'max:5000']),
                'subjects.*.level' => array_merge($required, ['string', Rule::in(array_keys($this->levelOptions()))]),
            ]),
            5 => $request->validate([
                'availabilities' => $arrayRequired,
                'availabilities.*.day_of_week' => array_merge($required, ['integer', Rule::in(array_keys($this->dayOptions()))]),
                'availabilities.*.start_time' => array_merge($required, ['date_format:H:i']),
                'availabilities.*.end_time' => array_merge($required, ['date_format:H:i']),
                'availabilities.*.is_available' => ['nullable', 'boolean'],
            ]),
            6 => $request->validate([
                'phone' => array_merge($required, ['string', 'max:40']),
                'whatsapp_same_as_phone' => ['nullable', 'boolean'],
                'whatsapp' => [
                    Rule::requiredIf(fn () => ! $soft && ! $request->boolean('whatsapp_same_as_phone')),
                    'nullable',
                    'string',
                    'max:40',
                ],
                'accept_terms' => $soft ? ['nullable', 'boolean'] : ['accepted'],
            ]),
            default => [],
        };

        if ($step === 3 && ! $soft) {
            $this->assertSubjectSelections($data['marks'] ?? [], 'marks');
        }

        if ($step === 4 && ! $soft) {
            $this->assertSubjectSelections($data['subjects'] ?? [], 'subjects');
        }

        if ($step === 5 && ! $soft) {
            foreach (($data['availabilities'] ?? []) as $index => $slot) {
                if (($slot['end_time'] ?? '') <= ($slot['start_time'] ?? '')) {
                    throw ValidationException::withMessages([
                        "availabilities.{$index}.end_time" => 'End time must be after start time.',
                    ]);
                }
            }
        }

        if ($step === 6) {
            $data['whatsapp_same_as_phone'] = $request->boolean('whatsapp_same_as_phone');
            $data['accept_terms'] = $request->boolean('accept_terms');
        }

        unset($data['profile_image']);

        return $data;
    }

    private function validateEducationStep(Request $request, bool $soft): array
    {
        $required = $soft ? ['nullable'] : ['required'];
        $arrayRequired = $soft ? ['nullable', 'array'] : ['required', 'array', 'min:1'];

        $data = $request->validate([
            'high_school_syllabus' => array_merge($required, ['string', Rule::in(array_keys($this->syllabusOptions()))]),
            'high_school_syllabus_other' => [
                Rule::requiredIf(fn () => ! $soft && $request->input('high_school_syllabus') === 'other'),
                'nullable',
                'string',
                'max:255',
            ],
            'attended_university' => ['nullable', 'boolean'],
            'graduated' => ['nullable', 'boolean'],
            'university_id' => ['nullable', 'integer', 'exists:universities,id'],
            'university_is_other' => ['nullable', 'boolean'],
            'university_other' => ['nullable', 'string', 'max:255'],
            'qualification_id' => ['nullable', 'integer', 'exists:qualifications,id'],
            'qualification_is_other' => ['nullable', 'boolean'],
            'qualification_other' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'tutoring_bio' => array_merge($required, ['string', 'max:3000']),
            'tutoring_experience' => array_merge($required, ['string', 'max:3000']),
            'tutoring_style' => ['nullable', 'string', 'max:255'],
            'experience_years' => array_merge($required, ['integer', 'min:0', 'max:60']),
            'teaching_modes' => $arrayRequired,
            'teaching_modes.*' => ['string', Rule::in(array_keys($this->teachingModeOptions()))],
            'heard_from' => ['nullable', 'string', Rule::in(array_keys($this->heardFromOptions()))],
            'heard_from_other' => [
                Rule::requiredIf(fn () => ! $soft && $request->input('heard_from') === 'other'),
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $data['attended_university'] = $request->boolean('attended_university');
        $data['graduated'] = $request->boolean('graduated');
        $universityIsOther = $request->boolean('university_is_other');
        $qualificationIsOther = $request->boolean('qualification_is_other');

        if ($data['attended_university'] && ! $soft) {
            if (! $universityIsOther && blank($data['university_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'university_id' => 'Select a university/college from the list, or choose Other.',
                ]);
            }

            if ($universityIsOther && blank($data['university_other'] ?? null)) {
                throw ValidationException::withMessages([
                    'university_other' => 'Enter the university or college name.',
                ]);
            }

            if (! $qualificationIsOther && blank($data['qualification_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'qualification_id' => 'Select a course from the list, or choose Other.',
                ]);
            }

            if ($qualificationIsOther && blank($data['qualification_other'] ?? null)) {
                throw ValidationException::withMessages([
                    'qualification_other' => 'Enter the course name.',
                ]);
            }
        }

        if ($universityIsOther) {
            $data['university_id'] = null;
            $data['university'] = $data['university_other'] ?? null;
        } else {
            $data['university_other'] = null;
            $university = filled($data['university_id'] ?? null)
                ? University::query()->find($data['university_id'])
                : null;
            $data['university'] = $university?->name;
        }

        if ($qualificationIsOther) {
            $data['qualification_id'] = null;
            $data['programme'] = $data['qualification_other'] ?? null;
        } else {
            $data['qualification_other'] = null;
            $qualification = filled($data['qualification_id'] ?? null)
                ? Qualification::query()->find($data['qualification_id'])
                : null;
            $data['programme'] = $qualification?->name;
        }

        if (! $data['attended_university']) {
            $data['university_id'] = null;
            $data['university'] = null;
            $data['university_other'] = null;
            $data['qualification_id'] = null;
            $data['programme'] = null;
            $data['qualification_other'] = null;
            $data['graduated'] = false;
        }

        unset($data['university_is_other'], $data['qualification_is_other']);

        return $data;
    }

    private function assertSubjectSelections(array $rows, string $field): void
    {
        foreach ($rows as $index => $row) {
            if (blank($row['subject_id'] ?? null) && blank($row['subject_other'] ?? null)) {
                throw ValidationException::withMessages([
                    "{$field}.{$index}.subject_id" => 'Select a subject or enter an Other subject name.',
                ]);
            }
        }
    }

    private function syncMarks(TutorApplication $application, array $marks): void
    {
        $application->marks()->delete();

        foreach (array_values($marks) as $index => $mark) {
            if (blank($mark['mark'] ?? null)) {
                continue;
            }

            if (blank($mark['subject_id'] ?? null) && blank($mark['subject_other'] ?? null)) {
                continue;
            }

            $subject = filled($mark['subject_id'] ?? null)
                ? Subject::query()->find($mark['subject_id'])
                : null;

            TutorMark::query()->create([
                'tutor_application_id' => $application->id,
                'subject_id' => $subject?->id,
                'subject_other' => $subject ? null : trim((string) ($mark['subject_other'] ?? '')),
                'mark' => (int) $mark['mark'],
                'year' => filled($mark['year'] ?? null) ? (int) $mark['year'] : null,
                'level' => $mark['level'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function syncSubjects(TutorApplication $application, array $subjects): void
    {
        $application->subjects()->delete();

        foreach (array_values($subjects) as $index => $subjectRow) {
            if (blank($subjectRow['hourly_rate'] ?? null)) {
                continue;
            }

            if (blank($subjectRow['subject_id'] ?? null) && blank($subjectRow['subject_other'] ?? null)) {
                continue;
            }

            $subject = filled($subjectRow['subject_id'] ?? null)
                ? Subject::query()->find($subjectRow['subject_id'])
                : null;

            $subjectName = $subject?->name ?: trim((string) ($subjectRow['subject_other'] ?? 'Other'));

            TutorApplicationSubject::query()->create([
                'tutor_application_id' => $application->id,
                'subject_id' => $subject?->id,
                'subject_name' => $subjectName,
                'subject_other' => $subject ? null : trim((string) ($subjectRow['subject_other'] ?? '')),
                'hourly_rate' => $subjectRow['hourly_rate'],
                'level' => $subjectRow['level'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function syncAvailabilities(TutorApplication $application, array $availabilities): void
    {
        $application->availabilities()->delete();

        foreach (array_values($availabilities) as $slot) {
            if (blank($slot['day_of_week'] ?? null) || blank($slot['start_time'] ?? null) || blank($slot['end_time'] ?? null)) {
                continue;
            }

            TutorAvailability::query()->create([
                'tutor_application_id' => $application->id,
                'day_of_week' => (int) $slot['day_of_week'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'is_available' => array_key_exists('is_available', $slot)
                    ? filter_var($slot['is_available'], FILTER_VALIDATE_BOOLEAN)
                    : true,
            ]);
        }
    }

    private function steps(): array
    {
        return [
            1 => ['key' => 'about', 'label' => 'About you', 'copy' => 'Photo, languages, and where you teach from.'],
            2 => ['key' => 'education', 'label' => 'Education', 'copy' => 'University/college, course, bio, and experience.'],
            3 => ['key' => 'marks', 'label' => 'Your marks', 'copy' => 'Share the marks that support the subjects you teach.'],
            4 => ['key' => 'subjects', 'label' => 'Subjects & rates', 'copy' => 'Select subjects you offer and your hourly rate.'],
            5 => ['key' => 'availability', 'label' => 'Availability', 'copy' => 'Tell us which days and times learners can book you.'],
            6 => ['key' => 'contact', 'label' => 'Contact', 'copy' => 'Phone number learners can use to reach you.'],
        ];
    }

    private function subjectOptions()
    {
        return Subject::query()
            ->selectRaw('MIN(id) as id, name')
            ->groupBy('name')
            ->orderBy('name')
            ->get()
            ->map(fn ($subject) => [
                'id' => (int) $subject->id,
                'name' => $subject->name,
            ]);
    }

    private function languageOptions(): array
    {
        return [
            'English',
            'Afrikaans',
            'isiZulu',
            'isiXhosa',
            'Sesotho',
            'Setswana',
            'Sepedi',
            'Xitsonga',
            'siSwati',
            'Tshivenda',
            'isiNdebele',
            'French',
            'Portuguese',
        ];
    }

    private function syllabusOptions(): array
    {
        return [
            'nsc' => 'National Senior Certificate (NSC)',
            'ieeb' => 'Independent Examinations Board (IEB)',
            'cambridge' => 'Cambridge International (IGCSE / AS / A-Level)',
            'other' => 'Other / International',
        ];
    }

    private function levelOptions(): array
    {
        return [
            'high_school' => 'High school',
            'university' => 'University / College',
            'both' => 'High school & university',
        ];
    }

    private function teachingModeOptions(): array
    {
        return [
            'online' => 'Online',
            'in_person' => 'In person',
        ];
    }

    private function heardFromOptions(): array
    {
        return [
            'search' => 'Google / Search',
            'social' => 'Social media',
            'friend' => 'Friend or family referral',
            'university' => 'University notice board',
            'whatsapp' => 'WhatsApp group',
            'other' => 'Other',
        ];
    }

    private function dayOptions(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }
}
