<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Qualification;
use App\Models\University;
use App\Services\Admissions\PublicAdmissionInfoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QualificationController extends Controller
{
    public function show(Request $request, University $university, Qualification $qualification, PublicAdmissionInfoService $admissionInfo): View
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
        $isTvetCollegeQualification = $admissionInfo->isTvetCollegeQualification($qualification);
        $collegeAdmissionSummary = $isTvetCollegeQualification
            ? $admissionInfo->collegeAdmissionSummary($qualification, $rules)
            : null;
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
        $titleSuffix = $isTvetCollegeQualification ? 'Entry Requirements' : 'APS and Requirements';
        $title = $qualification->name.' at '.$university->name.': '.$titleSuffix.' | Chamu';
        $description = $isTvetCollegeQualification
            ? 'View entry grade, programme type, NQF route, subject checks and college admission notes for '.$qualification->name.' at '.$university->name.'.'
            : 'View the APS, subject requirements, qualification type and admission information for '.$qualification->name.' at '.$university->name.'.';

        return view('public.qualifications.show', [
            'university' => $university,
            'qualification' => $qualification,
            'rules' => $rules,
            'scoreSummary' => $scoreSummary,
            'requirements' => $requirements,
            'relatedQualifications' => $relatedQualifications,
            'closingLabel' => $closingLabel,
            'admissionInfo' => $admissionInfo,
            'isTvetCollegeQualification' => $isTvetCollegeQualification,
            'collegeAdmissionSummary' => $collegeAdmissionSummary,
            'originBreadcrumb' => $this->originBreadcrumb($request),
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

    /**
     * @return array{label: string, url: string}|null
     */
    private function originBreadcrumb(Request $request): ?array
    {
        if ($request->query('from') !== 'course-match') {
            return null;
        }

        $courseMatchPath = route('course-match.index', [], false);
        $returnTo = $request->query('return_to');
        $url = $courseMatchPath;

        if (is_string($returnTo) && $returnTo !== '') {
            $parts = parse_url($returnTo);
            $path = $parts['path'] ?? null;
            $host = $parts['host'] ?? null;

            if (
                is_string($path)
                && str_starts_with($path, $courseMatchPath)
                && ($host === null || strcasecmp($host, $request->getHost()) === 0)
            ) {
                $url = $path
                    .(isset($parts['query']) ? '?'.$parts['query'] : '')
                    .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
            }
        }

        return [
            'label' => 'Course matches',
            'url' => $url,
        ];
    }
}
