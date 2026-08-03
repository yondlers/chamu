<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Qualification;
use App\Models\University;
use App\Services\Admissions\PublicAdmissionInfoService;
use App\Support\SourceMeta;
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
            'careers',
            'qualificationSubjectRequirements' => fn ($query) => $query->orderBy('id'),
            'admissionScoreVariants' => fn ($query) => $query->orderBy('admission_score_required')->orderBy('id'),
        ]);

        $rules = $admissionInfo->relevantAdmissionRules($qualification);
        $scoreSummary = $admissionInfo->admissionScoreSummary($qualification, $rules);
        $isTvetCollegeQualification = $admissionInfo->isTvetCollegeQualification($qualification);
        $collegeAdmissionSummary = $isTvetCollegeQualification
            ? $admissionInfo->collegeAdmissionSummary($qualification, $rules)
            : null;
        $usesPassTypeAdmission = ($rules->first()?->admissionRule?->score_type ?? null) === 'pass_type';
        $usesAggregateAverageAdmission = ($rules->first()?->admissionRule?->score_type ?? null) === 'aggregate_average';
        $requirements = $qualification->qualificationSubjectRequirements
            ->groupBy(fn ($requirement) => $requirement->requirement_group ?: 'requirement_'.$requirement->id);
        $originBreadcrumb = $this->originBreadcrumb($request);
        $user = $request->user();
        $qualificationMatch = $user === null
            ? null
            : $admissionInfo->qualificationMatchSummary($qualification, $user, $this->matchTermId($request));
        $hasSavedMarks = $qualificationMatch !== null;
        $qualificationAction = $this->qualificationAction($request, $university, $qualification, $originBreadcrumb, $hasSavedMarks);
        $closingLabel = $admissionInfo->closingLabel(
            $qualification->closing_month ?? $qualification->faculty?->closing_month ?? $university->default_closing_month,
            $qualification->closing_day ?? $qualification->faculty?->closing_day ?? $university->default_closing_day,
        );
        $durationLabel = $this->durationLabel($qualification->duration_years);
        $relatedQualifications = $university->qualifications()
            ->with(['university', 'faculty', 'qualificationType'])
            ->whereKeyNot($qualification->id)
            ->when($qualification->faculty_id !== null, fn ($query) => $query->where('faculty_id', $qualification->faculty_id))
            ->orderBy('name')
            ->limit(6)
            ->get();

        $canonical = route('public.qualifications.show', [
            'university' => $university->slug,
            'qualification' => $qualification->slug,
        ]);
        $titleSuffix = match (true) {
            $isTvetCollegeQualification || $usesPassTypeAdmission => 'Entry Requirements',
            $usesAggregateAverageAdmission => 'Aggregated Average and Requirements',
            default => 'APS and Requirements',
        };
        $title = $qualification->name.' at '.$university->name.': '.$titleSuffix.' | Chamu';
        $description = match (true) {
            $isTvetCollegeQualification => 'View entry grade, programme type, NQF route, subject checks and college admission notes for '.$qualification->name.' at '.$university->name.'.',
            $usesPassTypeAdmission => 'View pass type, subject checks, entry grade, NQF level and admission notes for '.$qualification->name.' at '.$university->name.'.',
            $usesAggregateAverageAdmission => 'View the aggregate average, qualification type, NQF level and admission notes for '.$qualification->name.' at '.$university->name.'.',
            default => 'View the APS, subject requirements, qualification type and admission information for '.$qualification->name.' at '.$university->name.'.',
        };

        return view('public.qualifications.show', [
            'university' => $university,
            'qualification' => $qualification,
            'rules' => $rules,
            'scoreSummary' => $scoreSummary,
            'requirements' => $requirements,
            'relatedQualifications' => $relatedQualifications,
            'closingLabel' => $closingLabel,
            'durationLabel' => $durationLabel,
            'admissionInfo' => $admissionInfo,
            'isTvetCollegeQualification' => $isTvetCollegeQualification,
            'collegeAdmissionSummary' => $collegeAdmissionSummary,
            'usesPassTypeAdmission' => $usesPassTypeAdmission,
            'qualificationAction' => $qualificationAction,
            'qualificationMatch' => $qualificationMatch,
            'originBreadcrumb' => $originBreadcrumb,
            'sourceInfo' => SourceMeta::make(
                $qualification->source_url,
                $qualification->updated_at,
                $university->website,
            ),
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

    private function durationLabel(null|float|string $duration): ?string
    {
        if ($duration === null || $duration === '') {
            return null;
        }

        $value = (float) $duration;
        $formatted = rtrim(rtrim(number_format($value, 1), '0'), '.');

        return $formatted.' '.($value === 1.0 ? 'year' : 'years');
    }

    /**
     * @param  array{label: string, url: string}|null  $originBreadcrumb
     * @return array{label: string, url: string, icon: string, kind: string}
     */
    private function qualificationAction(
        Request $request,
        University $university,
        Qualification $qualification,
        ?array $originBreadcrumb,
        bool $hasSavedMarks
    ): array {
        $publicBrowseUrl = route('aps.index', [
            'university_id' => $university->id,
        ]);
        $matchUrl = route('course-match.index', [
            'university_id' => $university->id,
            'faculty_id' => $qualification->faculty_id,
            'search' => $qualification->name,
        ]);
        $browseUrl = route('course-match.index', [
            'university_id' => $university->id,
        ]);

        if ($request->user() === null) {
            return [
                'label' => 'Browse Qualifications',
                'url' => $publicBrowseUrl,
                'icon' => 'list-search',
                'kind' => 'public_browse',
            ];
        }

        if ($hasSavedMarks) {
            return [
                'label' => $originBreadcrumb ? 'Back to Course matches' : 'View My Matches',
                'url' => $originBreadcrumb['url'] ?? $matchUrl,
                'icon' => $originBreadcrumb ? 'arrow-left' : 'target',
                'kind' => 'saved_match',
            ];
        }

        return [
            'label' => 'Browse Qualifications',
            'url' => $browseUrl,
            'icon' => 'list-search',
            'kind' => 'browse_qualifications',
        ];
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

    private function matchTermId(Request $request): ?int
    {
        $termId = $request->integer('term_id');

        if ($termId !== 0) {
            return $termId;
        }

        $returnTo = $request->query('return_to');

        if (! is_string($returnTo) || $returnTo === '') {
            return null;
        }

        $query = parse_url($returnTo, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return null;
        }

        parse_str($query, $params);
        $returnToTermId = $params['term_id'] ?? null;

        return is_numeric($returnToTermId) ? (int) $returnToTermId : null;
    }
}
