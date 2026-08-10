<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserSubjectResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SubjectController extends Controller
{
    public function welcome(Request $request)
    {
        $user = $request->user();
        $user->addRole('pupil');

        if ($this->userHasSubjectPreferences($user)) {
            return redirect()->route('subjects.index', ['manage' => 1]);
        }

        return view('subjects.welcome', [
            'user' => $user,
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $manage = $request->boolean('manage');
        $continue = $request->boolean('continue');

        if ($user->isPupil() && ! $manage && ! $continue && ! $this->userHasSubjectPreferences($user)) {
            return redirect()->route('subjects.welcome');
        }

        $curriculums = Schema::hasTable('curriculums')
            ? DB::table('curriculums')
                ->select('id', 'name', 'abbreviation')
                ->when(Schema::hasColumn('curriculums', 'is_live'), fn ($query) => $query->where('is_live', true))
                ->orderBy('abbreviation')
                ->get()
            : collect();

        $grades = Schema::hasTable('grades')
            ? DB::table('grades')
                ->select('id', 'curriculum_id', 'name', 'sort_order')
                ->orderBy('sort_order')
                ->get()
            : collect();

        $defaultCurriculum = $curriculums->firstWhere('abbreviation', 'CAPS') ?? $curriculums->first();
        $curriculumId = $request->integer('curriculum_id')
            ?: (int) ($user->curriculum_id ?: optional($defaultCurriculum)->id);

        $gradeOptions = $grades->where('curriculum_id', $curriculumId)->values();
        $defaultGrade = $gradeOptions->firstWhere('name', 'Grade 12') ?? $gradeOptions->first();
        $gradeId = $request->integer('grade_id')
            ?: (int) ($user->grade_id ?: optional($defaultGrade)->id);

        if ($gradeId && ! $gradeOptions->contains('id', $gradeId)) {
            $gradeId = (int) optional($defaultGrade)->id;
        }

        $selectedGrade = $gradeOptions->firstWhere('id', $gradeId);
        $terms = $this->termsForGrade($curriculumId ?: null, $gradeId ?: null, optional($selectedGrade)->name);
        $termId = $request->integer('term_id') ?: optional($terms->first())->id;

        if ($termId && ! $terms->contains('id', $termId)) {
            $termId = optional($terms->first())->id;
        }

        $subjects = collect();
        if ($curriculumId && $gradeId) {
            $subjects = DB::table('subjects')
                ->select('id', 'name', 'code', 'abbreviation', 'sort_order')
                ->where('curriculum_id', $curriculumId)
                ->where('grade_id', $gradeId)
                ->when(Schema::hasColumn('subjects', 'is_live'), fn ($query) => $query->where('is_live', true))
                ->orderBy('name')
                ->get();
        }

        $selectedSubjectIds = [];
        if ($user->grade_id !== null && (int) $user->grade_id === (int) $gradeId) {
            $selectedSubjectIds = DB::table('user_subject_preferences')
                ->where('user_id', $user->id)
                ->where('grade_id', $gradeId)
                ->pluck('subject_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $results = collect();
        if ($gradeId && $termId) {
            $results = DB::table('user_subject_results')
                ->where('user_id', $user->id)
                ->where('grade_id', $gradeId)
                ->where('term_id', $termId)
                ->get()
                ->keyBy('subject_id');
        }

        return view('subjects.index', [
            'user' => $user,
            'curriculums' => $curriculums,
            'grades' => $grades,
            'curriculumId' => $curriculumId,
            'gradeId' => $gradeId,
            'terms' => $terms,
            'termId' => $termId,
            'subjects' => $subjects,
            'selectedSubjectIds' => $selectedSubjectIds,
            'results' => $results,
            'manage' => $manage,
            'continue' => $continue,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $manage = $request->boolean('manage');

        $data = $request->validate([
            'curriculum_id' => ['required', 'exists:curriculums,id'],
            'grade_id' => ['required', 'exists:grades,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'subjects' => ['required', 'array', 'min:7'],
            'subjects.*' => ['integer', 'exists:subjects,id'],
            'marks' => ['nullable', 'array'],
            'marks.*' => ['nullable', 'integer', 'min:0', 'max:100'],
        ], [
            'subjects.required' => 'Select at least 7 subjects.',
            'subjects.min' => 'Select at least 7 subjects.',
        ]);

        $grade = DB::table('grades')
            ->where('id', $data['grade_id'])
            ->where('curriculum_id', $data['curriculum_id'])
            ->first(['id', 'name', 'curriculum_id']);

        if ($grade === null) {
            return back()
                ->withInput()
                ->withErrors(['grade_id' => 'Choose a valid grade for your curriculum.']);
        }

        $allowedTermNames = $this->allowedTermNames($grade->name);
        $termBelongsToGrade = DB::table('terms')
            ->where('id', $data['term_id'])
            ->where('curriculum_id', $data['curriculum_id'])
            ->where('grade_id', $data['grade_id'])
            ->whereIn('name', $allowedTermNames)
            ->exists();

        if (! $termBelongsToGrade) {
            return back()
                ->withInput()
                ->withErrors(['term_id' => 'Choose a valid term for your grade.']);
        }

        $subjectIds = collect($data['subjects'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $allowedSubjectIds = DB::table('subjects')
            ->select('id')
            ->where('curriculum_id', $data['curriculum_id'])
            ->where('grade_id', $data['grade_id'])
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($allowedSubjectIds->count() < 7) {
            return back()
                ->withInput()
                ->withErrors(['subjects' => 'Select at least 7 subjects from your grade and curriculum.']);
        }

        $aps = function (int $mark): int {
            return match (true) {
                $mark >= 80 => 7,
                $mark >= 70 => 6,
                $mark >= 60 => 5,
                $mark >= 50 => 4,
                $mark >= 40 => 3,
                $mark >= 30 => 2,
                default => 1,
            };
        };

        DB::transaction(function () use ($user, $data, $allowedSubjectIds, $aps) {
            $user->forceFill([
                'curriculum_id' => $data['curriculum_id'],
                'grade_id' => $data['grade_id'],
            ])->save();

            $user->addRole('pupil');

            DB::table('user_subject_preferences')
                ->where('user_id', $user->id)
                ->where('grade_id', $data['grade_id'])
                ->delete();

            foreach ($allowedSubjectIds as $index => $subjectId) {
                DB::table('user_subject_preferences')->insert([
                    'user_id' => $user->id,
                    'curriculum_id' => $data['curriculum_id'],
                    'grade_id' => $data['grade_id'],
                    'subject_id' => $subjectId,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $changedMarks = [];
            $removedMarks = [];
            $submittedSubjectCount = 0;
            $selectedSubjectIds = $allowedSubjectIds->all();

            foreach (($data['marks'] ?? []) as $subjectId => $mark) {
                $subjectId = (int) $subjectId;

                if (! in_array($subjectId, $selectedSubjectIds, true)) {
                    continue;
                }

                $submittedSubjectCount++;

                if ($mark === null || $mark === '') {
                    $existingResult = UserSubjectResult::query()
                        ->where('user_id', $user->id)
                        ->where('grade_id', $data['grade_id'])
                        ->where('term_id', $data['term_id'])
                        ->where('subject_id', $subjectId)
                        ->first();

                    if ($existingResult !== null) {
                        $removedMarks[] = [
                            'subject_id' => $subjectId,
                            'previous_mark' => $existingResult->mark,
                            'previous_aps_score' => $existingResult->aps_score,
                        ];
                        $existingResult->delete();
                    }

                    continue;
                }

                $mark = (int) $mark;
                $apsScore = $aps($mark);

                $result = UserSubjectResult::firstOrNew([
                    'user_id' => $user->id,
                    'grade_id' => $data['grade_id'],
                    'term_id' => $data['term_id'],
                    'subject_id' => $subjectId,
                ]);
                $previousMark = $result->exists ? $result->mark : null;
                $previousApsScore = $result->exists ? $result->aps_score : null;

                $result->fill([
                    'mark' => $mark,
                    'aps_score' => $apsScore,
                ]);
                $result->save();

                $changedMarks[] = [
                    'subject_id' => $subjectId,
                    'result_id' => $result->id,
                    'previous_mark' => $previousMark,
                    'new_mark' => $mark,
                    'previous_aps_score' => $previousApsScore,
                    'new_aps_score' => $apsScore,
                ];
            }

            AuditLog::create([
                'name' => 'Subjects and marks updated',
                'description' => $user->name.' saved subjects and term marks.',
                'user_id' => $user->id,
                'event' => 'subjects.marks.updated',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 250, ''),
                'url' => request()->fullUrl(),
                'metadata' => [
                    'grade_id' => (int) $data['grade_id'],
                    'term_id' => (int) $data['term_id'],
                    'selected_subject_count' => count($selectedSubjectIds),
                    'submitted_subject_count' => $submittedSubjectCount,
                    'changed_marks' => $changedMarks,
                    'removed_marks' => $removedMarks,
                ],
            ]);
        });

        $redirectParams = [
            'manage' => $manage ? 1 : null,
            'curriculum_id' => $data['curriculum_id'],
            'grade_id' => $data['grade_id'],
            'term_id' => $data['term_id'],
        ];

        if (! $manage) {
            return redirect()
                ->route('aps.index')
                ->with('status', 'Subjects and latest marks saved. You can browse courses any time.');
        }

        return redirect()
            ->route('subjects.index', array_filter($redirectParams))
            ->with('status', 'Subjects and marks updated.');
    }

    private function userHasSubjectPreferences(User $user): bool
    {
        return DB::table('user_subject_preferences')
            ->where('user_id', $user->id)
            ->exists();
    }

    private function allowedTermNames(?string $gradeName): array
    {
        if ($gradeName === 'Grade 12') {
            return ['Term 1', 'Term 2', 'Term 3', 'NSC'];
        }

        return ['Term 1', 'Term 2', 'Term 3', 'Term 4'];
    }

    private function termsForGrade(?int $curriculumId, ?int $gradeId, ?string $gradeName)
    {
        if ($curriculumId === null || $gradeId === null) {
            return collect();
        }

        $allowed = $this->allowedTermNames($gradeName);

        return DB::table('terms')
            ->where('curriculum_id', $curriculumId)
            ->where('grade_id', $gradeId)
            ->whereIn('name', $allowed)
            ->orderByRaw("case name when 'Term 1' then 1 when 'Term 2' then 2 when 'Term 3' then 3 when 'Term 4' then 4 when 'NSC' then 4 else 5 end")
            ->get(['id', 'name']);
    }
}
