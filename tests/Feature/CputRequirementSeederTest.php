<?php

namespace Tests\Feature;

use Database\Seeders\AdmissionRuleSeeder;
use Database\Seeders\CapsSubjectSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\NqfLevelSeeder;
use Database\Seeders\QualificationTypeSeeder;
use Database\Seeders\SubjectCategorySeeder;
use Database\Seeders\Universities\CPUT\RequirementSeeder as CputRequirementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CputRequirementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_cput_geomatics_and_education_pages_seed_structured_public_requirements(): void
    {
        $this->seedCputRequirements();

        $cput = DB::table('universities')->where('abbreviation', 'CPUT')->first();

        $this->assertNotNull($cput);
        $this->assertSame('cape-peninsula-university-of-technology', $cput->slug);

        $geomatics = $this->qualification('Bachelor of Geomatics', 'Bachelor');

        $this->assertSame(36, $geomatics->aps_required);
        $this->assertSame(36.0, (float) $geomatics->admission_score_required);
        $this->assertSame('https://prospectus.cput.ac.za/index.php/course-details?f=140&q=BPGMTS', $geomatics->source_url);
        $this->assertStringNotContainsString('ECP consideration notes', $geomatics->notes);
        $this->assertSame([
            'English Home Language' => 60,
            'Mathematics' => 60,
            'Physical Sciences' => 60,
            'Technical Mathematics' => 60,
            'Technical Sciences' => 60,
        ], $this->subjectRequirementMarks($geomatics->id));

        $intermediatePhase = $this->qualification('Bachelor in Education: Intermediate Phase Teaching', 'Bachelor');

        $this->assertSame(32, $intermediatePhase->aps_required);
        $this->assertSame('bachelor', $intermediatePhase->minimum_pass_type);
        $this->assertSame('https://prospectus.cput.ac.za/index.php/course-details?f=100&q=BEINPT', $intermediatePhase->source_url);
        $this->assertGreaterThan(0, $this->subjectRequirementCount($intermediatePhase->id));
        $this->assertContains('Official South African language', $this->subjectRequirementNames($intermediatePhase->id));
        $this->assertContains('Recognised NSC 20-credit subject', $this->subjectRequirementNames($intermediatePhase->id));
        $this->assertContains('Mathematics', $this->subjectRequirementNames($intermediatePhase->id));
    }

    private function seedCputRequirements(): void
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
            CputRequirementSeeder::class,
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

    private function subjectRequirementCount(int $qualificationId): int
    {
        return DB::table('qualification_subject_requirements')
            ->where('qualification_id', $qualificationId)
            ->count();
    }
}
