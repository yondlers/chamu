<?php

namespace Tests\Feature;

use Database\Seeders\AdmissionRuleSeeder;
use Database\Seeders\CapsSubjectSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\NqfLevelSeeder;
use Database\Seeders\QualificationTypeSeeder;
use Database\Seeders\SubjectCategorySeeder;
use Database\Seeders\Universities\UNISA\RequirementSeeder as UnisaRequirementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnisaRequirementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_unisa_undergraduate_programmes_are_seeded_with_aps_streams_and_conservative_subject_rules(): void
    {
        $this->seedUnisaRequirements();

        $unisa = DB::table('universities')->where('abbreviation', 'UNISA')->first();
        $grade12Id = DB::table('grades')->where('name', 'Grade 12')->value('id');
        $nqfLevel7Id = DB::table('nqf_levels')->where('level', 7)->value('id');

        $this->assertNotNull($unisa);
        $this->assertSame('university-of-south-africa', $unisa->slug);
        $this->assertSame(54, DB::table('qualifications')->where('university_id', $unisa->id)->count());

        $businessManagement = $this->qualification('Bachelor of Commerce in Business Management');
        $this->assertSame('98310-BSM', $businessManagement->abbreviation);
        $this->assertSame($grade12Id, $businessManagement->required_grade_id);
        $this->assertSame($nqfLevel7Id, $businessManagement->nqf_level_id);
        $this->assertSame(21, $businessManagement->aps_required);
        $this->assertSame('bachelor', $businessManagement->minimum_pass_type);
        $this->assertStringContainsString('Mathematics guidance', $businessManagement->notes);
        $this->assertSame([
            'English First Additional Language' => 50,
            'English Home Language' => 50,
            'Mathematics' => 50,
        ], $this->subjectRequirementMarks($businessManagement->id));

        $rule = $this->admissionRuleFor($businessManagement->id);
        $this->assertSame('nsc_aps_excluding_lo', $rule->code);
        $this->assertSame(21, json_decode($rule->overrides, true, 512, JSON_THROW_ON_ERROR)['minimum_aps']);

        $communication = $this->qualification('Bachelor of Arts in Communication Studies');
        $this->assertSame(20, $communication->aps_required);
        $this->assertSame([
            'English First Additional Language' => 50,
            'English Home Language' => 50,
        ], $this->subjectRequirementMarks($communication->id));
        $this->assertStringNotContainsString('Mathematics typically', $communication->notes);

        $economicsCount = DB::table('qualifications')
            ->where('university_id', $unisa->id)
            ->whereIn('abbreviation', ['98305-ECS', '98305-QEC'])
            ->count();

        $this->assertSame(2, $economicsCount);

        $foodNutrition = $this->qualification('Bachelor of Consumer Science Food and Nutrition');
        $this->assertStringContainsString('HPCSA', $foodNutrition->notes);
        $this->assertStringContainsString('Practical component', $foodNutrition->notes);

        $informationTechnology = $this->qualification('Diploma in Information Technology');
        $nqfLevel6Id = DB::table('nqf_levels')->where('level', 6)->value('id');
        $this->assertSame('98806-ITE', $informationTechnology->abbreviation);
        $this->assertSame($nqfLevel6Id, $informationTechnology->nqf_level_id);
        $this->assertSame(18, $informationTechnology->aps_required);
        $this->assertSame('diploma', $informationTechnology->minimum_pass_type);
        $this->assertStringContainsString('Work-integrated learning', $informationTechnology->notes);
        $this->assertSame([
            'English First Additional Language' => 50,
            'English Home Language' => 50,
            'Mathematics' => 50,
        ], $this->subjectRequirementMarks($informationTechnology->id));

        $accountingSciences = $this->qualification('Diploma in Accounting Sciences');
        $this->assertNull($accountingSciences->aps_required);
        $this->assertSame([
            'English First Additional Language' => 50,
            'English Home Language' => 50,
            'Mathematical Literacy' => 50,
            'Mathematics' => 40,
        ], $this->subjectRequirementMarks($accountingSciences->id));

        $tourism = $this->qualification('Diploma in Tourism Management');
        $this->assertSame('98223', $tourism->abbreviation);
        $this->assertStringContainsString('Input correction', $tourism->notes);
        $this->assertSame([
            'Mathematical Literacy' => 70,
            'Mathematics' => 40,
        ], $this->subjectRequirementMarks($tourism->id));

        $internalAuditing = $this->qualification('Bachelor of Accounting Sciences in Internal Auditing');
        $this->assertSame('98303-AUI', $internalAuditing->abbreviation);
        $this->assertSame(21, $internalAuditing->aps_required);
        $this->assertStringContainsString('Presentation: Online', $internalAuditing->notes);
        $this->assertSame([
            'English First Additional Language' => 50,
            'English Home Language' => 50,
            'Mathematics' => 50,
        ], $this->subjectRequirementMarks($internalAuditing->id));

        $foundationPhase = $this->qualification('Bachelor of Education in Foundation Phase Teaching');
        $this->assertSame(23, $foundationPhase->aps_required);
        $this->assertStringContainsString('Teaching phase: Grade R to Grade 3', $foundationPhase->notes);
        $this->assertStringContainsString('Practical training', $foundationPhase->notes);

        $socialWork = $this->qualification('Bachelor of Social Work');
        $this->assertStringContainsString('Professional registration', $socialWork->notes);
        $this->assertStringContainsString('Police clearance certificate', $socialWork->notes);

        $visualArts = $this->qualification('Bachelor of Arts in Visual Multimedia Arts');
        $this->assertStringContainsString('Additional selection', $visualArts->notes);
    }

    private function seedUnisaRequirements(): void
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
            UnisaRequirementSeeder::class,
        ]);
    }

    private function qualification(string $name): object
    {
        $qualification = DB::table('qualifications')
            ->where('name', $name)
            ->first();

        $this->assertNotNull($qualification);

        return $qualification;
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
            ->all();
    }

    private function admissionRuleFor(int $qualificationId): object
    {
        $rule = DB::table('university_admission_rules')
            ->join('admission_rules', 'admission_rules.id', '=', 'university_admission_rules.admission_rule_id')
            ->where('university_admission_rules.qualification_id', $qualificationId)
            ->select('admission_rules.code', 'university_admission_rules.overrides')
            ->first();

        $this->assertNotNull($rule);

        return $rule;
    }
}
