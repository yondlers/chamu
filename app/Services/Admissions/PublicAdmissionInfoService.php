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
}
