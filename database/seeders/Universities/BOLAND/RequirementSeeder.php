<?php

namespace Database\Seeders\Universities\BOLAND;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequirementSeeder extends Seeder
{
    private const WEBSITE = 'https://bolandcollege.com/';

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
            ['Occupational Certificate', 'OccCert', null, 52],
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
            ->where('abbreviation', 'BOLAND')
            ->first();

        DB::table('universities')->updateOrInsert(
            ['abbreviation' => 'BOLAND'],
            [
                'country_id' => $countryId,
                'name' => 'Boland College',
                'slug' => $existing?->slug ?: $this->uniqueUniversitySlug('boland-college'),
                'website' => self::WEBSITE,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('universities')
            ->where('abbreviation', 'BOLAND')
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
            'published_requirements' => $programme['entry'] ?? null,
            'subject_requirements' => $programme['subject_requirements'] ?? [],
            'aggregate_average_required' => $this->aggregateAverageRequired($programme),
            'source_url' => $programme['source_url'] ?? null,
        ]);

        return $payload === [] ? null : json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function admissionRuleNotes(array $programme): string
    {
        if ($this->aggregateAverageRequired($programme) !== null) {
            return 'Boland College publishes a minimum aggregate for this programme together with specific Mathematics or Mathematical Literacy thresholds. Equivalent NC(V) routes still require manual review.';
        }

        return 'Boland College does not publish APS for this programme; matching should use the listed subject marks where captured, entry route, NQF/NC(V)/ABET alternatives, age or register checks, campus availability and manual review.';
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationTypeName(array $programme): string
    {
        return match ($programme['qualification_type'] ?? null) {
            'National Certificate (Vocational)' => 'National Certificate Vocational',
            'NATED / Report 191' => 'NATED',
            'Occupational Certificate' => 'Occupational Certificate',
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
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function requiredGradeName(array $programme): ?string
    {
        if (($programme['qualification_type'] ?? null) === 'National Certificate (Vocational)') {
            $entryText = strtolower((string) ($programme['entry'] ?? ''));

            return match (true) {
                str_contains($entryText, 'grade 11') => 'Grade 11',
                str_contains($entryText, 'grade 10') => 'Grade 10',
                default => 'Grade 9',
            };
        }

        if (($programme['qualification_type'] ?? null) === 'NATED / Report 191') {
            return 'Grade 12';
        }

        $entryText = strtolower((string) ($programme['entry'] ?? ''));

        return match (true) {
            str_contains($entryText, 'open access') => null,
            str_contains($entryText, 'grade 9') || str_contains($entryText, 'abet l4') || str_contains($entryText, 'nqf level 1') => 'Grade 9',
            str_contains($entryText, 'grade 10') || str_contains($entryText, 'nqf level 2') => 'Grade 10',
            str_contains($entryText, 'grade 11') || str_contains($entryText, 'nqf level 3') => 'Grade 11',
            str_contains($entryText, 'grade 12') || str_contains($entryText, 'nqf level 4') || str_contains($entryText, 'ncv level 4') => 'Grade 12',
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
            'Credits' => $programme['credits'] ?? null,
        ] as $label => $value) {
            if ($value !== null && $value !== '') {
                $notes[] = $label.': '.$value.'.';
            }
        }

        if (! empty($programme['entry'])) {
            $notes[] = 'Entry requirements: '.$programme['entry'].'.';
        }

        if ($this->aggregateAverageRequired($programme) !== null) {
            $notes[] = 'Aggregate average requirement: '.$this->aggregateAverageRequired($programme).'%.';
        } else {
            $notes[] = 'APS is not listed as a requirement for this Boland College programme.';
        }

        if (! empty($programme['subject_requirements'])) {
            $notes[] = 'Published school subject marks captured for matching: '.$this->requirementsText($programme['subject_requirements']).'.';
        }

        if (! empty($programme['additional_requirements'])) {
            $notes[] = 'Additional requirements: '.implode('; ', $programme['additional_requirements']).'.';
        }

        if (! empty($programme['careers'])) {
            $notes[] = 'Career paths: '.implode('; ', $programme['careers']).'.';
        }

        $notes[] = 'Campus availability is linked from the Boland College programme page and should be confirmed before final admission decisions.';
        $notes[] = 'Manual review required for equivalent NC(V), ABET, NQF, PLP, numeracy/literacy assessment, register, age, colourblind or work-experience routes where listed.';
        $notes[] = 'Source: official Boland College programme page.';

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
            $this->occupational('bookkeeper', 'Economic & Management Sciences', 'Bookkeeper', 5, 'https://bolandcollege.com/what-to-study/faculty-of-economic-management-sciences/bookkeeper/', 'Grade 12 Certificate / NCV Level 4 / Relevant FETC at NQF Level 4', 3, 364, ['Accounting Clerk', 'Financial Administrator', 'Senior Bookkeeper']),
            $this->occupational('office-administrator', 'Economic & Management Sciences', 'Office Administrator', 5, 'https://bolandcollege.com/what-to-study/faculty-of-economic-management-sciences/office-administrator/', 'Grade 12 Certificate / NCV Level 4 / Relevant FETC at NQF Level 4', 3, 445, ['Administration Officer', 'Office Supervisor', 'Administrative Assistant']),
            $this->ncv('electrical-infrastructure-construction', 'Engineering Studies', 'Electrical Infrastructure Construction', 'https://bolandcollege.com/what-to-study/faculty-of-engineering-studies/electrical-infrastructure-construction/', 'Grade 9 pass with a pass in Mathematics OR PLP students: English 50%, Mathematics 55%, Science 50% and Life Skills 40%', 3, [$this->required('Mathematics', 30, 'Boland source lists Grade 9 pass with a pass in Mathematics; PLP alternative thresholds remain in notes.')], ['Electrical Engineering', 'Construction Electrician', 'Industrial Engineering']),
            $this->ncv('engineering-related-design', 'Engineering Studies', 'Engineering & Related Design', 'https://bolandcollege.com/what-to-study/faculty-of-engineering-studies/engineering-related-design/', 'Grade 9 pass with a pass in Mathematics OR PLP students: English 50%, Mathematics 55%, Science 50% and Life Skills 40%', 3, [$this->required('Mathematics', 30, 'Boland source lists Grade 9 pass with a pass in Mathematics; PLP alternative thresholds remain in notes.')], ['Manufacturing & Industrial Engineering', 'Boilermaking', 'Automotive Repair & Maintenance']),
            $this->occupational('automotive-motor-mechanic', 'Engineering Studies', 'Automotive Motor Mechanic', 4, 'https://bolandcollege.com/what-to-study/faculty-of-engineering-studies/automotive-motor-mechanic/', 'Grade 9/ABET L4/NQF Level 1 with Mathematics', 3, 540, ['Motor Mechanic', 'Petrol Mechanic', 'Automotive Technician'], [$this->required('Mathematics', null), $this->required('Science', null)], ['The student must be 16 or older', 'Mathematics and Science are compulsory subjects in Grade 9']),
            $this->occupational('bricklayer', 'Engineering Studies', 'Bricklayer', 4, 'https://bolandcollege.com/what-to-study/faculty-of-engineering-studies/bricklayer/', 'NQF Level 3 / Grade 11 or older / Minimum age of 16', 3, 361, ['Bricklayer', 'Construction Worker', 'Construction Foreman'], [], ['Minimum age of 16']),
            $this->occupational('diesel-mechanic', 'Engineering Studies', 'Diesel Mechanic', 4, 'https://bolandcollege.com/what-to-study/faculty-of-engineering-studies/diesel-mechanic/', 'Grade 9/ABET L4/NQF Level 1 with Mathematics', 3, 540, ['Diesel Mechanic', 'Truck Mechanic', 'Heavy Duty Equipment Service Mechanic'], [$this->required('Mathematics', null), $this->required('Science', null)], ['The student must be 16 or older', 'Mathematics and Science are compulsory subjects in Grade 9']),
            $this->occupational('electrician', 'Engineering Studies', 'Electrician', 4, 'https://bolandcollege.com/what-to-study/faculty-of-engineering-studies/electrician/', 'Grade 9/ABET L4/NQF Level 1 with Mathematics', 3, 360, ['Electrician', 'Electrical Engineering Technician', 'Electrical Installation Inspector'], [$this->required('Mathematics', null), $this->required('Science', null)], ['The student must be 16 or older', 'Mathematics and Science are compulsory subjects in Grade 9', 'Student will complete a colourblind test']),
            $this->occupational('cook', 'Hospitality & Tourism', 'Cook', 4, 'https://bolandcollege.com/faculty-of-hospitality-tourism/cook/', 'Open access/NQF Level 1 or at least 16 years of age with possible assessment of numeracy and literacy', 1, 184, ['Restaurant Cook', 'Deli Cook', 'Kitchen Assistant'], [], ['Possible assessment of numeracy and literacy']),
            $this->occupational('chef', 'Hospitality & Tourism', 'Chef', 5, 'https://bolandcollege.com/faculty-of-hospitality-tourism/chef/', 'NQF Level 2 / Grade 10 with Mathematics', 3, 558, ['Chef', 'Chef de Partie'], [$this->required('Mathematics', null)]),
            $this->occupational('tourism-information-officer', 'Hospitality & Tourism', 'Tourism Information Officer', 5, 'https://bolandcollege.com/faculty-of-hospitality-tourism/tourism-information-officer/', 'Grade 12 Certificate / NCV Level 4 / relevant NQF Level 4 qualification / relevant FETC at NQF Level 4', 2, 280, ['Travel Advisor', 'Tourism Information Officer', 'Tourism Coordinator'], [], ['Assessment of numeracy and literacy']),
            $this->ncv('it-computer-science', 'Information Technology & Agriculture', 'Information Technology & Computer Science', 'https://bolandcollege.com/what-to-study/faculty-of-information-technology-agriculture/it-computer-science/', 'Grade 11 with a pass in Mathematics or Mathematical Literacy with a minimum of 55% OR PLP students: English 50%, Mathematics 55%, Science 50% and Life Skills 40%', 3, [$this->oneOf([['subject' => 'Mathematics', 'minimum_mark' => 55], ['subject' => 'Mathematical Literacy', 'minimum_mark' => 55]], 'Mathematics or Mathematical Literacy 55%')], ['Computer Programming', 'Information Technology Management', 'Data Processing']),
            $this->nated('farming-management', 'Information Technology & Agriculture', 'Farming Management & Mechanisation', 'https://bolandcollege.com/what-to-study/faculty-of-information-technology-agriculture/farming-management/', 'Grade 12 pass with a minimum aggregate of 45% OR NCV Level 4 with a minimum aggregate of 45%', 3, [$this->oneOf([['subject' => 'Mathematics', 'minimum_mark' => 40], ['subject' => 'Mathematical Literacy', 'minimum_mark' => 50]], 'Mathematics 40% or Mathematical Literacy 50%')], 45, ['Farmer', 'Farm Supervisor', 'Farm Manager']),
            $this->ncv('safety-in-society', 'Social & Security Sciences', 'Safety in Society', 'https://bolandcollege.com/what-to-study/faculty-of-social-security-sciences/safety-in-society/', 'Grade 10 pass OR PLP students: English 50%, Mathematics 40% and Life Skills 40%', 3, [], ['Entry-level Security Officer', 'Access to Police College', 'Access to Correctional Services Training']),
            $this->occupational('early-childhood-development-practitioner', 'Social & Security Sciences', 'Early Childhood Development Practitioner', 4, 'https://bolandcollege.com/what-to-study/faculty-of-social-security-sciences/early-childhood-development-practitioner/', 'Grade 11 / NCV Level 3', 1, 131, ['Preschools and daycare centres', 'Home-based care', 'Community programmes'], [], ['Students must undergo verification against the Child Protection Register']),
            $this->occupational('management-assistant', 'Office Management Sciences', 'Management Assistant', 5, 'https://bolandcollege.com/what-to-study/faculty-of-office-management-sciences/management-assistant/', 'Grade 12 Certificate / NCV Level 4 / Relevant FETC at NQF Level 4', 2, 316, ['Administrative Secretary', 'Executive Assistant', 'Personal Assistant']),
            $this->ncv('robotics', 'Information Technology & Agriculture', 'Robotics', 'https://bolandcollege.com/what-to-study/faculty-of-information-technology-agriculture/robotics/', 'Grade 11 pass with 30% and above in Mathematics OR PLP students: English 50%, Mathematics 55%, Science 50% and Life Skills 40%', 3, [$this->required('Mathematics', 30)], ['IT Technician', 'IoT Operator/Device Manager', 'IT Hardware Installer']),
            $this->ncv('primary-agriculture', 'Information Technology & Agriculture', 'Primary Agriculture', 'https://bolandcollege.com/what-to-study/faculty-of-information-technology-agriculture/primary-agriculture/', 'Grade 9', 3, [], ['Agricultural Economics', 'Farm Management', 'Horticulture']),
            $this->occupational('welder', 'Engineering Studies', 'Welder', 4, 'https://bolandcollege.com/what-to-study/faculty-of-engineering-studies/welder/', 'Grade 9/ABET L4/NQF Level 1 with Mathematics', 3, 372, ['Fillet Welder', 'Plate Welder', 'Pipe Welder'], [$this->required('Mathematics', null), $this->required('Science', null)], ['The student must be 16 or older', 'Mathematics and Science are compulsory subjects in Grade 9']),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $subjectRequirements
     * @param  array<int, string>  $careers
     * @return array<string, mixed>
     */
    private function ncv(string $id, string $field, string $name, string $sourceUrl, string $entry, float $durationYears, array $subjectRequirements = [], array $careers = []): array
    {
        return [
            'id' => 'boland-ncv-'.$id,
            'field' => $field,
            'name' => $name,
            'qualification_type' => 'National Certificate (Vocational)',
            'levels' => ['Level 2', 'Level 3', 'Level 4'],
            'source_url' => $sourceUrl,
            'entry' => $entry,
            'duration_years' => $durationYears,
            'duration_text' => $durationYears.' years',
            'subject_requirements' => $subjectRequirements,
            'careers' => $careers,
            'additional_requirements' => str_contains($entry, 'PLP students')
                ? ['PLP alternative route has separate percentage thresholds listed by Boland College']
                : [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $subjectRequirements
     * @param  array<int, string>  $careers
     * @return array<string, mixed>
     */
    private function nated(string $id, string $field, string $name, string $sourceUrl, string $entry, float $durationYears, array $subjectRequirements = [], ?float $aggregateAverage = null, array $careers = []): array
    {
        return [
            'id' => 'boland-nated-'.$id,
            'field' => $field,
            'name' => $name,
            'qualification_type' => 'NATED / Report 191',
            'levels' => ['N4', 'N5', 'N6'],
            'source_url' => $sourceUrl,
            'entry' => $entry,
            'duration_years' => $durationYears,
            'duration_text' => '3 years (18 months theory and 18 months practical work experience)',
            'aggregate_average_required' => $aggregateAverage,
            'subject_requirements' => $subjectRequirements,
            'careers' => $careers,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $subjectRequirements
     * @param  array<int, string>  $careers
     * @param  array<int, string>  $additionalRequirements
     * @return array<string, mixed>
     */
    private function occupational(
        string $id,
        string $field,
        string $name,
        int $nqfLevel,
        string $sourceUrl,
        string $entry,
        float $durationYears,
        int $credits,
        array $careers = [],
        array $subjectRequirements = [],
        array $additionalRequirements = []
    ): array {
        return [
            'id' => 'boland-occupational-'.$id,
            'field' => $field,
            'name' => $name,
            'qualification_type' => 'Occupational Certificate',
            'levels' => ['NQF Level '.$nqfLevel],
            'nqf_level' => $nqfLevel,
            'source_url' => $sourceUrl,
            'entry' => $entry,
            'duration_years' => $durationYears,
            'duration_text' => $durationYears.' '.($durationYears === 1.0 ? 'year' : 'years'),
            'credits' => $credits,
            'subject_requirements' => $subjectRequirements,
            'additional_requirements' => $additionalRequirements,
            'careers' => $careers,
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
            'science' => 'Science',
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
        $base = $base ?: 'boland-college';
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
