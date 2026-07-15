<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubjectFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_user_without_subject_preferences_sees_curriculum_subjects(): void
    {
        $records = $this->createSubjectRecords();

        $response = $this->actingAs($records['user'])->get('/');

        $response->assertOk();

        $subjects = $response->viewData('subjects');

        $this->assertTrue($subjects->contains('id', $records['subject_id']));
    }

    public function test_logged_in_user_without_subject_preferences_can_open_curriculum_subject_content(): void
    {
        $records = $this->createSubjectRecords();

        $response = $this->actingAs($records['user'])->get(route('content.index', [
            'subject_id' => $records['subject_id'],
        ]));

        $response->assertOk();
        $this->assertNotNull($response->viewData('subject'));
        $response->assertSee('Physical Sciences');
    }

    /**
     * @return array{user: User, subject_id: int}
     */
    private function createSubjectRecords(): array
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

        $categoryId = DB::table('subject_categories')->insertGetId([
            'name' => 'Core',
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $subjectId = DB::table('subjects')->insertGetId([
            'curriculum_id' => $curriculumId,
            'grade_id' => $gradeId,
            'subject_category_id' => $categoryId,
            'name' => 'Physical Sciences',
            'code' => 'PHSC',
            'abbreviation' => 'PHSC',
            'colour' => '#01225E',
            'icon' => 'flask-conical',
            'sort_order' => 1,
            'is_live' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $user = User::create([
            'user_type_id' => $userTypeId,
            'country_id' => $countryId,
            'curriculum_id' => $curriculumId,
            'grade_id' => $gradeId,
            'name' => 'Test Learner',
            'first_name' => 'Test',
            'last_name' => 'Learner',
            'username' => 'testlearner',
            'email' => 'learner@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        return [
            'user' => $user,
            'subject_id' => $subjectId,
        ];
    }
}
