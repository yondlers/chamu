<?php

namespace Tests\Feature;

use Database\Seeders\AdmissionRuleSeeder;
use Database\Seeders\CapsSubjectSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\NqfLevelSeeder;
use Database\Seeders\QualificationTypeSeeder;
use Database\Seeders\SubjectCategorySeeder;
use Database\Seeders\Universities\EDUVOS\RequirementSeeder as EduvosRequirementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EduvosRequirementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_eduvos_undergraduate_programmes_are_seeded_from_official_admissions_rules(): void
    {
        $this->seedEduvosRequirements();

        $eduvos = DB::table('universities')->where('abbreviation', 'EDUVOS')->first();
        $this->assertNotNull($eduvos);
        $this->assertSame('Eduvos', $eduvos->name);
        $this->assertSame('eduvos', $eduvos->slug);
        $this->assertSame('https://www.eduvos.com/', $eduvos->website);
        $this->assertSame(59, DB::table('qualifications')->where('university_id', $eduvos->id)->count());

        $names = DB::table('qualifications')
            ->where('university_id', $eduvos->id)
            ->pluck('name')
            ->all();

        $this->assertNotContains('Bachelor of Commerce Honours in Business Management', $names);
        $this->assertNotContains('Postgraduate Diploma in Accounting', $names);
        $this->assertNotContains('Master of Laws in Commercial Law', $names);

        $this->assertSame('eduvos_points', $this->admissionRuleForUniversity($eduvos->id)->code);

        $accounting = $this->qualification('Bachelor of Commerce in Accounting', 'Bachelor');
        $this->assertSame('https://www.eduvos.com/programmes/bachelor-of-commerce-in-accounting/', $accounting->source_url);
        $this->assertSame(25.0, (float) $accounting->admission_score_required);
        $this->assertSame(3.0, (float) $accounting->duration_years);
        $this->assertSame('bachelor', $accounting->minimum_pass_type);
        $this->assertStringContainsString('SAQA ID: 120729', $accounting->notes);
        $this->assertStringContainsString('25 Eduvos points or more', $accounting->notes);
        $this->assertSame(['Mathematics' => 50], $this->subjectRequirementMarks($accounting->id));
    }

    public function test_eduvos_science_pre_degree_and_access_rules_are_structured_without_postgraduate_rules(): void
    {
        $this->seedEduvosRequirements();

        $biomedicine = $this->qualification('Bachelor of Science in Biomedicine', 'Bachelor');
        $this->assertSame(32.0, (float) $biomedicine->admission_score_required);
        $this->assertSame([
            'English First Additional Language' => 50,
            'English Home Language' => 50,
            'Life Sciences' => 50,
            'Mathematics' => 50,
            'Physical Sciences' => 50,
        ], $this->subjectRequirementMarks($biomedicine->id));
        $this->assertStringContainsString('points for the best two', $biomedicine->notes);

        $dataScience = $this->qualification('Bachelor of Science in Information Technology: Data Science', 'Bachelor');
        $this->assertNull($dataScience->admission_score_required);
        $this->assertSame(['Mathematics' => 50], $this->subjectRequirementMarks($dataScience->id));
        $this->assertStringContainsString('bridging modules', $dataScience->notes);

        $preDegreeScience = $this->qualification('Pre-degree Foundation Programme: Science', 'Pre-Degree Foundation Programme');
        $this->assertSame(24.0, (float) $preDegreeScience->admission_score_required);
        $this->assertSame([
            'English First Additional Language' => 40,
            'English Home Language' => 40,
            'Mathematics' => 30,
        ], $this->subjectRequirementMarks($preDegreeScience->id));

        $bioscienceCertificate = $this->qualification('Higher Certificate in Bioscience', 'Higher Certificate');
        $this->assertSame('higher_certificate', $bioscienceCertificate->minimum_pass_type);
        $this->assertSame([
            'English First Additional Language' => 40,
            'English Home Language' => 40,
            'Mathematical Literacy' => 50,
            'Mathematics' => 40,
        ], $this->subjectRequirementMarks($bioscienceCertificate->id));

        $access = $this->qualification('Bachelor of Commerce Access Programme: Accounting', 'Access Programme');
        $this->assertSame(0, $this->subjectRequirementCount($access->id));
        $this->assertStringContainsString('Mathematics below 45%', $access->notes);
    }

    public function test_eduvos_public_qualification_page_displays_eduvos_admission_information(): void
    {
        $this->seedEduvosRequirements();

        $response = $this->get(route('public.qualifications.show', [
            'university' => 'eduvos',
            'qualification' => 'bachelor-of-commerce-in-accounting',
        ]));

        $response->assertOk();
        $response->assertSee('Bachelor of Commerce in Accounting');
        $response->assertSee('Eduvos');
        $response->assertSee('Eduvos points');
        $response->assertSee('25');
        $response->assertSee('Mathematics');
        $response->assertSee('50%');
        $response->assertDontSee('Honours');
    }

    private function seedEduvosRequirements(): void
    {
        $this->seedLookupData();
        $this->seed(EduvosRequirementSeeder::class);
    }

    private function seedLookupData(): void
    {
        $now = now();

        DB::table('countries')->insert([
            'name' => 'South Africa',
            'nationality' => 'South African',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('curriculums')->insert([
            'country_id' => DB::table('countries')->where('name', 'South Africa')->value('id'),
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
    }

    private function qualification(string $name, string $qualificationType): object
    {
        $qualification = DB::table('qualifications')
            ->join('universities', 'universities.id', '=', 'qualifications.university_id')
            ->join('qualification_types', 'qualification_types.id', '=', 'qualifications.qualification_type_id')
            ->where('universities.abbreviation', 'EDUVOS')
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
