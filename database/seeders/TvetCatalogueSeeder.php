<?php

namespace Database\Seeders;

use App\Support\TvetColleges;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seed TVET programme catalogues from extracted prospectus/web JSON lists.
 */
class TvetCatalogueSeeder extends Seeder
{
    private const PDF_PATH = __DIR__.'/Tvet/programmes_extracted.json';

    private const WEB_PATH = __DIR__.'/Tvet/programmes_web_extracted.json';

    private const REMAINING_PATH = __DIR__.'/Tvet/programmes_remaining_extracted.json';

    public function run(): void
    {
        $this->seedQualificationTypes();

        $colleges = array_merge(
            $this->loadColleges(self::PDF_PATH),
            $this->loadColleges(self::WEB_PATH),
            $this->loadColleges(self::REMAINING_PATH),
        );

        $gradeIdsByName = $this->gradeIdsByName();
        $subjectIdsByGrade = $this->subjectIdsByGrade();
        $countryId = $this->countryId('South Africa');
        $directory = TvetColleges::directory();
        $assets = $this->assetsByAbbreviation();

        foreach ($colleges as $college) {
            $abbreviation = strtoupper((string) ($college['abbreviation'] ?? ''));
            $programmes = $college['programmes'] ?? [];

            if ($abbreviation === '' || $programmes === []) {
                continue;
            }

            DB::transaction(function () use (
                $college,
                $abbreviation,
                $programmes,
                $gradeIdsByName,
                $subjectIdsByGrade,
                $countryId,
                $directory,
                $assets,
            ): void {
                $meta = $directory[$abbreviation] ?? [];
                $asset = $assets[$abbreviation] ?? [];
                $sourceUrl = (string) ($college['source_url']
                    ?? $asset['prospectus_url']
                    ?? $meta['website']
                    ?? TvetColleges::SOURCE_URL);
                $collegeName = (string) ($college['college'] ?? $meta['name'] ?? $abbreviation.' TVET College');
                $website = (string) ($asset['website'] ?? $meta['website'] ?? null);

                $universityId = $this->universityId(
                    $countryId,
                    $abbreviation,
                    $collegeName,
                    $website,
                    $asset['logo'] ?? null,
                    $sourceUrl,
                    $meta,
                );

                DB::table('university_admission_rules')
                    ->where('university_id', $universityId)
                    ->delete();

                $sharedNotes = [
                    'Application method: Apply via the college online application or campus admissions office. A placement / pre-entry assessment may be compulsory.',
                    'Eligibility explanation: Published minimum school level and subjects are entry floors; colleges may apply placement testing, selection criteria and campus capacity.',
                    'Source note: Programme list extracted from '.$sourceUrl.'. Confirm latest offerings and campus availability with the college.',
                ];

                if (! empty($college['extraction_notes'])) {
                    $sharedNotes[] = 'Extraction note: '.$college['extraction_notes'];
                }

                foreach ($programmes as $raw) {
                    $programme = $this->normaliseProgramme($raw, $abbreviation, $sourceUrl);
                    if ($programme === null) {
                        continue;
                    }

                    $facultyId = $this->facultyId($universityId, $programme['field']);
                    $qualificationTypeId = $this->qualificationTypeId($programme['qualification_type_name']);
                    $requiredGradeName = $programme['required_grade'];
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

                    foreach ($programme['subject_requirements'] as $index => $requirement) {
                        $this->insertRequirement(
                            $qualificationId,
                            $requirement,
                            $index,
                            $requiredGradeName,
                            $gradeIdsByName,
                            $subjectIdsByGrade,
                        );
                    }

                    $this->assignSubjectLevelsRule($universityId, $qualificationId, $requiredGradeId, $collegeName);
                }
            });
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadColleges(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<string, array{logo: string, website: string, prospectus_url: string}>
     */
    private function assetsByAbbreviation(): array
    {
        return TvetCollegeAssetsSeeder::assets();
    }

    private function seedQualificationTypes(): void
    {
        foreach ([
            ['National Certificate Vocational', 'NCV', 4, 5],
            ['NATED', 'NATED', 6, 50],
            ['Pre-Learning Programme', 'PLP', 1, 51],
            ['Occupational Certificate', 'OccCert', null, 52],
            ['Other', null, null, 99],
        ] as [$name, $abbreviation, $nqfLevel, $sortOrder]) {
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

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>|null
     */
    private function normaliseProgramme(array $raw, string $abbreviation, string $sourceUrl): ?array
    {
        $name = trim((string) ($raw['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $type = $this->normaliseType((string) ($raw['type'] ?? 'Other'));
        $field = $this->facultyName((string) ($raw['field'] ?? 'General'), $type, $name);
        $requiredGrade = $this->requiredGrade($type, (string) ($raw['required_grade_guess'] ?? ''));
        $engineering = $this->isEngineering($field, $name);

        return [
            'id' => Str::slug(strtolower($abbreviation).'-'.$type.'-'.$name),
            'name' => $name,
            'type' => $type,
            'qualification_type_name' => $this->qualificationTypeName($type),
            'qualification_abbreviation' => $this->qualificationAbbreviation($type),
            'nqf_level' => $this->nqfLevelForType($type),
            'field' => $field,
            'required_grade' => $requiredGrade,
            'duration_years' => $type === 'NC(V)' ? 3.0 : null,
            'source_url' => $sourceUrl,
            'subject_requirements' => $this->defaultSubjectRequirements($type, $engineering, $requiredGrade),
            'notes' => array_values(array_filter([
                is_string($raw['notes'] ?? null) ? trim((string) $raw['notes']) : null,
                is_string($raw['required_grade_guess'] ?? null) ? 'Published entry guidance: '.trim((string) $raw['required_grade_guess']) : null,
            ])),
        ];
    }

    private function normaliseType(string $type): string
    {
        $normalised = strtoupper(trim($type));

        return match (true) {
            str_contains($normalised, 'NCV') || $normalised === 'NC(V)' => 'NC(V)',
            str_contains($normalised, 'NATED') || str_contains($normalised, 'REPORT 191') || str_contains($normalised, 'N4') => 'NATED',
            str_contains($normalised, 'PLP') || str_contains($normalised, 'PRE-LEARN') => 'PLP',
            str_contains($normalised, 'OCC') || str_contains($normalised, 'OCCUPATIONAL') => 'OccCert',
            default => 'Other',
        };
    }

    private function facultyName(string $field, string $type, string $name): string
    {
        $field = trim($field);
        if ($field !== '' && ! in_array(strtolower($field), ['general', 'services', 'other'], true)) {
            if (str_starts_with(strtolower($field), 'school of')) {
                return $field;
            }

            return match (true) {
                str_contains(strtolower($field), 'engine') => 'School of Engineering',
                str_contains(strtolower($field), 'business') => 'School of Business',
                str_contains(strtolower($field), 'hospital') || str_contains(strtolower($field), 'tour') || str_contains(strtolower($field), 'agric') => 'School of Services',
                default => $field,
            };
        }

        if ($this->isEngineering($field, $name)) {
            return 'School of Engineering';
        }

        if (preg_match('/hospitality|tourism|agriculture|education|safety|primary health|hair|beauty|clothing/i', $name)) {
            return 'School of Services';
        }

        return 'School of Business';
    }

    private function isEngineering(string $field, string $name): bool
    {
        return (bool) preg_match('/engine|electrical|mechanical|civil|mechatron|information technology|computer science|building construction|plumbing|welding|boilermaker|fitting/i', $field.' '.$name);
    }

    private function requiredGrade(string $type, string $guess): string
    {
        if (preg_match('/grade\s*12|nsc|senior certificate|n3/i', $guess) && ! preg_match('/grade\s*9/i', $guess)) {
            return 'Grade 12';
        }

        if (preg_match('/grade\s*11/i', $guess)) {
            return 'Grade 11';
        }

        if (preg_match('/grade\s*10/i', $guess)) {
            return 'Grade 10';
        }

        return match ($type) {
            'NATED' => preg_match('/n1|n2|n3|grade\s*9/i', $guess) ? 'Grade 9' : 'Grade 12',
            'OccCert' => preg_match('/grade\s*12/i', $guess) ? 'Grade 12' : (preg_match('/grade\s*11/i', $guess) ? 'Grade 11' : 'Grade 9'),
            default => 'Grade 9',
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultSubjectRequirements(string $type, bool $engineering, string $requiredGrade): array
    {
        $requirements = [
            [
                'type' => 'one_of',
                'label' => 'English Home Language or English First Additional Language',
                'subjects' => [
                    ['subject' => 'English Home Language', 'minimum_mark' => null],
                    ['subject' => 'English First Additional Language', 'minimum_mark' => null],
                ],
            ],
        ];

        if ($type === 'NC(V)' && $engineering) {
            $requirements[] = [
                'type' => 'required',
                'subject' => 'Mathematics',
                'minimum_mark' => null,
                'note' => 'Engineering NC(V) typically requires Mathematics (not Mathematical Literacy). Confirm with the college.',
            ];
        } elseif ($type === 'NATED' && $engineering && $requiredGrade === 'Grade 12') {
            $requirements[] = [
                'type' => 'required',
                'subject' => 'Mathematics',
                'minimum_mark' => null,
                'note' => 'Engineering N4–N6 pathways commonly require Mathematics and Physical Sciences or an N3 equivalent.',
            ];
            $requirements[] = [
                'type' => 'required',
                'subject' => 'Physical Sciences',
                'minimum_mark' => null,
                'note' => 'Confirm exact subject combination with the college prospectus.',
            ];
        }

        return $requirements;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function universityId(
        int $countryId,
        string $abbreviation,
        string $name,
        ?string $website,
        ?string $logo,
        string $sourceUrl,
        array $meta,
    ): int {
        $existing = DB::table('universities')->where('abbreviation', $abbreviation)->first();

        $values = [
            'country_id' => $countryId,
            'name' => $existing?->name ?: $name,
            'slug' => $existing?->slug ?: $this->uniqueUniversitySlug(Str::slug($name) ?: strtolower($abbreviation)),
            'updated_at' => now(),
            'created_at' => now(),
        ];

        if ($website) {
            $values['website'] = $website;
        }

        $resolvedLogo = UniversityLogoSeeder::logoFor($abbreviation, $existing?->logo) ?? $logo;
        if ($resolvedLogo) {
            $values['logo'] = $resolvedLogo;
        }

        if (Schema::hasColumn('universities', 'contact_source_url')) {
            $values['contact_source_url'] = $sourceUrl;
            if (! empty($meta['physical_address'])) {
                $values['physical_address'] = $meta['physical_address'];
            }
            if (! empty($meta['contact_phone'])) {
                $values['contact_phone'] = $meta['contact_phone'];
            }
            if (! empty($meta['contact_email'])) {
                $values['contact_email'] = $meta['contact_email'];
            }
        }

        DB::table('universities')->updateOrInsert(
            ['abbreviation' => $abbreviation],
            $values,
        );

        return (int) DB::table('universities')->where('abbreviation', $abbreviation)->value('id');
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
     * @param  list<string>  $sharedNotes
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
                'nqf_level_id' => $programme['nqf_level'] === null ? null : $this->nqfLevelId((int) $programme['nqf_level']),
                'required_grade_id' => $requiredGradeId,
                'slug' => $existing?->slug ?: Str::slug((string) $programme['id']),
                'abbreviation' => $programme['qualification_abbreviation'],
                'duration_years' => $programme['duration_years'],
                'aps_required' => null,
                'aggregate_average_required' => null,
                'admission_score_required' => null,
                'minimum_pass_type' => null,
                'is_selection_programme' => true,
                'notes' => $this->notes($programme, $sharedNotes),
                'source_url' => $programme['source_url'],
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
     * @param  list<string>  $sharedNotes
     */
    private function notes(array $programme, array $sharedNotes): string
    {
        $notes = $sharedNotes;
        $notes[] = 'Faculty: '.$programme['field'].'.';

        foreach ($programme['notes'] as $note) {
            if (is_string($note) && trim($note) !== '') {
                $notes[] = trim($note);
            }
        }

        $notes[] = 'Minimum requirements do not guarantee admission; placement testing, selection criteria and campus capacity may apply.';

        return collect($notes)
            ->map(fn (string $note): string => trim($note))
            ->filter()
            ->unique()
            ->implode(' ');
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
        $subjectName = match (trim($subjectName)) {
            'Physical Science', 'Science' => 'Physical Sciences',
            default => trim($subjectName),
        };
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

    private function assignSubjectLevelsRule(
        int $universityId,
        int $qualificationId,
        ?int $requiredGradeId,
        string $collegeName,
    ): void {
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
                'notes' => $collegeName.' matching is based on published school/NQF entry level, subjects, placement testing and college selection criteria rather than APS.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function qualificationTypeName(string $type): string
    {
        return match ($type) {
            'NC(V)' => 'National Certificate Vocational',
            'NATED' => 'NATED',
            'PLP' => 'Pre-Learning Programme',
            'OccCert' => 'Occupational Certificate',
            default => 'Other',
        };
    }

    private function qualificationAbbreviation(string $type): ?string
    {
        return match ($type) {
            'NC(V)' => 'NCV',
            'NATED' => 'NATED',
            'PLP' => 'PLP',
            'OccCert' => 'OccCert',
            default => null,
        };
    }

    private function nqfLevelForType(string $type): ?int
    {
        return match ($type) {
            'NC(V)' => 4,
            'NATED' => 6,
            'PLP' => 1,
            default => null,
        };
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

    private function nqfLevelId(int $level): ?int
    {
        return DB::table('nqf_levels')->where('level', $level)->value('id');
    }

    private function qualificationTypeId(string $name): int
    {
        return (int) DB::table('qualification_types')->where('name', $name)->value('id');
    }

    private function uniqueUniversitySlug(string $base): string
    {
        $base = $base ?: 'tvet-college';
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
}
