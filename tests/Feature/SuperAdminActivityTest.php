<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\SiteVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_site_activity(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);

        SiteVisit::create([
            'ip_address' => '127.0.0.1',
            'method' => 'GET',
            'url' => 'https://chamu.test/aps',
            'route_name' => 'aps.index',
            'device_type' => 'desktop',
            'platform' => 'macOS',
            'browser' => 'Safari',
            'visited_at' => now(),
        ]);
        AuditLog::create([
            'name' => 'Marks updated',
            'event' => 'marks.updated',
            'user_id' => $superAdmin->id,
            'auditable_type' => User::class,
            'auditable_id' => $superAdmin->id,
            'metadata' => ['term_id' => $records['term_id'], 'grade_id' => $records['grade_id']],
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.index'));

        $response->assertOk();
        $response->assertSee('Grouped by session');
        $response->assertSee('Guest visitor');
        $response->assertSee('Accounts created');
        $response->assertSee('admin@example.com');
        $response->assertSee('Mark-entry audit log');
    }

    public function test_super_admin_can_view_account_details_with_marks(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $learner = $this->createUser($records, [
            'name' => 'Learner Account',
            'first_name' => 'Learner',
            'last_name' => 'Account',
            'email' => 'learner-account@example.com',
            'username' => 'learneraccount',
        ]);

        DB::table('user_subject_preferences')->insert([
            'user_id' => $learner->id,
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'subject_id' => $records['subject_id'],
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_subject_results')->insert([
            'user_id' => $learner->id,
            'grade_id' => $records['grade_id'],
            'term_id' => $records['term_id'],
            'subject_id' => $records['subject_id'],
            'mark' => 82,
            'aps_score' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        SiteVisit::create([
            'user_id' => $learner->id,
            'ip_address' => '127.0.0.1',
            'method' => 'GET',
            'url' => 'https://chamu.test/marks',
            'route_name' => 'marks.index',
            'device_type' => 'desktop',
            'platform' => 'macOS',
            'browser' => 'Safari',
            'visited_at' => now(),
        ]);
        AuditLog::create([
            'name' => 'Marks updated',
            'event' => 'marks.updated',
            'user_id' => $learner->id,
            'auditable_type' => User::class,
            'auditable_id' => $learner->id,
            'metadata' => [
                'term_id' => $records['term_id'],
                'grade_id' => $records['grade_id'],
                'changed_marks' => [['subject_id' => $records['subject_id'], 'mark' => 82]],
            ],
        ]);
        DB::table('exam_sessions')->insert([
            'user_id' => $learner->id,
            'subject_id' => $records['subject_id'],
            'curriculum_id' => $records['curriculum_id'],
            'title' => 'Diagnostic quiz',
            'quiz_type' => 'practice',
            'source' => 'test',
            'score' => 8,
            'total_marks' => 10,
            'percentage' => 80,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $indexResponse = $this->actingAs($superAdmin)->get(route('admin.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('learner-account@example.com');
        $indexResponse->assertSee(route('admin.accounts.show', $learner), false);

        $detailResponse = $this->actingAs($superAdmin)->get(route('admin.accounts.show', $learner));

        $detailResponse->assertOk();
        $detailResponse->assertSee('Learner Account');
        $detailResponse->assertSee('Mathematics');
        $detailResponse->assertSee('82%');
        $detailResponse->assertSee('APS 7');
        $detailResponse->assertSee('Recent visits');
        $detailResponse->assertSee('Mark audits');
        $detailResponse->assertSee('Diagnostic quiz');
    }

    public function test_saving_marks_creates_an_audit_log(): void
    {
        $records = $this->createRecords();
        $user = $this->createUser($records);

        DB::table('user_subject_preferences')->insert([
            'user_id' => $user->id,
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'subject_id' => $records['subject_id'],
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->put(route('marks.update'), [
            'term_id' => $records['term_id'],
            'marks' => [
                $records['subject_id'] => 82,
            ],
        ]);

        $response->assertRedirect(route('marks.index', ['term_id' => $records['term_id']]));
        $this->assertDatabaseHas('user_subject_results', [
            'user_id' => $user->id,
            'term_id' => $records['term_id'],
            'subject_id' => $records['subject_id'],
            'mark' => 82,
            'aps_score' => 7,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'name' => 'Marks updated',
            'event' => 'marks.updated',
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function createRecords(): array
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
        $termId = DB::table('terms')->insertGetId([
            'curriculum_id' => $curriculumId,
            'grade_id' => $gradeId,
            'name' => 'Term 1',
            'from_date' => now()->startOfYear()->toDateString(),
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
            'name' => 'Mathematics',
            'code' => 'MATH',
            'abbreviation' => 'MATH',
            'sort_order' => 1,
            'is_live' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'user_type_id' => $userTypeId,
            'country_id' => $countryId,
            'curriculum_id' => $curriculumId,
            'grade_id' => $gradeId,
            'term_id' => $termId,
            'subject_id' => $subjectId,
        ];
    }

    /**
     * @param array<string, int> $records
     * @param array<string, mixed> $overrides
     */
    private function createUser(array $records, array $overrides = []): User
    {
        return User::create(array_merge([
            'user_type_id' => $records['user_type_id'],
            'country_id' => $records['country_id'],
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'name' => 'Test Learner',
            'first_name' => 'Test',
            'last_name' => 'Learner',
            'username' => 'testlearner',
            'email' => 'learner@example.com',
            'password' => Hash::make('Password123!'),
        ], $overrides));
    }
}
