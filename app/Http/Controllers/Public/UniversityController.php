<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\University;
use App\Services\Admissions\PublicAdmissionInfoService;
use Illuminate\View\View;

class UniversityController extends Controller
{
    public function show(University $university, PublicAdmissionInfoService $admissionInfo): View
    {
        $university->load([
            'country',
            'faculties' => fn ($query) => $query
                ->withCount('qualifications')
                ->orderBy('name'),
        ]);

        $qualificationCount = $university->qualifications()->count();
        $qualificationPreview = $university->qualifications()
            ->with(['faculty', 'qualificationType', 'nqfLevel'])
            ->withCount('qualificationSubjectRequirements')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function ($qualification) use ($admissionInfo) {
                $rules = $admissionInfo->relevantAdmissionRules($qualification);
                $qualification->public_admission_score = $admissionInfo->admissionScoreSummary($qualification, $rules);

                return $qualification;
            });

        $canonical = route('public.universities.show', ['university' => $university->slug]);
        $title = $university->name.' Courses and Requirements | Chamu';
        $description = 'Explore qualifications, faculties and admission information for '.$university->name.'. Check which programmes may match your APS on Chamu.';

        return view('public.universities.show', [
            'university' => $university,
            'qualificationCount' => $qualificationCount,
            'qualificationPreview' => $qualificationPreview,
            'closingLabel' => $admissionInfo->closingLabel($university->default_closing_month, $university->default_closing_day),
            'seo' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'jsonLd' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            [
                                '@type' => 'ListItem',
                                'position' => 1,
                                'name' => 'Chamu',
                                'item' => url('/'),
                            ],
                            [
                                '@type' => 'ListItem',
                                'position' => 2,
                                'name' => $university->name,
                                'item' => $canonical,
                            ],
                        ],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebPage',
                        'name' => $title,
                        'description' => $description,
                        'url' => $canonical,
                    ],
                ],
            ],
        ]);
    }
}
