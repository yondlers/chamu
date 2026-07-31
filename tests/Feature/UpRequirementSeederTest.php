<?php

namespace Tests\Feature;

use App\Models\Qualification;
use App\Models\University;
use Database\Seeders\AdmissionRuleSeeder;
use Database\Seeders\CapsSubjectSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\NqfLevelSeeder;
use Database\Seeders\QualificationTypeSeeder;
use Database\Seeders\SubjectCategorySeeder;
use Database\Seeders\Universities\UP\RequirementSeeder as UpRequirementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpRequirementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_badmin_public_administration_and_international_relations_is_seeded_with_depth(): void
    {
        $this->seedUpRequirements();

        $qualification = DB::table('qualifications')
            ->join('universities', 'universities.id', '=', 'qualifications.university_id')
            ->join('faculties', 'faculties.id', '=', 'qualifications.faculty_id')
            ->leftJoin('nqf_levels', 'nqf_levels.id', '=', 'qualifications.nqf_level_id')
            ->where('universities.abbreviation', 'UP')
            ->where('qualifications.name', 'Bachelor of Administration specialising in Public Administration and International Relations')
            ->select('qualifications.*', 'faculties.name as faculty_name', 'nqf_levels.level as nqf_level')
            ->first();

        $this->assertNotNull($qualification);
        $this->assertSame('Faculty of Economic and Management Sciences', $qualification->faculty_name);
        $this->assertSame(28, $qualification->aps_required);
        $this->assertEquals(3.0, (float) $qualification->duration_years);
        $this->assertSame(7, $qualification->nqf_level);
        $this->assertSame(6, $qualification->closing_month);
        $this->assertSame(30, $qualification->closing_day);
        $this->assertSame(
            'https://www.up.ac.za/programmes/undergraduate/badmin-specialising-public-administration-and-international-relations/2027',
            $qualification->source_url,
        );
        $this->assertStringContainsString('public administration', strtolower($qualification->notes));
        $this->assertStringContainsString('official UP application portal', $qualification->notes);
        $this->assertStringContainsString('Do not pay anyone who promises guaranteed admission', $qualification->notes);

        $requirements = DB::table('qualification_subject_requirements')
            ->where('qualification_id', $qualification->id)
            ->orderBy('id')
            ->get(['subject_name', 'aps_level_required', 'requirement_type', 'requirement_group']);

        $this->assertCount(3, $requirements);
        $this->assertSame('English Home Language or English First Additional Language', $requirements[0]->subject_name);
        $this->assertSame(5, $requirements[0]->aps_level_required);
        $this->assertSame('required', $requirements[0]->requirement_type);

        $this->assertSame('Mathematics', $requirements[1]->subject_name);
        $this->assertSame(3, $requirements[1]->aps_level_required);
        $this->assertSame('required', $requirements[1]->requirement_type);
        $this->assertSame('Mathematical Literacy', $requirements[2]->subject_name);
        $this->assertSame(4, $requirements[2]->aps_level_required);
        $this->assertSame('alternative', $requirements[2]->requirement_type);
        $this->assertSame($requirements[1]->requirement_group, $requirements[2]->requirement_group);
    }

    public function test_badmin_public_page_renders_seeded_depth(): void
    {
        $this->seedUpRequirements();

        $university = University::where('abbreviation', 'UP')->firstOrFail();
        $qualification = Qualification::where('university_id', $university->id)
            ->where('name', 'Bachelor of Administration specialising in Public Administration and International Relations')
            ->firstOrFail();

        $response = $this->get(route('public.qualifications.show', [
            'university' => $university->slug,
            'qualification' => $qualification->slug,
        ]));

        $response->assertOk();
        $response->assertSee('Bachelor of Administration specialising in Public Administration and International Relations');
        $response->assertSee('Level 7');
        $response->assertSee('3 years');
        $response->assertSee('Planning notes');
        $response->assertSee('Programme scope');
        $response->assertSee('official UP application portal');
        $response->assertSee('Document checklist');
        $response->assertSee('Application safety');
        $response->assertSee('Source and review');
        $response->assertSee($qualification->source_url, false);
    }

    public function test_up_undergraduate_catalogue_seeds_only_official_undergraduate_entries(): void
    {
        $this->seedUpRequirements();

        $up = University::where('abbreviation', 'UP')->firstOrFail();
        $qualifications = Qualification::where('university_id', $up->id)->get();

        $this->assertCount(128, $qualifications);
        $this->assertSame(128, $qualifications->pluck('slug')->unique()->count());

        $sourceUrls = $qualifications->pluck('source_url')->filter();
        $this->assertTrue(
            $sourceUrls->every(fn (string $sourceUrl): bool => str_contains($sourceUrl, '/programmes/undergraduate/') || $sourceUrl === 'https://www.up.ac.za/node/67483'),
        );
        $this->assertFalse($sourceUrls->contains(fn (string $sourceUrl): bool => str_contains($sourceUrl, '/programmes/postgraduate/')));

        $this->assertDatabaseHas('qualifications', [
            'university_id' => $up->id,
            'name' => 'BSc in Architecture',
            'source_url' => 'https://www.up.ac.za/programmes/undergraduate/bsc-architecture/2027',
        ]);
        $this->assertDatabaseHas('qualifications', [
            'university_id' => $up->id,
            'name' => 'Bachelor of Nursing Science',
            'source_url' => 'https://www.up.ac.za/node/67483',
        ]);
        $this->assertDatabaseHas('qualifications', [
            'university_id' => $up->id,
            'name' => 'Higher Certificate in Sports Sciences',
            'source_url' => 'https://www.up.ac.za/programmes/undergraduate/higher-certificate-sports-sciences/2027',
        ]);
        $this->assertDatabaseMissing('qualifications', [
            'university_id' => $up->id,
            'name' => 'Higher Certificate in Sports Sciences - One-year programme',
        ]);
        $this->assertDatabaseMissing('qualifications', [
            'university_id' => $up->id,
            'name' => 'Higher Certificate in Sports Sciences - Two-year programme',
        ]);

        $architecture = Qualification::where('university_id', $up->id)
            ->where('name', 'BSc in Architecture')
            ->firstOrFail();

        $this->assertStringContainsString('Programme code: 12132031.', $architecture->notes);
        $this->assertStringContainsString('Career pointers:', $architecture->notes);
        $this->assertStringContainsString('Closing-date context:', $architecture->notes);
    }

    private function seedUpRequirements(): void
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
            UpRequirementSeeder::class,
        ]);
    }
}
