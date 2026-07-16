<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_only_offers_pupil_accounts(): void
    {
        $this->createSignupLookups();
        DB::table('user_types')->insert([
            [
                'name' => 'teacher',
                'description' => 'Teacher',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'parent',
                'description' => 'Parent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('Pupil');
        $response->assertDontSee('Teacher');
        $response->assertDontSee('Parent');
    }

    public function test_new_user_can_register_and_is_redirected_to_subject_setup(): void
    {
        $lookups = $this->createSignupLookups();

        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'Learner',
            'username' => 'testlearner',
            'email' => 'learner@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'user_type_id' => $lookups['user_type_id'],
            'curriculum_id' => $lookups['curriculum_id'],
            'grade_id' => $lookups['grade_id'],
        ]);

        $response->assertRedirect(route('subjects.index'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name' => 'Test Learner',
            'first_name' => 'Test',
            'last_name' => 'Learner',
            'username' => 'testlearner',
            'email' => 'learner@example.com',
            'user_type_id' => $lookups['user_type_id'],
            'country_id' => $lookups['country_id'],
            'curriculum_id' => $lookups['curriculum_id'],
            'grade_id' => $lookups['grade_id'],
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function createSignupLookups(): array
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
}
