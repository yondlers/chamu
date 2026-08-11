<?php

namespace Database\Seeders\Universities\EWC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequirementSeeder extends Seeder
{
    private const PROGRAMMES_PATH = __DIR__.'/ewc_occupational_programmes.json';

    private const PROGRAMMES_SOURCE_URL = 'https://www.ewc.edu.za/docs/Occupational%20Ministerial%20Programmes%20Admission%20Requirements.pdf';

    private const WEBSITE = 'https://www.ewc.edu.za/';

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
                $facultyId = $this->facultyId($universityId, (string) ($programme['field'] ?? 'Occupational Ministerial Programmes'));
                $qualificationTypeId = $this->qualificationTypeId('Occupational Certificate');
                $requiredGradeName = (string) ($programme['required_grade'] ?? 'Grade 9');
                $requiredGradeId = $gradeIdsByName[$requiredGradeName] ?? null;
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

                foreach ($this->subjectRequirementsFor($programme) as $index => $requirement) {
                    $this->insertRequirement(
                        $qualificationId,
                        $requirement,
                        $index,
                        $requiredGradeName,
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
            ['name' => 'Occupational Certificate'],
            [
                'abbreviation' => 'OccCert',
                'nqf_level_id' => null,
                'sort_order' => 52,
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
        $abbreviation = (string) ($institution['abbreviation'] ?? 'EWC');
        $name = (string) ($institution['name'] ?? 'Ekurhuleni West TVET College');
        $existing = DB::table('universities')
            ->where('abbreviation', $abbreviation)
            ->first();

        DB::table('universities')->updateOrInsert(
            ['abbreviation' => $abbreviation],
            [
                'country_id' => $countryId,
                'name' => $name,
                'slug' => $existing?->slug ?: $this->uniqueUniversitySlug(Str::slug($name) ?: 'ekurhuleni-west-tvet-college'),
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
                'nqf_level_id' => null,
                'required_grade_id' => $requiredGradeId,
                'slug' => $existing?->slug ?: Str::slug((string) ($programme['id'] ?? $name)),
                'abbreviation' => 'OccCert',
                'duration_years' => null,
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

        foreach (($programme['subject_requirements'] ?? []) as $requirement) {
            if (($requirement['type'] ?? null) === 'one_of') {
                $requirements[] = [
                    'type' => 'one_of',
                    'label' => $requirement['label'] ?? 'One of the listed subjects',
                    'subjects' => $requirement['subjects'] ?? [],
                ];

                continue;
            }

            $subjectName = $this->normalisedSubjectName((string) ($requirement['subject'] ?? ''));

            if ($this->isEnglishSubject($subjectName)) {
                $requirements[] = $this->englishRequirement($requirement['minimum_mark'] ?? 40);

                continue;
            }

            $requirements[] = $this->required(
                $subjectName,
                $requirement['minimum_mark'] ?? null,
                $requirement['note'] ?? null,
            );
        }

        return $requirements;
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
    private function englishRequirement(null|int|float $minimumMark = 40): array
    {
        return [
            'type' => 'one_of',
            'label' => 'English Home Language or English First Additional Language',
            'subjects' => [
                ['subject' => 'English Home Language', 'minimum_mark' => $minimumMark],
                ['subject' => 'English First Additional Language', 'minimum_mark' => $minimumMark],
            ],
        ];
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
                'notes' => 'EWC matching is based on published school/NQF entry level, subjects and college selection notes rather than APS.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $programme
     * @param  array<int, string>  $sharedNotes
     */
    private function notes(array $programme, array $sharedNotes): string
    {
        $notes = $sharedNotes;

        $notes[] = 'Faculty: '.($programme['field'] ?? 'Occupational Ministerial Programmes').'.';
        $notes[] = 'Admission requirement: Entry requirement '.$programme['entry_summary'].'.';

        if (! empty($programme['accepted_equivalents'])) {
            $notes[] = 'Alternative routes: Accepted equivalents include '.implode(', ', $programme['accepted_equivalents']).'.';
        }

        if (($programme['mathematics_policy'] ?? null) === 'mathematics_and_technology') {
            $notes[] = 'Academic requirement: Grade 9 applicants need Mathematics and Technology. Grade 10/11 applicants for this programme are assessed on Mathematics and Science.';
        }

        if (($programme['mathematics_policy'] ?? null) === 'mathematics_only') {
            $notes[] = 'Academic requirement: Mathematics is required. Mathematical Literacy is not the listed requirement for Automotive Motor Mechanic.';
        }

        $notes[] = 'Minimum requirements do not guarantee admission; EWC selection and capacity rules may still apply.';

        return collect($notes)
            ->map(fn (string $note): string => trim($note))
            ->filter()
            ->unique()
            ->implode(' ');
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
        $base = $base ?: 'ekurhuleni-west-tvet-college';
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
