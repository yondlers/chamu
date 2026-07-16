<?php

namespace App\Services\Admissions;

use App\Models\Qualification;
use App\Models\QualificationSubjectRequirement;
use App\Models\UniversityAdmissionRule;
use Illuminate\Support\Collection;

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
