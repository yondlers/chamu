<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeToChamu;
use App\Models\AuditLog;
use App\Models\Bursary;
use App\Models\BursaryDocumentRequirement;
use App\Models\SiteVisit;
use App\Models\SocialPost;
use App\Models\SocialPostResponse;
use App\Models\User;
use App\Models\UserApplicationDocument;
use App\Models\UserApplicationProfile;
use App\Models\UserSubjectResult;
use App\Support\Social\FacebookGraph;
use App\Support\Social\InstagramGraph;
use App\Support\Social\LinkedInGraph;
use App\Support\Social\SocialImageStorage;
use App\Support\Social\SocialMediaConfig;
use App\Support\Social\ThreadsGraph;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

class MarkController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->grade_id === null) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Choose your grade before adding marks.');
        }

        $selectedSubjects = DB::table('user_subject_preferences')
            ->join('subjects', 'subjects.id', '=', 'user_subject_preferences.subject_id')
            ->where('user_subject_preferences.user_id', $user->id)
            ->where('user_subject_preferences.grade_id', $user->grade_id)
            ->select('subjects.id', 'subjects.name', 'subjects.code', 'subjects.abbreviation', 'user_subject_preferences.sort_order')
            ->orderBy('user_subject_preferences.sort_order')
            ->get();

        if ($selectedSubjects->isEmpty()) {
            return redirect()
                ->route('subjects.index')
                ->with('status', 'Select your subjects before adding marks.');
        }

        $terms = DB::table('terms')
            ->where('curriculum_id', $user->curriculum_id)
            ->where('grade_id', $user->grade_id)
            ->orderBy('from_date')
            ->orderBy('name')
            ->get(['id', 'name']);

        $termId = $request->integer('term_id') ?: optional($terms->first())->id;

        $results = DB::table('user_subject_results')
            ->where('user_id', $user->id)
            ->where('grade_id', $user->grade_id)
            ->where('term_id', $termId)
            ->get()
            ->keyBy('subject_id');

        return view('marks.index', [
            'user' => $user,
            'subjects' => $selectedSubjects,
            'terms' => $terms,
            'termId' => $termId,
            'results' => $results,
        ]);
            
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if ($user->grade_id === null) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Choose your grade before adding marks.');
        }

        $data = $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
            'marks' => ['nullable', 'array'],
            'marks.*' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $termBelongsToGrade = DB::table('terms')
            ->where('id', $data['term_id'])
            ->where('curriculum_id', $user->curriculum_id)
            ->where('grade_id', $user->grade_id)
            ->exists();

        if (! $termBelongsToGrade) {
            return back()->withErrors(['term_id' => 'Choose a valid term.'])->withInput();
        }

        $selectedSubjectIds = DB::table('user_subject_preferences')
            ->where('user_id', $user->id)
            ->where('grade_id', $user->grade_id)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();

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

        $changedMarks = [];
        $removedMarks = [];
        $submittedSubjectCount = 0;

        foreach (($data['marks'] ?? []) as $subjectId => $mark) {
            $subjectId = (int) $subjectId;

            if (! in_array($subjectId, $selectedSubjectIds, true)) {
                continue;
            }

            $submittedSubjectCount++;

            if ($mark === null || $mark === '') {
                $existingResult = UserSubjectResult::query()
                    ->where('user_id', $user->id)
                    ->where('grade_id', $user->grade_id)
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
                'grade_id' => $user->grade_id,
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
            'name' => 'Marks updated',
            'description' => $user->name.' saved term marks.',
            'user_id' => $user->id,
            'event' => 'marks.updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'url' => $request->fullUrl(),
            'metadata' => [
                'grade_id' => $user->grade_id,
                'term_id' => (int) $data['term_id'],
                'selected_subject_count' => count($selectedSubjectIds),
                'submitted_subject_count' => $submittedSubjectCount,
                'changed_marks' => $changedMarks,
                'removed_marks' => $removedMarks,
            ],
        ]);

        return redirect()
            ->route('marks.index', ['term_id' => $data['term_id']])
            ->with('status', 'Marks updated.');
            
    }
}
