<?php

namespace Tests\Feature;

use Database\Seeders\AdmissionRuleSeeder;
use Database\Seeders\CapsSubjectSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\NqfLevelSeeder;
use Database\Seeders\QualificationTypeSeeder;
use Database\Seeders\SubjectCategorySeeder;
use Database\Seeders\Universities\TNC\RequirementSeeder as TncRequirementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TncRequirementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tnc_programmes_are_seeded_with_tvet_entry_grades_and_subject_rules(): void
    {
        $this->seedTncRequirements();

        $tnc = DB::table('universities')->where('abbreviation', 'TNC')->first();
        $grade9Id = DB::table('grades')->where('name', 'Grade 9')->value('id');
        $grade12Id = DB::table('grades')->where('name', 'Grade 12')->value('id');

        $this->assertNotNull($tnc);
        $this->assertSame('tshwane-north-tvet-college', $tnc->slug);
        $this->assertSame(49, DB::table('qualifications')->where('university_id', $tnc->id)->count());
        $this->assertGreaterThanOrEqual(
            20,
            DB::table('qualifications')
                ->where('university_id', $tnc->id)
                ->where('required_grade_id', $grade9Id)
                ->count(),
        );

        $electricalInfrastructure = $this->qualification('Electrical Infrastructure Construction', 'National Certificate Vocational');
        $this->assertSame($grade9Id, $electricalInfrastructure->required_grade_id);
        $this->assertSame(
            ['English', 'Mathematics'],
            $this->subjectRequirementNames($electricalInfrastructure->id),
        );

        $mechatronics = $this->qualification('Mechatronics', 'National Certificate Vocational');
        $this->assertSame($grade9Id, $mechatronics->required_grade_id);
        $this->assertSame(
            ['Mathematics', 'Physical Sciences'],
            $this->subjectRequirementNames($mechatronics->id),
        );

        $financialManagement = $this->qualification('Financial Management', 'NATED');
        $this->assertSame($grade12Id, $financialManagement->required_grade_id);
        $this->assertSame([
            'Accounting' => 30,
            'English' => 40,
        ], $this->subjectRequirementMarks($financialManagement->id));

        $rule = DB::table('university_admission_rules')
            ->join('admission_rules', 'admission_rules.id', '=', 'university_admission_rules.admission_rule_id')
            ->where('university_admission_rules.qualification_id', $electricalInfrastructure->id)
            ->select('admission_rules.code', 'university_admission_rules.grade_id')
            ->first();

        $this->assertSame('subject_levels_only', $rule->code);
        $this->assertSame($grade9Id, $rule->grade_id);
    }

    private function seedTncRequirements(): void
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
            TncRequirementSeeder::class,
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

    /**
     * @return array<int, string>
     */
    private function subjectRequirementNames(int $qualificationId): array
    {
        return DB::table('qualification_subject_requirements')
            ->where('qualification_id', $qualificationId)
            ->orderBy('subject_name')
            ->pluck('subject_name')
            ->all();
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
}
