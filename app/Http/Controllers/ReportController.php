<?php

namespace App\Http\Controllers;

use App\Services\Matching\CourseMatcher;
use App\Services\Reports\BursaryReportService;
use App\Services\Reports\StudentReviewService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(
        Request $request,
        CourseMatcher $courseMatcher,
        BursaryReportService $bursaryReport,
        StudentReviewService $reviewService,
    ) {
        $user = $request->user();
        $courseMatch = $courseMatcher->forUser($user, null, 2);
        $bursaryReadiness = $bursaryReport->readinessFor($user);

        return view('reports.index', [
            'user' => $user,
            'courseMatch' => $courseMatch,
            'courseReview' => $courseMatch['has_marks'] ? $reviewService->savedOrTemplateReview($user, $courseMatch) : null,
            'bursaryReadiness' => $bursaryReadiness,
            'openBursaryCount' => $bursaryReadiness['ready'] ? $bursaryReport->openBursaries()->count() : 0,
        ]);
    }

    public function course(
        Request $request,
        CourseMatcher $courseMatcher,
        StudentReviewService $reviewService,
    ): Response {
        $user = $request->user();
        $courseMatch = $courseMatcher->forUser($user);

        if (! $courseMatch['has_marks']) {
            return redirect()
                ->route('reports.index')
                ->withErrors(['course_report' => 'Upload subject marks before pulling a Course Matcher report.']);
        }

        $this->prepareLargeReportRuntime();

        $pdf = Pdf::loadView('reports.course-pdf', [
            'user' => $user,
            'courseMatch' => $courseMatch,
            'courseReview' => $reviewService->savedOrTemplateReview($user, $courseMatch),
            'generatedAt' => now(),
            'brandLogoPath' => public_path('images/brand/chamu-logo.png'),
        ])
            ->setPaper('a4')
            ->setOption([
                'defaultFont' => 'DejaVu Sans',
                'dpi' => 96,
                'isFontSubsettingEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        return $pdf->download($this->filename($user, 'course-matcher'));
    }

    public function bursaries(Request $request, BursaryReportService $bursaryReport): Response
    {
        $user = $request->user();
        $readiness = $bursaryReport->readinessFor($user);

        if (! $readiness['ready']) {
            return redirect()
                ->route('reports.index')
                ->withErrors(['bursary_report' => 'Complete your application profile and required documents before pulling a bursary report.']);
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

    private function filename(object $user, string $type): string
    {
        $name = Str::slug((string) ($user->name ?: $user->email ?: 'student'));

        return 'chamu-'.$type.'-'.$name.'-'.now()->format('Ymd').'.pdf';
    }

    private function prepareLargeReportRuntime(): void
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);
    }
}
