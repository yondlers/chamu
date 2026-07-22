<?php

namespace Tests\Feature;

use Database\Seeders\AdmissionRuleSeeder;
use Database\Seeders\CapsSubjectSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\NqfLevelSeeder;
use Database\Seeders\QualificationTypeSeeder;
use Database\Seeders\SubjectCategorySeeder;
use Database\Seeders\Universities\TSC\RequirementSeeder as TscRequirementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TscRequirementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tsc_programmes_are_seeded_without_inventing_aps_or_subject_requirements(): void
    {
        $this->seedTscRequirements();

        $tsc = DB::table('universities')->where('abbreviation', 'TSC')->first();
        $grade9Id = DB::table('grades')->where('name', 'Grade 9')->value('id');
        $grade12Id = DB::table('grades')->where('name', 'Grade 12')->value('id');
        $nqfLevel4Id = DB::table('nqf_levels')->where('level', 4)->value('id');

        $this->assertNotNull($tsc);
        $this->assertSame('tshwane-south-tvet-college', $tsc->slug);
        $this->assertSame(25, DB::table('qualifications')->where('university_id', $tsc->id)->count());
        $this->assertSame(
            7,
            DB::table('qualifications')
                ->where('university_id', $tsc->id)
                ->where('required_grade_id', $grade9Id)
                ->count(),
        );

        $civilConstruction = $this->qualification('Civil & Construction', 'National Certificate Vocational');
        $this->assertSame($grade9Id, $civilConstruction->required_grade_id);
        $this->assertNull($civilConstruction->aps_required);
        $this->assertSame(0, $this->subjectRequirementCount($civilConstruction->id));
        $this->assertStringContainsString('Campus', $civilConstruction->notes);

        $civilEngineering = $this->qualification('Civil Engineering', 'NATED');
        $this->assertSame($grade12Id, $civilEngineering->required_grade_id);
        $this->assertNull($civilEngineering->aps_required);

        $electrician = $this->qualification('Electrician', 'Occupational Certificate');
        $this->assertNull($electrician->required_grade_id);
        $this->assertSame($nqfLevel4Id, $electrician->nqf_level_id);
        $this->assertStringContainsString('manual review', strtolower($electrician->notes));

        $rule = DB::table('university_admission_rules')
            ->join('admission_rules', 'admission_rules.id', '=', 'university_admission_rules.admission_rule_id')
            ->where('university_admission_rules.qualification_id', $civilConstruction->id)
            ->select('admission_rules.code', 'university_admission_rules.grade_id')
            ->first();

        $this->assertSame('subject_levels_only', $rule->code);
        $this->assertSame($grade9Id, $rule->grade_id);
    }

    private function seedTscRequirements(): void
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
            TscRequirementSeeder::class,
        ]);
    }

    private function qualification(string $name, string $qualificationType): object
    {
        $qualification = DB::table('qualifications')
            ->join('qualification_types', 'qualification_types.id', '=', 'qualifications.qualification_type_id')
            ->where('qualifications.name', $name)
            ->where('qualification_types.name', $qualificationType)
            ->select('qualifications.*')
            ->first();

        $this->assertNotNull($qualification);

        return $qualification;
    }

    private function subjectRequirementCount(int $qualificationId): int
    {
        return DB::table('qualification_subject_requirements')
            ->where('qualification_id', $qualificationId)
            ->count();
    }
}
