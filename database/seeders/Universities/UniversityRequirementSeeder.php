<?php

namespace Database\Seeders\Universities;

use Database\Seeders\UniversityLogoSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

abstract class UniversityRequirementSeeder extends Seeder
{
    abstract protected function abbreviation(): string;

    abstract protected function universityName(): string;

    abstract protected function requirementsPath(): string;

    protected function admissionRuleCode(): string
    {
        return 'nsc_aps_excluding_lo';
    }

    protected function website(): ?string
    {
        return null;
    }

    protected function facultyAdmissionRuleCode(array $facultyData): ?string
    {
        return null;
    }

    public function run(): void
    {
        $facultyFiles = glob(database_path($this->requirementsPath())) ?: [];
        sort($facultyFiles);

        DB::transaction(function () use ($facultyFiles): void {
            $subjectIdsByGrade = $this->subjectIdsByGrade();
            $gradeIdsByName = $this->gradeIdsByName();
            $countryId = $this->countryId('South Africa');
            $universityId = $this->universityId($countryId);
            $this->assignAdmissionRule($universityId);

            foreach ($facultyFiles as $facultyFile) {
                $facultyData = json_decode(file_get_contents($facultyFile), true, 512, JSON_THROW_ON_ERROR);
                $facultyId = $this->facultyId($facultyData, $universityId);
                $facultyAdmissionRuleCode = $this->facultyAdmissionRuleCode($facultyData);

                if ($facultyAdmissionRuleCode !== null) {
                    $this->assignFacultyAdmissionRule($universityId, $facultyId, $facultyAdmissionRuleCode);
                }

                foreach ($facultyData['qualifications'] as $qualificationData) {
                    $qualificationTypeId = $this->qualificationTypeId($qualificationData['qualification_type']);
                    $qualificationId = $this->qualificationId($qualificationData, $universityId, $facultyId, $qualificationTypeId, $gradeIdsByName);

                    DB::table('qualification_subject_requirements')
                        ->where('qualification_id', $qualificationId)
                        ->delete();

                    DB::table('qualification_admission_score_variants')
                        ->where('qualification_id', $qualificationId)
                        ->delete();

                    foreach (($qualificationData['admission_score_variants'] ?? []) as $variantData) {
                        $this->insertAdmissionScoreVariant($qualificationId, $variantData, $subjectIdsByGrade, $gradeIdsByName);
                    }

                    foreach (($qualificationData['subject_requirements'] ?? []) as $index => $requirementData) {
                        if (($requirementData['type'] ?? null) === 'additional_subjects') {
                            continue;
                        }

                        if (($requirementData['type'] ?? null) === 'subject_group_count') {
                            $requirementGroup = 'requirement_'.$qualificationId.'_'.($index + 1);
                            $minimumLevel = $requirementData['minimum_level'] ?? null;
                            $note = json_encode([
                                'required_count' => $requirementData['count'] ?? 1,
                                'label' => $requirementData['label'] ?? null,
                            ]);

                            foreach (($requirementData['subjects'] ?? []) as $subjectData) {
                                $subjectIsArray = is_array($subjectData);
                                $subjectName = $subjectIsArray ? $subjectData['subject'] : $subjectData;
                                $subjectMinimumLevel = $subjectIsArray
                                    ? ($subjectData['minimum_level'] ?? $minimumLevel)
                                    : $minimumLevel;
                                $subjectMinimumMark = $subjectIsArray
                                    ? ($subjectData['minimum_mark'] ?? $requirementData['minimum_mark'] ?? null)
                                    : ($requirementData['minimum_mark'] ?? null);
                                $subjectGradeName = $subjectIsArray
                                    ? ($subjectData['grade'] ?? $subjectData['grade_name'] ?? $requirementData['grade'] ?? $requirementData['grade_name'] ?? $qualificationData['required_grade'] ?? 'Grade 12')
                                    : ($requirementData['grade'] ?? $requirementData['grade_name'] ?? $qualificationData['required_grade'] ?? 'Grade 12');
                                $subjectNote = $subjectIsArray ? ($subjectData['note'] ?? $note) : $note;

                                $this->insertSubjectRequirement(
                                    $qualificationId,
                                    $subjectName,
                                    $subjectMinimumLevel,
                                    $subjectMinimumMark,
                                    'subject_group_count',
                                    $requirementGroup,
                                    $subjectIdsByGrade,
                                    $gradeIdsByName,
                                    $subjectGradeName,
                                    $subjectNote
                                );
                            }

                            continue;
                        }

                        if (($requirementData['type'] ?? null) === 'subject_group_count_choices') {
                            $requirementGroup = 'requirement_'.$qualificationId.'_'.($index + 1);

                            foreach (($requirementData['choices'] ?? []) as $choiceIndex => $choice) {
                                $note = json_encode([
                                    'choice_key' => 'choice_'.($choiceIndex + 1),
                                    'required_count' => $choice['count'] ?? $requirementData['count'] ?? 1,
                                    'label' => $choice['label'] ?? null,
                                ]);

                                foreach (($choice['subjects'] ?? []) as $subjectData) {
                                    $subjectIsArray = is_array($subjectData);
                                    $subjectName = $subjectIsArray ? $subjectData['subject'] : $subjectData;
                                    $subjectMinimumLevel = $subjectIsArray
                                        ? ($subjectData['minimum_level'] ?? $choice['minimum_level'] ?? $requirementData['minimum_level'] ?? null)
                                        : ($choice['minimum_level'] ?? $requirementData['minimum_level'] ?? null);
                                    $subjectMinimumMark = $subjectIsArray
                                        ? ($subjectData['minimum_mark'] ?? $choice['minimum_mark'] ?? $requirementData['minimum_mark'] ?? null)
                                        : ($choice['minimum_mark'] ?? $requirementData['minimum_mark'] ?? null);
                                    $subjectGradeName = $subjectIsArray
                                        ? ($subjectData['grade'] ?? $subjectData['grade_name'] ?? $requirementData['grade'] ?? $requirementData['grade_name'] ?? $qualificationData['required_grade'] ?? 'Grade 12')
                                        : ($requirementData['grade'] ?? $requirementData['grade_name'] ?? $qualificationData['required_grade'] ?? 'Grade 12');
                                    $subjectNote = $subjectIsArray ? ($subjectData['note'] ?? $note) : $note;

                                    $this->insertSubjectRequirement(
                                        $qualificationId,
                                        $subjectName,
                                        $subjectMinimumLevel,
                                        $subjectMinimumMark,
                                        'subject_group_count_choice',
                                        $requirementGroup,
                                        $subjectIdsByGrade,
                                        $gradeIdsByName,
                                        $subjectGradeName,
                                        $subjectNote
                                    );
                                }
                            }

                            continue;
                        }

                        $subjects = $this->requirementSubjects($requirementData);

                        if ($subjects === []) {
                            continue;
                        }

                        $requirementGroup = count($subjects) > 1
                            ? 'requirement_'.$qualificationId.'_'.($index + 1)
                            : null;

                        foreach ($subjects as $subjectIndex => $subject) {
                                $this->insertSubjectRequirement(
                                    $qualificationId,
                                    $subject['subject'],
                                    $subject['minimum_level'],
                                    $subject['minimum_mark'] ?? null,
                                    $subjectIndex === 0 ? 'required' : 'alternative',
                                    $requirementGroup,
                                    $subjectIdsByGrade,
                                    $gradeIdsByName,
                                $requirementData['grade'] ?? $requirementData['grade_name'] ?? $qualificationData['required_grade'] ?? 'Grade 12',
                                $subject['note'] ?? null
                            );
                        }
                    }
                }
            }
        });
    }

