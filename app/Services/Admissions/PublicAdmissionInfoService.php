<?php

namespace App\Services\Admissions;

use App\Models\AdmissionRule;
use App\Models\Qualification;
use App\Models\QualificationSubjectRequirement;
use App\Models\UniversityAdmissionRule;
use App\Models\User;
use App\Support\TvetColleges;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicAdmissionInfoService
{
    /**
     * @return Collection<int, UniversityAdmissionRule>
     */
    public function relevantAdmissionRules(Qualification $qualification): Collection
    {
        return UniversityAdmissionRule::query()
            ->with('admissionRule')
            ->where('university_id', $qualification->university_id)
            ->whereHas('admissionRule', fn ($query) => $query->where('is_active', true))
            ->where(function ($query) use ($qualification) {
                $query
                    ->where('qualification_id', $qualification->id)
                    ->orWhere(function ($query) use ($qualification) {
                        $query
                            ->whereNull('qualification_id')
                            ->where('faculty_id', $qualification->faculty_id);
                    })
                    ->orWhere(function ($query) {
                        $query
                            ->whereNull('qualification_id')
                            ->whereNull('faculty_id');
                    });
            })
            ->orderBy('priority')
            ->get()
            ->sortBy([
                fn (UniversityAdmissionRule $rule) => (int) $rule->priority,
                fn (UniversityAdmissionRule $rule) => $rule->qualification_id !== null ? -3 : ($rule->faculty_id !== null ? -2 : -1),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, UniversityAdmissionRule>  $rules
     * @return array{label: string, value: string, raw: float|null, source: string|null}
     */
    public function admissionScoreSummary(Qualification $qualification, Collection $rules): array
    {
        $rule = $rules->first();
        $usesAggregateAverage = ($rule?->admissionRule?->score_type ?? null) === 'aggregate_average';
        $usesPassType = ($rule?->admissionRule?->score_type ?? null) === 'pass_type';

        if ($usesPassType) {
            $requiredPassType = $qualification->minimum_pass_type ?? $rule?->admissionRule?->minimum_pass_type;

            return [
                'label' => $rule?->admissionRule?->score_label ?? 'Pass type',
                'value' => $requiredPassType === null ? 'Pass required' : $this->passTypeLabel($requiredPassType),
                'raw' => null,
                'source' => $rule?->admissionRule?->name,
            ];
        }

        if (($rule?->admissionRule?->score_type ?? null) === 'subject_levels') {
            return [
                'label' => 'Entry basis',
                'value' => $qualification->requiredGrade?->name
                    ? $qualification->requiredGrade->name.' or equivalent'
                    : 'Subject-level check',
                'raw' => null,
                'source' => $rule?->admissionRule?->name,
            ];
        }

        $value = match (true) {
            $qualification->admission_score_required !== null => (float) $qualification->admission_score_required,
            $usesAggregateAverage && $qualification->aggregate_average_required !== null => (float) $qualification->aggregate_average_required,
            $qualification->aps_required !== null => (float) $qualification->aps_required,
            default => null,
        };
        $suffix = $rule?->admissionRule?->score_suffix ?? ($usesAggregateAverage ? '%' : '');

        return [
            'label' => $rule?->admissionRule?->score_label ?? ($usesAggregateAverage ? 'Aggregate average' : 'APS'),
            'value' => $value === null ? 'Not listed' : $this->formatScore($value, $suffix),
            'raw' => $value,
            'source' => $rule?->admissionRule?->name,
        ];
    }

    public function isTvetCollegeQualification(Qualification $qualification): bool
    {
        $university = $qualification->university;

        return TvetColleges::isTvet(
            $university?->abbreviation,
            $university?->name,
        );
    }

    /**
     * @param  Collection<int, UniversityAdmissionRule>  $rules
     * @return array{
     *     summary_label: string,
     *     summary_value: string,
     *     summary_source: string,
     *     intro: string,
     *     cards: array<int, array{label: string, value: string, hint: string}>,
     *     route_summary: array{
     *         title: string,
     *         badge: string,
     *         intro: string,
     *         source_note: string,
     *         checks: array<int, array{label: string, value: string, hint: string}>
     *     }|null,
     *     notes: array<int, string>
     * }
     */
    public function collegeAdmissionSummary(Qualification $qualification, Collection $rules): array
    {
        $score = $this->collegeScoreSummary($qualification);
        $routeSummary = $this->collegeRouteSummary($qualification);
        $entryValue = $qualification->requiredGrade?->name
            ? $qualification->requiredGrade->name.' or equivalent'
            : 'Check college route';
        $programmeType = $qualification->qualificationType?->name ?? 'College programme';
        $nqfValue = $qualification->nqfLevel?->level
            ? 'Level '.$qualification->nqfLevel->level
            : 'Use programme notes';
        $manualReview = $this->requiresCollegeManualReview($qualification, $rules);

        return [
            'summary_label' => 'Entry route',
            'summary_value' => $entryValue,
            'summary_source' => $programmeType,
            'intro' => 'TVET college admission is usually checked through entry grade or equivalent NQF/NC(V)/NATED route, programme type, subject marks where published, and college selection notes.',
            'cards' => [
                [
                    'label' => 'Entry grade / NQF route',
                    'value' => $entryValue,
                    'hint' => $qualification->requiredGrade?->name
                        ? 'Equivalent NC(V), NATED or NQF routes may apply when listed by the college.'
                        : 'Confirm the minimum route from the programme notes and official college source.',
                ],
                [
                    'label' => 'Programme type',
                    'value' => $programmeType,
                    'hint' => $routeSummary['badge'] ?? 'NC(V), NATED and occupational programmes use different admission routes.',
                ],
                [
                    'label' => 'Route progression',
                    'value' => $routeSummary['badge'] ?? 'Check route',
                    'hint' => $routeSummary
                        ? $routeSummary['source_note']
                        : 'Confirm whether this is NC(V), NATED/Report 191, occupational, short course or another college route.',
                ],
                [
                    'label' => 'APS / score',
                    'value' => $score['value'],
                    'hint' => $score['hint'],
                ],
                [
                    'label' => 'Selection check',
                    'value' => $manualReview ? 'Manual review may apply' : 'College criteria may apply',
                    'hint' => 'Campus capacity, intake availability, portfolios, workplace/RPL rules or selection tests can still affect admission.',
                ],
                [
                    'label' => 'NQF',
                    'value' => $nqfValue,
                    'hint' => 'Use this alongside the entry route; it is not the same as a school grade.',
                ],
            ],
            'route_summary' => $routeSummary,
            'notes' => $this->qualificationNoteBullets($qualification->notes),
        ];
    }

    /**
     * @return array{
     *     term_label: string|null,
     *     status_label: string,
     *     status_tone: string,
     *     is_match: bool,
     *     is_almost_there: bool,
     *     requires_manual_review: bool,
     *     admission_score_type: string,
     *     admission_score_label: string,
     *     admission_score_required_display: string,
     *     admission_score_actual_display: string,
     *     admission_score_gap_display: string,
     *     admission_score_gap: float,
     *     closing_label: string,
     *     met_requirements: array<int, string>,
     *     missing_requirements: array<int, string>,
     *     missing_score_components: array<int, string>
     * }|null
     */
    public function qualificationMatchSummary(Qualification $qualification, User $user, ?int $termId = null): ?array
    {
        if ($user->grade_id === null) {
            return null;
        }

        $latestTermId = DB::table('user_subject_results')
            ->where('user_id', $user->id)
            ->where('grade_id', $user->grade_id)
            ->whereNotNull('mark')
            ->orderByDesc('term_id')
            ->value('term_id');

        $selectedTermId = $termId ?: $latestTermId;

        if ($selectedTermId === null) {
            return null;
        }

        $results = $this->userResultsForTerm($user, (int) $selectedTermId);

        if ($results->isEmpty() && $termId !== null && $latestTermId !== null && (int) $termId !== (int) $latestTermId) {
            $selectedTermId = (int) $latestTermId;
            $results = $this->userResultsForTerm($user, $selectedTermId);
        }

        if ($results->isEmpty()) {
            return null;
        }

        $qualification->loadMissing([
            'faculty',
            'university',
            'qualificationSubjectRequirements',
            'admissionScoreVariants',
        ]);

        $resultBySubjectId = $results->keyBy('subject_id');
        $normalise = fn (?string $value): string => trim(strtolower(preg_replace('/[^a-z0-9]+/i', ' ', (string) $value)));
        $matchingResult = function (object $requirement) use ($results, $resultBySubjectId, $normalise): ?object {
            $subjectId = $requirement->subject_id ?? null;

            if ($subjectId !== null && $resultBySubjectId->has($subjectId)) {
                return $resultBySubjectId->get($subjectId);
            }

            $requirementName = $normalise($requirement->subject_name ?? '');

            if ($requirementName === '') {
                return null;
            }

            if (str_contains($requirementName, 'english')) {
                return $results->first(fn ($result) => str_contains($normalise($result->name), 'english'));
            }

            return $results->first(function ($result) use ($requirementName, $normalise) {
                $subjectName = $normalise($result->name);

                return $subjectName !== ''
                    && (
                        $subjectName === $requirementName
                        || str_contains($requirementName, $subjectName)
                        || str_contains($subjectName, $requirementName)
                    );
            });
        };
        $requirementThresholdLabel = function (object $requirement): string {
            if (($requirement->aps_level_required ?? null) !== null) {
                return 'level '.(int) $requirement->aps_level_required;
            }

            if (($requirement->minimum_mark ?? null) !== null) {
                return (int) $requirement->minimum_mark.'%';
            }

            return 'required';
        };
        $requirementIsMet = function (?object $result, object $requirement): bool {
            if ($result === null) {
                return false;
            }

            if (($requirement->aps_level_required ?? null) !== null) {
                return (int) $result->aps_score >= (int) $requirement->aps_level_required;
            }

            if (($requirement->minimum_mark ?? null) !== null) {
                return (float) $result->mark >= (float) $requirement->minimum_mark;
            }

            return true;
        };

        $requirements = $qualification->qualificationSubjectRequirements;
        $missing = [];
        $met = [];

        foreach ($requirements->groupBy(fn ($requirement) => $requirement->requirement_group ?: 'requirement_'.$requirement->id) as $requirementGroup) {
            $firstRequirement = $requirementGroup->first();

            if (($firstRequirement->requirement_type ?? null) === 'subject_group_count_choice') {
                $choiceGroups = $requirementGroup->groupBy(function ($requirement) {
                    $config = json_decode($requirement->notes ?? '[]', true) ?: [];

                    return $config['choice_key'] ?? 'choice';
                });
                $passedChoice = null;
                $choiceLabels = [];

                foreach ($choiceGroups as $choiceGroup) {
                    $choiceConfig = json_decode($choiceGroup->first()->notes ?? '[]', true) ?: [];
                    $requiredCount = (int) ($choiceConfig['required_count'] ?? 1);
                    $thresholdLabel = $requirementThresholdLabel($choiceGroup->first());
                    $choiceLabels[] = trim($choiceConfig['label'] ?? $choiceGroup->pluck('subject_name')->filter()->implode(', '));
                    $passedRequirements = $choiceGroup->filter(function ($requirement) use ($matchingResult, $requirementIsMet) {
                        return $requirementIsMet($matchingResult($requirement), $requirement);
                    });

                    if ($passedRequirements->count() >= $requiredCount) {
                        $passedChoice = [
                            'count' => $requiredCount,
                            'label' => trim($choiceConfig['label'] ?? 'listed subjects'),
                            'threshold' => $thresholdLabel,
                        ];
                        break;
                    }
                }

                if ($passedChoice !== null) {
                    $met[] = $passedChoice['count'].' from '.$passedChoice['label'].' '.$passedChoice['threshold'];
                } else {
                    $missing[] = 'Required subject combination: '.implode(' OR ', $choiceLabels);
                }

                continue;
            }

            if (($firstRequirement->requirement_type ?? null) === 'subject_group_count') {
                $groupConfig = json_decode($firstRequirement->notes ?? '[]', true) ?: [];
                $requiredCount = (int) ($groupConfig['required_count'] ?? 1);
                $thresholdLabel = $requirementThresholdLabel($firstRequirement);
                $passedRequirements = $requirementGroup->filter(function ($requirement) use ($matchingResult, $requirementIsMet) {
                    return $requirementIsMet($matchingResult($requirement), $requirement);
                });

                if ($passedRequirements->count() >= $requiredCount) {
                    $met[] = $requiredCount.' of '.trim($groupConfig['label'] ?? 'listed subjects').' '.$thresholdLabel;
                } else {
                    $remainingCount = max($requiredCount - $passedRequirements->count(), 0);
                    $missing[] = $remainingCount.' more of: '.$requirementGroup
                        ->pluck('subject_name')
                        ->filter()
                        ->implode(', ')
                        .' '.$thresholdLabel;
                }

                continue;
            }

            $passedRequirement = null;
            $groupMessages = [];

            foreach ($requirementGroup as $requirement) {
                $message = trim(($requirement->subject_name ?? 'Subject').' '.$requirementThresholdLabel($requirement));

                if ($requirementIsMet($matchingResult($requirement), $requirement)) {
                    $passedRequirement = $requirement;
                    break;
                }

                $groupMessages[] = $message;
            }

            if ($passedRequirement !== null) {
                $met[] = trim(($passedRequirement->subject_name ?? 'Subject').' '.$requirementThresholdLabel($passedRequirement));
            } else {
                $missing[] = implode(' or ', $groupMessages);
            }
        }

        $ruleAssignment = $this->relevantAdmissionRules($qualification)->first();
        $admissionRule = $ruleAssignment?->admissionRule;
        $ruleConfig = array_replace_recursive(
            $admissionRule?->config ?? [],
            $ruleAssignment?->overrides ?? [],
        );
        $score = $this->scoreForAdmissionRule($results, $admissionRule, $ruleConfig);
        $usesAggregateAverage = ($admissionRule->score_type ?? null) === 'aggregate_average';
        $usesPassType = ($admissionRule->score_type ?? null) === 'pass_type';
        $admissionScoreType = $admissionRule->score_type
            ?? ($qualification->aggregate_average_required !== null ? 'aggregate_average' : 'aps');
        $admissionScoreLabel = $admissionRule->score_label
            ?? ($usesAggregateAverage ? 'Aggregated average' : 'APS');
        $admissionScoreSuffix = $admissionRule->score_suffix ?? ($usesAggregateAverage ? '%' : '');
        $passTypeRanking = $ruleConfig['ranking'] ?? [
            'none' => 0,
            'senior_certificate' => 1,
            'nsc' => 1,
            'higher_certificate' => 2,
            'diploma' => 3,
            'bachelor' => 4,
        ];
        $requiredPassType = $qualification->minimum_pass_type ?? $admissionRule?->minimum_pass_type ?? null;
        $admissionScoreVariant = $qualification->admissionScoreVariants
            ->filter(fn ($variant) => $requirementIsMet($matchingResult($variant), $variant))
            ->sortBy('admission_score_required')
            ->first();
        $fallbackAdmissionScoreVariant = $qualification->admissionScoreVariants
            ->sortBy('admission_score_required')
            ->first();

        if ($usesPassType) {
            $admissionScoreRequired = $requiredPassType === null ? null : (float) ($passTypeRanking[$requiredPassType] ?? 0);
        } elseif ($admissionScoreVariant !== null) {
            $admissionScoreRequired = (float) $admissionScoreVariant->admission_score_required;
        } elseif ($qualification->admission_score_required !== null) {
            $admissionScoreRequired = (float) $qualification->admission_score_required;
        } elseif ($fallbackAdmissionScoreVariant !== null) {
            $admissionScoreRequired = (float) $fallbackAdmissionScoreVariant->admission_score_required;
        } elseif ($usesAggregateAverage) {
            $admissionScoreRequired = $qualification->aggregate_average_required === null ? null : (float) $qualification->aggregate_average_required;
        } else {
            $admissionScoreRequired = $qualification->aps_required === null ? null : (float) $qualification->aps_required;
        }

        $admissionScoreActual = $score['actual'];
        $hasScoreRequirement = $admissionScoreRequired !== null;
        $requiresManualScoreReview = $hasScoreRequirement
            && $admissionScoreActual === null
            && (
                ($admissionRule?->calculation_method ?? null) === 'programme_specific_manual_review'
                || ($ruleConfig['requires_manual_verification'] ?? false) === true
                || str_contains((string) ($admissionRule?->calculation_method ?? ''), 'manual_review')
            );
        $admissionScoreGap = $admissionScoreRequired === null || $requiresManualScoreReview
            ? 0.0
            : max($admissionScoreRequired - ($admissionScoreActual ?? 0), 0);
        $hasSubjectRequirements = $requirements->isNotEmpty();
        $hasMachineCheckableRequirements = ($hasScoreRequirement && ! $requiresManualScoreReview) || $hasSubjectRequirements;
        $isMatch = ! $requiresManualScoreReview && $hasMachineCheckableRequirements && $admissionScoreGap === 0.0 && count($missing) === 0;
        $isAlmostThere = ! $isMatch
            && ! $requiresManualScoreReview
            && $hasMachineCheckableRequirements
            && ($admissionScoreGap === 0.0 || count($missing) === 0);
        $requiresManualReview = $requiresManualScoreReview || ! $hasMachineCheckableRequirements;
        $formatAdmissionScore = fn (float $value): string => $admissionScoreSuffix === '%'
            ? rtrim(rtrim(number_format($value, 1), '0'), '.').$admissionScoreSuffix
            : number_format($value, 0);
        $requiredPassTypeDisplay = $requiredPassType === null ? 'N/A' : $this->passTypeLabel($requiredPassType);
        $actualPassTypeDisplay = $this->passTypeLabel($score['pass_type'] ?? 'none');

        return [
            'term_label' => $results->first()->term_name ?? null,
            'status_label' => match (true) {
                $isMatch => 'You meet the listed requirements',
                $requiresManualReview => 'Review the published notes',
                $isAlmostThere => 'Almost there',
                default => 'Still needs attention',
            },
            'status_tone' => match (true) {
                $isMatch => 'success',
                $requiresManualReview => 'review',
                $isAlmostThere => 'warning',
                default => 'danger',
            },
            'is_match' => $isMatch,
            'is_almost_there' => $isAlmostThere,
            'requires_manual_review' => $requiresManualReview,
            'admission_score_type' => $admissionScoreType,
            'admission_score_label' => $admissionScoreLabel,
            'admission_score_required_display' => $admissionScoreRequired === null
                ? ($hasMachineCheckableRequirements ? 'N/A' : 'See notes')
                : ($usesPassType ? $requiredPassTypeDisplay : $formatAdmissionScore($admissionScoreRequired)),
            'admission_score_actual_display' => $usesPassType
                ? $actualPassTypeDisplay
                : ($admissionScoreActual === null ? ($requiresManualScoreReview ? 'Review' : 'N/A') : $formatAdmissionScore($admissionScoreActual)),
            'admission_score_gap_display' => $usesPassType
                ? ($admissionScoreGap === 0.0 ? 'Met' : 'Not met')
                : ($requiresManualScoreReview ? 'Review' : ($hasMachineCheckableRequirements ? $formatAdmissionScore($admissionScoreGap) : 'Review')),
            'admission_score_gap' => $admissionScoreGap,
            'closing_label' => $this->closingLabel(
                $qualification->closing_month ?? $qualification->faculty?->closing_month ?? $qualification->university?->default_closing_month,
                $qualification->closing_day ?? $qualification->faculty?->closing_day ?? $qualification->university?->default_closing_day,
            ) ?? 'Not listed',
            'met_requirements' => $met,
            'missing_requirements' => $missing,
            'missing_score_components' => $score['missing_components'],
        ];
    }

    public function requirementLabel(QualificationSubjectRequirement $requirement): string
    {
        if ($requirement->aps_level_required !== null) {
            return 'level '.(int) $requirement->aps_level_required;
        }

        if ($requirement->minimum_mark !== null) {
            return (int) $requirement->minimum_mark.'%';
        }

        return 'required';
    }

    /**
     * @param  Collection<int, QualificationSubjectRequirement>  $requirements
     */
    public function requirementGroupHeading(Collection $requirements): string
    {
        $firstRequirement = $requirements->first();

        if ($firstRequirement?->requirement_type === 'subject_group_count_choice') {
            return 'One of these subject combinations';
        }

        if ($firstRequirement?->requirement_type === 'subject_group_count') {
            $config = $this->structuredRequirementNote($firstRequirement->notes);
            $count = (int) ($config['required_count'] ?? 1);
            $label = trim((string) ($config['label'] ?? 'listed subjects'));

            return $count.' from '.$label;
        }

        return $requirements->count() > 1 ? 'One of these requirements' : 'Required subject';
    }

    /**
     * @param  Collection<int, QualificationSubjectRequirement>  $requirements
     * @return array<int, array{label: string, requirements: Collection<int, QualificationSubjectRequirement>}>
     */
    public function requirementChoiceGroups(Collection $requirements): array
    {
        if ($requirements->first()?->requirement_type !== 'subject_group_count_choice') {
            return [];
        }

        return $requirements
            ->groupBy(function (QualificationSubjectRequirement $requirement): string {
                $config = $this->structuredRequirementNote($requirement->notes);

                return (string) ($config['choice_key'] ?? 'choice');
            })
            ->map(function (Collection $choiceRequirements): array {
                $config = $this->structuredRequirementNote($choiceRequirements->first()?->notes);
                $label = trim((string) ($config['label'] ?? $choiceRequirements
                    ->map(fn (QualificationSubjectRequirement $requirement) => trim(($requirement->subject_name ?: $requirement->subject?->name ?: 'Subject').' '.$this->requirementLabel($requirement)))
                    ->implode(' and ')));

                return [
                    'label' => $label,
                    'requirements' => $choiceRequirements->values(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, QualificationSubjectRequirement>  $requirements
     * @return array<int, string>
     */
    public function requirementNotes(Collection $requirements): array
    {
        return $requirements
            ->pluck('notes')
            ->filter()
            ->unique()
            ->reject(fn (string $note): bool => $this->structuredRequirementNote($note) !== null)
            ->values()
            ->all();
    }

    public function closingLabel(?int $month, ?int $day): ?string
    {
        if ($month === null || $day === null) {
            return null;
        }

        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        return $day.' '.($months[$month] ?? '').' '.(now()->year + 1);
    }

    public function passTypeLabel(string $passType): string
    {
        return [
            'none' => 'No pass yet',
            'senior_certificate' => 'Senior Certificate pass',
            'nsc' => 'NSC pass',
            'higher_certificate' => 'Higher Certificate pass',
            'diploma' => 'Diploma pass',
            'bachelor' => 'Bachelor pass',
        ][$passType] ?? str($passType)->replace('_', ' ')->title()->toString();
    }

    /**
     * @return Collection<int, object>
     */
    private function userResultsForTerm(User $user, int $termId): Collection
    {
        return DB::table('user_subject_results')
            ->join('subjects', 'subjects.id', '=', 'user_subject_results.subject_id')
            ->leftJoin('terms', 'terms.id', '=', 'user_subject_results.term_id')
            ->where('user_subject_results.user_id', $user->id)
            ->where('user_subject_results.grade_id', $user->grade_id)
            ->where('user_subject_results.term_id', $termId)
            ->whereNotNull('user_subject_results.mark')
            ->select(
                'user_subject_results.subject_id',
                'user_subject_results.mark',
                'user_subject_results.aps_score',
                'subjects.name',
                'subjects.code',
                'subjects.abbreviation',
                'terms.name as term_name',
            )
            ->get();
    }

    /**
     * @param  Collection<int, object>  $results
     * @return array{actual: float|null, pass_type?: string, missing_components: array<int, string>}
     */
    private function scoreForAdmissionRule(Collection $results, ?AdmissionRule $rule, array $config): array
    {
        if ($rule === null) {
            return ['actual' => null, 'missing_components' => []];
        }

        $normaliseSubjectName = fn (?string $value): string => trim(strtolower(preg_replace('/[^a-z0-9]+/i', ' ', (string) $value)));
        $isLifeOrientation = function (object $result): bool {
            $code = strtoupper($result->code ?? $result->abbreviation ?? '');

            return $code === 'LO' || strcasecmp($result->name, 'Life Orientation') === 0;
        };
        $ruleResults = $rule->include_life_orientation
            ? $results
            : $results->reject($isLifeOrientation);
        $subjectCount = $rule->subject_count === null ? null : (int) $rule->subject_count;
        $scoreSubjects = match (true) {
            $subjectCount === null => $ruleResults,
            $rule->subject_selection_strategy === 'best_subjects' => $ruleResults->sortByDesc('mark')->take($subjectCount),
            default => $ruleResults->take($subjectCount),
        };
        $findResultBySubjectName = function (string $subjectName) use ($results, $normaliseSubjectName): ?object {
            $normalisedSubjectName = $normaliseSubjectName($subjectName);

            return $results->first(function ($result) use ($normalisedSubjectName, $normaliseSubjectName) {
                $resultName = $normaliseSubjectName($result->name);

                return $resultName !== ''
                    && (
                        $resultName === $normalisedSubjectName
                        || str_contains($normalisedSubjectName, $resultName)
                        || str_contains($resultName, $normalisedSubjectName)
                    );
            });
        };
        $pointsForMark = function (float $mark, array $bands): float {
            foreach ($bands as $band) {
                if ($mark >= (float) $band['minimum_mark'] && $mark <= (float) $band['maximum_mark']) {
                    return (float) $band['points'];
                }
            }

            return 0.0;
        };
        $isLanguageResult = fn (object $result): bool => str_contains($normaliseSubjectName($result->name), 'language')
            || str_contains($normaliseSubjectName($result->name), 'english')
            || str_contains($normaliseSubjectName($result->name), 'afrikaans')
            || str_contains($normaliseSubjectName($result->name), 'isizulu')
            || str_contains($normaliseSubjectName($result->name), 'isixhosa')
            || str_contains($normaliseSubjectName($result->name), 'sesotho')
            || str_contains($normaliseSubjectName($result->name), 'setswana')
            || str_contains($normaliseSubjectName($result->name), 'sepedi')
            || str_contains($normaliseSubjectName($result->name), 'xitsonga')
            || str_contains($normaliseSubjectName($result->name), 'tshivenda');
        $isHomeLanguageResult = fn (object $result): bool => str_contains($normaliseSubjectName($result->name), 'home language');
        $isMathematicsFamilyResult = fn (object $result): bool => in_array($normaliseSubjectName($result->name), [
            'mathematics',
            'mathematical literacy',
            'technical mathematics',
        ], true);
        $nscPassType = function ($ruleResults) use ($isLifeOrientation, $isHomeLanguageResult): string {
            $subjects = $ruleResults->reject($isLifeOrientation);
            $homeLanguagePassed = $subjects->contains(fn ($result) => $isHomeLanguageResult($result) && (float) $result->mark >= 40);
            $subjectsAt50 = $subjects->filter(fn ($result) => (float) $result->mark >= 50)->count();
            $subjectsAt40 = $subjects->filter(fn ($result) => (float) $result->mark >= 40)->count();
            $subjectsAt30 = $subjects->filter(fn ($result) => (float) $result->mark >= 30)->count();

            return match (true) {
                $homeLanguagePassed && $subjectsAt50 >= 4 && $subjectsAt30 >= 6 => 'bachelor',
                $homeLanguagePassed && $subjectsAt40 >= 4 && $subjectsAt30 >= 6 => 'diploma',
                $homeLanguagePassed && $subjectsAt40 >= 3 && $subjectsAt30 >= 6 => 'higher_certificate',
                default => 'none',
            };
        };
        $seniorCertificatePassed = function ($ruleResults) use ($isHomeLanguageResult, $isLanguageResult): bool {
            return $ruleResults->filter(fn ($result) => (float) $result->mark >= 40)->count() >= 3
                && $ruleResults->contains(fn ($result) => $isHomeLanguageResult($result) && (float) $result->mark >= 40)
                && $ruleResults->filter(fn ($result) => (float) $result->mark >= 30)->count() >= 5
                && $ruleResults->contains(fn ($result) => $isLanguageResult($result) && (float) $result->mark >= 30)
                && $ruleResults->filter(fn ($result) => (float) $result->mark >= 20)->count() >= 6;
        };
        $nmuApplicantScore = function ($ruleResults, array $config) use ($isHomeLanguageResult, $isLanguageResult, $isMathematicsFamilyResult, $isLifeOrientation): float {
            $eligibleSubjects = $ruleResults->reject($isLifeOrientation)->values();
            $selected = collect();
            $selectedSubjectIds = [];
            $addResult = function (?object $result) use (&$selected, &$selectedSubjectIds): void {
                if ($result === null || in_array((int) $result->subject_id, $selectedSubjectIds, true)) {
                    return;
                }

                $selected->push($result);
                $selectedSubjectIds[] = (int) $result->subject_id;
            };

            $addResult($eligibleSubjects->filter($isHomeLanguageResult)->sortByDesc('mark')->first());
            $addResult($eligibleSubjects->reject($isHomeLanguageResult)->filter($isLanguageResult)->sortByDesc('mark')->first());
            $addResult($eligibleSubjects->filter($isMathematicsFamilyResult)->sortByDesc('mark')->first());
            $eligibleSubjects
                ->reject(fn ($result) => in_array((int) $result->subject_id, $selectedSubjectIds, true))
                ->sortByDesc('mark')
                ->take(max(6 - $selected->count(), 0))
                ->each($addResult);

            $score = (float) $selected->take(6)->sum(fn ($result) => (float) $result->mark);

            if (($config['life_orientation_bonus']['apply_without_quintile_data'] ?? false) === true) {
                $lifeOrientation = $ruleResults->first($isLifeOrientation);
                $minimumMark = (float) ($config['life_orientation_bonus']['minimum_mark'] ?? 50);

                if ($lifeOrientation !== null && (float) $lifeOrientation->mark >= $minimumMark) {
                    $score += (float) ($config['life_orientation_bonus']['points'] ?? 7);
                }
            }

            return $score;
        };
        $achievedNscPassType = $rule->calculation_method === 'nsc_pass_type'
            ? $nscPassType($ruleResults)
            : null;
        $achievedSeniorCertificatePassType = $rule->calculation_method === 'senior_certificate_pass' && $seniorCertificatePassed($ruleResults)
            ? 'senior_certificate'
            : 'none';

        return match ($rule->calculation_method) {
            'aps_level_sum' => [
                'actual' => (float) $scoreSubjects->sum(fn ($result) => (int) $result->aps_score),
                'missing_components' => [],
            ],
            'average_mark' => [
                'actual' => $scoreSubjects->isEmpty() ? null : (float) $scoreSubjects->avg('mark'),
                'missing_components' => [],
            ],
            'raw_mark_sum' => [
                'actual' => (float) $scoreSubjects->sum(fn ($result) => (float) $result->mark)
                    / max((float) ($config['score_divisor'] ?? 1), 1),
                'missing_components' => [],
            ],
            'weighted_mark_sum' => [
                'actual' => (float) $scoreSubjects->sum(fn ($result) => (float) $result->mark)
                    + collect($config['additional_subject_weights'] ?? [])->sum(function ($weight) use ($findResultBySubjectName) {
                        $result = $findResultBySubjectName($weight['subject'] ?? '');

                        return $result === null ? 0 : ((float) $result->mark * (float) ($weight['additional_weight'] ?? 0));
                    }),
                'missing_components' => [],
            ],
            'nmu_applicant_score' => [
                'actual' => $nmuApplicantScore($ruleResults, $config),
                'missing_components' => [],
            ],
            'subject_point_sum' => [
                'actual' => (float) $scoreSubjects->sum(function ($result) use ($config, $normaliseSubjectName, $pointsForMark) {
                    $subjectName = $normaliseSubjectName($result->name);
                    $scale = collect($config['subject_point_scales'] ?? [])
                        ->first(function ($scale) use ($subjectName, $normaliseSubjectName) {
                            return collect($scale['subjects'] ?? [])
                                ->contains(fn ($subject) => $normaliseSubjectName($subject) === $subjectName);
                        });
                    $scale ??= $config['default_point_scale'] ?? [];

                    return $pointsForMark((float) $result->mark, $scale['bands'] ?? []);
                }),
                'missing_components' => [],
            ],
            'composite_sum' => [
                'actual' => (float) $scoreSubjects->sum(fn ($result) => (float) $result->mark),
                'missing_components' => collect($config['components'] ?? [])
                    ->filter(fn ($component) => ($component['method'] ?? null) === 'external_sum' && ($component['required'] ?? false))
                    ->pluck('label')
                    ->values()
                    ->all(),
            ],
            'nsc_pass_type' => [
                'actual' => (float) (($config['ranking'] ?? [])[$achievedNscPassType] ?? 0),
                'pass_type' => $achievedNscPassType,
                'missing_components' => [],
            ],
            'senior_certificate_pass' => [
                'actual' => (float) (($config['ranking'] ?? [])[$achievedSeniorCertificatePassType] ?? 0),
                'pass_type' => $achievedSeniorCertificatePassType,
                'missing_components' => [],
            ],
            default => [
                'actual' => null,
                'missing_components' => [],
            ],
        };
    }

    private function formatScore(float $value, ?string $suffix): string
    {
        if ($suffix === '%') {
            return rtrim(rtrim(number_format($value, 1), '0'), '.').'%';
        }

        return number_format($value, 0);
    }

    /**
     * @return array{value: string, hint: string}
     */
    private function collegeScoreSummary(Qualification $qualification): array
    {
        if ($qualification->aps_required !== null) {
            return [
                'value' => 'APS '.(int) $qualification->aps_required,
                'hint' => 'This college programme publishes an APS; still check the programme notes before matching.',
            ];
        }

        if ($qualification->admission_score_required !== null) {
            return [
                'value' => 'Score '.$this->formatScore((float) $qualification->admission_score_required, null),
                'hint' => 'This is the published admission score captured for the programme.',
            ];
        }

        if ($qualification->aggregate_average_required !== null) {
            return [
                'value' => $this->formatScore((float) $qualification->aggregate_average_required, '%'),
                'hint' => 'This is the published aggregate average captured for the programme.',
            ];
        }

        return [
            'value' => 'Not used or not published',
            'hint' => 'No APS-style score is captured; use entry route, subjects and notes instead.',
        ];
    }

    /**
     * @return array{
     *     title: string,
     *     badge: string,
     *     intro: string,
     *     source_note: string,
     *     checks: array<int, array{label: string, value: string, hint: string}>
     * }|null
     */
    private function collegeRouteSummary(Qualification $qualification): ?array
    {
        $routeText = $this->collegeRouteText($qualification);

        if ($this->isNatedRoute($routeText)) {
            $levels = $this->natedLevelLabel($routeText);
            $levelNumbers = $this->natedLevelNumbers($routeText);

            if ($levelNumbers !== [] && max($levelNumbers) <= 3) {
                return [
                    'title' => 'NATED / Report 191 route',
                    'badge' => $levels.' sequence',
                    'intro' => 'N1-N3 programmes are sequential National N Certificate levels. A school-leaver is usually checked for N1 entry, while N2 and N3 need proof of the previous N-level unless the college publishes a direct Grade 12 route.',
                    'source_note' => 'N1, N2 and N3 are progression levels, not ordinary school grades.',
                    'checks' => [
                        [
                            'label' => 'N1 entry',
                            'value' => 'Usually Grade 9 or equivalent',
                            'hint' => 'Engineering programmes can require Mathematics, college placement tests or related technical subjects.',
                        ],
                        [
                            'label' => 'N2 / N3 entry',
                            'value' => 'N2 needs N1; N3 needs N2',
                            'hint' => 'If the college allows Grade 12 entry into N2, check the published Mathematics, Physical Science and placement-test notes.',
                        ],
                        [
                            'label' => 'Next route',
                            'value' => 'N3 can lead into N4-N6',
                            'hint' => 'N4-N6 is the later NATED theory route that can support a National N Diploma with relevant workplace experience.',
                        ],
                    ],
                ];
            }

            return [
                'title' => 'NATED / Report 191 route',
                'badge' => $levels.' sequence',
                'intro' => 'NATED programmes are sequential National N Certificate levels. A school-leaver can usually be checked for the first level, but later levels need proof of the previous N-level.',
                'source_note' => 'N4, N5 and N6 are progression levels, not ordinary school grades.',
                'checks' => [
                    [
                        'label' => 'N4 entry',
                        'value' => 'Usually Grade 12 or relevant NC(V) Level 4',
                        'hint' => 'The field can still require specific school subjects, selection checks or campus capacity.',
                    ],
                    [
                        'label' => 'N5 / N6 entry',
                        'value' => 'N5 needs N4; N6 needs N5',
                        'hint' => 'If the college page lists N5 or N6, verify the learner has passed the preceding N-level subjects.',
                    ],
                    [
                        'label' => 'National N Diploma',
                        'value' => 'N4-N6 plus workplace experience',
                        'hint' => 'The diploma route normally needs the N4-N6 theory component plus relevant workplace experience or logbook evidence.',
                    ],
                ],
            ];
        }

        if ($this->isOccupationalRoute($routeText)) {
            return [
                'title' => 'Occupational route',
                'badge' => 'Workplace-based route',
                'intro' => 'Occupational programmes are built around an occupation or trade and can include knowledge, practical and workplace components.',
                'source_note' => 'Entry can depend on QCTO/SETA rules, workplace placement, RPL or provider selection.',
                'checks' => [
                    [
                        'label' => 'Entry evidence',
                        'value' => 'Check grade, NQF or RPL route',
                        'hint' => 'Some occupational programmes accept prior learning or workplace experience instead of a single school grade.',
                    ],
                    [
                        'label' => 'Practical component',
                        'value' => 'Workplace or practical training may apply',
                        'hint' => 'The user may need employer placement, logbook evidence or practical assessments.',
                    ],
                    [
                        'label' => 'Manual review',
                        'value' => 'Confirm with the college',
                        'hint' => 'Do not auto-reject only from APS when occupational criteria are not fully published.',
                    ],
                ],
            ];
        }

        if ($this->isNcvRoute($routeText)) {
            return [
                'title' => 'NC(V) route',
                'badge' => 'Levels 2-4',
                'intro' => 'NC(V) is the school-age vocational route at TVET colleges. It usually starts at Level 2 and progresses one level at a time through Level 4.',
                'source_note' => 'NC(V) Level 2 is usually the Grade 9 entry route; Level 4 is an NQF Level 4 exit level.',
                'checks' => [
                    [
                        'label' => 'Level 2 entry',
                        'value' => 'Usually Grade 9 or equivalent',
                        'hint' => 'Engineering and technical programmes can still require Mathematics or relevant school subjects.',
                    ],
                    [
                        'label' => 'Level 3 / Level 4',
                        'value' => 'Progress after passing the previous level',
                        'hint' => 'A learner does not jump into later NC(V) levels unless the college confirms an equivalent route.',
                    ],
                    [
                        'label' => 'After Level 4',
                        'value' => 'NQF Level 4 exit',
                        'hint' => 'This can support work or further study where the next qualification accepts NC(V) Level 4.',
                    ],
                ],
            ];
        }

        return null;
    }

    private function collegeRouteText(Qualification $qualification): string
    {
        return strtolower(implode(' ', array_filter([
            $qualification->name,
            $qualification->abbreviation,
            $qualification->qualificationType?->name,
            $qualification->qualificationType?->abbreviation,
            $qualification->notes,
        ])));
    }

    private function isNatedRoute(string $routeText): bool
    {
        return str_contains($routeText, 'nated')
            || str_contains($routeText, 'report 191')
            || str_contains($routeText, 'national n diploma')
            || preg_match('/\bn[1-6]\b/i', $routeText) === 1;
    }

    private function isNcvRoute(string $routeText): bool
    {
        return str_contains($routeText, 'national certificate vocational')
            || str_contains($routeText, 'national certificate (vocational)')
            || str_contains($routeText, 'nc(v)')
            || preg_match('/\bncv\b/i', $routeText) === 1;
    }

    private function isOccupationalRoute(string $routeText): bool
    {
        return str_contains($routeText, 'occupational')
            || str_contains($routeText, 'qcto')
            || str_contains($routeText, 'workplace training')
            || str_contains($routeText, 'recognition of prior learning')
            || str_contains($routeText, 'rpl');
    }

    private function natedLevelLabel(string $routeText): string
    {
        if (preg_match('/\bn\s*([1-6])\s*(?:-|to)\s*n?\s*([1-6])\b/i', $routeText, $matches) === 1) {
            return 'N'.$matches[1].'-N'.$matches[2];
        }

        $levels = [];

        for ($level = 1; $level <= 6; $level++) {
            if (preg_match('/\bn'.$level.'\b/i', $routeText) === 1) {
                $levels[] = $level;
            }
        }

        if ($levels === []) {
            return 'N4-N6';
        }

        $minimum = min($levels);
        $maximum = max($levels);

        return $minimum === $maximum ? 'N'.$minimum : 'N'.$minimum.'-N'.$maximum;
    }

    /**
     * @return array<int, int>
     */
    private function natedLevelNumbers(string $routeText): array
    {
        if (preg_match('/\bn\s*([1-6])\s*(?:-|to)\s*n?\s*([1-6])\b/i', $routeText, $matches) === 1) {
            $start = (int) $matches[1];
            $end = (int) $matches[2];

            return range(min($start, $end), max($start, $end));
        }

        $levels = [];

        for ($level = 1; $level <= 6; $level++) {
            if (preg_match('/\bn'.$level.'\b/i', $routeText) === 1) {
                $levels[] = $level;
            }
        }

        return $levels;
    }

    /**
     * @param  Collection<int, UniversityAdmissionRule>  $rules
     */
    private function requiresCollegeManualReview(Qualification $qualification, Collection $rules): bool
    {
        if ($qualification->is_selection_programme) {
            return true;
        }

        $text = strtolower(collect([
            $qualification->notes,
            ...$rules->pluck('notes')->all(),
            ...$rules->pluck('admissionRule.description')->all(),
        ])->filter()->implode(' '));

        return Str::contains($text, [
            'manual review',
            'selection',
            'portfolio',
            'interview',
            'audition',
            'rpl',
            'workplace',
            'previous n-level',
            'preceding n-level',
            'n5 requires n4',
            'n6 requires n5',
            'campus capacity',
            'availability must be confirmed',
            'requires verification',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function qualificationNoteBullets(?string $notes): array
    {
        if ($notes === null || trim($notes) === '') {
            return [];
        }

        $normalised = preg_replace('/\s+/', ' ', trim($notes)) ?? trim($notes);
        $parts = preg_split('/(?<=\.)\s+(?=[A-Z])/', $normalised) ?: [$normalised];

        return collect($parts)
            ->map(fn (string $note): string => trim($note))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function structuredRequirementNote(?string $note): ?array
    {
        if ($note === null || $note === '') {
            return null;
        }

        $decoded = json_decode($note, true);

        if (! is_array($decoded)) {
            return null;
        }

        return array_key_exists('required_count', $decoded) || array_key_exists('choice_key', $decoded)
            ? $decoded
            : null;
    }
}
