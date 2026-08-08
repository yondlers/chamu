<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TutorApplication;
use App\Models\TutorApplicationSubject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

        return view('tutor.application', [
            'user' => $request->user(),
            'application' => $application->load(['subjects', 'province']),
            'step' => $step,
            'steps' => $this->steps(),
            'provinces' => $this->provinces(),
            'subjectOptions' => $this->subjectOptions(),
            'languageOptions' => $this->languageOptions(),
            'syllabusOptions' => $this->syllabusOptions(),
            'levelOptions' => $this->levelOptions(),
            'teachingModeOptions' => $this->teachingModeOptions(),
            'heardFromOptions' => $this->heardFromOptions(),
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

        $data = $this->validateStep($request, $step, $action === 'exit');

        DB::transaction(function () use ($request, $application, $user, $step, $action, $data) {
            if ($step === 1 && $request->hasFile('profile_image')) {
                if ($application->profile_image_path) {
                    Storage::disk('public')->delete($application->profile_image_path);
                }

                $data['profile_image_path'] = $request->file('profile_image')
                    ->store('tutor-profiles/'.$user->id, 'public');
            }

            if ($step === 3) {
                if (! empty($data['subjects'])) {
                    $this->syncSubjects($application, $data['subjects']);
                }
                unset($data['subjects']);
            }

            if ($step === 4 && ($data['whatsapp_same_as_phone'] ?? false)) {
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
                $application->status = TutorApplication::STATUS_SUBMITTED;
                $application->submitted_at = now();
                $application->accept_terms = true;
            }

            $application->save();

            if ($action === 'submit') {
                $this->promoteUserToTutor($user);

                AuditLog::create([
                    'name' => 'Tutor application submitted',
                    'description' => $user->name.' submitted a tutor application.',
                    'user_id' => $user->id,
                    'event' => 'tutor.application.submitted',
                    'auditable_type' => TutorApplication::class,
                    'auditable_id' => $application->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                    'url' => $request->fullUrl(),
                    'metadata' => [
                        'subject_count' => $application->subjects()->count(),
                        'phone' => $application->phone,
                    ],
                ]);
            } else {
                AuditLog::create([
                    'name' => 'Tutor application saved',
                    'description' => $user->name.' saved tutor application step '.$step.'.',
                    'user_id' => $user->id,
                    'event' => 'tutor.application.saved',
                    'auditable_type' => TutorApplication::class,
                    'auditable_id' => $application->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                    'url' => $request->fullUrl(),
                    'metadata' => [
                        'step' => $step,
                        'action' => $action,
                    ],
                ]);
            }
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

    private function findOrCreateDraft(User $user): TutorApplication
    {
        return TutorApplication::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'status' => TutorApplication::STATUS_DRAFT,
                'current_step' => 1,
                'whatsapp_same_as_phone' => true,
                'languages' => [],
                'teaching_modes' => ['online'],
            ]
        );
    }

    private function validateStep(Request $request, int $step, bool $soft): array
    {
        $required = $soft ? ['nullable'] : ['required'];
        $arrayRequired = $soft ? ['nullable', 'array'] : ['required', 'array', 'min:1'];
        $existingImage = TutorApplication::query()
            ->where('user_id', $request->user()->id)
            ->value('profile_image_path');

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
                    Rule::requiredIf(fn () => ! $soft && blank($existingImage)),
                    'nullable',
                    'image',
                    'max:5120',
                ],
            ]),
            2 => $request->validate([
                'high_school_syllabus' => array_merge($required, ['string', Rule::in(array_keys($this->syllabusOptions()))]),
                'attended_university' => ['nullable', 'boolean'],
                'graduated' => ['nullable', 'boolean'],
                'university' => ['nullable', 'string', 'max:255'],
                'programme' => ['nullable', 'string', 'max:255'],
                'specialization' => ['nullable', 'string', 'max:255'],
                'tutoring_bio' => array_merge($required, ['string', 'max:3000']),
                'tutoring_experience' => array_merge($required, ['string', 'max:3000']),
                'tutoring_style' => ['nullable', 'string', 'max:255'],
                'experience_years' => array_merge($required, ['integer', 'min:0', 'max:60']),
                'teaching_modes' => $arrayRequired,
                'teaching_modes.*' => ['string', Rule::in(array_keys($this->teachingModeOptions()))],
                'heard_from' => ['nullable', 'string', Rule::in(array_keys($this->heardFromOptions()))],
            ]),
            3 => $request->validate([
                'subjects' => $arrayRequired,
                'subjects.*.subject_name' => array_merge($required, ['string', 'max:120']),
                'subjects.*.hourly_rate' => array_merge($required, ['numeric', 'min:50', 'max:5000']),
                'subjects.*.level' => array_merge($required, ['string', Rule::in(array_keys($this->levelOptions()))]),
            ]),
            4 => $request->validate([
                'phone' => array_merge($required, ['string', 'max:40']),
                'whatsapp_same_as_phone' => ['nullable', 'boolean'],
                'whatsapp' => [
                    Rule::requiredIf(fn () => ! $soft && ! $request->boolean('whatsapp_same_as_phone')),
                    'nullable',
                    'string',
                    'max:40',
                ],
                'accept_terms' => $soft
                    ? ['nullable', 'boolean']
                    : ['accepted'],
            ]),
            default => [],
        };

        if ($step === 2) {
            $data['attended_university'] = $request->boolean('attended_university');
            $data['graduated'] = $request->boolean('graduated');
        }

        if ($step === 4) {
            $data['whatsapp_same_as_phone'] = $request->boolean('whatsapp_same_as_phone');
            $data['accept_terms'] = $request->boolean('accept_terms');
        }

        unset($data['profile_image']);

        return $data;
    }

    private function syncSubjects(TutorApplication $application, array $subjects): void
    {
        $application->subjects()->delete();

        foreach (array_values($subjects) as $index => $subject) {
            if (blank($subject['subject_name'] ?? null) || blank($subject['hourly_rate'] ?? null)) {
                continue;
            }

            TutorApplicationSubject::create([
                'tutor_application_id' => $application->id,
                'subject_name' => trim((string) $subject['subject_name']),
                'hourly_rate' => $subject['hourly_rate'],
                'level' => $subject['level'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function promoteUserToTutor(User $user): void
    {
        if (! Schema::hasTable('user_types')) {
            return;
        }

        $tutorTypeId = DB::table('user_types')->where('name', 'tutor')->value('id');

        if ($tutorTypeId === null) {
            DB::table('user_types')->insert([
                'name' => 'tutor',
                'description' => 'Tutor account for offering subject tutoring to learners.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $tutorTypeId = DB::table('user_types')->where('name', 'tutor')->value('id');
        }

        if ($tutorTypeId !== null && (int) $user->user_type_id !== (int) $tutorTypeId) {
            $user->forceFill([
                'user_type_id' => $tutorTypeId,
                'curriculum_id' => null,
                'grade_id' => null,
            ])->save();
        }
    }

    private function steps(): array
    {
        return [
            1 => ['key' => 'about', 'label' => 'About you', 'copy' => 'Photo, languages, and where you teach from.'],
            2 => ['key' => 'profile', 'label' => 'Tutoring profile', 'copy' => 'Bio, experience, education, and teaching modes.'],
            3 => ['key' => 'rates', 'label' => 'Subjects & rates', 'copy' => 'Set your hourly rate for each subject.'],
            4 => ['key' => 'contact', 'label' => 'Contact', 'copy' => 'Phone number learners can use to reach you.'],
        ];
    }

    private function provinces()
    {
        return Schema::hasTable('provinces')
            ? DB::table('provinces')->select('id', 'name')->orderBy('name')->get()
            : collect();
    }

    private function subjectOptions(): array
    {
        $fromDb = Schema::hasTable('subjects')
            ? DB::table('subjects')
                ->select('name')
                ->distinct()
                ->orderBy('name')
                ->pluck('name')
                ->filter()
                ->values()
                ->all()
            : [];

        $popular = [
            'Mathematics',
            'Mathematical Literacy',
            'Physical Sciences',
            'Life Sciences',
            'English Home Language',
            'English First Additional Language',
            'Afrikaans Home Language',
            'Accounting',
            'Business Studies',
            'Economics',
            'Geography',
            'History',
            'Computer Applications Technology',
            'Information Technology',
            'Engineering Graphics and Design',
        ];

        return collect($popular)
            ->merge($fromDb)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
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
}
