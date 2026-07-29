<?php

namespace App\Services\Admissions;

use App\Models\Qualification;
use App\Models\QualificationSubjectRequirement;
use App\Models\UniversityAdmissionRule;
use Illuminate\Support\Collection;
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
        $abbreviation = strtoupper(trim((string) $university?->abbreviation));

        if (in_array($abbreviation, ['CJC', 'TNC', 'TSC'], true)) {
            return true;
        }

        return str_contains(strtolower((string) $university?->name), 'tvet college');
    }

    /**
     * @param  Collection<int, UniversityAdmissionRule>  $rules
     * @return array{
     *     summary_label: string,
     *     summary_value: string,
     *     summary_source: string,
     *     intro: string,
     *     cards: array<int, array{label: string, value: string, hint: string}>,
     *     notes: array<int, string>
     * }
     */
    public function collegeAdmissionSummary(Qualification $qualification, Collection $rules): array
    {
        $score = $this->collegeScoreSummary($qualification);
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
                    'hint' => 'NC(V), NATED and occupational programmes use different admission routes.',
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
            'notes' => $this->qualificationNoteBullets($qualification->notes),
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
            'senior_certificate' => 'Senior Certificate pass',
            'nsc' => 'NSC pass',
            'higher_certificate' => 'Higher Certificate pass',
            'diploma' => 'Diploma pass',
            'bachelor' => 'Bachelor pass',
        ][$passType] ?? str($passType)->replace('_', ' ')->title()->toString();
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
