<?php

namespace Database\Seeders\Universities\TSC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequirementSeeder extends Seeder
{
    private const PROGRAMMES_PATH = __DIR__.'/tsc_programmes_chamu.json';

    private const WEBSITE = 'https://tsc.edu.za/';

    public function run(): void
    {
        $data = json_decode(file_get_contents(self::PROGRAMMES_PATH), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data): void {
            $this->seedQualificationTypes();

            $gradeIdsByName = $this->gradeIdsByName();
            $institution = $data['programmes'][0]['institution'] ?? [];
            $countryId = $this->countryId($institution['country'] ?? 'South Africa');
            $universityId = $this->universityId($countryId, $institution);

            DB::table('university_admission_rules')
                ->where('university_id', $universityId)
                ->delete();

            foreach ($data['programmes'] as $programmeRecord) {
                $programmeRecord = $this->withCurrentSourceData($programmeRecord);
                $programme = $programmeRecord['programme'];
                $admission = $programmeRecord['admission'] ?? [];
                $facultyId = $this->facultyId($universityId, (string) $programme['study_field']);
                $qualificationTypeId = $this->qualificationTypeId($this->qualificationTypeName($programme));
                $requiredGradeName = $this->requiredGradeName($programme, $admission);
                $requiredGradeId = $requiredGradeName === null ? null : ($gradeIdsByName[$requiredGradeName] ?? null);
                $qualificationId = $this->qualificationId(
                    $programmeRecord,
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

                foreach ($admission['subject_requirements'] ?? [] as $index => $requirement) {
                    $this->insertRequirement($qualificationId, $requiredGradeId, $requirement, (int) $index);
                }

                $this->assignSubjectLevelsRule($universityId, $qualificationId, $requiredGradeId, $programmeRecord);
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
        $abbreviation = (string) ($institution['abbreviation'] ?? 'TSC');
        $name = (string) ($institution['name'] ?? 'Tshwane South TVET College');
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
     * @param  array<string, mixed>  $programmeRecord
     */
    private function qualificationId(
        array $programmeRecord,
        int $universityId,
        int $facultyId,
        int $qualificationTypeId,
        ?int $requiredGradeId
    ): int {
        $programme = $programmeRecord['programme'];
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
                'slug' => $existing?->slug ?: Str::slug((string) ($programmeRecord['id'] ?? $name)),
                'abbreviation' => $this->qualificationAbbreviation($programme),
                'duration_years' => $this->durationYears($programmeRecord['duration'] ?? []),
                'aps_required' => $this->apsRequired($programmeRecord),
                'aggregate_average_required' => $this->aggregateAverageRequired($programmeRecord),
                'admission_score_required' => null,
                'minimum_pass_type' => null,
                'is_selection_programme' => true,
                'notes' => $this->notes($programmeRecord),
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
     * @param  array<string, mixed>  $programmeRecord
     */
    private function assignSubjectLevelsRule(int $universityId, int $qualificationId, ?int $requiredGradeId, array $programmeRecord): void
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
                'overrides' => $this->admissionRuleOverrides($programmeRecord),
                'notes' => 'TSC matching uses the official page entry route and captured subject rules. APS is only shown where the TSC page explicitly publishes it.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $programmeRecord
     */
    private function admissionRuleOverrides(array $programmeRecord): ?string
    {
        $programme = $programmeRecord['programme'];
        $admission = $programmeRecord['admission'] ?? [];
        $payload = $this->withoutEmptyValues([
            'confirmed_requirements' => $admission['confirmed_requirements'] ?? [],
            'subject_requirements' => $admission['subject_requirements'] ?? [],
            'aps_required' => $this->apsRequired($programmeRecord),
            'aggregate_average_required' => $this->aggregateAverageRequired($programmeRecord),
            'source_url' => $programme['source_url'] ?? null,
        ]);

        return $payload === [] ? null : json_encode($payload, JSON_THROW_ON_ERROR);
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
                    $subject['aps_level_required'] ?? null,
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
            $requirement['aps_level_required'] ?? null,
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
        null|int|float $apsLevelRequired,
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
            'aps_level_required' => $apsLevelRequired === null ? null : (int) ceil((float) $apsLevelRequired),
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
    private function qualificationTypeName(array $programme): string
    {
        return match ($programme['qualification_type'] ?? null) {
            'NC(V)' => 'National Certificate Vocational',
            'NATED / Report 191' => 'NATED',
            'Short / Bridging Programme' => 'Short Skills Programme',
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
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     * @param  array<string, mixed>  $admission
     */
    private function requiredGradeName(array $programme, array $admission): ?string
    {
        if (! empty($admission['required_grade'])) {
            return (string) $admission['required_grade'];
        }

        if (($programme['qualification_type'] ?? null) === 'NC(V)') {
            return 'Grade 9';
        }

        if (($programme['qualification_type'] ?? null) === 'NATED / Report 191') {
            return 'Grade 12';
        }

        $entryText = collect([
            $programme['levels_offered'] ?? null,
            $admission['matching_rules']['typical_entry_guidance'] ?? null,
            ...($admission['confirmed_requirements'] ?? []),
            ...collect($admission['subject_requirements'] ?? [])
                ->flatMap(fn (array $requirement): array => ($requirement['type'] ?? null) === 'one_of'
                    ? collect($requirement['subjects'] ?? [])->pluck('note')->filter()->all()
                    : [$requirement['note'] ?? null])
                ->filter()
                ->all(),
        ])
            ->filter(fn ($value): bool => is_scalar($value))
            ->map(fn ($value): string => (string) $value)
            ->implode(' ');
        $entryText = strtolower($entryText);

        return match (true) {
            str_contains($entryText, 'grade 9') || str_contains($entryText, 'nqf level 1') => 'Grade 9',
            str_contains($entryText, 'grade 10') || str_contains($entryText, 'nqf level 2') => 'Grade 10',
            str_contains($entryText, 'grade 11') || str_contains($entryText, 'nqf level 3') => 'Grade 11',
            str_contains($entryText, 'grade 12') || str_contains($entryText, 'matric') => 'Grade 12',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationNqfLevelId(array $programme): ?int
    {
        if (isset($programme['nqf_level']) && is_numeric($programme['nqf_level'])) {
            return $this->nqfLevelId((int) $programme['nqf_level']);
        }

        $levelsOffered = (string) ($programme['levels_offered'] ?? '');

        if (($programme['qualification_type'] ?? null) === 'NC(V)') {
            return $this->nqfLevelId(4);
        }

        if (($programme['qualification_type'] ?? null) === 'NATED / Report 191') {
            return $this->nqfLevelId(6);
        }

        if (preg_match('/NQF\s+Level\s+(\d+)/i', $levelsOffered, $matches) === 1) {
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
     * @param  array<string, mixed>  $programmeRecord
     */
    private function notes(array $programmeRecord): string
    {
        $programme = $programmeRecord['programme'];
        $admission = $programmeRecord['admission'] ?? [];
        $duration = $programmeRecord['duration'] ?? [];
        $dataQuality = $programmeRecord['data_quality'] ?? [];
        $notes = [];

        foreach ([
            'Qualification type' => $programme['qualification_type'] ?? null,
            'Levels offered' => $programme['levels_offered'] ?? null,
            'Study mode' => $programme['study_mode'] ?? null,
            'Academic cycle' => $programme['academic_cycle'] ?? null,
        ] as $label => $value) {
            if ($value !== null && $value !== '') {
                $notes[] = $label.': '.$value.'.';
            }
        }

        if (! empty($programme['campuses'])) {
            $notes[] = 'Campuses: '.implode(', ', $programme['campuses']).'.';
        } elseif (($programme['campus_confirmation_required'] ?? false) === true) {
            $notes[] = 'Campus availability must be confirmed with TSC.';
        }

        if (($programme['campus_confirmation_required'] ?? false) === true && ! empty($programme['campuses'])) {
            $notes[] = 'Campus availability still requires confirmation with TSC.';
        }

        $matchingRules = $admission['matching_rules'] ?? [];

        foreach ([
            'Official page title' => $programme['official_title'] ?? null,
            'Entry guidance' => $matchingRules['typical_entry_guidance'] ?? null,
            'Progression' => $matchingRules['progression'] ?? null,
            'National diploma note' => $matchingRules['national_diploma_note'] ?? null,
            'Requirements verification status' => $admission['requirements_verification_status'] ?? null,
            'Duration note' => $duration['notes'] ?? null,
            'Admission requirements data quality' => $dataQuality['admission_requirements'] ?? null,
        ] as $label => $value) {
            if ($value !== null && $value !== '') {
                $notes[] = $label.': '.$value.'.';
            }
        }

        if (! empty($admission['confirmed_requirements'])) {
            $notes[] = 'Confirmed entry requirements: '.implode('; ', $admission['confirmed_requirements']).'.';
        }

        if ($this->apsRequired($programmeRecord) !== null) {
            $notes[] = 'APS requirement: '.$this->apsRequired($programmeRecord).'.';
        }

        if ($this->aggregateAverageRequired($programmeRecord) !== null) {
            $notes[] = 'Aggregate average requirement: '.$this->aggregateAverageRequired($programmeRecord).'%.';
        }

        if (! empty($admission['subject_requirements'])) {
            $notes[] = 'Published subject requirements captured for matching: '.$this->requirementsText($admission['subject_requirements']).'.';
        }

        foreach (($programmeRecord['curriculum']['subjects_by_level'] ?? []) as $level => $subjects) {
            if (is_array($subjects) && $subjects !== []) {
                $notes[] = $level.' subjects listed by TSC: '.implode('; ', $subjects).'.';
            }
        }

        if (! empty($programmeRecord['curriculum']['subjects_by_level'])) {
            $notes[] = 'Programme subjects/modules are listed for context and are not treated as school admission requirements unless captured above.';
        }

        if (! empty($admission['manual_review_required_when'])) {
            $notes[] = 'Manual review required when: '.implode('; ', $admission['manual_review_required_when']).'.';
        }

        if ($this->apsRequired($programmeRecord) === null) {
            $notes[] = 'APS is not listed as a requirement on this TSC programme page.';
        }

        return collect($notes)
            ->map(fn (string $note): string => trim($note))
            ->filter()
            ->unique()
            ->implode(' ');
    }

    /**
     * @param  array<string, mixed>  $programmeRecord
     * @return array<string, mixed>
     */
    private function withCurrentSourceData(array $programmeRecord): array
    {
        $sourceUrl = (string) ($programmeRecord['programme']['source_url'] ?? '');
        $sourceData = $this->currentSourceDataByUrl()[$sourceUrl] ?? null;

        if ($sourceData === null) {
            return $programmeRecord;
        }

        $programmeRecord['programme']['official_title'] = $sourceData['official_title'];
        $programmeRecord['programme']['campuses'] = $sourceData['campuses'];
        $programmeRecord['programme']['campus_confirmation_required'] = false;
        $programmeRecord['programme']['nqf_level'] = $sourceData['nqf_level'];
        $programmeRecord['programme']['levels_offered'] = $sourceData['levels_offered'];

        $programmeRecord['admission']['required_grade'] = $sourceData['required_grade'];
        $programmeRecord['admission']['confirmed_requirements'] = $sourceData['requirements'];
        $programmeRecord['admission']['requirements_verification_status'] = 'confirmed from current official TSC programme page';
        $programmeRecord['admission']['subject_requirements'] = $sourceData['subject_requirements'];
        $programmeRecord['admission']['minimum_marks'] = $sourceData['minimum_marks'] ?? [];
        $programmeRecord['admission']['matching_rules']['typical_entry_guidance'] = implode('; ', $sourceData['requirements']);
        $programmeRecord['admission']['matching_rules']['aps_required'] = ($sourceData['aps_required'] ?? null) !== null;
        $programmeRecord['admission']['matching_rules']['aps_required_value'] = $sourceData['aps_required'] ?? null;
        $programmeRecord['admission']['manual_review_required_when'] = [
            'The learner is using an equivalent route not stated directly on the page',
            'The learner is applying above the first listed level without prior results',
            'Campus, intake or final college selection must still be confirmed',
        ];

        $programmeRecord['curriculum']['subjects_by_level'] = $sourceData['modules'];
        $programmeRecord['curriculum']['subject_list_verification_required'] = false;

        $programmeRecord['duration']['notes'] = $sourceData['duration_text'];
        $programmeRecord['data_quality']['admission_requirements'] = 'confirmed from current official TSC programme page';
        $programmeRecord['data_quality']['last_checked'] = '2026-07-29';

        return $programmeRecord;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function currentSourceDataByUrl(): array
    {
        return [
            'https://tsc.edu.za/programmes/civil-construction-l2-l3' => $this->sourceProgramme('Civil & Construction L2-L3', ['Odi Campus'], '12 months', 'Level 2-3', 3, 'Grade 9', ['Passed Grade 9 or equivalent', 'Mathematics: minimum 30%', 'Natural Science: minimum 40%'], [$this->required('Mathematics', 30), $this->required('Natural Science', 40)], ['Modules' => ['Plant and Equipment', 'Materials', 'Construction Planning', 'Plumbing / Carpentry / Masonry', 'Mathematics', 'English FAL', 'Life Skills', 'Computer Literacy']]),
            'https://tsc.edu.za/programmes/civil-engineering-n4-n6' => $this->sourceProgramme('Civil Engineering N4-N6', ['Odi Campus'], 'Three months per level', 'N4-N6', 6, 'Grade 12', ['Grade 12 with Mathematics and Physical Sciences, or', 'NC(V) Level 4 certificate'], [$this->required('Mathematics', null, 'TSC also accepts a relevant NC(V) Level 4 certificate route.'), $this->required('Physical Sciences', null, 'TSC also accepts a relevant NC(V) Level 4 certificate route.')], ['N4-N6 subjects' => ['Quantity Surveying', 'Building & Structural Construction', 'Building Administration', 'Building and Structural Surveying']]),
            'https://tsc.edu.za/programmes/electrical-infrastructure-l2-l3' => $this->sourceProgramme('Electrical Infrastructure Engineering L2-L4', ['Odi Campus'], '12 months', 'Level 2-4', 4, 'Grade 9', ['Passed Grade 9 or equivalent', 'Mathematics: minimum 30%', 'Natural Science: minimum 40%'], [$this->required('Mathematics', 30), $this->required('Natural Science', 40)], ['Modules' => ['Workshop Practice / Electrical Workmanship', 'Electronic Control and Digital Electronics', 'Electrical Systems and Construction', 'Electrical Principles and Practice', 'Mathematics', 'English FAL', 'Life Skills', 'Computer Literacy']]),
            'https://tsc.edu.za/programmes/natural-sciences-electrical-civil' => $this->sourceProgramme('Natural Sciences - Electrical & Civil Engineering', ['Atteridgeville Campus'], '3 months', 'N4-N6 bridge', 4, 'Grade 12', ['Grade 12 with Mathematics and Science (minimum 30% or 40% as required)'], [$this->required('Mathematics', 30, 'TSC states Mathematics and Science minimum 30% or 40% as required; confirm the exact route with campus selection.'), $this->required('Science', 30, 'TSC states Mathematics and Science minimum 30% or 40% as required; confirm the exact route with campus selection.')], ['N4-N6 route subjects' => ['Mathematics N4-N6', 'Electrotechnics N4-N6', 'Industrial Electronics N4-N6', 'Fault Finding & Protective Devices N4-N6', 'Quantity Surveying N4-N6', 'Building Administration N4-N6', 'Building & Structural Survey N4-N6', 'Building & Structural Construction N4-N6']]),
            'https://tsc.edu.za/programmes/electrical-engineering' => $this->sourceProgramme('Electrical Engineering N4-N6', ['Odi Campus'], 'Three months per level', 'N4-N6', 6, 'Grade 12', ['Grade 12 with Mathematics and Physical Sciences, or', 'NC(V) Level 4 certificate'], [$this->required('Mathematics', null, 'TSC also accepts a relevant NC(V) Level 4 certificate route.'), $this->required('Physical Sciences', null, 'TSC also accepts a relevant NC(V) Level 4 certificate route.')], ['N4-N6 subjects' => ['Mathematics', 'Engineering Science', 'Electrotechnics', 'Industrial Electronics', 'Power Machines']]),
            'https://tsc.edu.za/programmes/mechanical-engineering' => $this->sourceProgramme('Mechanical Engineering N4-N6', ['Odi Campus'], 'Three months per level', 'N4-N6', 6, 'Grade 12', ['Grade 12 with Mathematics and Physical Sciences, or', 'NC(V) Level 4 certificate'], [$this->required('Mathematics', null, 'TSC also accepts a relevant NC(V) Level 4 certificate route.'), $this->required('Physical Sciences', null, 'TSC also accepts a relevant NC(V) Level 4 certificate route.')], ['N4-N6 subjects' => ['Mathematics', 'Engineering Science', 'Mechanotechnics', 'Power Machines', 'Fluid Mechanics', 'Mechanical Draughting']]),
            'https://tsc.edu.za/programmes/erd' => $this->sourceProgramme('Engineering and Related Design L2-L4', ['Centurion Campus', 'Pretoria West Campus'], 'One year per level', 'Level 2-4', 4, 'Grade 9', ['Grade 9 certificate with an APS of 24 or higher'], [], ['Modules' => ['English', 'Mathematics / Mathematical Literacy', 'Life Orientation', 'Engineering Fundamentals', 'Engineering Technology', 'Engineering Systems', 'Fitting & Turning or Automotive Repair & Maintenance']], 24),
            'https://tsc.edu.za/programmes/marketing-l2-l3' => $this->sourceProgramme('Marketing L2-L4', ['Centurion Campus', 'Odi Campus', 'Atteridgeville Campus', 'Pretoria West Campus'], '12 months', 'Level 2-4', 4, 'Grade 9', ['Passed Grade 9 or equivalent', 'Mathematics: minimum 20-30%', 'Economic and Management Sciences: minimum 40%'], [$this->required('Mathematics', 20, 'TSC lists Mathematics as 20-30%; 20% is captured as the lower published threshold.'), $this->required('Economic and Management Sciences', 40)], ['Modules' => ['Contact Centre Operations', 'Advertising and Operations', 'Marketing Communications', 'Marketing', 'Mathematical Literacy', 'Life Skills', 'Computer Literacy', 'English FAL']]),
            'https://tsc.edu.za/programmes/office-administration-l2-l3' => $this->sourceProgramme('Office Administration L2-L4', ['Centurion Campus', 'Odi Campus', 'Atteridgeville Campus'], '12 months', 'Level 2-4', 4, 'Grade 9', ['Passed Grade 9 or equivalent', 'Mathematics: minimum 20-30%', 'Economic and Management Sciences: minimum 40%'], [$this->required('Mathematics', 20, 'TSC lists Mathematics as 20-30%; 20% is captured as the lower published threshold.'), $this->required('Economic and Management Sciences', 40)], ['Modules' => ['Business Practice', 'Office Practice', 'New Venture Creation', 'Personal Assistant', 'Office Data Processing', 'Mathematical Literacy', 'Life Skills', 'Computer Literacy', 'English FAL']]),
            'https://tsc.edu.za/programmes/management-assistant' => $this->sourceProgramme('Management Assistant N5-N6', ['Centurion Campus', 'Odi Campus', 'Atteridgeville Campus'], 'Six months per level', 'N5-N6', 6, 'Grade 12', ['Grade 12 certificate, or', 'NC(V) Level 4 certificate'], [], ['N5-N6 subjects' => ['Information Processing', 'Office Practice', 'Communication', 'Computer Practice']]),
            'https://tsc.edu.za/programmes/financial-management' => $this->sourceProgramme('Financial Management N5-N6', ['Centurion Campus', 'Atteridgeville Campus'], 'Six months per level', 'N5-N6', 6, 'Grade 12', ['Grade 12 certificate, or', 'NC(V) Level 4 certificate'], [], ['N5-N6 subjects' => ['Cost and Management Accounting', 'Income Tax', 'Financial Accounting', 'Computerised Financial Systems']]),
            'https://tsc.edu.za/programmes/hospitality-l2-l4' => $this->sourceProgramme('Hospitality L2-L4', ['Centurion Campus', 'Odi Campus'], 'One year per level', 'Level 2-4', 4, 'Grade 9', ['Grade 9 certificate with an APS of 24 or higher'], [], ['Modules' => ['English', 'Mathematics / Mathematical Literacy', 'Life Orientation', 'Hospitality Generics', 'Food Preparation', 'Client Services & Human Relations', 'Hospitality Services']], 24),
            'https://tsc.edu.za/programmes/hospitality-n4-n6' => $this->sourceProgramme('Hospitality N4-N6', ['Centurion Campus', 'Odi Campus'], 'Six months per level', 'N4-N6', 6, 'Grade 12', ['Grade 12 certificate, or', 'NC(V) Level 4 certificate'], [], ['N4-N6 subjects' => ['Computer Practice', 'Applied Management', 'Catering Theory and Practical', 'Communication & Human Relations']]),
            'https://tsc.edu.za/programmes/tourism-l2-l3' => $this->sourceProgramme('Tourism L2-L4', ['Odi Campus'], '12 months', 'Level 2-4', 4, 'Grade 9', ['Passed Grade 9', 'Mathematics: 20-30%', 'Economic and Management Sciences: minimum 40%'], [$this->required('Mathematics', 20, 'TSC lists Mathematics as 20-30%; 20% is captured as the lower published threshold.'), $this->required('Economic and Management Sciences', 40)], ['Modules' => ['Client Service and Human Relations Services', 'Science of Tourism', 'Sustainable Tourism in South Africa', 'Tourism Operations', 'Mathematical Literacy', 'Life Skills and Computer Literacy', 'English FAL']]),
            'https://tsc.edu.za/programmes/tourism-n4-n6' => $this->sourceProgramme('Tourism N5-N6', ['Odi Campus'], 'Six months per level', 'N5-N6', 6, 'Grade 12', ['Grade 12 certificate, and/or', 'NC(V) Level 4 certificate'], [], ['N5-N6 subjects' => ['Catering Theory and Practical', 'Sanitation, Housekeeping and Safety', 'Nutrition and Menu Planning', 'Food and Beverages', 'Applied Management', 'Communication and Human Relations', 'Introductory Communication', 'Computer Practice N4']]),
            'https://tsc.edu.za/programmes/clothing-production' => $this->sourceProgramme('Clothing Production N5-N6', ['Odi Campus'], 'Six months per level', 'N5-N6', 6, 'Grade 12', ['Grade 12 certificate, and/or', 'NC(V) Level 4 certificate'], [], ['N5-N6 subjects' => ['Clothing Construction', 'Pattern Construction', 'Fashion Drawing', 'Entrepreneurship and Business Management', 'Introductory Computer Practice', 'Computer Practice N4']]),
            'https://tsc.edu.za/programmes/bricklayer' => $this->sourceProgramme('Bricklayer', ['Atteridgeville Campus', 'Odi Campus'], 'Three years', 'NQF Level 2-4', 4, 'Grade 11', ['Grade 11, with Mathematics, Science and Technical subjects'], [$this->required('Mathematics'), $this->required('Science'), $this->required('Technical subjects')], ['Modules' => ['Knowledge Module', 'Practical Module', 'Work based Module']]),
            'https://tsc.edu.za/programmes/carpenter' => $this->sourceProgramme('Carpenter', ['Atteridgeville Campus', 'Odi Campus'], 'Three years', 'NQF Level 2-4', 4, 'Grade 11', ['Grade 11, with Mathematics, Science and Technical subjects'], [$this->required('Mathematics'), $this->required('Science'), $this->required('Technical subjects')], ['Modules' => ['Knowledge Module', 'Practical Module', 'Work based Module']]),
            'https://tsc.edu.za/programmes/electrician-l4' => $this->sourceProgramme('Electrician L4', ['Centurion Campus', 'Pretoria West Campus'], '3 years', 'NQF Level 2-4', 4, 'Grade 9', ['NQF Level 1 qualification or equivalent', 'Mathematics at NQF Level 1', 'Natural Science at NQF Level 1'], [$this->level('Mathematics', 1, 'TSC lists Mathematics at NQF Level 1.'), $this->level('Natural Science', 1, 'TSC lists Natural Science at NQF Level 1.')], ['Modules' => ['Health, Safety, Quality and Legislation', 'Tools, Equipment and Materials', 'Electricity and Electronics', 'Wireways and Wiring', 'Rotating Electrical Machinery', 'Electrical Supply Systems and Components', 'Fault Finding']]),
            'https://tsc.edu.za/programmes/fitter-turner' => $this->sourceProgramme('Fitter & Turner L4', ['Centurion Campus', 'Odi Campus'], '4 years', 'NQF Level 2-4', 4, 'Grade 9', ['NQF Level 1 qualification or equivalent', 'Mathematics at NQF Level 1'], [$this->level('Mathematics', 1, 'TSC lists Mathematics at NQF Level 1.')], ['Modules' => ['Basic Engineering Theory', 'Fitting Theory', 'Machining Theory', 'Practical Skill Modules', 'Work Experience Modules']]),
            'https://tsc.edu.za/programmes/plumber' => $this->sourceProgramme('Plumber', ['Atteridgeville Campus', 'Odi Campus'], 'Three years', 'NQF Level 2-4', 4, 'Grade 9', ['Grade 9, with Mathematics, Science and Technical subjects'], [$this->required('Mathematics'), $this->required('Science'), $this->required('Technical subjects')], ['Modules' => ['Knowledge Module', 'Practical Module', 'Work based Module']]),
            'https://tsc.edu.za/programmes/welder-l4' => $this->sourceProgramme('Welder L4', ['Centurion Campus'], '3 years', 'NQF Level 2-4', 4, 'Grade 9', ['NQF Level 1 qualification or equivalent', 'Mathematics at NQF Level 1', 'Natural Science at NQF Level 1'], [$this->level('Mathematics', 1, 'TSC lists Mathematics at NQF Level 1.'), $this->level('Natural Science', 1, 'TSC lists Natural Science at NQF Level 1.')], ['Modules' => ['Introduction to the welding trade', 'Occupational Safety, Health and Environmental Protection', 'Welding schematics, calculations, welds and welded joints', 'Practical Skill Modules', 'Work Experience Modules']]),
            'https://tsc.edu.za/programmes/bookkeeping' => $this->sourceProgramme('Bookkeeper L5', ['Centurion Campus', 'Atteridgeville Campus'], '3 years', 'NQF Level 5', 5, 'Grade 12', ['NQF Level 4 qualification (Grade 12 or equivalent)', 'Mathematics at NQF Level 4'], [$this->level('Mathematics', 4, 'TSC lists Mathematics at NQF Level 4 for Bookkeeper.')], ['Modules' => ['Accounting Information Systems', 'Bookkeeping Practice', 'Financial Accounting', 'Basic Principles of Cost and Management Accounting', 'Basic Taxation', 'Principles of Taxation', 'End User Computing', 'Business Communication and Customer Services', 'Work Experience Modules']]),
            'https://tsc.edu.za/programmes/retail-buyer' => $this->sourceProgramme('Retail Buyer', ['Atteridgeville Campus'], 'Six months', 'NQF Level 4', 4, 'Grade 12', ['Report 191 or equivalent; pass Grade 12 subjects as required'], [], ['Modules' => ['Knowledge Modules', 'Practical Skill Modules']]),
            'https://tsc.edu.za/programmes/cook-l4' => $this->sourceProgramme('Cook L4', ['Centurion Campus', 'Odi Campus'], '1 year', 'NQF Level 2-4', 4, 'Grade 9', ['NQF Level 1 qualification or equivalent', 'Mathematics at NQF Level 1'], [$this->level('Mathematics', 1, 'TSC lists Mathematics at NQF Level 1.')], ['Modules' => ['Personal Hygiene and Safety', 'Food Safety and Quality Assurance', 'Workplace Safety', 'Numeracy, Units of Measure and Computer Literacy', 'Theory of Food Production', 'Practical Skill Modules', 'Work Experience Modules']]),
        ];
    }

    /**
     * @param  array<int, string>  $campuses
     * @param  array<int, string>  $requirements
     * @param  array<int, array<string, mixed>>  $subjectRequirements
     * @param  array<string, array<int, string>>  $modules
     * @return array<string, mixed>
     */
    private function sourceProgramme(
        string $officialTitle,
        array $campuses,
        string $durationText,
        string $levelsOffered,
        int $nqfLevel,
        ?string $requiredGrade,
        array $requirements,
        array $subjectRequirements,
        array $modules,
        ?int $apsRequired = null
    ): array {
        return [
            'official_title' => $officialTitle,
            'campuses' => $campuses,
            'duration_text' => $durationText,
            'levels_offered' => $levelsOffered,
            'nqf_level' => $nqfLevel,
            'required_grade' => $requiredGrade,
            'requirements' => $requirements,
            'subject_requirements' => $subjectRequirements,
            'modules' => $modules,
            'aps_required' => $apsRequired,
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
    private function level(string $subject, int $level, ?string $note = null): array
    {
        return [
            'type' => 'required',
            'subject' => $subject,
            'aps_level_required' => $level,
            'note' => $note,
        ];
    }

    /**
     * @param  array<int, array{subject: string, minimum_mark?: int|float|null, aps_level_required?: int|float|null, note?: string|null}>  $subjects
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
     * @param  array<string, mixed>  $programmeRecord
     */
    private function apsRequired(array $programmeRecord): ?int
    {
        $value = $programmeRecord['admission']['matching_rules']['aps_required_value'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $programmeRecord
     */
    private function aggregateAverageRequired(array $programmeRecord): ?float
    {
        $value = $programmeRecord['admission']['matching_rules']['aggregate_average_required'] ?? null;

        return is_numeric($value) ? (float) $value : null;
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

        if (($requirement['aps_level_required'] ?? null) !== null) {
            return $text.' level '.(int) $requirement['aps_level_required'];
        }

        if (($requirement['minimum_mark'] ?? null) !== null) {
            return $text.' '.(int) $requirement['minimum_mark'].'%';
        }

        return $text.' required';
    }

    private function normalisedSubjectName(string $subjectName): string
    {
        return match (strtolower(trim($subjectName))) {
            'natural science' => 'Natural Sciences',
            'science' => 'Science',
            'physical science' => 'Physical Sciences',
            'economic and management sciences' => 'Economic and Management Sciences',
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

    /**
     * @param  array<string, mixed>  $duration
     */
    private function durationYears(array $duration): ?float
    {
        $value = $duration['value'] ?? null;
        $unit = strtolower((string) ($duration['unit'] ?? ''));

        if (! is_numeric($value)) {
            return null;
        }

        return match ($unit) {
            'year', 'years' => (float) $value,
            'month', 'months' => round((float) $value / 12, 1),
            default => null,
        };
    }

    private function uniqueUniversitySlug(string $base): string
    {
        $base = $base ?: 'tshwane-south-tvet-college';
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
