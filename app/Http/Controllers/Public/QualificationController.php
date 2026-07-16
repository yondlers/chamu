<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Qualification;
use App\Models\University;
use App\Services\Admissions\PublicAdmissionInfoService;
use Illuminate\View\View;

class QualificationController extends Controller
{
    public function show(University $university, Qualification $qualification, PublicAdmissionInfoService $admissionInfo): View
    {
        abort_if((int) $qualification->university_id !== (int) $university->id, 404);

        $qualification->load([
            'university.country',
            'faculty',
            'qualificationType',
            'nqfLevel',
            'requiredGrade',
            'qualificationSubjectRequirements' => fn ($query) => $query->orderBy('id'),
            'admissionScoreVariants' => fn ($query) => $query->orderBy('admission_score_required')->orderBy('id'),
        ]);

        $rules = $admissionInfo->relevantAdmissionRules($qualification);
        $scoreSummary = $admissionInfo->admissionScoreSummary($qualification, $rules);
        $requirements = $qualification->qualificationSubjectRequirements
            ->groupBy(fn ($requirement) => $requirement->requirement_group ?: 'requirement_'.$requirement->id);
        $closingLabel = $admissionInfo->closingLabel(
            $qualification->closing_month ?? $qualification->faculty?->closing_month ?? $university->default_closing_month,
            $qualification->closing_day ?? $qualification->faculty?->closing_day ?? $university->default_closing_day,
        );
        $relatedQualifications = $university->qualifications()
            ->with(['faculty', 'qualificationType'])
            ->whereKeyNot($qualification->id)
            ->when($qualification->faculty_id !== null, fn ($query) => $query->where('faculty_id', $qualification->faculty_id))
            ->orderBy('name')
            ->limit(6)
            ->get();

        $canonical = route('public.qualifications.show', [
            'university' => $university->slug,
            'qualification' => $qualification->slug,
        ]);
        $title = $qualification->name.' at '.$university->name.': APS and Requirements | Chamu';
        $description = 'View the APS, subject requirements, qualification type and admission information for '.$qualification->name.' at '.$university->name.'.';

        return view('public.qualifications.show', [
            'university' => $university,
            'qualification' => $qualification,
            'rules' => $rules,
            'scoreSummary' => $scoreSummary,
            'requirements' => $requirements,
            'relatedQualifications' => $relatedQualifications,
            'closingLabel' => $closingLabel,
            'admissionInfo' => $admissionInfo,
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
                                'item' => route('public.universities.show', ['university' => $university->slug]),
                            ],
                            [
                                '@type' => 'ListItem',
                                'position' => 3,
                                'name' => $qualification->name,
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
