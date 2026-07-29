<?php

namespace Database\Seeders\Universities\SCC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequirementSeeder extends Seeder
{
    private const WEBSITE = 'https://sccollege.co.za/';
    private const NCV_OVERVIEW_SOURCE = 'https://sccollege.co.za/ncv-programmes/';

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedQualificationTypes();

            $gradeIdsByName = $this->gradeIdsByName();
            $countryId = $this->countryId('South Africa');
            $universityId = $this->universityId($countryId);

            DB::table('university_admission_rules')
                ->where('university_id', $universityId)
                ->delete();

            foreach ($this->programmes() as $programme) {
                $facultyId = $this->facultyId($universityId, (string) $programme['field']);
                $qualificationTypeId = $this->qualificationTypeId($this->qualificationTypeName($programme));
                $requiredGradeName = $programme['required_grade'] ?? null;
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

                foreach ($programme['subject_requirements'] ?? [] as $index => $requirement) {
                    $this->insertRequirement($qualificationId, $requiredGradeId, $requirement, (int) $index);
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
            ['National Introductory Certificate', 'NIC', 4, 51],
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

        return (int) DB::table('countries')
            ->where('name', $countryName)
            ->value('id');
    }

    private function universityId(int $countryId): int
    {
        $existing = DB::table('universities')
            ->where('abbreviation', 'SCC')
            ->first();

        DB::table('universities')->updateOrInsert(
            ['abbreviation' => 'SCC'],
            [
                'country_id' => $countryId,
                'name' => 'South Cape TVET College',
                'slug' => $existing?->slug ?: $this->uniqueUniversitySlug('south-cape-tvet-college'),
                'website' => self::WEBSITE,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('universities')
            ->where('abbreviation', 'SCC')
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
                'aps_required' => null,
                'aggregate_average_required' => $this->aggregateAverageRequired($programme),
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
    private function insertRequirement(int $qualificationId, ?int $requiredGradeId, array $requirement, int $index): void
    {
        if (($requirement['type'] ?? null) === 'one_of') {
            $group = 'requirement_'.$qualificationId.'_'.($index + 1);

            foreach (($requirement['subjects'] ?? []) as $subjectIndex => $subject) {
                $this->insertSubjectRequirement(
                    $qualificationId,
                    $requiredGradeId,
                    (string) $subject['subject'],
                    $subject['minimum_mark'] ?? null,
                    $subjectIndex === 0 ? 'required' : 'alternative',
                    $group,
                    $subject['note'] ?? ($requirement['label'] ?? null),
                );
            }

            return;
        }

        $this->insertSubjectRequirement(
            $qualificationId,
            $requiredGradeId,
            (string) $requirement['subject'],
            $requirement['minimum_mark'] ?? null,
            'required',
            null,
            $requirement['note'] ?? null,
        );
    }

    private function insertSubjectRequirement(
        int $qualificationId,
        ?int $requiredGradeId,
        string $subjectName,
        null|int|float $minimumMark,
        string $requirementType,
        ?string $requirementGroup,
        ?string $note = null
    ): void {
        DB::table('qualification_subject_requirements')->insert([
            'qualification_id' => $qualificationId,
            'subject_id' => null,
            'grade_id' => $requiredGradeId,
            'subject_name' => $this->normalisedSubjectName($subjectName),
            'minimum_mark' => $minimumMark === null ? null : (int) ceil((float) $minimumMark),
            'aps_level_required' => null,
            'requirement_type' => $requirementType,
            'requirement_group' => $requirementGroup,
            'notes' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function assignAdmissionRule(int $universityId, int $qualificationId, ?int $requiredGradeId, array $programme): void
    {
        $ruleCode = $this->aggregateAverageRequired($programme) === null
            ? 'subject_levels_only'
            : 'nsc_aggregate_excluding_lo';

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
                'priority' => $this->aggregateAverageRequired($programme) === null ? 20 : 10,
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
            'published_requirements' => $programme['entry'] ?? [],
            'placement_tests' => $programme['placement_tests'] ?? [],
            'subject_requirements' => $programme['subject_requirements'] ?? [],
            'aggregate_average_required' => $this->aggregateAverageRequired($programme),
            'source_url' => $programme['source_url'] ?? null,
            'ncv_overview_source' => ($programme['qualification_type'] ?? null) === 'NC(V)' ? self::NCV_OVERVIEW_SOURCE : null,
        ]);

        return $payload === [] ? null : json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function admissionRuleNotes(array $programme): string
    {
        if ($this->aggregateAverageRequired($programme) !== null) {
            return 'South Cape TVET College publishes an aggregate average for this programme together with specific subject thresholds. Equivalent NC(V) or N-level routes still require manual review.';
        }

        return 'South Cape TVET College matching should use the listed entry route, captured school subjects, placement-test notes, N-level progression, campus availability and manual review rather than APS.';
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationTypeName(array $programme): string
    {
        return match ($programme['qualification_type'] ?? null) {
            'NC(V)' => 'National Certificate Vocational',
            'NATED / Report 191' => 'NATED',
            'National Introductory Certificate' => 'National Introductory Certificate',
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
            'National Introductory Certificate' => 'NIC',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationNqfLevelId(array $programme): ?int
    {
        $level = $programme['nqf_level'] ?? null;

        return is_numeric($level) ? $this->nqfLevelId((int) $level) : null;
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
    private function durationYears(array $programme): ?float
    {
        $years = $programme['duration_years'] ?? null;

        return is_numeric($years) ? (float) $years : null;
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function aggregateAverageRequired(array $programme): ?float
    {
        $average = $programme['aggregate_average_required'] ?? null;

        return is_numeric($average) ? (float) $average : null;
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
            'Campuses' => $this->listText($programme['campuses'] ?? []),
            'Course fees on source page' => $programme['course_fee'] ?? null,
        ] as $label => $value) {
            if ($value !== null && $value !== '') {
                $notes[] = $label.': '.$value.'.';
            }
        }

        if (! empty($programme['entry'])) {
            $notes[] = 'Entry requirements: '.implode('; ', $programme['entry']).'.';
        }

        if ($this->aggregateAverageRequired($programme) !== null) {
            $notes[] = 'Aggregate average requirement: '.$this->aggregateAverageRequired($programme).'%.';
        } else {
            $notes[] = 'APS is not listed as a requirement for this South Cape TVET College programme.';
        }

        if (! empty($programme['subject_requirements'])) {
            $notes[] = 'Published school subject marks captured for matching: '.$this->requirementsText($programme['subject_requirements']).'.';
        }

        if (! empty($programme['placement_tests'])) {
            $notes[] = 'Placement test requirements: '.implode('; ', $programme['placement_tests']).'.';
        }

        foreach (($programme['modules'] ?? []) as $level => $modules) {
            $notes[] = $level.' subjects listed by South Cape TVET College: '.implode('; ', $modules).'.';
        }

        if (! empty($programme['modules'])) {
            $notes[] = 'The listed NC(V), N-level or introductory subjects are programme curriculum after admission and are not seeded as Grade 12 subject requirements.';
        }

        if (! empty($programme['careers'])) {
            $notes[] = 'Career paths: '.implode('; ', $programme['careers']).'.';
        }

        $notes[] = 'Manual review required for placement tests, equivalent routes, N-level progression, NC(V) alternatives, campus availability and final college selection.';
        $notes[] = 'Source: official South Cape TVET College programme page.';

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
            $this->ncv(
                'tourism',
                'NC(V) Programmes',
                'Tourism',
                'https://sccollege.co.za/classes/national-certificate-vocational-tourism-level-2-4/',
                ['Mathematics', 'English', 'Life Orientation'],
                ['Minimum 40% for Numeracy in the College placement test for Grade 9 learners', 'Minimum 40% for Literacy in the College placement test for Grade 9 learners'],
                ['Mossel Bay'],
                'R 14 929 per semester',
                ['Accommodation Management', 'Conference and Events Planning', 'Tourism Development', 'Transportation Manager', 'Travel Counselling', 'Game Ranging and Safari Work'],
                [
                    'Fundamental Subjects' => ['Life Skills and Computer Literacy', 'Mathematical Literacy', 'English'],
                    'Vocational Subjects' => ['Client Services and Human Relations', 'Science of Tourism', 'Tourism Operations', 'Sustainable Tourism'],
                ],
            ),
            $this->ncv(
                'safety-in-society',
                'NC(V) Programmes',
                'Safety in Society',
                'https://sccollege.co.za/classes/national-certificate-vocational-safety-in-society-level-2-4/',
                ['Mathematics', 'English', 'Life Orientation'],
                ['Minimum 40% for Numeracy in the College placement test for Grade 9 learners', 'Minimum 40% for Literacy in the College placement test for Grade 9 learners'],
                ['Oudtshoorn'],
                'R 10 527 per semester',
                ['SAPS internships or permanent posts', 'Security and Surveillance', 'Civil and human rights related work', 'Community Policing', 'Metro Policing', 'National Intelligence', 'Legal Assistance', 'Community Development'],
                [
                    'Level 2 vocational subjects' => ['Introduction to Governance', 'Introduction to Law', 'Principles of Criminal Justice', 'Introduction to Policing Practices'],
                    'Level 3 vocational subjects' => ['Governance', 'Criminal Law', 'Criminal Justice Structures and Mandates', 'Theory of Policing Practices'],
                    'Level 4 vocational subjects' => ['Governance', 'Criminal Justice Procedures', 'Law of Procedures and Evidence', 'Applied Policing'],
                ],
            ),
            $this->ncv(
                'electrical-infrastructure-construction',
                'NC(V) Programmes',
                'Electrical Infrastructure Construction',
                'https://sccollege.co.za/classes/national-certificate-vocational-electrical-infrastructure-construction-level-2-4/',
                ['Mathematics', 'English', 'Life Orientation', 'Technology', 'Natural Sciences', 'Life Sciences'],
                ['Minimum 50% for Numeracy in the College placement test for Grade 9 learners', 'Minimum 40% for Literacy in the College placement test for Grade 9 learners'],
                ['Mossel Bay'],
                'R 15 824 per semester',
                ['Electrical Engineering', 'Industrial Engineering', 'Sound Technology', 'Theatre Technology', 'Process Level Control', 'Digital Electronics', 'Instrumentation'],
                [
                    'Fundamental Subjects' => ['Life Skills and Computer Literacy', 'Mathematical Literacy', 'English'],
                    'Vocational Subjects' => ['Electrical Principles and Practices', 'Workshop Practice', 'Electronic Control and Digital Electronics', 'Electrical Systems and Construction'],
                ],
            ),
            $this->ncv(
                'hospitality',
                'NC(V) Programmes',
                'Hospitality',
                'https://sccollege.co.za/classes/national-certificate-vocational-hospitality-level-2-4/',
                ['Mathematics', 'English', 'Life Orientation'],
                ['Minimum 40% for Numeracy in the College placement test for Grade 9 learners', 'Minimum 40% for Literacy in the College placement test for Grade 9 learners'],
                ['Oudtshoorn'],
                'R 19 458 per semester',
                ['Service Management', 'Frontline Reception', 'Bartender', 'Housekeeping', 'Events Management', 'Entry Level Chef'],
                [
                    'Fundamental Subjects' => ['Life Skills and Computer Literacy', 'Mathematical Literacy', 'English'],
                    'Vocational Subjects' => ['Customer Service and Human Relations', 'Food Preparation', 'Hospitality Generics', 'Hospitality Services'],
                ],
            ),
            $this->ncv(
                'office-administration',
                'NC(V) Programmes',
                'Office Administration',
                'https://sccollege.co.za/classes/national-certificate-vocational-office-administration-level-2-4/',
                ['Mathematics', 'English', 'Life Orientation'],
                ['Minimum 40% for Numeracy in the College placement test for Grade 9 learners', 'Minimum 40% for Literacy in the College placement test for Grade 9 learners'],
                ['Beaufort West', 'Bitou', 'George'],
                'R 10 275 per semester',
                ['Administrative Officer', 'Company Secretary', 'Freight Forwarder', 'Human Resource Manager', 'Legal Secretary', 'Personal Assistant'],
                [
                    'Fundamental Subjects' => ['Life Skills and Computer Literacy', 'Mathematical Literacy', 'English'],
                    'Level 2 vocational subjects' => ['Business Practice', 'Office Practice', 'Office Data Processing'],
                    'Level 3 vocational subjects' => ['Business Practice', 'Office Practice', 'Office Data Processing'],
                    'Level 4 vocational subjects' => ['Business Practice', 'Office Practice', 'Office Data Processing'],
                    'Optional Subjects' => ['New Venture Creation', 'Second Language'],
                ],
            ),
            $this->ncv(
                'engineering-related-design',
                'NC(V) Programmes',
                'Engineering and Related Design',
                'https://sccollege.co.za/classes/national-certificate-vocational-engineering-related-design-level-2-4/',
                ['Mathematics', 'English', 'Life Orientation', 'Technology', 'Natural Sciences', 'Life Sciences'],
                ['Minimum 40% for Literacy in the College placement test for Grade 9 learners'],
                ['Mossel Bay'],
                'R 20 568 per semester',
                ['Chemical Engineering', 'Civil Engineering', 'Panel Beating', 'Coal Technology', 'Geology', 'Mechanical Engineering', 'Automotive Repair', 'Metallurgical Engineering', 'Petroleum Engineering', 'Car Manufacturing', 'Welding', 'Architectural Technology', 'Tool Making', 'Building Management', 'Motor Mechanics'],
                [
                    'Fundamental Subjects' => ['Life Skills and Computer Literacy', 'Mathematical Literacy', 'English'],
                    'Vocational Subjects' => ['Engineering Principles and Practices', 'Workshop Practice', 'Technical Drawings', 'Engineering Systems'],
                ],
            ),
            $this->n1ToN3(
                'electrical',
                'Electrical',
                'https://sccollege.co.za/classes/electrical-n1-n3/',
                ['Mossel Bay'],
                'R 2107 per semester',
                ['Electrical Engineering and Construction', 'Industrial Engineering', 'Process Control', 'Digital Electronic Engineering', 'Industrial Electronic Engineering'],
                [
                    'N1' => ['Mathematics', 'Industrial Electronics', 'Engineering Science', 'Electrical Trade Theory'],
                    'N2' => ['Mathematics', 'Industrial Electronics', 'Engineering Science', 'Electrical Trade Theory'],
                    'N3' => ['Mathematics', 'Industrial Electronics', 'Engineering Science', 'Electrical Trade Theory'],
                ],
            ),
            $this->n1ToN3(
                'boilermaking',
                'Boilermaking',
                'https://sccollege.co.za/classes/boilermaking-n1-n3/',
                ['Mossel Bay'],
                'R 2107 per semester',
                ['Factories', 'Power Stations', 'Petrol Refineries', 'Chemical Companies', 'Mines'],
                [
                    'N1' => ['Mathematics', 'Engineering Science', 'Plating and Structural Steel Drawing', 'Metalworkers Theory'],
                    'N2' => ['Mathematics', 'Engineering Science', 'Plating and Structural Steel Drawing', 'Platers Theory'],
                    'N3' => ['Mathematics', 'Engineering Science', 'Plating and Structural Steel Drawing', 'Mechanotechnology'],
                ],
            ),
            [
                'id' => 'scc-national-introductory-certificate',
                'field' => 'Business Studies',
                'name' => 'National Introductory Certificate',
                'qualification_type' => 'National Introductory Certificate',
                'levels' => ['Introductory N4 bridge'],
                'nqf_level' => 4,
                'required_grade' => null,
                'source_url' => 'https://sccollege.co.za/classes/national-introductory-certificate/',
                'entry' => [
                    'Bridging course for students who do not meet the academic requirement for direct entry into National N4-N6 programmes',
                    'The SCC source page does not publish a single school-grade threshold for this course',
                ],
                'duration_years' => 1,
                'duration_text' => 'One year',
                'campuses' => ['Bitou', 'Oudtshoorn', 'Beaufort West', 'George', 'Hessequa Campus'],
                'course_fee' => 'R 3121',
                'modules' => [
                    'Subjects' => ['Introductory Entrepreneurship N4', 'Introductory Communication N4', 'Introductory Computer Practice N4', 'Introductory Accounting N4'],
                ],
            ],
            $this->nated(
                'business-management',
                'Business Studies',
                'Business Management',
                'https://sccollege.co.za/classes/business-management-n4-n6/',
                ['Grade 12 Certificate'],
                [],
                null,
                ['George'],
                'R 3121 per semester',
                ['Entrepreneur', 'Sales', 'Bookkeeping', 'Administration'],
                [
                    'N4' => ['Introductory Accounting', 'Management Communication', 'Computer Practice', 'Entrepreneurship and Business Management'],
                    'N5' => ['Public Relations', 'Sales Management', 'Computer Practice', 'Entrepreneurship and Business Management'],
                    'N6' => ['Entrepreneurship and Business Management', 'Public Relations', 'Sales Management', 'Computer Practice'],
                ],
            ),
            $this->nated(
                'educare',
                'Business Studies',
                'Educare',
                'https://sccollege.co.za/classes/national-n-diploma-educare-n4-n6/',
                ['Current Grade 12 learners: English 40% and minimum aggregate excluding Life Orientation 45%', 'NC(V) Level 4 students: English 40% and minimum aggregate 45%'],
                [$this->required('English', 40)],
                45,
                ['Beaufort West', 'George', 'Mossel Bay', 'Oudtshoorn'],
                'R 3792 per semester',
                ['Educare practitioner', 'Play group practitioner', 'Manage an ECD site', 'Start and manage an ECD site', 'Au pair'],
                [
                    'N4' => ['Day Care Personnel Development', 'Educare Didactics: Theory and Practical', 'Education', 'Child Health', 'Computer Practice (additional subject)'],
                    'N5' => ['Entrepreneurship and Business Management', 'Day Care Communication', 'Educare Didactics: Theory and Practical', 'Educational Psychology'],
                    'N6' => ['Day Care Management', 'Day Care Communication', 'Educare Didactics: Theory and Practical', 'Educational Psychology'],
                ],
            ),
            $this->nated(
                'hospitality-and-catering-services',
                'Business Studies',
                'Hospitality and Catering Services',
                'https://sccollege.co.za/classes/national-n-diploma-hospitality-and-catering-services-n4-n6/',
                ['Current Grade 12 learners: English 40%, Mathematics 40% or Mathematical Literacy 40%, and minimum aggregate excluding Life Orientation 45%', 'NC(V) Level 4 students: English 40%, Mathematical Literacy 50%, and minimum aggregate 45%'],
                [$this->required('English', 40), $this->oneOf([['subject' => 'Mathematics', 'minimum_mark' => 40], ['subject' => 'Mathematical Literacy', 'minimum_mark' => 40]], 'Mathematics 40% or Mathematical Literacy 40%')],
                45,
                ['Oudtshoorn'],
                'R 7042',
                ['Hotel and guesthouse Management', 'Restaurant Management', 'Food Services Management', 'Food and Beverage Management', 'Function Catering', 'Events Management', 'Entrepreneur'],
                [
                    'N4' => ['Applied Management', 'Nutrition and Menu Planning', 'Catering (Theory and Practical)', 'Sanitation and Safety'],
                    'N5' => ['Applied Management', 'Food and Beverage Services', 'Catering (Theory and Practical)', 'Entrepreneurship and Business Management'],
                    'N6' => ['Applied Management', 'Catering (Theory and Practical)', 'Caterer / Client Relations', 'Computer Practice'],
                ],
            ),
            $this->nated(
                'human-resource-management',
                'Business Studies',
                'Human Resource Management',
                'https://sccollege.co.za/classes/national-n-diploma-human-resource-management-n4-n6/',
                ['Current Grade 12 learners: minimum NSC pass requirements, English 40%, Mathematics 50% or Mathematical Literacy 60%, Accounting 50%, and minimum aggregate excluding Life Orientation 45%'],
                [$this->required('English', 40), $this->oneOf([['subject' => 'Mathematics', 'minimum_mark' => 50], ['subject' => 'Mathematical Literacy', 'minimum_mark' => 60]], 'Mathematics 50% or Mathematical Literacy 60%'), $this->required('Accounting', 50)],
                45,
                ['Beaufort West', 'Bitou', 'George', 'Hessequa', 'Mossel Bay', 'Oudtshoorn'],
                'R 3121 per semester',
                ['Financial Assistant', 'Cost Account Assistant', 'Entrepreneur', 'Clerk', 'Financial Services', 'Banking'],
                [
                    'N4' => ['Financial Accounting', 'Entrepreneurship and Business Management', 'Management Communication', 'Computerised Financial Systems'],
                    'N5' => ['Financial Accounting', 'Entrepreneurship and Business Management', 'Computerised Financial Systems', 'Cost and Management Accounting'],
                    'N6' => ['Financial Accounting', 'Entrepreneurship and Business Management', 'Computerised Financial Systems', 'Cost and Management Accounting'],
                ],
            ),
            $this->nated(
                'tourism-management',
                'Business Studies',
                'Tourism Management',
                'https://sccollege.co.za/classes/national-n-diploma-tourism-n4-n6/',
                ['Current Grade 12 learners: Matric Certificate, English 40%, Mathematics or Mathematical Literacy 50%, minimum aggregate excluding Life Orientation 40%, and 40% minimum for all subjects', 'NC(V) Level 4 students: minimum NC(V) pass requirements, English 40%, and minimum aggregate 45%'],
                [$this->required('English', 40), $this->oneOf([['subject' => 'Mathematics', 'minimum_mark' => 50], ['subject' => 'Mathematical Literacy', 'minimum_mark' => 50]], 'Mathematics or Mathematical Literacy 50%')],
                40,
                ['Bitou', 'George', 'Oudtshoorn'],
                'R 14 929 per semester',
                ['Travel Agent', 'Guesthouse Manager', 'Hotel Reception', 'Consultant at Tour Operator', 'Airport Staff', 'Entrepreneur'],
                [
                    'N4' => ['Travel Services', 'Tourism Communication', 'Tourist Destinations', 'Travel Office Procedures', 'Computer Practice'],
                    'N5' => ['Travel Services', 'Tourism Communication', 'Tourist Destinations', 'Travel Office Procedures'],
                    'N6' => ['Travel Services', 'Tourism Communication', 'Tourist Destinations', 'Travel Office Procedures'],
                ],
            ),
            $this->nated(
                'public-management',
                'Business Studies',
                'Public Management',
                'https://sccollege.co.za/classes/national-n-diploma-public-management-n4-n6/',
                ['Grade 12 National Senior Certificate or equivalent qualification'],
                [],
                null,
                ['Beaufort West', 'George', 'Mossel Bay', 'Oudtshoorn'],
                'R 3121 per semester',
                ['Entrepreneur', 'Sales', 'Bookkeeping', 'Administration'],
                [
                    'N4' => ['Computer Practice', 'Introductory Accounting', 'Management Communication', 'Public Administration'],
                    'N5' => ['Computer Practice', 'Public Finance', 'Municipal Administration', 'Public Administration'],
                    'N6' => ['Computer Practice', 'Municipal Administration', 'Public Administration', 'Public Law'],
                ],
            ),
        ];
    }

    /**
     * @param  array<int, string>  $entrySubjects
     * @param  array<int, string>  $placementTests
     * @param  array<int, string>  $campuses
     * @param  array<int, string>  $careers
     * @param  array<string, array<int, string>>  $modules
     * @return array<string, mixed>
     */
    private function ncv(
        string $id,
        string $field,
        string $name,
        string $sourceUrl,
        array $entrySubjects,
        array $placementTests,
        array $campuses,
        string $courseFee,
        array $careers,
        array $modules
    ): array {
        return [
            'id' => 'scc-ncv-'.$id.'-level-2-4',
            'field' => $field,
            'name' => $name,
            'qualification_type' => 'NC(V)',
            'levels' => ['Level 2', 'Level 3', 'Level 4'],
            'nqf_level' => 4,
            'required_grade' => 'Grade 9',
            'source_url' => $sourceUrl,
            'entry' => ['Grade 9 with a minimum pass with '.implode(', ', $entrySubjects)],
            'duration_years' => 3,
            'duration_text' => '3 years',
            'campuses' => $campuses,
            'course_fee' => $courseFee,
            'subject_requirements' => collect($entrySubjects)
                ->map(fn (string $subject): array => $this->required($subject, null, 'SCC lists this Grade 9 subject for NC(V) Level 2 entry; no school-subject percentage is published.'))
                ->all(),
            'placement_tests' => $placementTests,
            'careers' => $careers,
            'modules' => $modules,
        ];
    }

    /**
     * @param  array<int, string>  $campuses
     * @param  array<int, string>  $careers
     * @param  array<string, array<int, string>>  $modules
     * @return array<string, mixed>
     */
    private function n1ToN3(
        string $id,
        string $name,
        string $sourceUrl,
        array $campuses,
        string $courseFee,
        array $careers,
        array $modules
    ): array {
        return [
            'id' => 'scc-nated-'.$id.'-n1-n3',
            'field' => 'Engineering Studies',
            'name' => $name,
            'qualification_type' => 'NATED / Report 191',
            'levels' => ['N1', 'N2', 'N3'],
            'nqf_level' => 4,
            'required_grade' => 'Grade 9',
            'source_url' => $sourceUrl,
            'entry' => [
                'N1: Grade 9 with a minimum pass of 50% in Mathematics',
                'N2: N1 with 3 subjects passed, or Grade 12 with Mathematics and Physical Science',
                'N3: N2 with 3 subjects passed',
            ],
            'duration_years' => 1,
            'duration_text' => 'One year; each level is 12 weeks',
            'campuses' => $campuses,
            'course_fee' => $courseFee,
            'subject_requirements' => [$this->required('Mathematics', 50, 'SCC N1 entry lists Grade 9 with a minimum pass of 50% in Mathematics.')],
            'placement_tests' => [
                'N1 applicants must obtain 60% for Numeracy and 50% for Literacy in the College placement test',
                'Grade 12 students using the N2 route must obtain 60% for Numeracy and 50% for Literacy in the College placement test',
            ],
            'careers' => $careers,
            'modules' => $modules,
        ];
    }

    /**
     * @param  array<int, string>  $entry
     * @param  array<int, array<string, mixed>>  $subjectRequirements
     * @param  array<int, string>  $campuses
     * @param  array<int, string>  $careers
     * @param  array<string, array<int, string>>  $modules
     * @return array<string, mixed>
     */
    private function nated(
        string $id,
        string $field,
        string $name,
        string $sourceUrl,
        array $entry,
        array $subjectRequirements,
        ?float $aggregateAverage,
        array $campuses,
        string $courseFee,
        array $careers,
        array $modules
    ): array {
        return [
            'id' => 'scc-nated-'.$id.'-n4-n6',
            'field' => $field,
            'name' => $name,
            'qualification_type' => 'NATED / Report 191',
            'levels' => ['N4', 'N5', 'N6'],
            'nqf_level' => 6,
            'required_grade' => 'Grade 12',
            'source_url' => $sourceUrl,
            'entry' => $entry,
            'duration_years' => 3,
            'duration_text' => '3 years',
            'campuses' => $campuses,
            'course_fee' => $courseFee,
            'aggregate_average_required' => $aggregateAverage,
            'subject_requirements' => $subjectRequirements,
            'careers' => $careers,
            'modules' => $modules,
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
     * @param  array<int, array{subject: string, minimum_mark?: int|float|null, note?: string|null}>  $subjects
     * @return array<string, mixed>
     */
    private function oneOf(array $subjects, string $label): array
    {
        return [
            'type' => 'one_of',
            'label' => $label,
            'subjects' => $subjects,
        ];
    }

    /**
     * @param  array<int, string>  $values
     */
    private function listText(array $values): string
    {
        return implode(', ', $values);
    }

    /**
     * @param  array<int, array<string, mixed>>  $requirements
     */
    private function requirementsText(array $requirements): string
    {
        return collect($requirements)
            ->flatMap(function (array $requirement): array {
                if (($requirement['type'] ?? null) === 'one_of') {
                    return collect($requirement['subjects'] ?? [])
                        ->map(fn (array $subject): string => $this->requirementText($subject))
                        ->all();
                }

                return [$this->requirementText($requirement)];
            })
            ->implode('; ');
    }

    /**
     * @param  array<string, mixed>  $requirement
     */
    private function requirementText(array $requirement): string
    {
        $text = $this->normalisedSubjectName((string) $requirement['subject']);

        if (($requirement['minimum_mark'] ?? null) !== null) {
            $text .= ' '.(int) $requirement['minimum_mark'].'%';
        } else {
            $text .= ' required';
        }

        return $text;
    }

    private function normalisedSubjectName(string $subjectName): string
    {
        return match (strtolower(trim($subjectName))) {
            'maths', 'math' => 'Mathematics',
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
        $base = $base ?: 'south-cape-tvet-college';
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
