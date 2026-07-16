<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_filter_aps_results_by_multiple_universities(): void
    {
        $records = $this->createLookupRecords();
        $typeId = $this->createQualificationType();
        $uj = $this->createUniversity($records['country_id'], 'University of Johannesburg', 'UJ');
        $uct = $this->createUniversity($records['country_id'], 'University of Cape Town', 'UCT');
        $wits = $this->createUniversity($records['country_id'], 'University of the Witwatersrand', 'WITS');

        $this->createQualification($uj, $typeId, 'UJ Engineering', 30);
        $this->createQualification($uct, $typeId, 'UCT Accounting', 34);
        $this->createQualification($wits, $typeId, 'Wits Medicine', 35);

        $response = $this->get(route('aps.index', [
            'aps_score' => 35,
            'university_ids' => [$uj, $uct],
        ]));

        $response->assertOk();
        $response->assertSee('UJ Engineering');
        $response->assertSee('UCT Accounting');
        $response->assertDontSee('Wits Medicine');
        $response->assertSee('2 universities selected');
    }

    public function test_aps_still_supports_legacy_single_university_filter(): void
    {
        $records = $this->createLookupRecords();
        $typeId = $this->createQualificationType();
        $uj = $this->createUniversity($records['country_id'], 'University of Johannesburg', 'UJ');
        $uct = $this->createUniversity($records['country_id'], 'University of Cape Town', 'UCT');

        $this->createQualification($uj, $typeId, 'UJ Engineering', 30);
        $this->createQualification($uct, $typeId, 'UCT Accounting', 34);

        $response = $this->get(route('aps.index', [
            'aps_score' => 35,
            'university_id' => $uct,
        ]));

        $response->assertOk();
        $response->assertSee('UCT Accounting');
        $response->assertDontSee('UJ Engineering');
        $response->assertSee('UCT (University of Cape Town)');
    }

    public function test_authenticated_user_can_view_aps_screen(): void
    {
        $records = $this->createLookupRecords();
        $user = User::factory()->create([
            'user_type_id' => $records['user_type_id'],
            'country_id' => $records['country_id'],
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'name' => 'Test Learner',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('aps.index', ['aps_score' => 32]));

        $response->assertOk();
        $response->assertSee('Find courses from your APS score');
    }

    /**
     * @return array<string, int>
     */
    private function createLookupRecords(): array
    {
        $now = now();

        $userTypeId = DB::table('user_types')->insertGetId([
            'name' => 'pupil',
            'description' => 'Learner',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'South Africa',
            'nationality' => 'South African',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $curriculumId = DB::table('curriculums')->insertGetId([
            'country_id' => $countryId,
            'name' => 'NSC (National Senior Certificate)',
            'abbreviation' => 'CAPS',
            'is_live' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $gradeId = DB::table('grades')->insertGetId([
            'curriculum_id' => $curriculumId,
            'name' => 'Grade 12',
            'sort_order' => 12,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'user_type_id' => $userTypeId,
            'country_id' => $countryId,
            'curriculum_id' => $curriculumId,
            'grade_id' => $gradeId,
        ];
    }

    private function createUniversity(int $countryId, string $name, string $abbreviation): int
    {
        return DB::table('universities')->insertGetId([
            'country_id' => $countryId,
            'name' => $name,
            'abbreviation' => $abbreviation,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createQualificationType(): int
    {
        return DB::table('qualification_types')->insertGetId([
            'name' => 'Bachelor Degree',
            'abbreviation' => 'BDeg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createQualification(int $universityId, int $typeId, string $name, int $apsRequired): void
    {
        $facultyId = DB::table('faculties')->insertGetId([
            'university_id' => $universityId,
            'name' => 'Test Faculty '.$universityId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('qualifications')->insert([
            'university_id' => $universityId,
            'faculty_id' => $facultyId,
            'qualification_type_id' => $typeId,
            'name' => $name,
            'duration_years' => 3,
            'aps_required' => $apsRequired,
            'is_selection_programme' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
