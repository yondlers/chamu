<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Matching\CourseMatcher;
use App\Services\Reports\BursaryReportService;
use App\Services\Reports\StudentReviewService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(Request $request, BursaryReportService $bursaryReport)
    {
        $accountSearch = trim((string) $request->query('account_search', ''));
        $accounts = User::query()
            ->with(['userType', 'curriculum', 'grade'])
            ->withCount([
                'userSubjectResults as marks_count' => fn ($query) => $query->whereNotNull('mark'),
                'applicationDocuments as documents_count',
            ])
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
            ->paginate(20)
            ->withQueryString();

        $accounts->setCollection($accounts->getCollection()->map(function (User $account) use ($bursaryReport): User {
            $account->setAttribute('bursary_report_readiness', $bursaryReport->readinessFor($account));

            return $account;
        }));

        return view('admin.reports.index', [
            'accounts' => $accounts,
            'accountSearch' => $accountSearch,
        ]);
    }

    public function course(
        User $user,
        CourseMatcher $courseMatcher,
        StudentReviewService $reviewService,
    ): Response {
        $courseMatch = $courseMatcher->forUser($user);

        if (! $courseMatch['has_marks']) {
            return redirect()
                ->route('admin.reports.index')
                ->withErrors(['course_report' => $user->name.' has no uploaded marks for a Course Matcher report.']);
        }

        $pdf = Pdf::loadView('reports.course-pdf', [
            'user' => $user,
            'courseMatch' => $courseMatch,
            'courseReview' => $reviewService->review($user, $courseMatch),
            'generatedAt' => now(),
            'brandLogoPath' => public_path('images/brand/chamu-logo.png'),
        ])
            ->setPaper('a4')
            ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

        return $pdf->download($this->filename($user, 'course-matcher'));
    }

    public function bursaries(User $user, BursaryReportService $bursaryReport): Response
    {
        $readiness = $bursaryReport->readinessFor($user);

        if (! $readiness['ready']) {
            return redirect()
                ->route('admin.reports.index')
                ->withErrors(['bursary_report' => $user->name.' is missing application profile data for a bursary report.']);
        }

        $pdf = Pdf::loadView('reports.bursary-pdf', [
            'user' => $user,
            'bursaries' => $bursaryReport->openBursaries(),
            'generatedAt' => now(),
            'brandLogoPath' => public_path('images/brand/chamu-logo.png'),
        ])
            ->setPaper('a4', 'landscape')
            ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

        return $pdf->download($this->filename($user, 'bursaries'));
    }

    private function filename(User $user, string $type): string
    {
        $name = Str::slug((string) ($user->name ?: $user->email ?: 'student'));

        return 'chamu-'.$type.'-'.$name.'-'.now()->format('Ymd').'.pdf';
    }
}
