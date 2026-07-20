<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\SiteVisit;
use App\Models\SocialPost;
use App\Models\SocialPostResponse;
use App\Models\User;
use App\Support\Social\SocialMediaConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SuperAdminActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_site_activity(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);

        $siteVisit = SiteVisit::create([
            'ip_address' => '127.0.0.1',
            'method' => 'GET',
            'url' => 'https://chamu.test/aps',
            'route_name' => 'aps.index',
            'device_type' => 'desktop',
            'platform' => 'macOS',
            'browser' => 'Safari',
            'visited_at' => now(),
        ]);
        SiteVisit::create([
            'ip_address' => '127.0.0.2',
            'method' => 'GET',
            'url' => 'https://chamu.test/aps?university_id=20',
            'route_name' => 'aps.index',
            'device_type' => 'desktop',
            'platform' => 'macOS',
            'browser' => 'Safari',
            'visited_at' => now(),
        ]);
        $auditLog = AuditLog::create([
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
        $response->assertSee('APS page, university selected, no APS yet');
        $response->assertSee(route('admin.site-visits.index'), false);
        $response->assertSee(route('admin.site-visits.show', $siteVisit), false);
        $response->assertSee(route('admin.activity-logs.index'), false);
        $response->assertSee(route('admin.audit-logs.index'), false);
        $response->assertSee(route('admin.audit-logs.show', $auditLog), false);
        $response->assertSee(route('admin.facebook.index'), false);
        $response->assertSee(route('admin.instagram.index'), false);
        $response->assertSee(route('admin.linkedin.index'), false);
        $response->assertSee('Accounts created');
        $response->assertSee('admin@example.com');
        $response->assertSee('Audit log');
        $response->assertSee('Automated marketing');
    }

    public function test_super_admin_can_view_social_pages_and_unified_activity_log(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $siteVisit = SiteVisit::create([
            'ip_address' => '127.0.0.8',
            'method' => 'GET',
            'url' => 'https://chamu.test/funding',
            'route_name' => 'funding.index',
            'device_type' => 'mobile',
            'platform' => 'iOS',
            'browser' => 'Safari',
            'visited_at' => now(),
        ]);
        $auditLog = AuditLog::create([
            'name' => 'Marketing audit seed',
            'event' => 'marketing.seeded',
            'user_id' => $superAdmin->id,
            'auditable_type' => User::class,
            'auditable_id' => $superAdmin->id,
            'metadata' => ['platform' => 'facebook'],
        ]);

        foreach ([
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'linkedin' => 'LinkedIn',
        ] as $routeKey => $platformName) {
            $response = $this->actingAs($superAdmin)->get(route('admin.'.$routeKey.'.index'));

            $response->assertOk();
            $response->assertSee($platformName);
            $response->assertSee('Post composer');
            $response->assertSee('Integration readiness');

            if ($routeKey === 'facebook') {
                $response->assertSee('Token configured');
            } else {
                $response->assertSee('API pending');
            }
        }

        $activityResponse = $this->actingAs($superAdmin)->get(route('admin.activity-logs.index'));

        $activityResponse->assertOk();
        $activityResponse->assertSee('Unified timeline');
        $activityResponse->assertSee('Site visit');
        $activityResponse->assertSee('Audit');
        $activityResponse->assertSee('https://chamu.test/funding');
        $activityResponse->assertSee('marketing.seeded');
        $activityResponse->assertSee(route('admin.site-visits.show', $siteVisit), false);
        $activityResponse->assertSee(route('admin.audit-logs.show', $auditLog), false);
    }

    public function test_facebook_admin_page_detects_token_without_rendering_it(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $token = SocialMediaConfig::accessToken('facebook');

        $response = $this->actingAs($superAdmin)->get(route('admin.facebook.index'));

        $this->assertNotNull($token);
        $response->assertOk();
        $response->assertSee('Access token configured');
        $response->assertSee('https://graph.facebook.com/v25.0/me/feed');
        $response->assertDontSee($token);
    }

    public function test_super_admin_can_store_review_and_publish_social_post_with_saved_response(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'page_12345'], 200),
        ]);

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);

        $storeResponse = $this->actingAs($superAdmin)->post(route('admin.facebook.posts.store'), [
            'title' => 'Hello Chamu campaign',
            'audience' => 'Parents and learners',
            'message' => 'Hello World! We are Chamu',
            'link_url' => 'https://chamu.test/funding',
            'media_url' => 'asset://welcome-card',
            'status' => 'draft',
            'intent' => 'queue',
        ]);

        $socialPost = SocialPost::first();

        $this->assertInstanceOf(SocialPost::class, $socialPost);
        $storeResponse->assertRedirect(route('admin.facebook.posts.show', $socialPost));
        $this->assertSame($superAdmin->id, $socialPost->user_id);
        $this->assertSame('facebook', $socialPost->platform);
        $this->assertSame('queued', $socialPost->status);
        $this->assertSame('Hello World! We are Chamu', $socialPost->request_payload['fields']['message']);
        $this->assertArrayNotHasKey('access_token', $socialPost->request_payload['fields']);

        $reviewResponse = $this->actingAs($superAdmin)->get(route('admin.facebook.posts.show', $socialPost));

        $reviewResponse->assertOk();
        $reviewResponse->assertSee('Hello Chamu campaign');
        $reviewResponse->assertSee('Saved request payload');
        $reviewResponse->assertDontSee(SocialMediaConfig::accessToken('facebook'));

        $publishResponse = $this->actingAs($superAdmin)->post(route('admin.facebook.posts.publish', $socialPost));
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $publishResponse->assertRedirect(route('admin.facebook.posts.show', $socialPost));
        $this->assertSame('published', $socialPost->status);
        $this->assertSame('page_12345', $socialPost->external_post_id);
        $this->assertSame(['id' => 'page_12345'], $socialPost->response_payload);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame($socialPost->id, $responseRecord->social_post_id);
        $this->assertSame('publish', $responseRecord->response_type);
        $this->assertSame('page_12345', $responseRecord->external_response_id);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['fields']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v25.0/me/feed'
                && $data['message'] === 'Hello World! We are Chamu'
                && $data['link'] === 'https://chamu.test/funding'
                && $data['access_token'] === SocialMediaConfig::accessToken('facebook');
        });
    }

    public function test_super_admin_can_view_full_site_visit_list_and_visit_details(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $oldVisit = SiteVisit::create([
            'ip_address' => '127.0.0.9',
            'method' => 'GET',
            'url' => 'https://chamu.test/old-page',
            'route_name' => 'old.route',
            'device_type' => 'desktop',
            'platform' => 'macOS',
            'browser' => 'Safari',
            'visited_at' => now()->subHours(3),
        ]);

        $listResponse = $this->actingAs($superAdmin)->get(route('admin.site-visits.index'));

        $listResponse->assertOk();
        $listResponse->assertSee("Who's on the site", false);
        $listResponse->assertSee('not limited to the last 10 minutes');
        $listResponse->assertSee('https://chamu.test/old-page');
        $listResponse->assertSee(route('admin.site-visits.show', $oldVisit), false);

        $detailResponse = $this->actingAs($superAdmin)->get(route('admin.site-visits.show', $oldVisit));

        $detailResponse->assertOk();
        $detailResponse->assertSee('site_visits');
        $detailResponse->assertSee('https://chamu.test/old-page');
        $detailResponse->assertSee('Route Name');
    }

    public function test_super_admin_can_view_full_audit_log_list_and_audit_details(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $auditLog = AuditLog::create([
            'name' => 'Custom audit',
            'event' => 'custom.event',
            'user_id' => $superAdmin->id,
            'auditable_type' => User::class,
            'auditable_id' => $superAdmin->id,
            'metadata' => ['reason' => 'test detail'],
        ]);

        $listResponse = $this->actingAs($superAdmin)->get(route('admin.audit-logs.index'));

        $listResponse->assertOk();
        $listResponse->assertSee('Audit records');
        $listResponse->assertSee('custom.event');
        $listResponse->assertSee(route('admin.audit-logs.show', $auditLog), false);

        $detailResponse = $this->actingAs($superAdmin)->get(route('admin.audit-logs.show', $auditLog));

        $detailResponse->assertOk();
        $detailResponse->assertSee('audit_logs');
        $detailResponse->assertSee('custom.event');
        $detailResponse->assertSee('test detail');
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

        $accountsResponse = $this->actingAs($superAdmin)->get(route('admin.accounts.index'));
        $accountsResponse->assertOk();
        $accountsResponse->assertSee('Account list');
        $accountsResponse->assertSee('learner-account@example.com');
        $accountsResponse->assertSee(route('admin.accounts.show', $learner), false);

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
     * @param  array<string, int>  $records
     * @param  array<string, mixed>  $overrides
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
