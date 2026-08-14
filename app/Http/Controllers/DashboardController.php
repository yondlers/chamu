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
use App\Services\Matching\CourseMatcher;
use App\Services\Reports\StudentReviewService;
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

class DashboardController extends Controller
{
    public function index(Request $request, CourseMatcher $courseMatcher, StudentReviewService $studentReview)
    {
        $user = $request->user();

        $selectedSubjects = collect();
        $latestTerm = null;
        $results = collect();
        $courseMatch = null;
        $dashboardReview = null;
        $pendingQuizzes = collect();
        $recentAttempts = collect();
        $recentBursaryApplications = collect();
        $applicationSummary = (object) [
            'total' => 0,
            'submitted' => 0,
            'postal_ready' => 0,
            'failed' => 0,
        ];

        if ($user->grade_id !== null) {
            $selectedSubjects = DB::table('user_subject_preferences')
                ->join('subjects', 'subjects.id', '=', 'user_subject_preferences.subject_id')
                ->where('user_subject_preferences.user_id', $user->id)
                ->where('user_subject_preferences.grade_id', $user->grade_id)
                ->select('subjects.id', 'subjects.name', 'subjects.code', 'subjects.abbreviation')
                ->orderBy('subjects.name')
                ->get();

            $latestTermId = DB::table('user_subject_results')
                ->where('user_id', $user->id)
                ->where('grade_id', $user->grade_id)
                ->whereNotNull('mark')
                ->orderByDesc('term_id')
                ->value('term_id');

            if ($latestTermId !== null) {
                $latestTerm = DB::table('terms')->where('id', $latestTermId)->first(['id', 'name']);
                $results = DB::table('user_subject_results')
                    ->join('subjects', 'subjects.id', '=', 'user_subject_results.subject_id')
                    ->where('user_subject_results.user_id', $user->id)
                    ->where('user_subject_results.grade_id', $user->grade_id)
                    ->where('user_subject_results.term_id', $latestTermId)
                    ->whereNotNull('user_subject_results.mark')
                    ->select('subjects.name', 'subjects.code', 'subjects.abbreviation', 'user_subject_results.mark', 'user_subject_results.aps_score')
                    ->orderBy('subjects.name')
                    ->get();
            }
        }

        $courseMatch = $courseMatcher->forUser($user, null, 2);

        if ($courseMatch['has_marks']) {
            $term = $courseMatch['term'];
            $latestTerm = $term === null ? null : (object) [
                'id' => $term->id,
                'name' => $term->label ?? $term->term_name ?? 'Latest marks',
            ];
            $results = $courseMatch['results'];
            $dashboardReview = $studentReview->review($user, $courseMatch);
        }

        $isLifeOrientation = function (object $result): bool {
            $code = strtoupper($result->code ?? $result->abbreviation ?? '');

            return $code === 'LO' || strcasecmp($result->name, 'Life Orientation') === 0;
        };

        $apsTotal = $results->reject($isLifeOrientation)->sum(fn ($result) => (int) $result->aps_score);
        $averageMark = $results->reject($isLifeOrientation)->avg('mark');

        $apsProgress = DB::table('user_subject_results')
            ->join('grades', 'grades.id', '=', 'user_subject_results.grade_id')
            ->join('terms', 'terms.id', '=', 'user_subject_results.term_id')
            ->join('subjects', 'subjects.id', '=', 'user_subject_results.subject_id')
            ->where('user_subject_results.user_id', $user->id)
            ->whereNotNull('user_subject_results.mark')
            ->whereIn('terms.name', ['Term 1', 'Term 2', 'Term 3', 'Term 4'])
            ->where(function ($query) {
                $query
                    ->whereNull('subjects.code')
                    ->orWhereRaw('upper(subjects.code) <> ?', ['LO']);
            })
            ->whereRaw('lower(subjects.name) <> ?', ['life orientation'])
            ->select(
                'grades.id as grade_id',
                'grades.name as grade_name',
                'grades.sort_order as grade_sort_order',
                'terms.id as term_id',
                'terms.name as term_name',
                'terms.from_date',
                DB::raw('sum(coalesce(user_subject_results.aps_score, 0)) as aps_total'),
                DB::raw('count(user_subject_results.id) as reported_subjects'),
            )
            ->groupBy(
                'grades.id',
                'grades.name',
                'grades.sort_order',
                'terms.id',
                'terms.name',
                'terms.from_date',
            )
            ->orderBy('grades.sort_order')
            ->orderBy('terms.from_date')
            ->orderBy('terms.name')
            ->get()
            ->map(function ($progress) {
                $termNumber = (int) filter_var($progress->term_name, FILTER_SANITIZE_NUMBER_INT);

                return (object) [
                    'grade_name' => $progress->grade_name,
                    'term_name' => $progress->term_name,
                    'label' => $progress->grade_name.' T'.$termNumber,
                    'aps_total' => (int) $progress->aps_total,
                    'reported_subjects' => (int) $progress->reported_subjects,
                ];
            });

        if ($courseMatch !== null) {
            $apsProgress = $courseMatch['progress'];
        }

        $pendingQuizzes = DB::table('exam_sessions')
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_sessions.subject_id')
            ->where('exam_sessions.user_id', $user->id)
            ->whereNull('exam_sessions.completed_at')
            ->select('exam_sessions.id', 'exam_sessions.title', 'exam_sessions.quiz_type', 'exam_sessions.source', 'subjects.name as subject_name', 'exam_sessions.updated_at')
            ->latest('exam_sessions.updated_at')
            ->limit(4)
            ->get();

        $recentAttempts = DB::table('exam_sessions')
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_sessions.subject_id')
            ->where('exam_sessions.user_id', $user->id)
            ->whereNotNull('exam_sessions.completed_at')
            ->select('exam_sessions.id', 'exam_sessions.title', 'exam_sessions.percentage', 'subjects.name as subject_name', 'exam_sessions.completed_at')
            ->latest('exam_sessions.completed_at')
            ->limit(4)
            ->get();

        if (Schema::hasTable('bursary_applications')) {
            $summary = DB::table('bursary_applications')
                ->where('user_id', $user->id)
                ->selectRaw('count(*) as total')
                ->selectRaw("sum(case when status = 'submitted' then 1 else 0 end) as submitted")
                ->selectRaw("sum(case when status = 'postal_ready' then 1 else 0 end) as postal_ready")
                ->selectRaw("sum(case when status = 'failed' then 1 else 0 end) as failed")
                ->first();

            $applicationSummary = (object) [
                'total' => (int) ($summary->total ?? 0),
                'submitted' => (int) ($summary->submitted ?? 0),
                'postal_ready' => (int) ($summary->postal_ready ?? 0),
                'failed' => (int) ($summary->failed ?? 0),
            ];

            $recentBursaryApplications = DB::table('bursary_applications')
                ->leftJoin('bursaries', 'bursaries.id', '=', 'bursary_applications.bursary_id')
                ->leftJoin('companies', 'companies.id', '=', 'bursaries.company_id')
                ->where('bursary_applications.user_id', $user->id)
                ->select(
                    'bursary_applications.id',
                    'bursary_applications.status',
                    'bursary_applications.delivery_type',
                    'bursary_applications.submitted_at',
                    'bursary_applications.receipt_sent_at',
                    'bursary_applications.created_at',
                    'bursaries.id as bursary_id',
                    'bursaries.title as bursary_title',
                    'companies.name as company_name',
                )
                ->selectSub(function ($query) {
                    $query
                        ->from('bursary_application_documents')
                        ->selectRaw('count(*)')
                        ->whereColumn('bursary_application_documents.bursary_application_id', 'bursary_applications.id');
                }, 'documents_count')
                ->latest('bursary_applications.created_at')
                ->limit(5)
                ->get();
        }

        return view('dashboard.index', [
            'user' => $user,
            'selectedSubjects' => $selectedSubjects,
            'latestTerm' => $latestTerm,
            'results' => $results,
            'apsTotal' => $apsTotal,
            'averageMark' => $averageMark,
            'apsProgress' => $apsProgress,
            'courseMatch' => $courseMatch,
            'dashboardReview' => $dashboardReview,
            'pendingQuizzes' => $pendingQuizzes,
            'recentAttempts' => $recentAttempts,
            'applicationSummary' => $applicationSummary,
            'recentBursaryApplications' => $recentBursaryApplications,
        ]);
            
    }
}
