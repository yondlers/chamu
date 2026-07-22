<?php

namespace Database\Seeders\Universities\UNISA;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequirementSeeder extends Seeder
{
    private const PROGRAMME_PATHS = [
        __DIR__.'/unisa_bachelors_chamu.json',
        __DIR__.'/unisa_diplomas_chamu.json',
        __DIR__.'/unisa_part_2.json',
    ];

    private const WEBSITE = 'https://www.unisa.ac.za/';

    public function run(): void
    {
        $datasets = collect(self::PROGRAMME_PATHS)
            ->filter(fn (string $path): bool => file_exists($path))
            ->map(fn (string $path): array => json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR))
            ->all();

        DB::transaction(function () use ($datasets): void {
            $this->seedQualificationTypes();

            $gradeIdsByName = $this->gradeIdsByName();
            $subjectIdsByGrade = $this->subjectIdsByGrade();
            $countryId = $this->countryId('South Africa');
            $universityId = $this->universityId($countryId);

            DB::table('university_admission_rules')
                ->where('university_id', $universityId)
                ->delete();

            foreach ($datasets as $dataset) {
                foreach ($dataset['programmes'] as $rawProgramme) {
                    $programmeRecord = $this->normaliseProgrammeRecord($rawProgramme);
                    $qualification = $programmeRecord['qualification'];
                    $facultyId = $this->facultyId($universityId, (string) $qualification['college']);
                    $qualificationTypeId = $this->qualificationTypeId((string) $qualification['qualification_type']);
                    $requiredGradeId = $gradeIdsByName['Grade 12'] ?? null;
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

                    $this->insertLanguageRequirements($qualificationId, $gradeIdsByName, $subjectIdsByGrade, $programmeRecord);
                    $this->insertMathematicsRequirement($qualificationId, $gradeIdsByName, $subjectIdsByGrade, $programmeRecord);
                    $this->assignAdmissionRule($universityId, $qualificationId, $requiredGradeId, $programmeRecord);
                }
            }
        });
    }

    private function seedQualificationTypes(): void
    {
        $types = [
            ['Diploma', 'Dip', 6, 9],
            ['Bachelor\'s Degree', null, 7, 14],
        ];

        foreach ($types as [$name, $abbreviation, $nqfLevel, $sortOrder]) {
            DB::table('qualification_types')->updateOrInsert(
                ['name' => $name],
                [
                    'abbreviation' => $abbreviation,
                    'nqf_level_id' => $this->nqfLevelId($nqfLevel),
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
            ->where('abbreviation', 'UNISA')
            ->first();

        DB::table('universities')->updateOrInsert(
            ['abbreviation' => 'UNISA'],
            [
                'country_id' => $countryId,
                'name' => 'University of South Africa',
                'slug' => $existing?->slug ?: $this->uniqueUniversitySlug('university-of-south-africa'),
                'website' => self::WEBSITE,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('universities')
            ->where('abbreviation', 'UNISA')
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
        $qualification = $programmeRecord['qualification'];
        $name = (string) $qualification['name'];
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
                'nqf_level_id' => $this->nqfLevelId((int) ($qualification['nqf_level'] ?? 7)),
                'required_grade_id' => $requiredGradeId,
                'slug' => $existing?->slug ?: Str::slug((string) $programmeRecord['id']),
                'abbreviation' => $this->qualificationIdentifier($qualification),
                'duration_years' => $qualification['minimum_nominal_years'] ?? null,
                'aps_required' => $programmeRecord['admission']['minimum_aps'] ?? null,
                'aggregate_average_required' => null,
                'admission_score_required' => null,
                'minimum_pass_type' => $this->minimumPassType($qualification),
                'is_selection_programme' => true,
                'notes' => $this->notes($programmeRecord),
                'source_url' => $qualification['source_url'] ?? self::WEBSITE,
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
    private function insertLanguageRequirements(int $qualificationId, array $gradeIdsByName, array $subjectIdsByGrade, array $programmeRecord): void
    {
        $minimum = $this->languageMinimum($programmeRecord);

        if ($minimum === null) {
            return;
        }

        $group = 'unisa_language_'.$qualificationId;
        $note = 'UNISA '.$this->qualificationLabel($programmeRecord).' route requires '.$minimum.'% in the language of teaching and learning; seeded as English HL/FAL alternatives for CAPS matching.';

        foreach (['English Home Language', 'English First Additional Language'] as $index => $subject) {
            $this->insertSubjectRequirement(
                $qualificationId,
                $subject,
                $minimum,
                $index === 0 ? 'required' : 'alternative',
                $group,
                $gradeIdsByName,
                $subjectIdsByGrade,
                $note,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $programmeRecord
     */
    private function insertMathematicsRequirement(int $qualificationId, array $gradeIdsByName, array $subjectIdsByGrade, array $programmeRecord): void
    {
        $mathematics = $programmeRecord['admission']['mathematics'] ?? [];
        $minimum = $mathematics['typical_minimum_percentage']
            ?? $mathematics['minimum_percentage']
            ?? $mathematics['typical_mathematics_minimum_percentage']
            ?? null;

        if (! $this->requiresMathematics($mathematics) || ! is_numeric($minimum)) {
            return;
        }

        $mathLitAccepted = ($mathematics['mathematical_literacy_accepted'] ?? false) === true;
        $mathLitMinimum = $mathematics['mathematical_literacy_minimum_percentage']
            ?? $mathematics['mathematical_literacy']['typical_minimum_when_accepted']
            ?? null;
        $group = $mathLitAccepted && is_numeric($mathLitMinimum) ? 'unisa_math_'.$qualificationId : null;
        $note = $this->mathematicsRequirementNote($mathematics, $mathLitAccepted, $mathLitMinimum);

        $this->insertSubjectRequirement(
            $qualificationId,
            'Mathematics',
            (int) $minimum,
            'required',
            $group,
            $gradeIdsByName,
            $subjectIdsByGrade,
            $note,
        );

        if ($mathLitAccepted && is_numeric($mathLitMinimum)) {
            $this->insertSubjectRequirement(
                $qualificationId,
                'Mathematical Literacy',
                (int) $mathLitMinimum,
                'alternative',
                $group,
                $gradeIdsByName,
                $subjectIdsByGrade,
                $note,
            );
        }
    }

    private function insertSubjectRequirement(
        int $qualificationId,
        string $subjectName,
        int $minimumMark,
        string $requirementType,
        ?string $requirementGroup,
        array $gradeIdsByName,
        array $subjectIdsByGrade,
        string $note
    ): void {
        $gradeName = 'Grade 12';
        $subjectIds = $subjectIdsByGrade[$gradeName] ?? [];

        DB::table('qualification_subject_requirements')->insert([
            'qualification_id' => $qualificationId,
            'subject_id' => $subjectIds[$subjectName] ?? null,
            'grade_id' => $gradeIdsByName[$gradeName] ?? null,
            'subject_name' => $subjectName,
            'minimum_mark' => $minimumMark,
            'aps_level_required' => null,
            'requirement_type' => $requirementType,
            'requirement_group' => $requirementGroup,
            'notes' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $programmeRecord
     */
    private function assignAdmissionRule(int $universityId, int $qualificationId, ?int $requiredGradeId, array $programmeRecord): void
    {
        $admissionRuleId = DB::table('admission_rules')
            ->where('code', 'nsc_aps_excluding_lo')
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
                'notes' => 'UNISA undergraduate APS is seeded from the supplied qualification record; APS alone must not be treated as proof of eligibility.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $programmeRecord
     */
    private function admissionRuleOverrides(array $programmeRecord): string
    {
        return json_encode($this->withoutEmptyValues([
            'qualification_code' => $programmeRecord['qualification']['qualification_code'] ?? null,
            'stream_code' => $programmeRecord['qualification']['stream_code'] ?? null,
            'minimum_aps' => $programmeRecord['admission']['minimum_aps'] ?? null,
            'minimum_pass_type' => $this->minimumPassType($programmeRecord['qualification']),
            'school_leaving_certificate' => $programmeRecord['admission']['school_leaving_certificate'] ?? $this->schoolLeavingCertificate($programmeRecord),
            'required_endorsement' => $programmeRecord['admission']['required_endorsement'] ?? null,
            'additional_requirements' => $programmeRecord['admission']['additional_requirements'] ?? [],
            'additional_selection' => $programmeRecord['admission']['additional_selection'] ?? null,
            'alternative_routes' => $programmeRecord['admission']['alternative_routes'] ?? [],
            'manual_review_required' => $programmeRecord['admission']['manual_review_required'] ?? null,
            'work_integrated_learning' => $programmeRecord['admission']['work_integrated_learning'] ?? null,
            'matching_evaluate_in_order' => $programmeRecord['chamu_matching']['evaluate_in_order'] ?? [],
            'never_do' => $programmeRecord['chamu_matching']['never_do'] ?? $programmeRecord['chamu_matching']['do_not_assume'] ?? $programmeRecord['chamu_matching']['never_assume'] ?? [],
        ]), JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $rawProgramme
     * @return array<string, mixed>
     */
    private function normaliseProgrammeRecord(array $rawProgramme): array
    {
        if (isset($rawProgramme['qualification'])) {
            return $rawProgramme;
        }

        return [
            'id' => $rawProgramme['id'],
            'institution' => [
                'name' => 'University of South Africa',
                'abbreviation' => 'UNISA',
                'institution_type' => 'Public distance-learning university',
                'country' => 'South Africa',
            ],
            'qualification' => [
                'name' => $rawProgramme['name'],
                'display_name' => $this->displayName($rawProgramme),
                'qualification_code' => $rawProgramme['qualification_code'] ?? null,
                'stream_code' => $rawProgramme['stream_code'] ?? null,
                'qualification_type' => $rawProgramme['qualification_type'] ?? 'Diploma',
                'nqf_level' => $rawProgramme['nqf_level'] ?? 6,
                'total_credits' => $rawProgramme['total_credits'] ?? 360,
                'minimum_nominal_years' => $rawProgramme['minimum_nominal_years'] ?? 3,
                'maximum_completion_years' => $rawProgramme['maximum_completion_years'] ?? 8,
                'field' => $rawProgramme['field'] ?? null,
                'college' => $rawProgramme['college'] ?? 'UNISA',
                'study_mode' => $rawProgramme['study_mode'] ?? [],
                'source_url' => $rawProgramme['source_url'] ?? null,
                'saqa_id' => $rawProgramme['saqa_id'] ?? null,
                'purpose' => $rawProgramme['purpose'] ?? null,
                'input_correction' => $rawProgramme['input_correction'] ?? null,
            ],
            'admission' => $rawProgramme['admission'],
            'technology_requirements' => $rawProgramme['technology_requirements'] ?? [],
            'career_examples' => $rawProgramme['career_examples'] ?? [],
            'funding' => $rawProgramme['funding'] ?? [],
            'chamu_matching' => $rawProgramme['chamu_matching'] ?? [],
            'data_quality' => $rawProgramme['data_quality'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $rawProgramme
     */
    private function displayName(array $rawProgramme): string
    {
        $identifier = collect([
            $rawProgramme['qualification_code'] ?? null,
            $rawProgramme['stream_code'] ?? null,
        ])
            ->filter()
            ->implode(' - ');

        return $identifier === '' ? $rawProgramme['name'] : $rawProgramme['name'].' ('.$identifier.')';
    }

    /**
     * @param  array<string, mixed>  $qualification
     */
    private function qualificationIdentifier(array $qualification): string
    {
        return collect([
            $qualification['qualification_code'] ?? null,
            $qualification['stream_code'] ?? null,
        ])
            ->filter()
            ->implode('-');
    }

    /**
     * @param  array<string, mixed>  $qualification
     */
    private function minimumPassType(array $qualification): string
    {
        return $qualification['qualification_type'] === 'Diploma' ? 'diploma' : 'bachelor';
    }

    /**
     * @param  array<string, mixed>  $programmeRecord
     */
    private function languageMinimum(array $programmeRecord): ?int
    {
        $structuredMinimum = $programmeRecord['admission']['school_leaving_certificate']['NSC']['language_of_teaching_and_learning']['minimum_percentage']
            ?? $programmeRecord['admission']['NSC']['language_of_teaching_and_learning']['typical_minimum_percentage']
            ?? null;

        if (is_numeric($structuredMinimum)) {
            return (int) $structuredMinimum;
        }

        $languageRequirement = (string) ($programmeRecord['admission']['language_requirement'] ?? '');

        if (preg_match('/(\d+)%/', $languageRequirement, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $programmeRecord
     */
    private function qualificationLabel(array $programmeRecord): string
    {
        return $programmeRecord['qualification']['qualification_type'] === 'Diploma' ? 'diploma' : 'bachelor';
    }

    /**
     * @param  array<string, mixed>  $mathematics
     */
    private function requiresMathematics(array $mathematics): bool
    {
        return ($mathematics['required'] ?? false) === true
            || ($mathematics['required_or_preferred'] ?? false) === true;
    }

    /**
     * @param  array<string, mixed>  $mathematics
     */
    private function mathematicsRequirementNote(array $mathematics, bool $mathLitAccepted, mixed $mathLitMinimum): string
    {
        if ($mathLitAccepted) {
            return 'UNISA dataset explicitly accepts Mathematical Literacy for this diploma route at the listed alternative threshold.';
        }

        $note = ($mathematics['required_or_preferred'] ?? false) === true
            ? 'UNISA dataset flags Mathematics as required/preferred at group level.'
            : 'UNISA dataset flags Mathematics as required.';

        $note .= ' Mathematical Literacy must not be auto-accepted unless the exact official qualification page allows it.';

        if (is_numeric($mathLitMinimum)) {
            $note .= ' Typical Mathematical Literacy threshold when accepted: '.$mathLitMinimum.'%.';
        }

        return $note;
    }

    /**
     * @param  array<string, mixed>  $programmeRecord
     * @return array<string, mixed>|null
     */
    private function schoolLeavingCertificate(array $programmeRecord): ?array
    {
        $admission = $programmeRecord['admission'] ?? [];
        $certificate = $this->withoutEmptyValues([
            'NSC' => $admission['NSC'] ?? null,
            'Senior Certificate' => $admission['Senior_Certificate'] ?? null,
            'NCV_Level_4' => $admission['NCV_Level_4'] ?? null,
        ]);

        return $certificate === [] ? null : $certificate;
    }

    /**
     * @param  array<string, mixed>  $programmeRecord
     */
    private function notes(array $programmeRecord): string
    {
        $qualification = $programmeRecord['qualification'];
        $admission = $programmeRecord['admission'];
        $math = $admission['mathematics'] ?? [];
        $notes = [];

        foreach ([
            'Display name' => $qualification['display_name'] ?? null,
            'Qualification code' => $qualification['qualification_code'] ?? null,
            'Stream code' => $qualification['stream_code'] ?? null,
            'College' => $qualification['college'] ?? null,
            'Field' => $qualification['field'] ?? null,
            'NQF level' => $qualification['nqf_level'] ?? null,
            'Total credits' => $qualification['total_credits'] ?? null,
            'Maximum completion time' => isset($qualification['maximum_completion_years']) ? $qualification['maximum_completion_years'].' years' : null,
            'SAQA ID' => $qualification['saqa_id'] ?? null,
            'Major' => $qualification['major'] ?? null,
            'Module count' => $qualification['module_count'] ?? null,
            'Presentation' => $qualification['presentation'] ?? null,
            'Module structure note' => $qualification['module_structure_note'] ?? null,
            'Teaching phase' => $qualification['teaching_phase'] ?? null,
            'Purpose' => $qualification['purpose'] ?? null,
        ] as $label => $value) {
            if ($value !== null && $value !== '') {
                $notes[] = $label.': '.$value.'.';
            }
        }

        if (! empty($qualification['study_mode'])) {
            $notes[] = 'Study mode: '.implode(', ', $qualification['study_mode']).'.';
        }

        if (! empty($qualification['special_note'])) {
            $notes[] = 'Special note: '.$qualification['special_note'];
        }

        if (! empty($qualification['input_correction'])) {
            $notes[] = 'Input correction: '.$this->keyValueText($qualification['input_correction']).'.';
        }

        if (! empty($admission['school_leaving_certificate']['NSC'])) {
            $nsc = $admission['school_leaving_certificate']['NSC'];
            $notes[] = 'NSC route: '.$nsc['endorsement'].' with language of teaching and learning at least '.$nsc['language_of_teaching_and_learning']['minimum_percentage'].'%.';
        } elseif (! empty($admission['NSC'])) {
            $nsc = $admission['NSC'];
            $notes[] = 'NSC route: '.$nsc['endorsement'].' with language of teaching and learning around '.$nsc['language_of_teaching_and_learning']['typical_minimum_percentage'].'%; verify exact qualification page.';
        } elseif (! empty($admission['required_endorsement'])) {
            $notes[] = 'NSC route: '.$admission['required_endorsement'].'.';
        }

        if (! empty($admission['school_leaving_certificate']['Senior Certificate'])) {
            $senior = $admission['school_leaving_certificate']['Senior Certificate'];
            $notes[] = 'Senior Certificate route: '.$senior['endorsement'].'; '.$senior['language_requirement'].'.';
        } elseif (! empty($admission['Senior_Certificate'])) {
            $senior = $admission['Senior_Certificate'];
            $notes[] = 'Senior Certificate route: '.$senior['endorsement'].'; '.$senior['language_rule'].'.';
        }

        if (! empty($admission['school_leaving_certificate']['NCV_Level_4']['rule'])) {
            $notes[] = 'NC(V) Level 4 route: '.$admission['school_leaving_certificate']['NCV_Level_4']['rule'].'.';
        } elseif (! empty($admission['NCV_Level_4']['rule'])) {
            $notes[] = 'NC(V) Level 4 route: '.$admission['NCV_Level_4']['rule'].'.';
        }

        if (! empty($admission['language_requirement'])) {
            $notes[] = 'Language requirement: '.$admission['language_requirement'].'.';
        }

        if (! empty($admission['alternative_routes'])) {
            $notes[] = 'Alternative routes: '.implode('; ', $admission['alternative_routes']).'.';
        }

        if (! empty($admission['additional_requirements'])) {
            $notes[] = 'Additional requirements: '.implode('; ', $admission['additional_requirements']).'.';
        }

        if (! empty($admission['additional_selection'])) {
            $notes[] = 'Additional selection: '.$this->keyValueText($admission['additional_selection']).'.';
        }

        if (! empty($admission['selection_note'])) {
            $notes[] = 'Selection note: '.$admission['selection_note'];
        }

        if ($this->requiresMathematics($math)) {
            $mathMinimum = $math['typical_minimum_percentage'] ?? $math['minimum_percentage'] ?? $math['typical_mathematics_minimum_percentage'] ?? null;
            $mathNote = 'Mathematics guidance: Mathematics'.($mathMinimum ? ' '.$mathMinimum.'%' : '').'.';

            if (($math['mathematical_literacy_accepted'] ?? false) === true) {
                $mathNote .= ' Mathematical Literacy accepted at '.$math['mathematical_literacy_minimum_percentage'].'%.';
            } elseif (! empty($math['mathematical_literacy']['typical_minimum_when_accepted'])) {
                $mathNote .= ' Mathematical Literacy is qualification-dependent; typical threshold when accepted is '.$math['mathematical_literacy']['typical_minimum_when_accepted'].'%, and must be verified.';
            } else {
                $mathNote .= ' Mathematical Literacy is qualification-dependent and requires manual review.';
            }

            if (! empty($math['confidence'])) {
                $mathNote .= ' Confidence: '.$math['confidence'].'.';
            }

            $notes[] = $mathNote;
        } elseif (! empty($math['note'])) {
            $notes[] = 'Mathematics guidance: '.$math['note'];
        }

        if (($admission['manual_review_required'] ?? false) === true) {
            $notes[] = 'Manual review is required by the supplied UNISA data.';
        }

        if (($admission['work_integrated_learning'] ?? false) === true) {
            $notes[] = 'Work-integrated learning is listed for this qualification.';
        }

        if (! empty($admission['practical_component']['note'])) {
            $notes[] = 'Practical component: '.$admission['practical_component']['note'];
        }

        if (! empty($programmeRecord['practical_training'])) {
            $notes[] = 'Practical training: '.$this->keyValueText($programmeRecord['practical_training']).'.';
        }

        if (! empty($programmeRecord['professional_registration'])) {
            $notes[] = 'Professional registration: '.$this->keyValueText($programmeRecord['professional_registration']).'.';
        }

        if (! empty($programmeRecord['special_documents'])) {
            $notes[] = 'Special documents: '.implode(', ', $programmeRecord['special_documents']).'.';
        }

        if (! empty($programmeRecord['technology_requirements'])) {
            $notes[] = 'Technology requirements: '.implode(', ', $programmeRecord['technology_requirements']).'.';
        }

        if (! empty($programmeRecord['career_examples'])) {
            $notes[] = 'Career examples: '.implode(', ', $programmeRecord['career_examples']).'.';
        }

        if (! empty($programmeRecord['funding']['note'])) {
            $notes[] = 'Funding note: '.$programmeRecord['funding']['note'].'.';
        }

        if (! empty($programmeRecord['data_quality'])) {
            $notes[] = 'Data quality: '.$this->keyValueText($programmeRecord['data_quality']).'.';
        }

        return collect($notes)
            ->map(fn (string $note): string => trim($note))
            ->filter()
            ->unique()
            ->implode(' ');
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function keyValueText(array $values): string
    {
        return collect($values)
            ->map(fn ($value, string $key): string => Str::headline($key).': '.$this->valueText($value))
            ->implode('; ');
    }

    private function valueText(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn ($item): string => $this->valueText($item), $value));
        }

        return (string) $value;
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
        $base = $base ?: 'university-of-south-africa';
        $slug = $base;
        $suffix = 2;

        while (DB::table('universities')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function nqfLevelId(int $level): ?int
    {
        return DB::table('nqf_levels')
            ->where('level', $level)
            ->value('id');
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
