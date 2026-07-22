<?php

namespace Tests\Feature;

use Database\Seeders\AdmissionRuleSeeder;
use Database\Seeders\CapsSubjectSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\NqfLevelSeeder;
use Database\Seeders\QualificationTypeSeeder;
use Database\Seeders\SubjectCategorySeeder;
use Database\Seeders\Universities\CJC\RequirementSeeder as CjcRequirementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CjcRequirementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_cjc_programmes_are_seeded_with_college_entry_rules_and_explicit_aps_only(): void
    {
        $this->seedCjcRequirements();

        $cjc = DB::table('universities')->where('abbreviation', 'CJC')->first();
        $grade9Id = DB::table('grades')->where('name', 'Grade 9')->value('id');
        $grade12Id = DB::table('grades')->where('name', 'Grade 12')->value('id');
        $nqfLevel4Id = DB::table('nqf_levels')->where('level', 4)->value('id');

        $this->assertNotNull($cjc);
        $this->assertSame('central-johannesburg-tvet-college', $cjc->slug);
        $this->assertSame(26, DB::table('qualifications')->where('university_id', $cjc->id)->count());
        $this->assertSame(
            10,
            DB::table('qualifications')
                ->join('qualification_types', 'qualification_types.id', '=', 'qualifications.qualification_type_id')
                ->where('qualifications.university_id', $cjc->id)
                ->where('qualification_types.name', 'National Certificate Vocational')
                ->where('qualifications.required_grade_id', $grade9Id)
                ->count(),
        );

        $artDesign = $this->qualification('Art and Design', 'NATED');
        $this->assertSame($grade12Id, $artDesign->required_grade_id);
        $this->assertSame(24, $artDesign->aps_required);
        $this->assertSame(0, $this->subjectRequirementCount($artDesign->id));
        $this->assertStringContainsString('Portfolio of Evidence', $artDesign->notes);

        $artDesignRule = $this->admissionRuleFor($artDesign->id);
        $this->assertSame('cjc_aps_double_english_next_four', $artDesignRule->code);
        $this->assertSame($grade12Id, $artDesignRule->grade_id);
        $this->assertSame(
            'Double English plus the next four best subjects',
            json_decode($artDesignRule->overrides, true, 512, JSON_THROW_ON_ERROR)['aps_calculation'],
        );

        $civilConstruction = $this->qualification('Civil Engineering and Building Construction', 'National Certificate Vocational');
        $this->assertSame($grade9Id, $civilConstruction->required_grade_id);
        $this->assertNull($civilConstruction->aps_required);
        $this->assertSame('subject_levels_only', $this->admissionRuleFor($civilConstruction->id)->code);

        $hairdresser = $this->qualification('Hairdresser', 'Occupational Certificate');
        $this->assertSame($grade9Id, $hairdresser->required_grade_id);
        $this->assertSame($nqfLevel4Id, $hairdresser->nqf_level_id);
        $this->assertStringContainsString('workplace training', strtolower($hairdresser->notes));

        $beauty = $this->qualification('Beauty and Nail Technology', 'Further Education and Training Certificate');
        $this->assertNull($beauty->required_grade_id);
        $this->assertSame($nqfLevel4Id, $beauty->nqf_level_id);
        $this->assertStringContainsString('Recognition of Prior Learning', $beauty->notes);

        $travel = $this->qualification('Travel and Tourism', 'NATED');
        $this->assertSame('cjc_aps_double_english_next_three', $this->admissionRuleFor($travel->id)->code);
    }

    private function seedCjcRequirements(): void
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
            CjcRequirementSeeder::class,
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

    private function admissionRuleFor(int $qualificationId): object
    {
        $rule = DB::table('university_admission_rules')
            ->join('admission_rules', 'admission_rules.id', '=', 'university_admission_rules.admission_rule_id')
            ->where('university_admission_rules.qualification_id', $qualificationId)
            ->select('admission_rules.code', 'university_admission_rules.grade_id', 'university_admission_rules.overrides')
            ->first();

        $this->assertNotNull($rule);

        return $rule;
    }

    private function subjectRequirementCount(int $qualificationId): int
    {
        return DB::table('qualification_subject_requirements')
            ->where('qualification_id', $qualificationId)
            ->count();
    }
}
