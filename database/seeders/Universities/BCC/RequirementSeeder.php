<?php

namespace Database\Seeders\Universities\BCC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequirementSeeder extends Seeder
{
    private const PROGRAMMES_PATH = __DIR__.'/bcc_ncv_programmes.json';

    private const PROGRAMMES_SOURCE_URL = 'https://bccollege.co.za/national-certificate-vocational-ncv/';

    private const WEBSITE = 'https://bccollege.co.za/';

    public function run(): void
    {
        $data = json_decode(file_get_contents(self::PROGRAMMES_PATH), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data): void {
            $this->seedQualificationTypes();

            $gradeIdsByName = $this->gradeIdsByName();
            $subjectIdsByGrade = $this->subjectIdsByGrade();
            $countryId = $this->countryId($data['institution']['country'] ?? 'South Africa');
            $universityId = $this->universityId($countryId, $data['institution'] ?? []);
            $sharedNotes = collect($data['shared_qualification_notes'] ?? [])
                ->filter(fn ($note): bool => is_string($note) && trim($note) !== '')
                ->values()
                ->all();

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
                    $requiredGradeId,
                    $sharedNotes,
                );

                DB::table('qualification_subject_requirements')
                    ->where('qualification_id', $qualificationId)
                    ->delete();

                DB::table('qualification_admission_score_variants')
                    ->where('qualification_id', $qualificationId)
                    ->delete();

                $requirements = $this->subjectRequirementsFor($programme);
                $requirementGradeName = $requiredGradeName ?? 'Grade 9';

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
        DB::table('qualification_types')->updateOrInsert(
            ['name' => 'National Certificate Vocational'],
            [
                'abbreviation' => 'NCV',
                'nqf_level_id' => $this->nqfLevelId(4),
                'sort_order' => 5,
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

        return (int) DB::table('countries')->where('name', $countryName)->value('id');
    }

    /**
     * @param  array<string, mixed>  $institution
     */
    private function universityId(int $countryId, array $institution): int
    {
        $abbreviation = (string) ($institution['abbreviation'] ?? 'BCC');
        $name = (string) ($institution['name'] ?? 'Buffalo City TVET College');
        $existing = DB::table('universities')
            ->where('abbreviation', $abbreviation)
            ->first();

        DB::table('universities')->updateOrInsert(
            ['abbreviation' => $abbreviation],
            [
                'country_id' => $countryId,
                'name' => $name,
                'slug' => $existing?->slug ?: $this->uniqueUniversitySlug(Str::slug($name) ?: 'buffalo-city-tvet-college'),
                'website' => self::WEBSITE,
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
     * @param  array<int, string>  $sharedNotes
     */
    private function qualificationId(
        array $programme,
        int $universityId,
        int $facultyId,
        int $qualificationTypeId,
        ?int $requiredGradeId,
        array $sharedNotes,
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
                'abbreviation' => 'NCV',
                'duration_years' => $this->durationYears($programme['duration'] ?? null),
                'aps_required' => null,
                'aggregate_average_required' => null,
                'admission_score_required' => null,
                'minimum_pass_type' => null,
                'is_selection_programme' => true,
                'notes' => $this->notes($programme, $sharedNotes),
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
     * @return array<int, array<string, mixed>>
     */
    private function subjectRequirementsFor(array $programme): array
    {
        $requirements = [];

        foreach (($programme['entry_points'][0]['required_school_subjects'] ?? []) as $subject) {
            $subjectName = $this->normalisedSubjectName((string) $subject);

            if ($this->isEnglishSubject($subjectName)) {
                $requirements[] = $this->englishRequirement();

                continue;
            }

            if ($subjectName === 'Mathematics') {
                $requirements[] = $this->required(
                    'Mathematics',
                    null,
                    'Buffalo City TVET College requires pure Mathematics for Engineering NC(V) programmes; Mathematical Literacy is not accepted.',
                );

                continue;
            }

            $requirements[] = $this->required($subjectName);
        }

        if (($programme['requires_pure_mathematics'] ?? false) === true
            && ! collect($requirements)->contains(fn (array $requirement): bool => ($requirement['subject'] ?? null) === 'Mathematics')
        ) {
            $requirements[] = $this->required(
                'Mathematics',
                null,
                'Buffalo City TVET College requires pure Mathematics for Engineering NC(V) programmes; Mathematical Literacy is not accepted.',
            );
        }

        return $requirements;
    }

    /**
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
    private function englishRequirement(null|int|float $minimumMark = null): array
    {
        return $this->oneOf([
            ['subject' => 'English Home Language', 'minimum_mark' => $minimumMark],
            ['subject' => 'English First Additional Language', 'minimum_mark' => $minimumMark],
        ], 'English Home Language or English First Additional Language');
    }

    private function isEnglishSubject(string $subject): bool
    {
        $normalised = strtolower(trim($subject));

        return $normalised === 'english' || str_starts_with($normalised, 'english ');
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
        array $subjectIdsByGrade,
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
        ?string $note = null,
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
                'notes' => 'BCC matching is based on published school/NQF entry level, subjects, placement testing and college selection criteria rather than APS.',
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
            default => (string) ($programme['qualification_type'] ?? 'National Certificate Vocational'),
        };
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function facultyName(array $programme): string
    {
        return (string) ($programme['field'] ?? 'General Programmes');
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function requiredGradeName(array $programme): string
    {
        return 'Grade 9';
    }

    /**
     * @param  array<string, mixed>  $programme
     */
    private function qualificationNqfLevelId(array $programme): ?int
    {
        if (! empty($programme['nqf_levels']) && is_array($programme['nqf_levels'])) {
            return $this->nqfLevelId(max(array_map('intval', $programme['nqf_levels'])));
        }

        return $this->nqfLevelId(4);
    }

    private function nqfLevelId(int $level): ?int
    {
        return DB::table('nqf_levels')->where('level', $level)->value('id');
    }

    /**
     * @param  array<string, mixed>  $programme
     * @param  array<int, string>  $sharedNotes
     */
    private function notes(array $programme, array $sharedNotes): string
    {
        $notes = $sharedNotes;

        if (! empty($programme['campuses'])) {
            $notes[] = 'Campus: '.implode(', ', $programme['campuses']).'.';
        }

        if (! empty($programme['field'])) {
            $notes[] = 'Faculty: '.$programme['field'].'.';
        }

        $entryRequirements = $this->entryRequirementsText($programme);

        if ($entryRequirements !== null) {
            $notes[] = $entryRequirements;
        }

        if (($programme['requires_pure_mathematics'] ?? false) === true) {
            $notes[] = 'Academic requirement: Pure Mathematics is required for this Engineering NC(V) programme; Mathematical Literacy is not accepted.';
        }

        $notes[] = 'Selection format: Applicants must pass the Buffalo City TVET College numeracy and English placement tests at 50% each before receiving an application form.';
        $notes[] = 'Minimum requirements do not guarantee admission; BCC placement testing, selection criteria and campus capacity may apply.';

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

            if (! empty($entryPoint['required_prior_qualification'])) {
                $sentences[] = 'Progression'.($target ? ' to '.$target : '').': '.$entryPoint['required_prior_qualification'].'.';
            }
        }

        return $sentences === [] ? null : 'Admission requirement: '.implode(' ', $sentences);
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

        return null;
    }

    private function normalisedSubjectName(string $subject): string
    {
        return match (trim($subject)) {
            'Physical Science', 'Science' => 'Physical Sciences',
            default => trim($subject),
        };
    }

    private function uniqueUniversitySlug(string $base): string
    {
        $base = $base ?: 'buffalo-city-tvet-college';
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
