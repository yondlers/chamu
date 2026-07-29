<?php

namespace Tests\Feature;

use Database\Seeders\AdmissionRuleSeeder;
use Database\Seeders\CapsSubjectSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\NqfLevelSeeder;
use Database\Seeders\QualificationTypeSeeder;
use Database\Seeders\SubjectCategorySeeder;
use Database\Seeders\Universities\VC\RequirementSeeder as VcRequirementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VcRequirementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_emeris_creative_development_seeds_official_requirements_on_legacy_url(): void
    {
        $this->seedVcRequirements();

        $vc = DB::table('universities')->where('abbreviation', 'VC')->first();
        $grade12Id = DB::table('grades')->where('name', 'Grade 12')->value('id');
        $nqfLevel5Id = DB::table('nqf_levels')->where('level', 5)->value('id');

        $this->assertNotNull($vc);
        $this->assertSame('Emeris', $vc->name);
        $this->assertSame('the-iies-varsity-college', $vc->slug);
        $this->assertSame('https://www.emeris.ac.za/', $vc->website);
        $this->assertSame(55, DB::table('qualifications')->where('university_id', $vc->id)->count());

        $creativeDevelopment = $this->qualification('Higher Certificate in Creative Development', 'Higher Certificate');

        $this->assertSame('higher-certificate-in-art-and-design', $creativeDevelopment->slug);
        $this->assertSame('HCCD0501', $creativeDevelopment->abbreviation);
        $this->assertSame(1.0, (float) $creativeDevelopment->duration_years);
        $this->assertSame($grade12Id, $creativeDevelopment->required_grade_id);
        $this->assertSame($nqfLevel5Id, $creativeDevelopment->nqf_level_id);
        $this->assertSame('https://www.emeris.ac.za/qualifications/higher-certificate-in-creative-development', $creativeDevelopment->source_url);
        $this->assertStringContainsString('Pretoria Lynnwood', $creativeDevelopment->notes);
        $this->assertStringContainsString('Credits: 120', $creativeDevelopment->notes);
        $this->assertStringContainsString('SAQA ID: 90661', $creativeDevelopment->notes);
        $this->assertStringContainsString('Emeris is an educational brand', $creativeDevelopment->notes);
        $this->assertSame(
            [
                'English First Additional Language' => 30,
                'English Home Language' => 30,
            ],
            $this->subjectRequirementMarks($creativeDevelopment->id),
        );
        $this->assertSame('nsc_pass_type', $this->admissionRuleForUniversity($vc->id)->code);
        $this->assertSame(
            0,
            DB::table('qualifications')
                ->where('university_id', $vc->id)
                ->where('name', 'Higher Certificate in Art and Design')
                ->count(),
        );

        $response = $this->get(route('public.qualifications.show', [
            'university' => 'the-iies-varsity-college',
            'qualification' => 'higher-certificate-in-art-and-design',
        ]));

        $response->assertOk();
        $response->assertSee('Higher Certificate in Creative Development');
        $response->assertSee('Emeris');
        $response->assertSee('Admission requirements');
        $response->assertSee('Higher Certificate pass');
        $response->assertSee('English Home Language');
        $response->assertSee('English Home Language 30%');
        $response->assertSee('30%');
        $response->assertSee('Grade 12');
        $response->assertSee('Level 5');
        $response->assertSee('1 year');
        $response->assertDontSee('English Home Language level 2');
        $response->assertDontSee('Published APS');
        $response->assertDontSee('Alternative score variants');
        $response->assertDontSee('N/A');
        $response->assertDontSee('Not listed');
    }

    public function test_emeris_seed_uses_official_online_variants_and_subject_alternatives(): void
    {
        $this->seedVcRequirements();

        $bcom = $this->qualification('Bachelor of Commerce', 'Bachelor');
        $this->assertSame('https://www.emeris.ac.za/qualifications/bachelor-of-commerce', $bcom->source_url);
        $this->assertSame(3.0, (float) $bcom->duration_years);
        $this->assertSame([
            'English First Additional Language' => 50,
            'English Home Language' => 50,
            'Mathematical Literacy' => 50,
            'Mathematics' => 30,
            'Technical Mathematics' => 50,
        ], $this->subjectRequirementMarks($bcom->id));

        $onlineBba = $this->qualification('Bachelor of Business Administration (Online)', 'Bachelor');
        $this->assertSame('online-bachelor-of-business-administration', $onlineBba->slug);
        $this->assertSame('BBA0701', $onlineBba->abbreviation);
        $this->assertSame(4.0, (float) $onlineBba->duration_years);
        $this->assertStringContainsString('Mode of study: (Online).', $onlineBba->notes);
        $this->assertSame([
            'English First Additional Language' => 30,
            'English Home Language' => 30,
        ], $this->subjectRequirementMarks($onlineBba->id));

        $digitalMarketing = $this->qualification('Higher Certificate in Digital Marketing (Online)', 'Higher Certificate');
        $this->assertSame(1.5, (float) $digitalMarketing->duration_years);
        $this->assertSame('online-higher-certificate-in-digital-marketing', $digitalMarketing->slug);

        $shortCourse = $this->qualification('Brand and Marketing Management (Online)', 'Short Learning Programme');
        $this->assertSame('online-brand-and-marketing-management', $shortCourse->slug);
        $this->assertStringContainsString('does not have any formal admission requirements', $shortCourse->notes);
        $this->assertSame(0, $this->subjectRequirementCount($shortCourse->id));
    }

    public function test_vega_school_full_time_programmes_are_seeded_under_emeris_with_legacy_links(): void
    {
        $this->seedVcRequirements();

        $vegaProgrammes = [
            ['Higher Certificate in Creative Development', 'Higher Certificate', 'higher-certificate-in-art-and-design', 'https://www.vegaschool.com/full-time/higher-certificate-in-creative-development'],
            ['Bachelor of Communication Design', 'Bachelor', 'bachelor-of-communication-design', 'https://www.vegaschool.com/full-time/bachelor-of-communication-design-degree'],
            ['Bachelor of Experience Design', 'Bachelor', 'bachelor-of-experience-design', 'https://www.vegaschool.com/full-time/bachelor-of-experience-design-degree'],
            ['Bachelor of Computer and Information Sciences in Game Design and Development', 'Bachelor', 'bachelor-of-computer-and-information-sciences-in-game-design-and-development', 'https://www.vegaschool.com/full-time/bachelor-of-computer-and-information-sciences-in-game-design-and-development-degree'],
            ['Bachelor of Commerce in Digital Marketing', 'Bachelor', 'bachelor-of-commerce-in-digital-marketing', 'https://www.vegaschool.com/full-time/bcom-digital-marketing-degree'],
            ['Bachelor of Commerce in Strategic Brand Management', 'Bachelor', 'bachelor-of-commerce-in-strategic-brand-management', 'https://www.vegaschool.com/full-time/bcom-strategic-brand-management-degree'],
            ['Bachelor of Arts in Strategic Brand Communication', 'Bachelor', 'bachelor-of-arts-in-strategic-brand-communication', 'https://www.vegaschool.com/full-time/bachelor-of-arts-strategic-brand-communication-degree'],
            ['Bachelor of Arts in Interior Design', 'Bachelor', 'bachelor-of-arts-in-interior-design', 'https://www.vegaschool.com/full-time/ba-interior-design-degree'],
        ];

        foreach ($vegaProgrammes as [$name, $qualificationType, $slug, $legacyUrl]) {
            $qualification = $this->qualification($name, $qualificationType);

            $this->assertSame($slug, $qualification->slug);
            $this->assertStringStartsWith('https://www.emeris.ac.za/qualifications/', $qualification->source_url);
            $this->assertStringContainsString('Legacy Vega School programme page: '.$legacyUrl, $qualification->notes);
            $this->assertStringContainsString('current Emeris qualification page', $qualification->notes);
        }

        $communicationDesign = $this->qualification('Bachelor of Communication Design', 'Bachelor');
        $this->assertSame([
            'English First Additional Language' => 50,
            'English Home Language' => 50,
        ], $this->subjectRequirementMarks($communicationDesign->id));

        $gameDesign = $this->qualification('Bachelor of Computer and Information Sciences in Game Design and Development', 'Bachelor');
        $this->assertSame([
            'English First Additional Language' => 50,
            'English Home Language' => 50,
            'Mathematical Literacy' => 60,
            'Mathematics' => 40,
            'Technical Mathematics' => 60,
        ], $this->subjectRequirementMarks($gameDesign->id));
    }

    public function test_legacy_art_and_design_row_is_merged_into_current_creative_development_record(): void
    {
        $countryId = $this->seedLookupData();
        $now = now();
        $qualificationTypeId = DB::table('qualification_types')->where('name', 'Higher Certificate')->value('id');
        $nqfLevel5Id = DB::table('nqf_levels')->where('level', 5)->value('id');

        $universityId = DB::table('universities')->insertGetId([
            'country_id' => $countryId,
            'name' => "The IIE's Varsity College",
            'abbreviation' => 'VC',
            'slug' => 'the-iies-varsity-college',
            'website' => 'https://www.varsitycollege.co.za/',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $facultyId = DB::table('faculties')->insertGetId([
            'university_id' => $universityId,
            'name' => 'Humanities and Social Sciences',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $legacyId = DB::table('qualifications')->insertGetId([
            'university_id' => $universityId,
            'faculty_id' => $facultyId,
            'qualification_type_id' => $qualificationTypeId,
            'nqf_level_id' => $nqfLevel5Id,
            'name' => 'Higher Certificate in Art and Design',
            'slug' => 'higher-certificate-in-art-and-design',
            'duration_years' => null,
            'is_selection_programme' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $currentId = DB::table('qualifications')->insertGetId([
            'university_id' => $universityId,
            'faculty_id' => $facultyId,
            'qualification_type_id' => $qualificationTypeId,
            'nqf_level_id' => $nqfLevel5Id,
            'name' => 'Higher Certificate in Creative Development',
            'slug' => 'higher-certificate-in-creative-development',
            'duration_years' => null,
            'is_selection_programme' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $staleId = DB::table('qualifications')->insertGetId([
            'university_id' => $universityId,
            'faculty_id' => $facultyId,
            'qualification_type_id' => $qualificationTypeId,
            'nqf_level_id' => $nqfLevel5Id,
            'name' => 'Higher Certificate in Computer',
            'slug' => 'higher-certificate-in-computer',
            'duration_years' => null,
            'is_selection_programme' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->seed(VcRequirementSeeder::class);

        $vc = DB::table('universities')->where('abbreviation', 'VC')->first();
        $creativeDevelopment = $this->qualification('Higher Certificate in Creative Development', 'Higher Certificate');

        $this->assertSame('Emeris', $vc->name);
        $this->assertSame('the-iies-varsity-college', $vc->slug);
        $this->assertSame($legacyId, (int) $creativeDevelopment->id);
        $this->assertSame('higher-certificate-in-art-and-design', $creativeDevelopment->slug);
        $this->assertSame(1.0, (float) $creativeDevelopment->duration_years);
        $this->assertFalse(DB::table('qualifications')->where('id', $currentId)->exists());
        $this->assertFalse(DB::table('qualifications')->where('id', $staleId)->exists());
        $this->assertSame(55, DB::table('qualifications')->where('university_id', $universityId)->count());
        $this->assertSame(
            1,
            DB::table('qualifications')
                ->where('university_id', $universityId)
                ->where('name', 'Higher Certificate in Creative Development')
                ->count(),
        );
        $this->assertSame(
            0,
            DB::table('qualifications')
                ->where('university_id', $universityId)
                ->where('name', 'Higher Certificate in Art and Design')
                ->count(),
        );
    }

    private function seedVcRequirements(): void
    {
        $this->seedLookupData();
        $this->seed(VcRequirementSeeder::class);
    }

    private function seedLookupData(): int
    {
        $now = now();
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'South Africa',
            'nationality' => 'South African',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('curriculums')->insert([
            'country_id' => $countryId,
            'name' => 'NSC (National Senior Certificate)',
            'abbreviation' => 'CAPS',
            'is_live' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->seed([
            NqfLevelSeeder::class,
            SubjectCategorySeeder::class,
            GradeSeeder::class,
            QualificationTypeSeeder::class,
            CapsSubjectSeeder::class,
            AdmissionRuleSeeder::class,
        ]);

        return $countryId;
    }

    private function qualification(string $name, string $qualificationType): object
    {
        $qualification = DB::table('qualifications')
            ->join('universities', 'universities.id', '=', 'qualifications.university_id')
            ->join('qualification_types', 'qualification_types.id', '=', 'qualifications.qualification_type_id')
            ->where('universities.abbreviation', 'VC')
            ->where('qualifications.name', $name)
            ->where('qualification_types.name', $qualificationType)
            ->select('qualifications.*')
            ->first();

        $this->assertNotNull($qualification);

        return $qualification;
    }

    private function admissionRuleForUniversity(int $universityId): object
    {
        $rule = DB::table('university_admission_rules')
            ->join('admission_rules', 'admission_rules.id', '=', 'university_admission_rules.admission_rule_id')
            ->where('university_admission_rules.university_id', $universityId)
            ->whereNull('university_admission_rules.faculty_id')
            ->whereNull('university_admission_rules.qualification_id')
            ->select('admission_rules.code', 'university_admission_rules.grade_id')
            ->first();

        $this->assertNotNull($rule);

        return $rule;
    }

    /**
     * @return array<string, int>
     */
    private function subjectRequirementMarks(int $qualificationId): array
    {
        return DB::table('qualification_subject_requirements')
            ->where('qualification_id', $qualificationId)
            ->orderBy('subject_name')
            ->pluck('minimum_mark', 'subject_name')
            ->map(fn ($mark): int => (int) $mark)
            ->all();
    }

    private function subjectRequirementCount(int $qualificationId): int
    {
        return DB::table('qualification_subject_requirements')
            ->where('qualification_id', $qualificationId)
            ->count();
    }
}
