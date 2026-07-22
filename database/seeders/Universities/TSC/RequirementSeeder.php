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

                $this->assignSubjectLevelsRule($universityId, $qualificationId, $requiredGradeId);
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
                'aps_required' => null,
                'aggregate_average_required' => null,
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
                'notes' => 'TSC programme pages do not publish APS requirements; matching should use qualification level, confirmed subject rules and manual review where requirements are not extractable.',
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

        if (! empty($admission['manual_review_required_when'])) {
            $notes[] = 'Manual review required when: '.implode('; ', $admission['manual_review_required_when']).'.';
        }

        $notes[] = 'APS is not listed as a requirement in the supplied TSC programme data.';

        return collect($notes)
            ->map(fn (string $note): string => trim($note))
            ->filter()
            ->unique()
            ->implode(' ');
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
