<?php

namespace Database\Seeders\Universities\CJC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequirementSeeder extends Seeder
{
    private const PROGRAMMES_PATH = __DIR__.'/cjc_programmes_chamu.json';

    private const WEBSITE = 'https://cjc.edu.za/';

    public function run(): void
    {
        $data = json_decode(file_get_contents(self::PROGRAMMES_PATH), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data): void {
            $this->seedQualificationTypes();
            $this->seedCjcAdmissionRules();

            $gradeIdsByName = $this->gradeIdsByName();
            $institution = $data['programmes'][0]['institution'] ?? [];
            $countryId = $this->countryId($institution['country'] ?? 'South Africa');
            $universityId = $this->universityId($countryId, $institution);

            DB::table('university_admission_rules')
                ->where('university_id', $universityId)
                ->delete();

            foreach ($data['programmes'] as $programme) {
                $facultyId = $this->facultyId($universityId, (string) ($programme['field'] ?? 'Programmes'));
                $qualificationTypeId = $this->qualificationTypeId($this->qualificationTypeName($programme));
                $requiredGradeName = $this->requiredGradeName($programme);
                $requiredGradeId = $requiredGradeName === null ? null : ($gradeIdsByName[$requiredGradeName] ?? null);
                $qualificationId = $this->qualificationId(
                    $programme,
                    $universityId,
                    $facultyId,
                    $qualificationTypeId,
                    $requiredGradeId,
                );

                DB::table('qualification_subject_requirements')
                    ->where('qualification_id', $qualificationId)
                    ->delete();

                DB::table('qualification_admission_score_variants')
                    ->where('qualification_id', $qualificationId)
                    ->delete();

                $this->assignAdmissionRule($universityId, $qualificationId, $requiredGradeId, $programme);
            }
        });
    }

    private function seedQualificationTypes(): void
    {
        $types = [
            ['National Certificate Vocational', 'NCV', 4, 5],
            ['NATED', 'NATED', 6, 50],
            ['Occupational Certificate', 'OccCert', null, 52],
            ['Further Education and Training Certificate', 'FETC', 4, 56],
            ['Legacy / Special Programme', null, null, 57],
        ];

        foreach ($types as [$name, $abbreviation, $nqfLevel, $sortOrder]) {
            DB::table('qualification_types')->updateOrInsert(
                ['name' => $name],
                [
                    'abbreviation' => $abbreviation,
                    'nqf_level_id' => $nqfLevel === null ? null : $this->nqfLevelId($nqfLevel),
                    'sort_order' => $sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedCjcAdmissionRules(): void
    {
        $lifeOrientationSubjectId = DB::table('subjects')
            ->join('grades', 'grades.id', '=', 'subjects.grade_id')
            ->join('curriculums', 'curriculums.id', '=', 'subjects.curriculum_id')
            ->where('curriculums.abbreviation', 'CAPS')
            ->where('grades.name', 'Grade 12')
            ->where('subjects.name', 'Life Orientation')
            ->value('subjects.id');

        $nscLevels = [
            ['level' => 7, 'label' => 'Outstanding Achievement', 'minimum_mark' => 80, 'maximum_mark' => 100, 'points' => 7],
            ['level' => 6, 'label' => 'Meritorious Achievement', 'minimum_mark' => 70, 'maximum_mark' => 79, 'points' => 6],
            ['level' => 5, 'label' => 'Substantial Achievement', 'minimum_mark' => 60, 'maximum_mark' => 69, 'points' => 5],
            ['level' => 4, 'label' => 'Adequate Achievement', 'minimum_mark' => 50, 'maximum_mark' => 59, 'points' => 4],
            ['level' => 3, 'label' => 'Moderate Achievement', 'minimum_mark' => 40, 'maximum_mark' => 49, 'points' => 3],
            ['level' => 2, 'label' => 'Elementary Achievement', 'minimum_mark' => 30, 'maximum_mark' => 39, 'points' => 2],
            ['level' => 1, 'label' => 'Not achieved', 'minimum_mark' => 0, 'maximum_mark' => 29, 'points' => 1],
        ];

        $englishSubjects = [
            'English Home Language',
            'English First Additional Language',
            'English Second Additional Language',
        ];

        $rules = [
            [
                'code' => 'cjc_aps_double_english_next_four',
                'name' => 'CJC APS: double English plus next four best subjects',
                'score_type' => 'aps',
                'calculation_method' => 'weighted_aps_level_sum',
                'score_label' => 'APS',
                'score_suffix' => null,
                'max_score' => 42,
                'include_life_orientation' => false,
                'life_orientation_subject_id' => $lifeOrientationSubjectId,
                'subject_count' => 6,
                'subject_selection_strategy' => 'english_then_best_subjects',
                'minimum_pass_type' => null,
                'points_scale' => $nscLevels,
                'config' => [
                    'excluded_subjects' => ['Life Orientation'],
                    'weighted_subjects' => [
                        ['subjects' => $englishSubjects, 'weight' => 2],
                    ],
                    'best_other_subject_count' => 4,
                ],
                'description' => 'CJC NATED APS formula where English is counted twice and the next four best NSC subjects are added, excluding Life Orientation.',
            ],
            [
                'code' => 'cjc_aps_double_english_next_three',
                'name' => 'CJC APS: double English plus next three best subjects',
                'score_type' => 'aps',
                'calculation_method' => 'weighted_aps_level_sum',
                'score_label' => 'APS',
                'score_suffix' => null,
                'max_score' => 35,
                'include_life_orientation' => false,
                'life_orientation_subject_id' => $lifeOrientationSubjectId,
                'subject_count' => 5,
                'subject_selection_strategy' => 'english_then_best_subjects',
                'minimum_pass_type' => null,
                'points_scale' => $nscLevels,
                'config' => [
                    'excluded_subjects' => ['Life Orientation'],
                    'weighted_subjects' => [
                        ['subjects' => $englishSubjects, 'weight' => 2],
                    ],
                    'best_other_subject_count' => 3,
                ],
                'description' => 'CJC APS formula where English is counted twice and the next three best subjects are added, excluding Life Orientation.',
            ],
            [
                'code' => 'cjc_aps_programme_specific_manual_review',
                'name' => 'CJC programme-specific APS requiring verification',
                'score_type' => 'aps',
                'calculation_method' => 'programme_specific_manual_review',
                'score_label' => 'APS',
                'score_suffix' => null,
                'max_score' => null,
                'include_life_orientation' => false,
                'life_orientation_subject_id' => $lifeOrientationSubjectId,
                'subject_count' => null,
                'subject_selection_strategy' => 'manual_review',
                'minimum_pass_type' => null,
                'points_scale' => $nscLevels,
                'config' => [
                    'requires_manual_verification' => true,
                    'excluded_subjects' => ['Life Orientation'],
                ],
                'description' => 'CJC source data lists an APS requirement, but the programme-specific APS calculation needs manual verification before automatic rejection or acceptance.',
            ],
        ];

        foreach ($rules as $rule) {
            $code = $rule['code'];
            unset($rule['code']);

            DB::table('admission_rules')->updateOrInsert(
                ['code' => $code],
                array_merge($rule, [
                    'points_scale' => json_encode($rule['points_scale'], JSON_THROW_ON_ERROR),
                    'config' => json_encode($rule['config'], JSON_THROW_ON_ERROR),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }
    }

    private function countryId(string $countryName): int
    {
        DB::table('countries')->updateOrInsert(
            ['name' => $countryName],
            [
                'nationality' => $countryName === 'South Africa' ? 'South African' : null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('countries')
            ->where('name', $countryName)
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $institution
     */
    private function universityId(int $countryId, array $institution): int
    {
        $abbreviation = (string) ($institution['abbreviation'] ?? 'CJC');
        $name = (string) ($institution['name'] ?? 'Central Johannesburg TVET College');
        $existing = DB::table('universities')
            ->where('abbreviation', $abbreviation)
            ->first();

        DB::table('universities')->updateOrInsert(
            ['abbreviation' => $abbreviation],
            [
                'country_id' => $countryId,
                'name' => $name,
                'slug' => $existing?->slug ?: $this->uniqueUniversitySlug(Str::slug($name)),
                'website' => self::WEBSITE,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('universities')
            ->where('abbreviation', $abbreviation)
            ->value('id');
    }

    private function facultyId(int $universityId, string $name): int
    {
        DB::table('faculties')->updateOrInsert(
            [
                'university_id' => $universityId,
                'name' => $name,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('faculties')
            ->where('university_id', $universityId)
            ->where('name', $name)
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationId(
        array $programme,
        int $universityId,
        int $facultyId,
        int $qualificationTypeId,
        ?int $requiredGradeId
    ): int {
        $name = (string) $programme['name'];
        $existing = DB::table('qualifications')
            ->where('university_id', $universityId)
            ->where('qualification_type_id', $qualificationTypeId)
            ->where('name', $name)
            ->first();

        DB::table('qualifications')->updateOrInsert(
            [
                'university_id' => $universityId,
                'qualification_type_id' => $qualificationTypeId,
                'name' => $name,
            ],
            [
                'faculty_id' => $facultyId,
                'nqf_level_id' => $this->qualificationNqfLevelId($programme),
                'required_grade_id' => $requiredGradeId,
                'slug' => $existing?->slug ?: Str::slug((string) ($programme['id'] ?? $name)),
                'abbreviation' => $this->qualificationAbbreviation($programme),
                'duration_years' => $this->durationYears($programme),
                'aps_required' => $this->apsRequired($programme),
                'aggregate_average_required' => null,
                'admission_score_required' => null,
                'minimum_pass_type' => null,
                'is_selection_programme' => true,
                'notes' => $this->notes($programme),
                'source_url' => $programme['source_url'] ?? self::WEBSITE,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('qualifications')
            ->where('university_id', $universityId)
            ->where('qualification_type_id', $qualificationTypeId)
            ->where('name', $name)
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function assignAdmissionRule(int $universityId, int $qualificationId, ?int $requiredGradeId, array $programme): void
    {
        $ruleCode = $this->admissionRuleCode($programme);
        $admissionRuleId = DB::table('admission_rules')
            ->where('code', $ruleCode)
            ->value('id');

        if ($admissionRuleId === null) {
            return;
        }

        DB::table('university_admission_rules')->updateOrInsert(
            [
                'university_id' => $universityId,
                'faculty_id' => null,
                'qualification_id' => $qualificationId,
                'admission_rule_id' => $admissionRuleId,
            ],
            [
                'grade_id' => $requiredGradeId,
                'priority' => $this->apsRequired($programme) === null ? 20 : 10,
                'is_default' => false,
                'overrides' => $this->admissionRuleOverrides($programme),
                'notes' => $this->admissionRuleNotes($programme),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function admissionRuleCode(array $programme): string
    {
        $aps = $this->apsRequired($programme);

        if ($aps === null) {
            return 'subject_levels_only';
        }

        $calculation = strtolower((string) (($programme['admission']['aps'] ?? [])['calculation'] ?? ''));

        if (str_contains($calculation, 'next three')) {
            return 'cjc_aps_double_english_next_three';
        }

        if (str_contains($calculation, 'next four')) {
            return 'cjc_aps_double_english_next_four';
        }

        return 'cjc_aps_programme_specific_manual_review';
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function admissionRuleOverrides(array $programme): string
    {
        $admission = $programme['admission'] ?? [];
        $aps = $admission['aps'] ?? [];
        $matching = $programme['matching'] ?? [];
        $dataQuality = $programme['data_quality'] ?? [];

        return json_encode($this->withoutEmptyValues([
            'aps_calculation' => is_array($aps) ? ($aps['calculation'] ?? null) : null,
            'accepted_routes' => $admission['accepted_routes'] ?? [],
            'additional_requirements' => $admission['additional_requirements'] ?? [],
            'manual_review_when' => $matching['manual_review_when'] ?? [],
            'source_warning' => $dataQuality['warning'] ?? null,
        ]), JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function admissionRuleNotes(array $programme): string
    {
        $aps = $this->apsRequired($programme);
        $calculation = (($programme['admission']['aps'] ?? [])['calculation'] ?? null);

        if ($aps !== null) {
            $note = 'CJC programme data lists APS '.$aps.'.';

            if ($calculation !== null && $calculation !== '') {
                $note .= ' Calculation: '.$calculation.'.';
            }

            return $note.' Manual review remains required where portfolio, audition, interview, progression level or dated source-page checks apply.';
        }

        return 'CJC programme data does not list APS for this programme; matching should use accepted routes, required grade or qualification level, campus availability and manual review where noted.';
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationTypeName(array $programme): string
    {
        return match ($programme['qualification_type'] ?? null) {
            'National Certificate (Vocational)' => 'National Certificate Vocational',
            'NATED / Report 191' => 'NATED',
            default => (string) ($programme['qualification_type'] ?? 'Other'),
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationAbbreviation(array $programme): ?string
    {
        return match ($programme['qualification_type'] ?? null) {
            'National Certificate (Vocational)' => 'NCV',
            'NATED / Report 191' => 'NATED',
            'Occupational Certificate' => 'OccCert',
            'Further Education and Training Certificate' => 'FETC',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function requiredGradeName(array $programme): ?string
    {
        if (($programme['qualification_type'] ?? null) === 'National Certificate (Vocational)') {
            return 'Grade 9';
        }

        if (($programme['qualification_type'] ?? null) === 'NATED / Report 191') {
            return 'Grade 12';
        }

        $admission = $programme['admission'] ?? [];
        $entryText = collect([
            ...($admission['accepted_routes'] ?? []),
            ...($admission['additional_requirements'] ?? []),
        ])
            ->filter(fn ($value): bool => is_scalar($value))
            ->map(fn ($value): string => (string) $value)
            ->implode(' ');
        $entryText = strtolower($entryText);

        return match (true) {
            str_contains($entryText, 'grade 9') || str_contains($entryText, 'nqf level 1') => 'Grade 9',
            str_contains($entryText, 'grade 10') || str_contains($entryText, 'nqf level 2') => 'Grade 10',
            str_contains($entryText, 'grade 11') || str_contains($entryText, 'nqf level 3') => 'Grade 11',
            str_contains($entryText, 'grade 12') || str_contains($entryText, 'matric') || str_contains($entryText, 'nsc') => 'Grade 12',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationNqfLevelId(array $programme): ?int
    {
        if (($programme['qualification_type'] ?? null) === 'National Certificate (Vocational)') {
            return $this->nqfLevelId(4);
        }

        if (($programme['qualification_type'] ?? null) === 'NATED / Report 191') {
            return $this->nqfLevelId(6);
        }

        $levels = implode(' ', $programme['levels'] ?? []);

        if (preg_match('/NQF\s+Level\s+(\d+)/i', $levels, $matches) === 1) {
            return $this->nqfLevelId((int) $matches[1]);
        }

        return null;
    }

    private function nqfLevelId(int $level): ?int
    {
        return DB::table('nqf_levels')
            ->where('level', $level)
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function apsRequired(array $programme): ?int
    {
        $aps = ($programme['admission'] ?? [])['aps'] ?? null;

        if (! is_array($aps) || ! is_numeric($aps['minimum'] ?? null)) {
            return null;
        }

        return (int) $aps['minimum'];
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function notes(array $programme): string
    {
        $admission = $programme['admission'] ?? [];
        $duration = $programme['duration'] ?? [];
        $matching = $programme['matching'] ?? [];
        $dataQuality = $programme['data_quality'] ?? [];
        $notes = [];

        foreach ([
            'Qualification type' => $programme['qualification_type'] ?? null,
            'Levels offered' => $this->listText($programme['levels'] ?? []),
            'Study mode' => $programme['study_mode'] ?? null,
            'Academic cycle' => $duration['academic_cycle'] ?? null,
            'Duration' => $this->durationText($duration, $programme['levels'] ?? []),
        ] as $label => $value) {
            if ($value !== null && $value !== '') {
                $notes[] = $label.': '.$value.'.';
            }
        }

        if (! empty($programme['campuses'])) {
            $notes[] = 'Campuses: '.implode(', ', $programme['campuses']).'.';
        } else {
            $notes[] = 'Campus availability must be confirmed with CJC.';
        }

        if (! empty($admission['accepted_routes'])) {
            $notes[] = 'Accepted routes: '.implode('; ', $admission['accepted_routes']).'.';
        }

        $aps = $this->apsRequired($programme);
        $calculation = is_array($admission['aps'] ?? null) ? ($admission['aps']['calculation'] ?? null) : null;

        if ($aps !== null) {
            $notes[] = 'APS requirement: '.$aps.($calculation ? ' ('.$calculation.')' : '').'.';
        } else {
            $notes[] = 'APS is not listed as a requirement for this programme in the supplied CJC data.';
        }

        if (! empty($admission['additional_requirements'])) {
            $notes[] = 'Additional requirements: '.implode('; ', $admission['additional_requirements']).'.';
        }

        if (! empty($programme['progression'])) {
            $notes[] = 'Progression rules: '.$this->keyValueText($programme['progression']).'.';
        }

        if (! empty($programme['verification_notes'])) {
            $notes[] = 'Verification notes: '.implode('; ', $programme['verification_notes']).'.';
        }

        if (! empty($matching['manual_review_when'])) {
            $notes[] = 'Manual review required when: '.implode('; ', $matching['manual_review_when']).'.';
        }

        if (! empty($programme['subjects'])) {
            $subjectLevels = implode(', ', array_keys(array_filter(
                $programme['subjects'],
                fn ($subjects): bool => is_array($subjects) && $subjects !== [],
            )));

            if ($subjectLevels !== '') {
                $notes[] = 'Curriculum module lists are available for: '.$subjectLevels.'. These are not seeded as admission subject requirements.';
            }
        }

        if (! empty($programme['careers'])) {
            $notes[] = 'Career paths: '.implode('; ', array_slice($programme['careers'], 0, 8)).'.';
        }

        if (! empty($programme['funding']['note'])) {
            $notes[] = 'Funding note: '.$programme['funding']['note'].'.';
        }

        if (! empty($dataQuality['warning'])) {
            $notes[] = 'Source warning: '.$dataQuality['warning'];
        }

        return collect($notes)
            ->map(fn (string $note): string => trim($note))
            ->filter()
            ->unique()
            ->implode(' ');
    }

    /**
     * @param  array<string, mixed>  $duration
     * @param  array<int, string>  $levels
     */
    private function durationText(array $duration, array $levels): ?string
    {
        if (isset($duration['total'], $duration['unit'])) {
            return $duration['total'].' '.$duration['unit'];
        }

        if (isset($duration['full_path_years'])) {
            return $duration['full_path_years'].' years full path';
        }

        if (isset($duration['full_programme_academic_months'])) {
            return $duration['full_programme_academic_months'].' academic months';
        }

        if (isset($duration['per_level'], $duration['unit'])) {
            return $duration['per_level'].' '.$duration['unit'].' per level across '.count($levels).' levels';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function durationYears(array $programme): ?float
    {
        $duration = $programme['duration'] ?? [];
        $levels = $programme['levels'] ?? [];

        if (isset($duration['total'], $duration['unit']) && is_numeric($duration['total'])) {
            return $this->durationValueToYears((float) $duration['total'], (string) $duration['unit']);
        }

        if (isset($duration['full_path_years']) && is_numeric($duration['full_path_years'])) {
            return (float) $duration['full_path_years'];
        }

        if (isset($duration['full_programme_academic_months']) && is_numeric($duration['full_programme_academic_months'])) {
            return round((float) $duration['full_programme_academic_months'] / 12, 1);
        }

        if (isset($duration['per_level'], $duration['unit']) && is_numeric($duration['per_level']) && count($levels) > 0) {
            $total = (float) $duration['per_level'] * count($levels);

            return $this->durationValueToYears($total, (string) $duration['unit']);
        }

        return null;
    }

    private function durationValueToYears(float $value, string $unit): ?float
    {
        $unit = strtolower($unit);

        return match ($unit) {
            'year', 'years', 'calendar year', 'calendar years' => round($value, 1),
            'month', 'months' => round($value / 12, 1),
            default => null,
        };
    }

    /**
     * @param  array<int, string>  $values
     */
    private function listText(array $values): string
    {
        return implode(', ', $values);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function keyValueText(array $values): string
    {
        return collect($values)
            ->map(function ($value, string $key): string {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_array($value)) {
                    $value = implode(', ', $this->withoutEmptyValues($value));
                }

                return Str::headline($key).': '.$value;
            })
            ->implode('; ');
    }

    /**
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    private function withoutEmptyValues(array $values): array
    {
        return collect($values)
            ->reject(fn ($value): bool => $value === null || $value === '' || $value === [])
            ->all();
    }

    private function uniqueUniversitySlug(string $base): string
    {
        $base = $base ?: 'central-johannesburg-tvet-college';
        $slug = $base;
        $suffix = 2;

        while (DB::table('universities')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return array<string, int>
     */
    private function gradeIdsByName(): array
    {
        return DB::table('grades')
            ->join('curriculums', 'curriculums.id', '=', 'grades.curriculum_id')
            ->where('curriculums.abbreviation', 'CAPS')
            ->pluck('grades.id', 'grades.name')
            ->all();
    }

    private function qualificationTypeId(string $name): int
    {
        return (int) DB::table('qualification_types')
            ->where('name', $name)
            ->value('id');
    }
}
