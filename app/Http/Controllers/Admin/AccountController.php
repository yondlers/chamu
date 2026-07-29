<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $accountSearch = trim((string) $request->query('account_search', ''));
        $accounts = User::query()
            ->with(['userType', 'curriculum', 'grade', 'province'])
            ->withCount([
                'userSubjectPreferences as subjects_count',
                'userSubjectResults as marks_count' => fn ($query) => $query->whereNotNull('mark'),
            ])
            ->withMax('siteVisits as last_seen_at', 'visited_at')
            ->when($accountSearch !== '', function ($query) use ($accountSearch) {
                $query->where(function ($query) use ($accountSearch) {
                    $query
                        ->where('name', 'like', "%{$accountSearch}%")
                        ->orWhere('first_name', 'like', "%{$accountSearch}%")
                        ->orWhere('last_name', 'like', "%{$accountSearch}%")
                        ->orWhere('username', 'like', "%{$accountSearch}%")
                        ->orWhere('email', 'like', "%{$accountSearch}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.accounts.index', [
            'accounts' => $accounts,
            'accountSearch' => $accountSearch,
        ]);
            
    }

    public function show(User $user)
    {
        $user->load(['userType', 'curriculum', 'grade', 'province', 'country', 'school', 'parent']);

        $selectedSubjects = $user->userSubjectPreferences()
            ->with(['subject', 'grade', 'curriculum'])
            ->orderBy('sort_order')
            ->get();

        $markResults = UserSubjectResult::query()
            ->with(['subject', 'term', 'grade'])
            ->where('user_subject_results.user_id', $user->id)
            ->whereNotNull('user_subject_results.mark')
            ->leftJoin('grades', 'grades.id', '=', 'user_subject_results.grade_id')
            ->leftJoin('terms', 'terms.id', '=', 'user_subject_results.term_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'user_subject_results.subject_id')
            ->select('user_subject_results.*')
            ->orderByDesc('user_subject_results.updated_at')
            ->orderBy('subjects.name')
            ->get();

        $latestResult = $markResults->first();
        $latestMarks = collect();

        if ($latestResult !== null) {
            $latestMarks = UserSubjectResult::query()
                ->with(['subject', 'term', 'grade'])
                ->where('user_subject_results.user_id', $user->id)
                ->where('user_subject_results.grade_id', $latestResult->grade_id)
                ->where('user_subject_results.term_id', $latestResult->term_id)
                ->whereNotNull('user_subject_results.mark')
                ->leftJoin('subjects', 'subjects.id', '=', 'user_subject_results.subject_id')
                ->select('user_subject_results.*')
                ->orderBy('subjects.name')
                ->get();
        }

        $isLifeOrientation = fn (UserSubjectResult $result): bool => Str::lower((string) ($result->subject?->code ?? '')) === 'lo'
            || Str::lower((string) ($result->subject?->name ?? '')) === 'life orientation';
        $countedLatestMarks = $latestMarks->reject($isLifeOrientation);
        $latestApsTotal = (int) $countedLatestMarks->sum(fn (UserSubjectResult $result) => (int) ($result->aps_score ?? 0));
        $latestAverageMark = $countedLatestMarks->avg('mark');
        $marksByTerm = $markResults->groupBy(function (UserSubjectResult $result) {
            return ($result->grade?->name ?? 'Unknown grade').' - '.($result->term?->name ?? 'Unknown term');
        });

        $recentVisits = SiteVisit::query()
            ->where('user_id', $user->id)
            ->latest('visited_at')
            ->limit(30)
            ->get();
        $markAuditLogs = AuditLog::query()
            ->where('event', 'marks.updated')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(30)
            ->get();
        $recentExamSessions = DB::table('exam_sessions')
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_sessions.subject_id')
            ->where('exam_sessions.user_id', $user->id)
            ->select(
                'exam_sessions.id',
                'exam_sessions.title',
                'exam_sessions.quiz_type',
                'exam_sessions.source',
                'exam_sessions.score',
                'exam_sessions.total_marks',
                'exam_sessions.percentage',
                'exam_sessions.completed_at',
                'exam_sessions.updated_at',
                'subjects.name as subject_name'
            )
            ->latest('exam_sessions.updated_at')
            ->limit(10)
            ->get();

        return view('admin.accounts.show', [
            'account' => $user,
            'selectedSubjects' => $selectedSubjects,
            'markResults' => $markResults,
            'latestResult' => $latestResult,
            'latestMarks' => $latestMarks,
            'latestApsTotal' => $latestApsTotal,
            'latestAverageMark' => $latestAverageMark,
            'marksByTerm' => $marksByTerm,
            'recentVisits' => $recentVisits,
            'markAuditLogs' => $markAuditLogs,
            'recentExamSessions' => $recentExamSessions,
        ]);
            
    }
}
