<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_email(): void
    {
        $records = $this->createSignupLookups();
        $user = $this->createUser($records, [
            'email' => 'kekanagomolemo@gmail.com',
            'username' => 'kekanagomolemo',
            'password' => Hash::make('gomolemo1000'),
        ]);

        $response = $this->post(route('login.store'), [
            'username' => 'kekanagomolemo@gmail.com',
            'password' => 'gomolemo1000',
        ]);

        $response->assertRedirect(route('aps.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_username(): void
    {
        $records = $this->createSignupLookups();
        $user = $this->createUser($records, [
            'email' => 'learner@example.com',
            'username' => 'testlearner',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->post(route('login.store'), [
            'username' => 'testlearner',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('aps.index'));
        $this->assertAuthenticatedAs($user);
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

    /**
     * @param array<string, int> $records
     * @param array<string, mixed> $overrides
     */
    private function createUser(array $records, array $overrides): User
    {
        return User::factory()->create(array_merge([
            'user_type_id' => $records['user_type_id'],
            'country_id' => $records['country_id'],
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'name' => 'Test Learner',
        ], $overrides));
    }
}
