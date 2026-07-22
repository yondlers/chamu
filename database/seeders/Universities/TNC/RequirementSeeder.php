<?php

namespace Database\Seeders\Universities\TNC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequirementSeeder extends Seeder
{
    private const PROGRAMMES_PATH = __DIR__.'/tnc_programmes_2026.json';

    private const PROGRAMMES_SOURCE_URL = 'https://www.tnc.edu.za/programmes.php';

    public function run(): void
    {
        $data = json_decode(file_get_contents(self::PROGRAMMES_PATH), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data): void {
            $this->seedQualificationTypes();

            $gradeIdsByName = $this->gradeIdsByName();
            $subjectIdsByGrade = $this->subjectIdsByGrade();
            $countryId = $this->countryId($data['institution']['country'] ?? 'South Africa');
            $universityId = $this->universityId($countryId, $data['institution'] ?? []);

            DB::table('university_admission_rules')
                ->where('university_id', $universityId)
                ->delete();

            foreach ($data['programmes'] as $programme) {
                $facultyId = $this->facultyId($universityId, $this->facultyName($programme));
                $qualificationTypeId = $this->qualificationTypeId($this->qualificationTypeName($programme));
                $requiredGradeName = $this->requiredGradeName($programme);
                $requiredGradeId = $requiredGradeName === null ? null : ($gradeIdsByName[$requiredGradeName] ?? null);
                $qualificationId = $this->qualificationId(
                    $programme,
                    $universityId,
                    $facultyId,
                    $qualificationTypeId,
                    $requiredGradeId
                );

                DB::table('qualification_subject_requirements')
                    ->where('qualification_id', $qualificationId)
                    ->delete();

                DB::table('qualification_admission_score_variants')
                    ->where('qualification_id', $qualificationId)
                    ->delete();

                $requirements = $this->subjectRequirementsFor($programme);
                $requirementGradeName = $requiredGradeName ?? 'Grade 12';

                foreach ($requirements as $index => $requirement) {
                    $this->insertRequirement(
                        $qualificationId,
                        $requirement,
                        $index,
                        $requirementGradeName,
                        $gradeIdsByName,
                        $subjectIdsByGrade,
                    );
                }

                $this->assignSubjectLevelsRule($universityId, $qualificationId, $requiredGradeId);
            }
        });
    }

    private function seedQualificationTypes(): void
    {
        $types = [
            ['National Certificate Vocational', 'NCV', 4, 5],
            ['NATED', 'NATED', 6, 50],
            ['Pre-Learning Programme', 'PLP', 1, 51],
            ['Occupational Certificate', 'OccCert', null, 52],
            ['International/industry diploma', null, null, 53],
            ['College Occupational Programme', null, null, 54],
            ['Short Skills Programme', null, null, 55],
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

        return (int) DB::table('countries')->where('name', $countryName)->value('id');
    }

    /**
     * @param  array<string, mixed>  $institution
     */
    private function universityId(int $countryId, array $institution): int
    {
        $abbreviation = (string) ($institution['abbreviation'] ?? 'TNC');
        $name = (string) ($institution['name'] ?? 'Tshwane North TVET College');
        $existing = DB::table('universities')
            ->where('abbreviation', $abbreviation)
            ->first();

        DB::table('universities')->updateOrInsert(
            ['abbreviation' => $abbreviation],
            [
                'country_id' => $countryId,
                'name' => $name,
                'slug' => $existing?->slug ?: $this->uniqueUniversitySlug(Str::slug($name)),
                'website' => $institution['website'] ?? 'https://www.tnc.edu.za/',
                'updated_at' => now(),
                'created_at' => now(),
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
                'duration_years' => $this->durationYears($programme['duration'] ?? null),
                'aps_required' => null,
                'aggregate_average_required' => null,
                'admission_score_required' => null,
                'minimum_pass_type' => null,
                'is_selection_programme' => $this->hasCollegeSelectionCriteria($programme),
                'notes' => $this->notes($programme),
                'source_url' => $programme['source_url'] ?? self::PROGRAMMES_SOURCE_URL,
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
    private function subjectRequirementsFor(array $programme): array
    {
        return match ($programme['id'] ?? null) {
            'tnc-ncv-mechatronics' => [
                $this->required('Mathematics'),
                $this->required('Physical Sciences', null, 'The TNC programmes page lists Mathematics and Physical Science for Mechatronics.'),
            ],
            'tnc-nated-management-assistant' => [
                $this->required('English', 40),
            ],
            'tnc-nated-financial-management' => [
                $this->required('English', 40),
                $this->required('Accounting', 30),
            ],
            'tnc-nated-art-and-design' => [
                $this->required('English', 50),
            ],
            'tnc-nated-public-relations' => [
                $this->required('English', 50),
            ],
            'tnc-nated-clothing-production' => [
                $this->required('English', 50),
            ],
            'tnc-nated-tourism' => [
                $this->required('English', 50),
                $this->required('Tourism', 50),
                $this->oneOf([
                    ['subject' => 'Accounting', 'minimum_mark' => 30],
                    ['subject' => 'Mathematics', 'minimum_mark' => 30],
                    ['subject' => 'Mathematical Literacy', 'minimum_mark' => 30],
                ], 'Accounting, Mathematics or Mathematical Literacy'),
            ],
            'tnc-nated-hospitality-and-catering-services' => [
                $this->required('English', 40),
                $this->oneOf([
                    ['subject' => 'Consumer Studies', 'minimum_mark' => 40],
                    ['subject' => 'Home Economics', 'minimum_mark' => 40],
                    ['subject' => 'Hospitality Studies', 'minimum_mark' => 40],
                ], 'Consumer Studies, Home Economics or Hospitality Studies'),
            ],
            'tnc-nated-public-management' => [
                $this->required('English', 40),
            ],
            'tnc-nated-legal-secretary' => [
                $this->required('English', 40),
            ],
            'tnc-nated-business-management' => [
                $this->required('English', 40),
                $this->required('Accounting', 30),
            ],
            'tnc-nated-civil-engineering',
            'tnc-nated-electrical-engineering' => [
                $this->natedEngineeringMathematicsChoice(40),
                $this->required('Physical Sciences', 40),
            ],
            'tnc-nated-mechanical-engineering' => [
                $this->natedEngineeringMathematicsChoice(40),
                $this->required('Physical Sciences', 30, 'Automotive requires 40%; Fitting and Boiler Making require 30% in Physical Science on the TNC programmes page.'),
            ],
            default => $this->subjectRequirementsFromJson($programme),
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function subjectRequirementsFromJson(array $programme): array
    {
        $requirements = [];

        foreach (($programme['entry_points'][0]['required_school_subjects'] ?? []) as $subject) {
            if (str_contains(strtolower((string) $subject), 'social science')) {
                $requirements[] = $this->socialScienceRequirement();

                continue;
            }

            $requirements[] = $this->required($this->normalisedSubjectName((string) $subject));
        }

        foreach (($programme['entry_points'][0]['required_subjects'] ?? []) as $subjectRequirement) {
            $requirements[] = $this->required(
                $this->normalisedSubjectName((string) ($subjectRequirement['subject'] ?? '')),
                $subjectRequirement['minimum_percentage'] ?? null,
            );
        }

        foreach (($programme['entry_requirements']['required_subjects'] ?? []) as $subject) {
            $requirements[] = $this->required($this->normalisedSubjectName((string) $subject));
        }

        $entryText = strtolower(implode(' ', array_filter([
            is_string($programme['minimum_entry_requirements'] ?? null) ? $programme['minimum_entry_requirements'] : null,
            is_string($programme['entry_requirements'] ?? null) ? $programme['entry_requirements'] : null,
        ])));

        if ($entryText !== '') {
            if (str_contains($entryText, 'mathematics')) {
                $requirements[] = $this->required(
                    'Mathematics',
                    null,
                    str_contains($entryText, 'not mathematical literacy') ? 'Mathematics is required; Mathematical Literacy is not accepted for this programme.' : null,
                );
            }

            if (str_contains($entryText, 'science')) {
                $requirements[] = $this->required('Physical Sciences');
            }

            if (str_contains($entryText, 'building drawing')) {
                $requirements[] = $this->required('Engineering Graphics and Design');
            }
        }

        return $requirements;
    }

    /**
     * @param  array<int, string|array{subject: string, minimum_mark?: int|null, note?: string|null}>  $subjects
     * @return array<string, mixed>
     */
    private function oneOf(array $subjects, string $label): array
    {
        return [
            'type' => 'one_of',
            'label' => $label,
            'subjects' => array_map(fn ($subject): array => is_array($subject) ? $subject : ['subject' => $subject], $subjects),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function required(string $subject, null|int|float $minimumMark = null, ?string $note = null): array
    {
        return [
            'type' => 'required',
            'subject' => $subject,
            'minimum_mark' => $minimumMark,
            'note' => $note,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function socialScienceRequirement(): array
    {
        return [
            'type' => 'subject_group_count',
            'count' => 1,
            'label' => 'Social Science subjects',
            'subjects' => [
                ['subject' => 'Geography'],
                ['subject' => 'History'],
                ['subject' => 'Religion Studies'],
            ],
            'note' => 'Published requirement is Social Science and English; CAPS subject options are used for matching.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function natedEngineeringMathematicsChoice(int $minimumMark): array
    {
        return $this->oneOf([
            ['subject' => 'Mathematics', 'minimum_mark' => $minimumMark],
            ['subject' => 'Technical Mathematics', 'minimum_mark' => $minimumMark],
            ['subject' => 'Mathematical Literacy', 'minimum_mark' => $minimumMark],
        ], 'Mathematics, Technical Mathematics or Mathematical Literacy');
    }

    /**
     * @param  array<string, mixed>  $requirement
     * @param  array<string, int>  $gradeIdsByName
     * @param  array<string, array<string, int>>  $subjectIdsByGrade
     */
    private function insertRequirement(
        int $qualificationId,
        array $requirement,
        int $index,
        string $gradeName,
        array $gradeIdsByName,
        array $subjectIdsByGrade
    ): void {
        if (($requirement['type'] ?? null) === 'one_of') {
            $group = 'requirement_'.$qualificationId.'_'.($index + 1);

            foreach (($requirement['subjects'] ?? []) as $subjectIndex => $subject) {
                $this->insertSubjectRequirement(
                    $qualificationId,
                    (string) $subject['subject'],
                    $subject['minimum_mark'] ?? null,
                    $subjectIndex === 0 ? 'required' : 'alternative',
                    $group,
                    $gradeName,
                    $gradeIdsByName,
                    $subjectIdsByGrade,
                    $subject['note'] ?? ($requirement['label'] ?? null),
                );
            }

            return;
        }

        if (($requirement['type'] ?? null) === 'subject_group_count') {
            $group = 'requirement_'.$qualificationId.'_'.($index + 1);
            $note = json_encode([
                'required_count' => $requirement['count'] ?? 1,
                'label' => $requirement['label'] ?? null,
            ]);

            foreach (($requirement['subjects'] ?? []) as $subject) {
                $this->insertSubjectRequirement(
                    $qualificationId,
                    (string) $subject['subject'],
                    $subject['minimum_mark'] ?? $requirement['minimum_mark'] ?? null,
                    'subject_group_count',
                    $group,
                    $gradeName,
                    $gradeIdsByName,
                    $subjectIdsByGrade,
                    $subject['note'] ?? $note,
                );
            }

            return;
        }

        $this->insertSubjectRequirement(
            $qualificationId,
            (string) $requirement['subject'],
            $requirement['minimum_mark'] ?? null,
            'required',
            null,
            $gradeName,
            $gradeIdsByName,
            $subjectIdsByGrade,
            $requirement['note'] ?? null,
        );
    }

    /**
     * @param  array<string, int>  $gradeIdsByName
     * @param  array<string, array<string, int>>  $subjectIdsByGrade
     */
    private function insertSubjectRequirement(
        int $qualificationId,
        string $subjectName,
        null|int|float $minimumMark,
        string $requirementType,
        ?string $requirementGroup,
        string $gradeName,
        array $gradeIdsByName,
        array $subjectIdsByGrade,
        ?string $note = null
    ): void {
        $subjectName = $this->normalisedSubjectName($subjectName);
        $subjectIds = $subjectIdsByGrade[$gradeName] ?? [];

        DB::table('qualification_subject_requirements')->insert([
            'qualification_id' => $qualificationId,
            'subject_id' => $subjectIds[$subjectName] ?? null,
            'grade_id' => $gradeIdsByName[$gradeName] ?? null,
            'subject_name' => $subjectName,
            'minimum_mark' => $minimumMark === null ? null : (int) ceil((float) $minimumMark),
            'aps_level_required' => null,
            'requirement_type' => $requirementType,
            'requirement_group' => $requirementGroup,
            'notes' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assignSubjectLevelsRule(int $universityId, int $qualificationId, ?int $requiredGradeId): void
    {
        $admissionRuleId = DB::table('admission_rules')
            ->where('code', 'subject_levels_only')
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
                'priority' => 10,
                'is_default' => false,
                'overrides' => null,
                'notes' => 'TNC matching is based on published school/NQF entry level, subjects and college selection criteria rather than APS.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationTypeName(array $programme): string
    {
        return match ($programme['qualification_type'] ?? null) {
            'NC(V)' => 'National Certificate Vocational',
            'PLP' => 'Pre-Learning Programme',
            'College/occupational-style programme (classification should be confirmed)' => 'College Occupational Programme',
            default => (string) ($programme['qualification_type'] ?? 'Other'),
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function facultyName(array $programme): string
    {
        if (! empty($programme['field'])) {
            return (string) $programme['field'];
        }

        return match ($programme['qualification_type'] ?? null) {
            'Short Skills Programme' => 'Short Skills Programmes',
            'International/industry diploma',
            'College/occupational-style programme (classification should be confirmed)',
            'Occupational Certificate' => 'Occupational Programmes',
            default => 'General Programmes',
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationAbbreviation(array $programme): ?string
    {
        return match ($programme['qualification_type'] ?? null) {
            'NC(V)' => 'NCV',
            'NATED' => 'NATED',
            'PLP' => 'PLP',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function requiredGradeName(array $programme): ?string
    {
        $entryTextParts = [];

        foreach (($programme['entry_points'] ?? []) as $entryPoint) {
            foreach (['minimum_school_level', 'minimum_qualification', 'required_prior_qualification'] as $key) {
                if (! empty($entryPoint[$key])) {
                    $entryTextParts[] = (string) $entryPoint[$key];
                }
            }
        }

        foreach ([
            $programme['minimum_entry_requirements'] ?? null,
            $programme['recommended_entry_requirements'] ?? null,
            $programme['entry_requirements'] ?? null,
            $programme['entry_requirements']['minimum'] ?? null,
            $programme['entry_requirements']['school_grade_range'] ?? null,
        ] as $entryText) {
            if (is_string($entryText)) {
                $entryTextParts[] = $entryText;
            }
        }

        $entryText = strtolower(implode(' ', $entryTextParts));

        return match (true) {
            str_contains($entryText, 'grade 9') || str_contains($entryText, 'nqf level 1') => 'Grade 9',
            str_contains($entryText, 'grade 10') || str_contains($entryText, 'nqf level 2') => 'Grade 10',
            str_contains($entryText, 'grade 11') || str_contains($entryText, 'nqf level 3') => 'Grade 11',
            str_contains($entryText, 'grade 12') || str_contains($entryText, 'matric') || str_contains($entryText, 'nqf level 4') => 'Grade 12',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationNqfLevelId(array $programme): ?int
    {
        if (! empty($programme['nqf_levels']) && is_array($programme['nqf_levels'])) {
            return $this->nqfLevelId(max(array_map('intval', $programme['nqf_levels'])));
        }

        $levels = $programme['levels'] ?? [];

        if (is_array($levels) && collect($levels)->contains(fn ($level): bool => is_string($level) && preg_match('/^N[4-6]$/', $level) === 1)) {
            return $this->nqfLevelId(6);
        }

        $text = strtolower((string) ($programme['name'] ?? ''));

        if (preg_match('/nqf level (\d+)/', $text, $matches) === 1) {
            return $this->nqfLevelId((int) $matches[1]);
        }

        return null;
    }

    private function nqfLevelId(int $level): ?int
    {
        return DB::table('nqf_levels')->where('level', $level)->value('id');
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function notes(array $programme): ?string
    {
        $notes = [];

        if (! empty($programme['qualification_family'])) {
            $notes[] = 'Qualification family: '.$programme['qualification_family'].'.';
        }

        if (! empty($programme['delivery_cycle'])) {
            $notes[] = 'Delivery cycle: '.$programme['delivery_cycle'].'.';
        }

        $duration = $this->durationText($programme['duration'] ?? null);

        if ($duration !== null) {
            $notes[] = 'Duration: '.$duration.'.';
        }

        if (! empty($programme['study_modes'])) {
            $notes[] = 'Study mode: '.implode(', ', $programme['study_modes']).'.';
        }

        if (! empty($programme['campuses'])) {
            $notes[] = 'Campuses: '.implode(', ', $programme['campuses']).'.';
        } elseif (! empty($programme['availability_note'])) {
            $notes[] = $programme['availability_note'];
        } elseif (($programme['qualification_type'] ?? null) !== 'Short Skills Programme') {
            $notes[] = 'Campus availability must be confirmed with TNC.';
        }

        $entryRequirements = $this->entryRequirementsText($programme);

        if ($entryRequirements !== null) {
            $notes[] = $entryRequirements;
        }

        foreach ($this->sourceSelectionNotes($programme) as $note) {
            $notes[] = $note;
        }

        foreach (['additional_requirement', 'funding', 'certification', 'accreditation_body', 'awarding_body', 'framework', 'availability_note', 'note'] as $key) {
            if (! empty($programme[$key]) && is_string($programme[$key])) {
                $notes[] = Str::ucfirst(str_replace('_', ' ', $key)).': '.$programme[$key].'.';
            }
        }

        foreach (($programme['notes'] ?? []) as $note) {
            if (is_string($note)) {
                $notes[] = $note;
            }
        }

        $notes[] = 'Minimum requirements do not guarantee admission; TNC selection criteria, learner profiling and campus capacity may apply.';

        return collect($notes)
            ->map(fn (string $note): string => trim($note))
            ->filter()
            ->unique()
            ->implode(' ');
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function entryRequirementsText(array $programme): ?string
    {
        $sentences = [];

        foreach (($programme['entry_points'] ?? []) as $entryPoint) {
            $target = $entryPoint['target_level'] ?? null;

            if (! empty($entryPoint['minimum_school_level'])) {
                $sentence = 'Entry'.($target ? ' to level '.$target : '').': '.$entryPoint['minimum_school_level'];

                if (! empty($entryPoint['accepted_equivalents'])) {
                    $sentence .= ' or '.implode('/', $entryPoint['accepted_equivalents']);
                }

                $sentences[] = $sentence.'.';
            }

            if (! empty($entryPoint['minimum_qualification'])) {
                $sentences[] = 'Entry'.($target ? ' to '.$target : '').': '.$entryPoint['minimum_qualification'].'.';
            }

            if (! empty($entryPoint['required_prior_qualification'])) {
                $sentences[] = 'Progression'.($target ? ' to '.$target : '').': '.$entryPoint['required_prior_qualification'].'.';
            }

            if (! empty($entryPoint['source_wording_warning'])) {
                $sentences[] = $entryPoint['source_wording_warning'];
            }

            if (! empty($entryPoint['technical_background_required_or_preferred'])) {
                $sentences[] = $entryPoint['technical_background_required_or_preferred'].'.';
            }
        }

        if (! empty($programme['minimum_entry_requirements'])) {
            $sentences[] = 'Minimum entry requirements: '.$programme['minimum_entry_requirements'].'.';
        }

        if (! empty($programme['recommended_entry_requirements'])) {
            $sentences[] = 'Recommended entry requirements: '.$programme['recommended_entry_requirements'].'.';
        }

        if (! empty($programme['entry_requirements']) && is_string($programme['entry_requirements'])) {
            $sentences[] = 'Entry requirements: '.$programme['entry_requirements'].'.';
        }

        if (! empty($programme['entry_requirements']) && is_array($programme['entry_requirements'])) {
            if (! empty($programme['entry_requirements']['minimum'])) {
                $sentences[] = 'Entry requirements: '.$programme['entry_requirements']['minimum'].'.';
            }

            if (! empty($programme['entry_requirements']['school_grade_range'])) {
                $sentences[] = 'Entry grade range: '.$programme['entry_requirements']['school_grade_range'].'.';
            }

            if (isset($programme['entry_requirements']['minimum_age'])) {
                $sentences[] = 'Minimum age: '.$programme['entry_requirements']['minimum_age'].'.';
            }
        }

        return $sentences === [] ? null : implode(' ', $sentences);
    }

    /**
     * @param  array<string, mixed>  $programme
     * @return array<int, string>
     */
    private function sourceSelectionNotes(array $programme): array
    {
        return match ($programme['id'] ?? null) {
            'tnc-nated-management-assistant',
            'tnc-nated-public-relations',
            'tnc-nated-legal-secretary' => [
                'Computer or typing-related subjects at 30% are listed by TNC as an advantage for this programme.',
            ],
            'tnc-nated-public-management' => [
                'A Business Studies-related subject at 30% is listed by TNC as an advantage for this programme.',
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function hasCollegeSelectionCriteria(array $programme): bool
    {
        foreach (($programme['entry_points'] ?? []) as $entryPoint) {
            if (($entryPoint['college_selection_criteria_apply'] ?? false)
                || ($entryPoint['specific_college_requirements_apply'] ?? false)
            ) {
                return true;
            }
        }

        return false;
    }

    private function durationYears(mixed $duration): ?float
    {
        if (is_array($duration)) {
            foreach (['full_path_if_completed_levels_2_to_4', 'total_to_diploma', 'levels', 'theory_total'] as $key) {
                $years = $this->durationYears($duration[$key] ?? null);

                if ($years !== null) {
                    return $years;
                }
            }

            return null;
        }

        if (! is_string($duration)) {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*years?/', $duration, $matches) === 1) {
            return (float) $matches[1];
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*months?/', $duration, $matches) === 1) {
            return round((float) $matches[1] / 12, 1);
        }

        return null;
    }

    private function durationText(mixed $duration): ?string
    {
        if (is_array($duration)) {
            return collect($duration)
                ->map(fn ($value, string $key): ?string => is_string($value) ? str_replace('_', ' ', $key).': '.$value : null)
                ->filter()
                ->implode('; ');
        }

        return is_string($duration) ? $duration : null;
    }

    private function normalisedSubjectName(string $subject): string
    {
        return match (trim($subject)) {
            'Physical Science', 'Science' => 'Physical Sciences',
            'I.T and Computer science' => 'Information Technology',
            default => trim($subject),
        };
    }

    private function uniqueUniversitySlug(string $base): string
    {
        $base = $base ?: 'tshwane-north-tvet-college';
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

    /**
     * @return array<string, array<string, int>>
     */
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

    private function qualificationTypeId(string $name): int
    {
        return (int) DB::table('qualification_types')
            ->where('name', $name)
            ->value('id');
    }
}
