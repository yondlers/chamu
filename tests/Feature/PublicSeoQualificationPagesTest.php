<?php

namespace Tests\Feature;

use App\Models\AdmissionRule;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\NqfLevel;
use App\Models\Qualification;
use App\Models\QualificationAdmissionScoreVariant;
use App\Models\QualificationSubjectRequirement;
use App\Models\QualificationType;
use App\Models\University;
use App\Models\UniversityAdmissionRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicSeoQualificationPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_university_page_returns_200_with_seo_and_qualification_preview(): void
    {
        $records = $this->createPublicQualificationRecords();
        $university = $records['university'];
        $qualification = $records['qualification'];

        $response = $this->get(route('public.universities.show', ['university' => $university->slug]));

        $response->assertOk();
        $response->assertSee('<title>University of Pretoria Courses and Requirements | Chamu</title>', false);
        $response->assertSee('<meta name="description" content="Explore qualifications, faculties and admission information for University of Pretoria. Check which programmes may match your APS on Chamu.">', false);
        $response->assertSee('<link rel="canonical" href="'.route('public.universities.show', ['university' => $university->slug]).'">', false);
        $response->assertSee('University of Pretoria');
        $response->assertSee('Faculty of Economic and Management Sciences');
        $response->assertSee('Bachelor of Commerce Accounting');
        $response->assertSee(route('public.qualifications.show', [
            'university' => $university->slug,
            'qualification' => $qualification->slug,
        ]), false);
        $response->assertDontSee('noindex');
    }

    public function test_invalid_university_slug_returns_404(): void
    {
        $this->createPublicQualificationRecords();

        $this->get('/universities/not-a-real-university')->assertNotFound();
    }

    public function test_public_qualification_page_returns_200_with_requirements_seo_and_json_ld(): void
    {
        $records = $this->createPublicQualificationRecords();
        $university = $records['university'];
        $qualification = $records['qualification'];

        $response = $this->get(route('public.qualifications.show', [
            'university' => $university->slug,
            'qualification' => $qualification->slug,
        ]));

        $response->assertOk();
        $response->assertSee('<title>Bachelor of Commerce Accounting at University of Pretoria: APS and Requirements | Chamu</title>', false);
        $response->assertSee('<link rel="canonical" href="'.route('public.qualifications.show', [
            'university' => $university->slug,
            'qualification' => $qualification->slug,
        ]).'">', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('BreadcrumbList');
        $response->assertSee('Published APS');
        $response->assertSee('English Home Language');
        $response->assertSee('level 5');
        $response->assertSee('UP APS');
        $response->assertSee('Check your full subject-level eligibility');
        $response->assertDontSee('Sign up to view this course');
    }

    public function test_public_qualification_page_displays_aggregate_average_and_subject_choice_requirements(): void
    {
        $records = $this->createPublicQualificationRecords();
        $university = $records['university'];
        $qualification = $records['qualification'];

        $qualification->update([
            'name' => 'BAccLLB',
            'aps_required' => null,
            'aggregate_average_required' => 80,
            'admission_score_required' => 80,
        ]);

        AdmissionRule::where('code', 'up_aps')->update([
            'score_type' => 'aggregate_average',
            'score_label' => 'Aggregated average',
            'score_suffix' => '%',
        ]);

        QualificationSubjectRequirement::where('qualification_id', $qualification->id)->delete();

        $choiceOneNotes = json_encode([
            'choice_key' => 'choice_1',
            'required_count' => 1,
            'label' => 'Mathematics 70%',
        ]);
        $choiceTwoNotes = json_encode([
            'choice_key' => 'choice_2',
            'required_count' => 2,
            'label' => 'Mathematics 60% and Accounting 70%',
        ]);

        QualificationSubjectRequirement::create([
            'qualification_id' => $qualification->id,
            'subject_name' => 'Mathematics',
            'minimum_mark' => 70,
            'requirement_type' => 'subject_group_count_choice',
            'requirement_group' => 'math_accounting_choice',
            'notes' => $choiceOneNotes,
        ]);
        QualificationSubjectRequirement::create([
            'qualification_id' => $qualification->id,
            'subject_name' => 'Mathematics',
            'minimum_mark' => 60,
            'requirement_type' => 'subject_group_count_choice',
            'requirement_group' => 'math_accounting_choice',
            'notes' => $choiceTwoNotes,
        ]);
        QualificationSubjectRequirement::create([
            'qualification_id' => $qualification->id,
            'subject_name' => 'Accounting',
            'minimum_mark' => 70,
            'requirement_type' => 'subject_group_count_choice',
            'requirement_group' => 'math_accounting_choice',
            'notes' => $choiceTwoNotes,
        ]);

        $response = $this->get(route('public.qualifications.show', [
            'university' => $university->slug,
            'qualification' => $qualification->slug,
        ]));

        $response->assertOk();
        $response->assertSee('Aggregated average');
        $response->assertSee('80%');
        $response->assertSee('One of these subject combinations');
        $response->assertSee('Mathematics 70%');
        $response->assertSee('Mathematics 60% and Accounting 70%');
        $response->assertSee('Accounting');
        $response->assertDontSee('choice_key');
        $response->assertDontSee('required_count');
    }

    public function test_qualification_from_another_university_returns_404(): void
    {
        $records = $this->createPublicQualificationRecords();
        $otherUniversity = University::create([
            'country_id' => $records['country_id'],
            'name' => 'University of Cape Town',
            'abbreviation' => 'UCT',
        ]);

        $this->get(route('public.qualifications.show', [
            'university' => $otherUniversity->slug,
            'qualification' => $records['qualification']->slug,
        ]))->assertNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    private function createPublicQualificationRecords(): array
    {
        $now = now();
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'South Africa',
            'nationality' => 'South African',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $curriculumId = DB::table('curriculums')->insertGetId([
            'country_id' => $countryId,
            'name' => 'NSC',
            'abbreviation' => 'CAPS',
            'is_live' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $grade = Grade::create([
            'curriculum_id' => $curriculumId,
            'name' => 'Grade 12',
            'sort_order' => 12,
        ]);
        $nqfLevel = NqfLevel::create([
            'level' => 7,
            'name' => 'Bachelor degree',
            'sort_order' => 7,
        ]);
        $type = QualificationType::create([
            'nqf_level_id' => $nqfLevel->id,
            'name' => 'Bachelor Degree',
            'abbreviation' => 'BDeg',
        ]);
        $university = University::create([
            'country_id' => $countryId,
            'name' => 'University of Pretoria',
            'abbreviation' => 'UP',
            'website' => 'https://www.up.ac.za',
            'default_closing_month' => 6,
            'default_closing_day' => 30,
        ]);
        $faculty = Faculty::create([
            'university_id' => $university->id,
            'name' => 'Faculty of Economic and Management Sciences',
        ]);
        $qualification = Qualification::create([
            'university_id' => $university->id,
            'faculty_id' => $faculty->id,
            'qualification_type_id' => $type->id,
            'nqf_level_id' => $nqfLevel->id,
            'required_grade_id' => $grade->id,
            'name' => 'Bachelor of Commerce Accounting',
            'duration_years' => 3,
            'aps_required' => 30,
            'admission_score_required' => 32,
            'is_selection_programme' => false,
            'notes' => 'Selection may apply when applications exceed available space.',
        ]);
        QualificationSubjectRequirement::create([
            'qualification_id' => $qualification->id,
            'subject_name' => 'English Home Language',
            'minimum_mark' => 60,
            'aps_level_required' => 5,
            'requirement_type' => 'required',
        ]);
        QualificationAdmissionScoreVariant::create([
            'qualification_id' => $qualification->id,
            'subject_name' => 'Mathematics',
            'minimum_mark' => 60,
            'aps_level_required' => 5,
            'admission_score_required' => 32,
            'label' => 'Mathematics route',
        ]);
        $rule = AdmissionRule::create([
            'code' => 'up_aps',
            'name' => 'UP APS',
            'score_type' => 'aps',
            'calculation_method' => 'aps_level_sum',
            'score_label' => 'APS',
            'is_active' => true,
        ]);
        UniversityAdmissionRule::create([
            'admission_rule_id' => $rule->id,
            'university_id' => $university->id,
            'faculty_id' => $faculty->id,
            'priority' => 10,
            'is_default' => true,
        ]);

        return [
            'country_id' => $countryId,
            'university' => $university,
            'qualification' => $qualification,
        ];
    }
}
