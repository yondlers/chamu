<?php

namespace Database\Seeders\Universities\WESTCOL;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequirementSeeder extends Seeder
{
    private const WEBSITE = 'https://westcol.co.za/';
    private const BUSINESS_SOURCE = 'https://westcol.co.za/program-post/business-studies/';
    private const ENGINEERING_SOURCE = 'https://westcol.co.za/program-post/engineering-studies/';
    private const OCCUPATIONAL_SOURCE = 'https://westcol.co.za/program-post/occupational-programmes/';

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedQualificationTypes();
            $this->seedWestcolAdmissionRules();

            $gradeIdsByName = $this->gradeIdsByName();
            $countryId = $this->countryId('South Africa');
            $universityId = $this->universityId($countryId);

            DB::table('university_admission_rules')
                ->where('university_id', $universityId)
                ->delete();

            foreach ($this->programmes() as $programme) {
                $facultyId = $this->facultyId($universityId, (string) $programme['field']);
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

                foreach ($programme['subject_requirements'] ?? [] as $requirement) {
                    $this->insertSubjectRequirement($qualificationId, $requiredGradeId, $requirement);
                }

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

    private function seedWestcolAdmissionRules(): void
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

        DB::table('admission_rules')->updateOrInsert(
            ['code' => 'westcol_published_aps_manual_review'],
            [
                'name' => 'Westcol published APS with route review',
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
                'points_scale' => json_encode($nscLevels, JSON_THROW_ON_ERROR),
                'config' => json_encode([
                    'requires_manual_verification' => true,
                    'excluded_subjects' => ['Life Orientation'],
                    'source_urls' => [
                        self::BUSINESS_SOURCE,
                        self::ENGINEERING_SOURCE,
                    ],
                ], JSON_THROW_ON_ERROR),
                'description' => 'Westcol publishes APS formulas for some NATED programmes, but the formulas use route-specific weighting and subject choices that must be checked with the programme notes before automatic matching.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
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

    private function universityId(int $countryId): int
    {
        $existing = DB::table('universities')
            ->where('abbreviation', 'WESTCOL')
            ->first();

        DB::table('universities')->updateOrInsert(
            ['abbreviation' => 'WESTCOL'],
            [
                'country_id' => $countryId,
                'name' => 'Westcol TVET College',
                'slug' => $existing?->slug ?: $this->uniqueUniversitySlug('westcol-tvet-college'),
                'website' => self::WEBSITE,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('universities')
            ->where('abbreviation', 'WESTCOL')
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
     * @param  array<string, mixed>  $requirement
     */
    private function insertSubjectRequirement(int $qualificationId, ?int $requiredGradeId, array $requirement): void
    {
        DB::table('qualification_subject_requirements')->insert([
            'qualification_id' => $qualificationId,
            'subject_id' => null,
            'grade_id' => $requiredGradeId,
            'subject_name' => $this->normalisedSubjectName((string) $requirement['subject']),
            'minimum_mark' => (int) $requirement['minimum_mark'],
            'aps_level_required' => null,
            'requirement_type' => 'required',
            'requirement_group' => null,
            'notes' => $requirement['note'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function assignAdmissionRule(int $universityId, int $qualificationId, ?int $requiredGradeId, array $programme): void
    {
        $ruleCode = $this->apsRequired($programme) === null
            ? 'subject_levels_only'
            : 'westcol_published_aps_manual_review';

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
    private function admissionRuleOverrides(array $programme): ?string
    {
        $payload = $this->withoutEmptyValues([
            'aps_calculation' => $programme['aps']['calculation'] ?? null,
            'accepted_routes' => $programme['entry'] ?? [],
            'additional_requirements' => $programme['additional_requirements'] ?? [],
            'subject_requirements' => $programme['subject_requirements'] ?? [],
            'source_url' => $programme['source_url'] ?? null,
        ]);

        return $payload === [] ? null : json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function admissionRuleNotes(array $programme): string
    {
        $aps = $this->apsRequired($programme);

        if ($aps !== null) {
            return 'Westcol publishes APS '.$aps.'. Formula: '.($programme['aps']['calculation'] ?? 'see programme notes').'. Manual review remains required for the weighted APS formula, N-level placement, campus availability and selection checks.';
        }

        return 'Westcol does not publish a single APS for this programme; matching should use the listed subject marks where captured, entry route, campus, placement assessment/interview/medical notes and manual review.';
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationTypeName(array $programme): string
    {
        return match ($programme['qualification_type'] ?? null) {
            'NC(V)' => 'National Certificate Vocational',
            'NATED / Report 191' => 'NATED',
            'Occupational Certificate' => 'Occupational Certificate',
            'Short Skills Programme' => 'Short Skills Programme',
            default => (string) ($programme['qualification_type'] ?? 'Other'),
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationAbbreviation(array $programme): ?string
    {
        return match ($programme['qualification_type'] ?? null) {
            'NC(V)' => 'NCV',
            'NATED / Report 191' => 'NATED',
            'Occupational Certificate' => 'OccCert',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function requiredGradeName(array $programme): ?string
    {
        if (($programme['qualification_type'] ?? null) === 'NATED / Report 191') {
            return 'Grade 12';
        }

        $entryText = strtolower(implode(' ', array_filter([
            ...($programme['entry'] ?? []),
            ...($programme['additional_requirements'] ?? []),
        ], fn ($value): bool => is_scalar($value))));

        return match (true) {
            str_contains($entryText, 'grade 9') => 'Grade 9',
            str_contains($entryText, 'grade 10') => 'Grade 10',
            str_contains($entryText, 'grade 11') => 'Grade 11',
            str_contains($entryText, 'grade 12') || str_contains($entryText, 'matric') => 'Grade 12',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationNqfLevelId(array $programme): ?int
    {
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
        $minimum = $programme['aps']['minimum'] ?? null;

        return is_numeric($minimum) ? (int) $minimum : null;
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function durationYears(array $programme): ?float
    {
        $years = $programme['duration_years'] ?? null;

        return is_numeric($years) ? (float) $years : null;
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function notes(array $programme): string
    {
        $notes = [];

        foreach ([
            'Qualification type' => $programme['qualification_type'] ?? null,
            'Levels offered' => $this->listText($programme['levels'] ?? []),
            'Duration' => $programme['duration_text'] ?? null,
        ] as $label => $value) {
            if ($value !== null && $value !== '') {
                $notes[] = $label.': '.$value.'.';
            }
        }

        if (! empty($programme['campuses'])) {
            $notes[] = 'Campuses: '.implode(', ', $programme['campuses']).'.';
        }

        if (! empty($programme['entry'])) {
            $notes[] = 'Entry requirements: '.implode('; ', $programme['entry']).'.';
        }

        $aps = $this->apsRequired($programme);
        if ($aps !== null) {
            $notes[] = 'APS requirement: '.$aps.' ('.$programme['aps']['calculation'].').';
        } else {
            $notes[] = 'APS is not listed as a single score for this programme on the Westcol source page.';
        }

        if (! empty($programme['subject_requirements'])) {
            $requirements = collect($programme['subject_requirements'])
                ->map(fn (array $requirement): string => $this->normalisedSubjectName((string) $requirement['subject']).' '.(int) $requirement['minimum_mark'].'%')
                ->implode('; ');
            $notes[] = 'Published school subject marks captured for matching: '.$requirements.'.';
        }

        if (! empty($programme['additional_requirements'])) {
            $notes[] = 'Additional requirements: '.implode('; ', $programme['additional_requirements']).'.';
        }

        foreach (($programme['modules'] ?? []) as $level => $modules) {
            $notes[] = $level.' modules listed by Westcol: '.implode('; ', $modules).'.';
        }

        if (! empty($programme['modules'])) {
            $notes[] = 'The N-level/module lists are college curriculum subjects after admission and are not seeded as Grade 12 subject requirements.';
        }

        $notes[] = 'Manual review required for equivalent routes, placement level, selection checks, campus availability and any source-page wording that is not a direct school-subject mark.';
        $notes[] = 'Source: official Westcol programme page.';

        return collect($notes)
            ->map(fn (string $note): string => trim($note))
            ->filter()
            ->unique()
            ->implode(' ');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function programmes(): array
    {
        return [
            [
                'id' => 'westcol-nated-management-assistant',
                'field' => 'Business Studies',
                'name' => 'Management Assistant',
                'qualification_type' => 'NATED / Report 191',
                'levels' => ['N4', 'N5', 'N6'],
                'source_url' => self::BUSINESS_SOURCE,
                'entry' => [
                    'Office Administration Level 4 Certificate',
                    'Grade 12 with Computer subjects/CAT',
                    'National Introductory Certificate: N4 Business Studies',
                    'Typing Skills Certificate',
                    'Equivalent NQF Level 4 occupational qualification',
                ],
                'aps' => [
                    'minimum' => 24,
                    'calculation' => 'double the English mark plus the next best four NSC subjects',
                ],
                'modules' => [
                    'N4' => ['Information Processing N4', 'Office Practice N4', 'Communication N4', 'Introductory Computer Practice N4 or Computer Practice N4'],
                    'N5' => ['Office Practice N5', 'Information Processing N5', 'Communication N5', 'Computer Practice N4 or Computer Practice N5'],
                    'N6' => ['Office Practice N6', 'Information Processing N6', 'Communication N6', 'Computer Practice N5 or N6 or Legal Practice N5'],
                ],
            ],
            [
                'id' => 'westcol-nated-business-management',
                'field' => 'Business Studies',
                'name' => 'Business Management',
                'qualification_type' => 'NATED / Report 191',
                'levels' => ['N4', 'N5', 'N6'],
                'source_url' => self::BUSINESS_SOURCE,
                'entry' => [
                    'NC(V) Level 4 in Management, Office Administration, Information Technology, Finance Economics and Accounting, or Transport and Logistics',
                    'National Introductory Certificate: N4 Business Studies',
                    'Equivalent occupational qualification',
                ],
                'aps' => [
                    'minimum' => 24,
                    'calculation' => 'double the English mark plus Accounting, Mathematics, Mathematical Literacy, Economics or Business Studies, plus the next best two NSC subjects',
                ],
                'additional_requirements' => [
                    'Applied Accounting or Grade 12 Accounting is required for the accounting-related module path',
                ],
                'modules' => [
                    'N4' => ['Introductory Accounting or Financial Accounting N4', 'Management Communication N4', 'Entrepreneurship and Business Management N4', 'Computer Practice N4'],
                    'N5' => ['Entrepreneurship and Business Management N5', 'Sales Management', 'two choices from Labour Relations N5, Computer Practice N5, Computerised Financial Systems N4 or Financial Accounting'],
                    'N6' => ['Entrepreneurship and Business Management N6', 'one choice from Labour Relations N5/N6, Sales Management N6 or Marketing Communication N6', 'two choices from Computer Practice N6, Personnel Management N6, Marketing Management N4, Computerised Financial Systems N4/N5 or Financial Accounting N5/N6'],
                ],
            ],
            [
                'id' => 'westcol-nated-financial-management',
                'field' => 'Business Studies',
                'name' => 'Financial Management',
                'qualification_type' => 'NATED / Report 191',
                'levels' => ['N4', 'N5', 'N6'],
                'source_url' => self::BUSINESS_SOURCE,
                'entry' => [
                    'NC(V) Level 4 in Finance, Economics and Accounting',
                    'Office Administration with Applied Accounting Level 4',
                    'Any other NC(V) Level 4 Certificate with Applied Accounting Level 4 as a subject',
                    'National Introductory Certificate: N4 Business Studies with Introductory Accounting',
                    'Equivalent occupational qualification',
                ],
                'aps' => [
                    'minimum' => 24,
                    'calculation' => 'double the English mark plus Accounting, Mathematics, Business Studies or Economics, plus the next best three NSC subjects',
                ],
                'additional_requirements' => [
                    'Applied Accounting, NSC/N3 Accounting or Grade 12 Accounting is required for Financial Accounting modules',
                ],
                'modules' => [
                    'N4' => ['Entrepreneurship and Business Management N4', 'Financial Accounting N4', 'Computerised Financial Systems N4', 'Management Communication N4'],
                    'N5' => ['Financial Accounting N5', 'Cost and Management Accounting N5', 'Computerised Financial Systems N5', 'Entrepreneurship and Business Management N4'],
                    'N6' => ['Financial Accounting N6', 'Cost and Management Accounting N6', 'Computerised Financial Systems N6', 'Entrepreneurship and Business Management N5 or N6'],
                ],
            ],
            [
                'id' => 'westcol-nated-hospitality-and-catering-services',
                'field' => 'Business Studies',
                'name' => 'Hospitality and Catering Services',
                'qualification_type' => 'NATED / Report 191',
                'levels' => ['N4', 'N5', 'N6'],
                'source_url' => self::BUSINESS_SOURCE,
                'modules' => [
                    'N4' => ['Catering Theory and Practical N4', 'Nutritional and Menu Planning N4', 'Sanitation and Safety N4', 'Applied Management N4'],
                    'N5' => ['Catering Theory and Practical N5', 'Food and Beverage Management N5', 'Entrepreneurship and Business Management N5', 'Applied Management N5'],
                    'N6' => ['Catering Theory and Practical N6', 'Communication and Human Relations N6', 'Introductory Computer Practice N4', 'Applied Management N6'],
                ],
            ],
            [
                'id' => 'westcol-nated-human-resource-management',
                'field' => 'Business Studies',
                'name' => 'Human Resource Management',
                'qualification_type' => 'NATED / Report 191',
                'levels' => ['N4', 'N5', 'N6'],
                'source_url' => self::BUSINESS_SOURCE,
                'entry' => [
                    'NC(V) Level 4 in Education and Development, Finance Economics and Accounting, Information Technology, Management, Marketing, Office Administration, or Transport and Logistics',
                    'National Introductory Certificate: N4 Business Studies',
                    'Equivalent NQF Level 4 occupational qualification',
                ],
                'aps' => [
                    'minimum' => 24,
                    'calculation' => 'double the English mark plus the next best four NSC subjects',
                ],
                'modules' => [
                    'N4' => ['Entrepreneurship and Business Management N4', 'Personnel Management N4', 'Management Communication N4', 'Computer Practice N4'],
                    'N5' => ['Personnel Management N5', 'Personal Training N5', 'Labour Relations N5', 'Computer Practice N5'],
                    'N6' => ['Personnel Management N6', 'Personal Training N6', 'Labour Relations N6', 'Computer Practice N6'],
                ],
            ],
            [
                'id' => 'westcol-nated-marketing-management',
                'field' => 'Business Studies',
                'name' => 'Marketing Management',
                'qualification_type' => 'NATED / Report 191',
                'levels' => ['N4', 'N5', 'N6'],
                'source_url' => self::BUSINESS_SOURCE,
                'entry' => [
                    'NC(V) Level 4 in Education and Development, Finance Economics and Accounting, Information Technology, Management, Marketing, or Office Administration',
                    'National Introductory Certificate: N4 Business Studies',
                    'Equivalent NQF Level 4 occupational qualification',
                ],
                'aps' => [
                    'minimum' => 24,
                    'calculation' => 'double the English mark plus the next best four NSC subjects',
                ],
                'modules' => [
                    'N4' => ['Entrepreneurship and Business Management N4', 'Marketing Management N4', 'Management Communication N4', 'Computer Practice N4'],
                    'N5' => ['Sales Management N5', 'Marketing Management N5', 'Labour Relation N5', 'Computer Practice N5'],
                    'N6' => ['Marketing Management N6', 'Sales Management N6', 'Marketing Communication N6', 'Marketing Research N6'],
                ],
            ],
            [
                'id' => 'westcol-nated-public-management',
                'field' => 'Business Studies',
                'name' => 'Public Management',
                'qualification_type' => 'NATED / Report 191',
                'levels' => ['N4', 'N5', 'N6'],
                'source_url' => self::BUSINESS_SOURCE,
                'entry' => [
                    'NC(V) Level 4 in Education and Development, Finance Economics and Accounting, Information Technology, Management, Marketing, Office Administration, Transport and Logistics, or Tourism',
                    'National Introductory Certificate: N4 Business Studies',
                    'Equivalent NQF Level 4 occupational qualification',
                ],
                'aps' => [
                    'minimum' => 24,
                    'calculation' => 'double the English mark plus Computer Application Technology or History, plus the next best four NSC subjects',
                ],
                'modules' => [
                    'N4' => ['Public Management N4', 'Management Communication N4', 'Computer Practice N4', 'Entrepreneurship and Business Management N4'],
                    'N5' => ['Public Administration N5', 'Public Finance N5', 'Municipal Administration N5', 'Entrepreneurship and Business Management N5'],
                    'N6' => ['Public Law N6', 'Public Administration N6', 'Municipal Administration N6', 'Public Finance N6'],
                ],
            ],
            [
                'id' => 'westcol-nated-tourism',
                'field' => 'Business Studies',
                'name' => 'Tourism',
                'qualification_type' => 'NATED / Report 191',
                'levels' => ['N4', 'N5', 'N6'],
                'source_url' => self::BUSINESS_SOURCE,
                'entry' => [
                    'Tourism NC(V) Level 4 Certificate',
                    'Equivalent NQF Level 4 occupational qualification',
                ],
                'aps' => [
                    'minimum' => 24,
                    'calculation' => 'double the English mark plus Tourism or Geography, plus the next best four NSC subjects',
                ],
                'additional_requirements' => [
                    'All N4 applicants must complete a placement test',
                ],
                'modules' => [
                    'N4' => ['Travel Office Procedures N4', 'Tourist Communication N4', 'Travel Services N4', 'Travel Destinations N4', 'Computer Practice N4'],
                    'N5' => ['Travel Office Procedures N5', 'Tourist Communication N5', 'Travel Services N5', 'Travel Destinations N5'],
                    'N6' => ['Travel Office Procedures N6', 'Hotel Reception N6', 'Travel Services N6', 'Travel Destinations N6'],
                ],
            ],
            [
                'id' => 'westcol-nated-engineering-studies-n4-to-n6',
                'field' => 'Engineering Studies',
                'name' => 'Engineering Studies N4 to N6',
                'qualification_type' => 'NATED / Report 191',
                'levels' => ['N4', 'N5', 'N6'],
                'source_url' => self::ENGINEERING_SOURCE,
                'entry' => [
                    'NC(V) Level 4 in Civil Engineering and Building Construction, Drawing Office Practice, Electrical Infrastructure Construction, Engineering and Related Design, Information Technology and Computer Science, Mechatronics, Process Instrumentation or Process Plant Operations',
                    'National Certificate: N3 Engineering Studies',
                    'Equivalent occupational qualification',
                ],
                'aps' => [
                    'minimum' => 24,
                    'calculation' => 'double Mathematics or Technical Mathematics plus one relevant technology subject or Physical Science, plus English and the next best two NSC subjects',
                ],
                'modules' => [
                    'N4' => ['Supervisory Management', 'Electrotechnics', 'Industrial Electronics', 'Industrial Instruments', 'Mechanical Draughting', 'Mechanotechnics', 'Engineering Science', 'Mathematics'],
                    'N5' => ['Supervisory Management', 'Strength of Materials and Structures', 'Mathematics', 'Power Machines', 'Mechanotechnics', 'Electrotechnics', 'Mechanical Drawing and Design', 'Fluids Mechanics', 'Industrial Electronics', 'Industrial Instruments'],
                    'N6' => ['Supervisory Management', 'Strength of Materials and Structures', 'Control Systems', 'Electrotechnics', 'Industrial Electronics', 'Industrial Instruments', 'Mechanical Drawing and Design', 'Power Machines', 'Fluids Mechanics', 'Mechanotechnics', 'Mathematics'],
                ],
            ],
            [
                'id' => 'westcol-installation-rules-wiremans-licence',
                'field' => 'Engineering Studies',
                'name' => "Installation Rules and Specialised Installation Rules (Wireman's Licence)",
                'qualification_type' => 'Short Skills Programme',
                'levels' => ['Installation Rules', 'Specialised Electrical Installation Codes'],
                'source_url' => self::ENGINEERING_SOURCE,
                'campuses' => ['Carletonville Campus'],
                'duration_text' => '1 trimester',
                'entry' => [
                    'Qualified artisan',
                    'N3 certificate with Electrotechnology as one of the subjects',
                ],
                'additional_requirements' => [
                    'External examinations are written in March, July and November',
                    'Delivery mode is examination only at Carletonville Campus',
                    'Pass mark is 50%',
                ],
                'modules' => [
                    'Subjects' => ['Installation Rules', 'Electrical Wiring Skills', 'Specialised Electrical Installation Codes'],
                ],
            ],
            [
                'id' => 'westcol-engineering-certificate-of-competency',
                'field' => 'Engineering Studies',
                'name' => 'Engineering Certificate of Competency',
                'qualification_type' => 'Short Skills Programme',
                'levels' => ['Engineering Certificate of Competency', 'N6 conversion course'],
                'source_url' => self::ENGINEERING_SOURCE,
                'campuses' => ['Carletonville Campus'],
                'entry' => [
                    'Certified copies of qualifications',
                    'Proof of appropriate practical experience on company letterhead',
                    'Testimonial of sobriety and conduct signed by an employer',
                    'Proof of age, name and identification number',
                    'Resident Engineer suitability letter where possible',
                ],
                'additional_requirements' => [
                    'Acceptance must be submitted when entering qualifying subjects',
                    'Examinations are held in June and November',
                    'Highest listed subject levels include the lower grades leading to that level',
                ],
                'modules' => [
                    'Compulsory subjects' => ['N3 Engineering Drawing', 'N3/N4 Engineering Science', 'N4/N5/N6 route subjects for Mechanical or Electrical Engineering', 'N6 Supervisory Management'],
                    'N6 conversion course' => ['Electrotechnics', 'N6 Industrial Electronics', 'N6 Strength of Materials', 'N6 Fluid Mechanics'],
                ],
            ],
            [
                'id' => 'westcol-occupational-hairdressing',
                'field' => 'Occupational Programmes',
                'name' => 'Hairdressing',
                'qualification_type' => 'Occupational Certificate',
                'levels' => ['Levels 2-4'],
                'source_url' => self::OCCUPATIONAL_SOURCE,
                'duration_years' => 3,
                'duration_text' => '3 years',
                'campuses' => ['Randfontein Campus'],
                'entry' => [
                    'Grade 9-12 pass',
                ],
                'additional_requirements' => [
                    'Placement Assessment to determine competency in literacy and numeracy',
                    'Individual interview to assess suitability prior to admission',
                    'Medical assessment may be required',
                ],
            ],
            [
                'id' => 'westcol-occupational-electrician-nqf-level-4',
                'field' => 'Occupational Programmes',
                'name' => 'Electrician',
                'qualification_type' => 'Occupational Certificate',
                'levels' => ['NQF Level 4'],
                'source_url' => self::OCCUPATIONAL_SOURCE,
                'duration_years' => 3,
                'duration_text' => '3 years',
                'campuses' => ['Krugersdorp-West Campus', 'Carletonville Campus'],
                'entry' => [
                    'Grade 9 pass or equivalent',
                    'Preference given to Grade 12 or AET Level 4 (GETC) routes where listed',
                ],
                'subject_requirements' => $this->artisanSubjectRequirements(),
                'additional_requirements' => [
                    'Placement Assessment to determine competency in literacy and numeracy',
                    'Individual interview to assess suitability prior to admission',
                    'Medical assessment may be required',
                    'AET Level 4 route lists 50% in Mathematics or Mathematical Literacy and 50% in English FAL',
                ],
            ],
            [
                'id' => 'westcol-occupational-welder-nqf-level-4',
                'field' => 'Occupational Programmes',
                'name' => 'Welder',
                'qualification_type' => 'Occupational Certificate',
                'levels' => ['NQF Level 4'],
                'source_url' => self::OCCUPATIONAL_SOURCE,
                'duration_years' => 3,
                'duration_text' => '3 years',
                'campuses' => ['Randfontein Campus'],
                'entry' => [
                    'Grade 9 pass or equivalent',
                    'Preference given to Grade 12 or AET Level 4 (GETC) routes where listed',
                ],
                'subject_requirements' => $this->artisanSubjectRequirements(),
                'additional_requirements' => [
                    'Medical assessment may be required',
                    'AET Level 4 route lists 50% in Mathematics or Mathematical Literacy and 50% in English FAL',
                ],
            ],
            [
                'id' => 'westcol-occupational-fitter-and-turner-nqf-level-4',
                'field' => 'Occupational Programmes',
                'name' => 'Fitter and Turner',
                'qualification_type' => 'Occupational Certificate',
                'levels' => ['NQF Level 4'],
                'source_url' => self::OCCUPATIONAL_SOURCE,
                'duration_years' => 3,
                'duration_text' => '3 years',
                'campuses' => ['Carletonville Campus'],
                'entry' => [
                    'Grade 9 pass or equivalent',
                    'Preference given to Grade 12 or AET Level 4 (GETC) routes where listed',
                ],
                'subject_requirements' => $this->artisanSubjectRequirements(),
                'additional_requirements' => [
                    'Placement Assessment to determine competency in literacy and numeracy',
                    'Individual interview to assess suitability prior to admission',
                    'Medical assessment may be required',
                    'AET Level 4 route lists 50% in Mathematics or Mathematical Literacy and 50% in English FAL',
                ],
            ],
            [
                'id' => 'westcol-occupational-chef-nqf-level-5',
                'field' => 'Occupational Programmes',
                'name' => 'Chef',
                'qualification_type' => 'Occupational Certificate',
                'levels' => ['NQF Level 5'],
                'source_url' => self::OCCUPATIONAL_SOURCE,
                'duration_years' => 3,
                'duration_text' => '3 years',
                'campuses' => ['Randfontein Campus'],
                'entry' => [
                    'Hospitality NC(V) Level 4 Certificate or Grade 12 pass',
                    'National Introductory Certificate: N4 Food Services',
                ],
                'additional_requirements' => [
                    'Placement Assessment to determine competency in literacy and numeracy',
                    'Individual interview to assess suitability prior to admission',
                    'Medical assessment may be required',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{subject: string, minimum_mark: int, note: string}>
     */
    private function artisanSubjectRequirements(): array
    {
        return [
            ['subject' => 'English', 'minimum_mark' => 50, 'note' => 'Westcol occupational artisan source lists 50% in English for Grade 9 pass or equivalent route.'],
            ['subject' => 'Mathematics', 'minimum_mark' => 50, 'note' => 'Westcol occupational artisan source lists 50% in Mathematics for Grade 9 pass or equivalent route.'],
            ['subject' => 'Physical Sciences', 'minimum_mark' => 50, 'note' => 'Westcol occupational artisan source lists 50% in Physical Science for Grade 9 pass or equivalent route.'],
        ];
    }

    /**
     * @param  array<int, string>  $values
     */
    private function listText(array $values): string
    {
        return implode(', ', $values);
    }

    private function normalisedSubjectName(string $subjectName): string
    {
        return match (strtolower(trim($subjectName))) {
            'physical science' => 'Physical Sciences',
            default => trim($subjectName),
        };
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
        $base = $base ?: 'westcol-tvet-college';
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
