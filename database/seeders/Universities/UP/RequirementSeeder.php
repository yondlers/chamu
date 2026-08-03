<?php

namespace Database\Seeders\Universities\UP;

use Database\Seeders\Universities\SeedsCareerRelationships;
use Database\Seeders\UniversityLogoSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RequirementSeeder extends Seeder
{
    use SeedsCareerRelationships;

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

                $this->seedUndergraduateCatalogue($universityId);
            }
        });
    }

    private function seedUndergraduateCatalogue(int $universityId): void
    {
        $path = database_path('seeders/Universities/UP/undergraduate_catalogue.json');

        if (! file_exists($path)) {
            return;
        }

        $catalogue = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        foreach (($catalogue['programmes'] ?? []) as $programmeData) {
            if (! $this->isUndergraduateSource($programmeData['source_url'] ?? null)) {
                continue;
            }

            $facultyId = $this->catalogueFacultyId($programmeData['faculty'], $universityId);
            $qualificationTypeId = $this->qualificationTypeId($programmeData['qualification_type']);

            $qualificationId = $this->catalogueQualificationId($programmeData, $universityId, $facultyId, $qualificationTypeId);

            $this->syncCareerRelationships(
                $qualificationId,
                $programmeData,
                $programmeData['source_url'] ?? null,
            );
        }
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

        $values = [
            'country_id' => $countryId,
            'name' => $universityData['university'],
            'logo' => $universityData['logo']
                ?? UniversityLogoSeeder::logoFor($universityData['abbreviation'], $existing?->logo),
            'website' => $universityData['website'] ?? null,
            'default_closing_month' => $universityData['default_closing_month'] ?? null,
            'default_closing_day' => $universityData['default_closing_day'] ?? null,
            'updated_at' => $now,
            'created_at' => $now,
        ];

        if (Schema::hasColumn('universities', 'slug')) {
            $values['slug'] = $this->universitySlug($universityData, $existing);
        }

        DB::table('universities')->updateOrInsert(
            ['abbreviation' => $universityData['abbreviation']],
            $values,
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

    private function catalogueFacultyId(string $name, int $universityId): int
    {
        $now = now();

        DB::table('faculties')->updateOrInsert(
            ['university_id' => $universityId, 'name' => $name],
            ['updated_at' => $now, 'created_at' => $now],
        );

        return (int) DB::table('faculties')
            ->where('university_id', $universityId)
            ->where('name', $name)
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
        $existing = DB::table('qualifications')
            ->where('university_id', $universityId)
            ->where('faculty_id', $facultyId)
            ->where('name', $qualificationData['name'])
            ->first();

        $values = [
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
        ];

        if (Schema::hasColumn('qualifications', 'slug')) {
            $values['slug'] = $this->qualificationSlug($qualificationData, $universityId, $existing);
        }

        DB::table('qualifications')->updateOrInsert(
            [
                'university_id' => $universityId,
                'faculty_id' => $facultyId,
                'name' => $qualificationData['name'],
            ],
            $values,
        );

        return (int) DB::table('qualifications')
            ->where('university_id', $universityId)
            ->where('faculty_id', $facultyId)
            ->where('name', $qualificationData['name'])
            ->value('id');
    }

    private function catalogueQualificationId(
        array $qualificationData,
        int $universityId,
        int $facultyId,
        int $qualificationTypeId
    ): int {
        $now = now();
        $existing = DB::table('qualifications')
            ->where('university_id', $universityId)
            ->where('faculty_id', $facultyId)
            ->where('name', $qualificationData['name'])
            ->first();

        $values = [
            'qualification_type_id' => $qualificationTypeId,
            'nqf_level_id' => $this->qualificationNqfLevelId($qualificationData, $qualificationTypeId),
            'abbreviation' => $qualificationData['abbreviation'] ?? $existing?->abbreviation,
            'duration_years' => $qualificationData['duration_years'] ?? $existing?->duration_years,
            'aps_required' => $existing?->aps_required,
            'admission_score_required' => $existing?->admission_score_required,
            'closing_month' => $qualificationData['closing_month'] ?? $existing?->closing_month,
            'closing_day' => $qualificationData['closing_day'] ?? $existing?->closing_day,
            'is_selection_programme' => $existing?->is_selection_programme ?? false,
            'notes' => $this->catalogueNotes($qualificationData, $existing?->aps_required !== null ? $existing?->notes : null),
            'source_url' => $qualificationData['source_url'] ?? $existing?->source_url,
            'updated_at' => $now,
            'created_at' => $now,
        ];

        if (Schema::hasColumn('qualifications', 'slug')) {
            $values['slug'] = $this->qualificationSlug($qualificationData, $universityId, $existing);
        }

        DB::table('qualifications')->updateOrInsert(
            [
                'university_id' => $universityId,
                'faculty_id' => $facultyId,
                'name' => $qualificationData['name'],
            ],
            $values,
        );

        return (int) DB::table('qualifications')
            ->where('university_id', $universityId)
            ->where('faculty_id', $facultyId)
            ->where('name', $qualificationData['name'])
            ->value('id');
    }

    private function universitySlug(array $universityData, ?object $existing): string
    {
        if ($existing?->slug) {
            return $existing->slug;
        }

        $base = Str::slug((string) ($universityData['slug'] ?? $universityData['university'])) ?: 'university';
        $slug = $base;
        $suffix = 2;

        while (DB::table('universities')
            ->where('slug', $slug)
            ->when($existing?->id, fn ($query) => $query->where('id', '<>', $existing->id))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function qualificationSlug(array $qualificationData, int $universityId, ?object $existing): string
    {
        if ($existing?->slug) {
            return $existing->slug;
        }

        $base = Str::slug((string) ($qualificationData['slug'] ?? $qualificationData['name'])) ?: 'qualification';
        $slug = $base;
        $suffix = 2;

        while (DB::table('qualifications')
            ->where('university_id', $universityId)
            ->where('slug', $slug)
            ->when($existing?->id, fn ($query) => $query->where('id', '<>', $existing->id))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function catalogueNotes(array $qualificationData, ?string $baseNotes): ?string
    {
        $notes = [];

        if ($baseNotes !== null && trim($baseNotes) !== '') {
            $notes[] = trim($baseNotes);
        }

        if (($qualificationData['official_name'] ?? null) && $qualificationData['official_name'] !== $qualificationData['name']) {
            $notes[] = 'UP listing title: '.$qualificationData['official_name'].'.';
        }

        if (($qualificationData['programme_code'] ?? null) !== null) {
            $notes[] = 'Programme code: '.$qualificationData['programme_code'].'.';
        }

        if (($qualificationData['closing_month'] ?? null) !== null && ($qualificationData['closing_day'] ?? null) !== null) {
            $notes[] = 'Closing-date context: UP\'s 2027 undergraduate listing shows South African applicants closing on '.$this->dateLabel((int) $qualificationData['closing_month'], (int) $qualificationData['closing_day']).'. Confirm the active intake year and late-application availability on the official source page.';
        }

        $notes[] = 'Application planning: use the official University of Pretoria application channels for applications, fees and uploaded documents. Chamu is an independent guide and cannot guarantee admission, placement or funding.';

        return $notes === [] ? null : implode("\n", array_values(array_unique($notes)));
    }

    private function dateLabel(int $month, int $day): string
    {
        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        return $day.' '.($months[$month] ?? '');
    }

    private function isUndergraduateSource(?string $sourceUrl): bool
    {
        if ($sourceUrl === null) {
            return false;
        }

        return str_contains($sourceUrl, '/programmes/undergraduate/')
            || $sourceUrl === 'https://www.up.ac.za/node/67483';
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
