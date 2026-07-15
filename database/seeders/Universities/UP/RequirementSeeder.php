<?php

namespace Database\Seeders\Universities\UP;

use Database\Seeders\UniversityLogoSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RequirementSeeder extends Seeder
{
    /**
     * Seed the University of Pretoria undergraduate admission requirements.
     */
    public function run(): void
    {
        $path = database_path('seeders/Universities/UP/requirements.json');
        $universities = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($universities): void {
            $grade12SubjectIds = $this->grade12SubjectIds();

            foreach ($universities as $universityData) {
                $countryId = $this->countryId($universityData['country']);
                $universityId = $this->universityId($universityData, $countryId);
                $this->assignAdmissionRule($universityId);

                foreach ($universityData['faculties'] as $facultyData) {
                    $facultyId = $this->facultyId($facultyData, $universityId);

                    foreach ($facultyData['qualifications'] as $qualificationData) {
                        $qualificationTypeId = $this->qualificationTypeId($qualificationData['qualification_type']);
                        $qualificationId = $this->qualificationId(
                            $qualificationData,
                            $universityId,
                            $facultyId,
                            $qualificationTypeId
                        );

                        DB::table('qualification_subject_requirements')
                            ->where('qualification_id', $qualificationId)
                            ->delete();

                        foreach (($qualificationData['subject_requirements'] ?? []) as $index => $requirementData) {
                            $hasAlternative = isset($requirementData['alternative_subject']);
                            $requirementGroup = $hasAlternative
                                ? 'requirement_'.$qualificationId.'_'.($index + 1)
                                : null;

                            $this->insertSubjectRequirement(
                                $qualificationId,
                                $requirementData['subject'],
                                $requirementData['minimum_level'] ?? null,
                                'required',
                                $requirementGroup,
                                $grade12SubjectIds
                            );

                            if ($hasAlternative) {
                                $this->insertSubjectRequirement(
                                    $qualificationId,
                                    $requirementData['alternative_subject'],
                                    $requirementData['alternative_minimum_level'] ?? null,
                                    'alternative',
                                    $requirementGroup,
                                    $grade12SubjectIds
                                );
                            }
                        }
                    }
                }
            }
        });
    }

    private function countryId(string $countryName): int
    {
        $now = now();

        DB::table('countries')->updateOrInsert(
            ['name' => $countryName],
            ['updated_at' => $now, 'created_at' => $now],
        );

        return (int) DB::table('countries')->where('name', $countryName)->value('id');
    }

    private function universityId(array $universityData, int $countryId): int
    {
        $now = now();
        $existing = DB::table('universities')
            ->where('abbreviation', $universityData['abbreviation'])
            ->first();

        DB::table('universities')->updateOrInsert(
            ['abbreviation' => $universityData['abbreviation']],
            [
                'country_id' => $countryId,
                'name' => $universityData['university'],
                'logo' => $universityData['logo']
                    ?? UniversityLogoSeeder::logoFor($universityData['abbreviation'], $existing?->logo),
                'website' => $universityData['website'] ?? null,
                'default_closing_month' => $universityData['default_closing_month'] ?? null,
                'default_closing_day' => $universityData['default_closing_day'] ?? null,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        return (int) DB::table('universities')
            ->where('abbreviation', $universityData['abbreviation'])
            ->value('id');
    }

    private function facultyId(array $facultyData, int $universityId): int
    {
        $now = now();

        DB::table('faculties')->updateOrInsert(
            ['university_id' => $universityId, 'name' => $facultyData['name']],
            [
                'closing_month' => $facultyData['closing_month'] ?? null,
                'closing_day' => $facultyData['closing_day'] ?? null,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        return (int) DB::table('faculties')
            ->where('university_id', $universityId)
            ->where('name', $facultyData['name'])
            ->value('id');
    }

    private function qualificationTypeId(string $name): int
    {
        $now = now();

        DB::table('qualification_types')->updateOrInsert(
            ['name' => $name],
            [
                'abbreviation' => null,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        return (int) DB::table('qualification_types')
            ->where('name', $name)
            ->value('id');
    }

    private function qualificationId(
        array $qualificationData,
        int $universityId,
        int $facultyId,
        int $qualificationTypeId
    ): int {
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
                'abbreviation' => $qualificationData['abbreviation'] ?? null,
                'duration_years' => $qualificationData['duration_years'] ?? null,
                'aps_required' => $qualificationData['aps_required'] ?? null,
                'admission_score_required' => $qualificationData['aps_required'] ?? null,
                'closing_month' => $qualificationData['closing_month'] ?? null,
                'closing_day' => $qualificationData['closing_day'] ?? null,
                'is_selection_programme' => $qualificationData['is_selection_programme'] ?? false,
                'notes' => $qualificationData['notes'] ?? null,
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

    private function assignAdmissionRule(int $universityId): void
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
                'qualification_id' => null,
                'admission_rule_id' => $admissionRuleId,
            ],
            [
                'grade_id' => $this->grade12Id(),
                'priority' => 100,
                'is_default' => true,
                'overrides' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
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

    private function insertSubjectRequirement(
        int $qualificationId,
        string $subjectName,
        ?int $minimumLevel,
        string $requirementType,
        ?string $requirementGroup,
        array $grade12SubjectIds
    ): void {
        DB::table('qualification_subject_requirements')->insert([
            'qualification_id' => $qualificationId,
            'subject_id' => $grade12SubjectIds[$subjectName] ?? null,
            'grade_id' => $this->grade12Id(),
            'subject_name' => $subjectName,
            'minimum_mark' => $minimumLevel !== null ? $this->minimumMarkForLevel($minimumLevel) : null,
            'aps_level_required' => $minimumLevel,
            'requirement_type' => $requirementType,
            'requirement_group' => $requirementGroup,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function minimumMarkForLevel(int $level): ?int
    {
        return match ($level) {
            7 => 80,
            6 => 70,
            5 => 60,
            4 => 50,
            3 => 40,
            2 => 30,
            1 => 0,
            default => null,
        };
    }

    private function grade12SubjectIds(): array
    {
        $subjects = DB::table('subjects')
            ->join('grades', 'grades.id', '=', 'subjects.grade_id')
            ->join('curriculums', 'curriculums.id', '=', 'subjects.curriculum_id')
            ->where('curriculums.abbreviation', 'CAPS')
            ->where('grades.name', 'Grade 12')
            ->select('subjects.id', 'subjects.name')
            ->get();

        return $subjects->pluck('id', 'name')->all();
    }

    private function grade12Id(): ?int
    {
        return DB::table('grades')
            ->join('curriculums', 'curriculums.id', '=', 'grades.curriculum_id')
            ->where('curriculums.abbreviation', 'CAPS')
            ->where('grades.name', 'Grade 12')
            ->value('grades.id');
    }
}