    private function countryId(string $countryName): int
    {
        $now = now();

        if (! DB::table('countries')->where('name', $countryName)->exists()) {
            DB::table('countries')->insert([
                'name' => $countryName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return (int) DB::table('countries')->where('name', $countryName)->value('id');
    }

    private function universityId(int $countryId): int
    {
        $now = now();
        $existing = DB::table('universities')
            ->where('abbreviation', $this->abbreviation())
            ->first();

        DB::table('universities')->updateOrInsert(
            ['abbreviation' => $this->abbreviation()],
            [
                'country_id' => $countryId,
                'name' => $this->universityName(),
                'logo' => UniversityLogoSeeder::logoFor($this->abbreviation(), $existing?->logo),
                'website' => $this->website() ?? $existing?->website,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        return (int) DB::table('universities')
            ->where('abbreviation', $this->abbreviation())
            ->value('id');
    }

    private function assignAdmissionRule(int $universityId): void
    {
        $admissionRuleId = DB::table('admission_rules')
            ->where('code', $this->admissionRuleCode())
            ->value('id');

        if ($admissionRuleId === null) {
            return;
        }

        DB::table('university_admission_rules')->updateOrInsert(
            [
                'university_id' => $universityId,
                'faculty_id' => null,
                'qualification_id' => null,
                'admission_rule_id' => $admissionRuleId,
            ],
            [
                'grade_id' => $this->gradeIdsByName()['Grade 12'] ?? null,
                'priority' => 100,
                'is_default' => true,
                'overrides' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function assignFacultyAdmissionRule(int $universityId, int $facultyId, string $admissionRuleCode): void
    {
        $admissionRuleId = DB::table('admission_rules')
            ->where('code', $admissionRuleCode)
            ->value('id');

        if ($admissionRuleId === null) {
            return;
        }

        DB::table('university_admission_rules')->updateOrInsert(
            [
                'university_id' => $universityId,
                'faculty_id' => $facultyId,
                'qualification_id' => null,
                'admission_rule_id' => $admissionRuleId,
            ],
            [
                'grade_id' => $this->gradeIdsByName()['Grade 12'] ?? null,
                'priority' => 90,
                'is_default' => false,
                'overrides' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function facultyId(array $facultyData, int $universityId): int
    {
        $now = now();

        DB::table('faculties')->updateOrInsert(
            ['university_id' => $universityId, 'name' => $this->facultyName($facultyData)],
            [
                'closing_month' => $this->monthNumber($facultyData['closing_month'] ?? $facultyData['default_application_closing_month'] ?? null),
                'closing_day' => $facultyData['closing_day'] ?? $facultyData['default_application_closing_day'] ?? null,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        return (int) DB::table('faculties')
            ->where('university_id', $universityId)
            ->where('name', $this->facultyName($facultyData))
            ->value('id');
    }

    private function qualificationTypeId(string $name): int
    {
        $now = now();

        DB::table('qualification_types')->updateOrInsert(
            ['name' => $name],
            ['updated_at' => $now, 'created_at' => $now],
        );

        return (int) DB::table('qualification_types')->where('name', $name)->value('id');
    }

    private function qualificationId(array $qualificationData, int $universityId, int $facultyId, int $qualificationTypeId, array $gradeIdsByName): int
    {
        $now = now();

        DB::table('qualifications')->updateOrInsert(
            [
                'university_id' => $universityId,
                'faculty_id' => $facultyId,
                'name' => $qualificationData['name'],
            ],
            [
                'qualification_type_id' => $qualificationTypeId,
                'nqf_level_id' => $this->qualificationNqfLevelId($qualificationData, $qualificationTypeId),
                'required_grade_id' => $this->requiredGradeId($qualificationData, $gradeIdsByName),
                'abbreviation' => $qualificationData['abbreviation'] ?? null,
                'duration_years' => $qualificationData['duration_years'] ?? null,
                'aps_required' => $qualificationData['aps_required'] ?? null,
                'aggregate_average_required' => $this->aggregateAverageRequired($qualificationData),
                'admission_score_required' => $this->admissionScoreRequired($qualificationData),
                'minimum_pass_type' => $qualificationData['minimum_pass_type'] ?? $qualificationData['pass_type_required'] ?? null,
                'closing_month' => $this->monthNumber($qualificationData['closing_month'] ?? $qualificationData['application_closing_month'] ?? null),
                'closing_day' => $qualificationData['closing_day'] ?? $qualificationData['application_closing_day'] ?? null,
                'is_selection_programme' => $qualificationData['is_selection_programme'] ?? $qualificationData['selection_programme'] ?? false,
                'notes' => $this->notes($qualificationData),
                'source_url' => $qualificationData['source_url'] ?? null,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        return (int) DB::table('qualifications')
            ->where('university_id', $universityId)
            ->where('faculty_id', $facultyId)
            ->where('name', $qualificationData['name'])
            ->value('id');
    }

    private function aggregateAverageRequired(array $qualificationData): ?float
    {
        $average = $qualificationData['aggregate_average_required']
            ?? $qualificationData['aggregated_average_required']
            ?? $qualificationData['Aggregated_Average_Required']
            ?? null;

        if ($average === null || $average === '') {
            return null;
        }

        if (is_numeric($average)) {
            return (float) $average;
        }

        if (preg_match('/\d+(?:\.\d+)?/', (string) $average, $matches) === 1) {
            return (float) $matches[0];
        }

        return null;
    }

    private function admissionScoreRequired(array $qualificationData): ?float
    {
        $score = $qualificationData['admission_score_required']
            ?? $qualificationData['minimum_admission_score']
            ?? $qualificationData['fps_required']
            ?? $qualificationData['FPS_Required']
            ?? $this->aggregateAverageRequired($qualificationData)
            ?? $qualificationData['aps_required']
            ?? null;

        if ($score === null || $score === '') {
            return null;
        }

        if (is_numeric($score)) {
            return (float) $score;
        }

        if (preg_match('/\d+(?:\.\d+)?/', (string) $score, $matches) === 1) {
            return (float) $matches[0];
        }

        return null;
    }

    private function requiredGradeId(array $qualificationData, array $gradeIdsByName): ?int
    {
        $gradeName = $qualificationData['required_grade'] ?? $qualificationData['grade'] ?? null;

        return $gradeName === null ? null : ($gradeIdsByName[$gradeName] ?? null);
    }

    private function qualificationNqfLevelId(array $qualificationData, int $qualificationTypeId): ?int
    {
        return $this->nqfLevelId($qualificationData['nqf_level'] ?? null)
            ?? DB::table('qualification_types')->where('id', $qualificationTypeId)->value('nqf_level_id');
    }

    private function nqfLevelId(mixed $level): ?int
    {
        if ($level === null || $level === '') {
            return null;
        }

        return DB::table('nqf_levels')->where('level', (int) $level)->value('id');
    }

    private function facultyName(array $facultyData): string
    {
        return $facultyData['name'] ?? $facultyData['faculty'];
    }

    private function notes(array $qualificationData): ?string
    {
        $notes = [];

        if (! empty($qualificationData['notes'])) {
            is_array($qualificationData['notes'])
                ? array_push($notes, ...$qualificationData['notes'])
                : $notes[] = $qualificationData['notes'];
        }

        if (! empty($qualificationData['recommended_subjects'])) {
            $notes[] = 'Recommended subjects: '.implode(', ', $qualificationData['recommended_subjects']).'.';
        }

        if (! empty($qualificationData['other_campuses'])) {
            $notes[] = 'Other campuses: '.implode(', ', $qualificationData['other_campuses']).'.';
        }

        foreach (($qualificationData['subject_requirements'] ?? []) as $requirement) {
            if (($requirement['type'] ?? null) !== 'additional_subjects') {
                continue;
            }

            if (isset($requirement['count'], $requirement['minimum_level'])) {
                $notes[] = "Requires {$requirement['count']} additional subjects at level {$requirement['minimum_level']}.";
            }
        }

        return $notes === [] ? null : implode(' ', $notes);
    }

    private function requirementSubjects(array $requirementData): array
    {
        if (isset($requirementData['one_of'])) {
            return collect($requirementData['one_of'])
                ->map(fn ($requirement) => [
                    'subject' => $requirement['subject'],
                    'minimum_level' => $requirement['minimum_level'] ?? null,
                    'minimum_mark' => $requirement['minimum_mark'] ?? null,
                    'note' => $requirement['note'] ?? null,
                ])
                ->all();
        }

        if (! isset($requirementData['subject'])) {
            return [];
        }

        return array_merge([[
            'subject' => $requirementData['subject'],
            'minimum_level' => $requirementData['minimum_level'] ?? null,
            'minimum_mark' => $requirementData['minimum_mark'] ?? null,
            'note' => $requirementData['note'] ?? null,
        ]], $this->alternatives($requirementData));
    }

    private function alternatives(array $requirementData): array
    {
        $alternatives = [];

        foreach (['', '_2', '_3', '_4', '_5'] as $suffix) {
            $subjectKey = 'alternative_subject'.$suffix;
            $levelKey = 'alternative_minimum_level'.$suffix;

            if (isset($requirementData[$subjectKey])) {
                $alternatives[] = [
                    'subject' => $requirementData[$subjectKey],
                    'minimum_level' => $requirementData[$levelKey] ?? null,
                    'minimum_mark' => $requirementData['alternative_minimum_mark'.$suffix] ?? null,
                ];
            }
        }

        return $alternatives;
    }

    private function monthNumber(null|int|string $month): ?int
    {
        if ($month === null || $month === '') {
            return null;
        }

        if (is_int($month) || ctype_digit((string) $month)) {
            return (int) $month;
        }

        return [
            'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
            'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
            'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
        ][strtolower($month)] ?? null;
    }

    private function insertSubjectRequirement(
        int $qualificationId,
        string $subjectName,
        ?int $minimumLevel,
        null|int|float $minimumMark,
        string $requirementType,
        ?string $requirementGroup,
        array $subjectIdsByGrade,
        array $gradeIdsByName,
        string $gradeName,
        ?string $note = null
    ): void
    {
        $gradeId = $gradeIdsByName[$gradeName] ?? $gradeIdsByName['Grade 12'] ?? null;
        $subjectIds = $subjectIdsByGrade[$gradeName] ?? $subjectIdsByGrade['Grade 12'] ?? [];

        DB::table('qualification_subject_requirements')->insert([
            'qualification_id' => $qualificationId,
            'subject_id' => $subjectIds[$subjectName] ?? null,
            'grade_id' => $gradeId,
            'subject_name' => $subjectName,
            'minimum_mark' => $minimumMark !== null ? (int) ceil((float) $minimumMark) : ($minimumLevel !== null ? $this->minimumMarkForLevel($minimumLevel) : null),
            'aps_level_required' => $minimumLevel,
            'requirement_type' => $requirementType,
            'requirement_group' => $requirementGroup,
            'notes' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertAdmissionScoreVariant(
        int $qualificationId,
        array $variantData,
        array $subjectIdsByGrade,
        array $gradeIdsByName
    ): void {
        $gradeName = $variantData['grade'] ?? $variantData['grade_name'] ?? 'Grade 12';
        $subjectName = $variantData['subject'] ?? $variantData['subject_name'] ?? null;
        $subjectIds = $subjectIdsByGrade[$gradeName] ?? $subjectIdsByGrade['Grade 12'] ?? [];
        $minimumLevel = $variantData['minimum_level'] ?? $variantData['aps_level_required'] ?? null;
        $minimumMark = $variantData['minimum_mark'] ?? null;

        if ($subjectName === null || ($variantData['admission_score_required'] ?? null) === null) {
            return;
        }

        DB::table('qualification_admission_score_variants')->insert([
            'qualification_id' => $qualificationId,
            'subject_id' => $subjectIds[$subjectName] ?? null,
            'subject_name' => $subjectName,
            'minimum_mark' => $minimumMark !== null ? (int) ceil((float) $minimumMark) : ($minimumLevel !== null ? $this->minimumMarkForLevel((int) $minimumLevel) : null),
            'aps_level_required' => $minimumLevel,
            'admission_score_required' => (float) $variantData['admission_score_required'],
            'label' => $variantData['label'] ?? null,
            'notes' => $variantData['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function minimumMarkForLevel(int $level): ?int
    {
        return match ($level) {
            7 => 80, 6 => 70, 5 => 60, 4 => 50, 3 => 40, 2 => 30, 1 => 0,
            default => null,
        };
    }

    private function subjectIdsByGrade(): array
    {
        return DB::table('subjects')
            ->join('grades', 'grades.id', '=', 'subjects.grade_id')
            ->join('curriculums', 'curriculums.id', '=', 'subjects.curriculum_id')
            ->where('curriculums.abbreviation', 'CAPS')
            ->select('subjects.id', 'subjects.name', 'grades.name as grade_name')
            ->get()
            ->groupBy('grade_name')
            ->map(fn ($subjects) => $subjects->pluck('id', 'name')->all())
            ->all();
    }

    private function gradeIdsByName(): array
    {
        return DB::table('grades')
            ->join('curriculums', 'curriculums.id', '=', 'grades.curriculum_id')
            ->where('curriculums.abbreviation', 'CAPS')
            ->pluck('grades.id', 'grades.name')
            ->all();
    }
}
