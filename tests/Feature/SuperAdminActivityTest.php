<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\SiteVisit;
use App\Models\SocialPost;
use App\Models\SocialPostResponse;
use App\Models\User;
use App\Support\Social\FacebookGraph;
use App\Support\Social\InstagramGraph;
use App\Support\Social\LinkedInGraph;
use App\Support\Social\SocialMediaConfig;
use App\Support\Social\ThreadsGraph;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
        $response->assertSee(route('admin.threads.index'), false);
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
            'threads' => 'Threads',
            'linkedin' => 'LinkedIn',
        ] as $routeKey => $platformName) {
            $response = $this->actingAs($superAdmin)->get(route('admin.'.$routeKey.'.index'));

            $response->assertOk();
            $response->assertSee($platformName);
            $response->assertSee('Post composer');
            $response->assertSee('Integration readiness');

            if (in_array($routeKey, ['facebook', 'instagram', 'threads'], true)) {
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
        $response->assertSee('https://graph.facebook.com/v25.0/testing-facebook-page/feed');
        $response->assertDontSee($token);
    }

    public function test_instagram_admin_page_uses_stored_business_account_without_rendering_token(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $token = SocialMediaConfig::accessToken('instagram');

        $response = $this->actingAs($superAdmin)->get(route('admin.instagram.index'));

        $this->assertNotNull($token);
        $response->assertOk();
        $response->assertSee('Access token configured');
        $response->assertSee(InstagramGraph::mediaEndpoint());
        $response->assertSee(InstagramGraph::mediaPublishEndpoint());
        $response->assertDontSee($token);
    }

    public function test_threads_admin_page_uses_stored_account_without_rendering_token(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $token = SocialMediaConfig::accessToken('threads');

        $response = $this->actingAs($superAdmin)->get(route('admin.threads.index'));

        $this->assertNotNull($token);
        $response->assertOk();
        $response->assertSee('Access token configured');
        $response->assertSee(ThreadsGraph::threadsEndpoint());
        $response->assertSee(ThreadsGraph::threadsPublishEndpoint());
        $response->assertSee('maxlength="500"', false);
        $response->assertSee('500 characters max on Threads');
        $response->assertDontSee($token);
    }

    public function test_linkedin_admin_page_uses_stored_app_credentials_without_rendering_credential(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);

        $response = $this->actingAs($superAdmin)->get(route('admin.linkedin.index'));

        $response->assertOk();
        $response->assertSee('LinkedIn OAuth access token pending');
        $response->assertSee('Organization authors need w_organization_social');
        $response->assertSee('https://api.linkedin.com/rest/posts');
        $response->assertSee('https://api.linkedin.com/rest/images?action=initializeUpload');
        $response->assertDontSee(LinkedInGraph::clientId());
        $response->assertDontSee(LinkedInGraph::clientCredential());
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

            return $request->url() === 'https://graph.facebook.com/v25.0/testing-facebook-page/feed'
                && $data['message'] === 'Hello World! We are Chamu'
                && $data['link'] === 'https://chamu.test/funding'
                && $data['access_token'] === SocialMediaConfig::accessToken('facebook');
        });
    }

    public function test_super_admin_can_upload_facebook_image_and_store_photo_payload(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $image = $this->uploadedLogoImage();

        $storeResponse = $this->actingAs($superAdmin)->post(route('admin.facebook.posts.store'), [
            'title' => 'Facebook logo post',
            'audience' => 'Parents and learners',
            'message' => 'Chamu Logo',
            'image_upload' => $image,
            'status' => 'draft',
            'intent' => 'queue',
        ]);

        $socialPost = SocialPost::first();

        $this->assertInstanceOf(SocialPost::class, $socialPost);
        $storeResponse->assertRedirect(route('admin.facebook.posts.show', $socialPost));
        $this->assertSame('facebook', $socialPost->platform);
        $this->assertSame('queued', $socialPost->status);
        $this->assertStringStartsWith(url('images/social-posts/facebook').'/', $socialPost->media_url);
        $this->assertSame(FacebookGraph::photosEndpoint(), $socialPost->request_payload['endpoint']);
        $this->assertSame('Chamu Logo', $socialPost->request_payload['fields']['caption']);
        $this->assertSame($socialPost->media_url, $socialPost->request_payload['fields']['url']);
        $this->assertArrayNotHasKey('access_token', $socialPost->request_payload['fields']);

        $publicPath = $this->publicPathFromUrl($socialPost->media_url);
        $this->assertFileExists($publicPath);
        $this->assertSame('image/png', mime_content_type($publicPath));
        @unlink($publicPath);
    }

    public function test_super_admin_can_publish_facebook_image_post_with_saved_response(): void
    {
        Http::fake([
            FacebookGraph::photosEndpoint() => Http::response(['id' => 'photo_12345', 'post_id' => 'page_12345'], 200),
        ]);

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'facebook',
            'title' => 'Facebook image launch',
            'message' => 'Chamu Logo',
            'audience' => 'Parents and learners',
            'media_url' => 'https://chamu.co.za/images/brand/chamu-logo.png',
            'status' => 'queued',
        ]);

        $publishResponse = $this->actingAs($superAdmin)->post(route('admin.facebook.posts.publish', $socialPost));
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $publishResponse->assertRedirect(route('admin.facebook.posts.show', $socialPost));
        $this->assertSame('published', $socialPost->status);
        $this->assertSame('page_12345', $socialPost->external_post_id);
        $this->assertSame(FacebookGraph::photosEndpoint(), $socialPost->request_payload['endpoint']);
        $this->assertSame('Chamu Logo', $socialPost->request_payload['fields']['caption']);
        $this->assertSame('https://chamu.co.za/images/brand/chamu-logo.png', $socialPost->request_payload['fields']['url']);
        $this->assertSame(['id' => 'photo_12345', 'post_id' => 'page_12345'], $socialPost->response_payload);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame('publish', $responseRecord->response_type);
        $this->assertSame('page_12345', $responseRecord->external_response_id);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['fields']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === FacebookGraph::photosEndpoint()
                && $data['caption'] === 'Chamu Logo'
                && $data['url'] === 'https://chamu.co.za/images/brand/chamu-logo.png'
                && $data['access_token'] === SocialMediaConfig::accessToken('facebook');
        });
    }

    public function test_super_admin_can_comment_on_published_facebook_post(): void
    {
        Http::fake([
            FacebookGraph::commentsEndpoint('page_12345') => Http::response(['id' => 'comment_12345'], 200),
        ]);

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'facebook',
            'title' => 'Facebook launch',
            'message' => 'Hello World! We are Chamu',
            'audience' => 'Parents and learners',
            'status' => 'published',
            'external_post_id' => 'page_12345',
            'published_at' => now(),
        ]);

        $commentResponse = $this->actingAs($superAdmin)->post(route('admin.facebook.posts.comments.store', $socialPost), [
            'comment_message' => 'Thanks for the question. We can help you compare APS and bursaries.',
        ]);
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $commentResponse->assertRedirect(route('admin.facebook.posts.show', $socialPost));
        $this->assertNotNull($socialPost->last_synced_at);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame('comment', $responseRecord->response_type);
        $this->assertSame('comment_12345', $responseRecord->external_response_id);
        $this->assertSame('Thanks for the question. We can help you compare APS and bursaries.', $responseRecord->body);
        $this->assertSame(FacebookGraph::commentsEndpoint('page_12345'), $responseRecord->request_payload['endpoint']);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['fields']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === FacebookGraph::commentsEndpoint('page_12345')
                && $data['message'] === 'Thanks for the question. We can help you compare APS and bursaries.'
                && $data['access_token'] === SocialMediaConfig::accessToken('facebook');
        });
    }

    public function test_super_admin_can_fetch_facebook_insights_for_published_post(): void
    {
        Http::fake([
            FacebookGraph::insightsEndpoint('page_12345').'*' => Http::response([
                'data' => [
                    [
                        'name' => 'post_impressions',
                        'period' => 'lifetime',
                        'values' => [['value' => 321]],
                    ],
                    [
                        'name' => 'post_clicks',
                        'period' => 'lifetime',
                        'values' => [['value' => 27]],
                    ],
                ],
            ], 200),
        ]);

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'facebook',
            'title' => 'Facebook launch',
            'message' => 'Hello World! We are Chamu',
            'audience' => 'Parents and learners',
            'status' => 'published',
            'external_post_id' => 'page_12345',
            'published_at' => now(),
        ]);

        $showResponse = $this->actingAs($superAdmin)->get(route('admin.facebook.posts.show', $socialPost));

        $showResponse->assertOk();
        $showResponse->assertSee('Facebook comments and insights');
        $showResponse->assertSee(route('admin.facebook.posts.insights.fetch', $socialPost), false);

        $insightsResponse = $this->actingAs($superAdmin)->post(route('admin.facebook.posts.insights.fetch', $socialPost));
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $insightsResponse->assertRedirect(route('admin.facebook.posts.show', $socialPost));
        $this->assertNotNull($socialPost->last_synced_at);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame('insights', $responseRecord->response_type);
        $this->assertSame('page_12345', $responseRecord->external_response_id);
        $this->assertSame('Facebook insights fetched.', $responseRecord->body);
        $this->assertSame(FacebookGraph::insightsEndpoint('page_12345'), $responseRecord->request_payload['endpoint']);
        $this->assertSame('post_impressions,post_impressions_unique,post_engaged_users,post_clicks,post_reactions_by_type_total', $responseRecord->request_payload['fields']['metric']);
        $this->assertSame(321, $responseRecord->response_payload['data'][0]['values'][0]['value']);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['fields']);

        Http::assertSent(function ($request) {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $data = array_merge($query, $request->data());

            return $request->method() === 'GET'
                && str_starts_with($request->url(), FacebookGraph::insightsEndpoint('page_12345'))
                && $data['metric'] === 'post_impressions,post_impressions_unique,post_engaged_users,post_clicks,post_reactions_by_type_total'
                && $data['access_token'] === SocialMediaConfig::accessToken('facebook');
        });
    }

    public function test_super_admin_can_upload_instagram_image_and_store_public_media_url(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $image = $this->uploadedLogoImage();

        $storeResponse = $this->actingAs($superAdmin)->post(route('admin.instagram.posts.store'), [
            'title' => 'Instagram logo post',
            'audience' => 'Student leads',
            'message' => 'Chamu Logo',
            'image_upload' => $image,
            'status' => 'draft',
            'intent' => 'queue',
        ]);

        $socialPost = SocialPost::first();

        $this->assertInstanceOf(SocialPost::class, $socialPost);
        $storeResponse->assertRedirect(route('admin.instagram.posts.show', $socialPost));
        $this->assertSame('instagram', $socialPost->platform);
        $this->assertSame('queued', $socialPost->status);
        $this->assertStringStartsWith(url('images/social-posts/instagram').'/', $socialPost->media_url);
        $this->assertSame('Chamu Logo', $socialPost->request_payload['fields']['caption']);
        $this->assertSame($socialPost->media_url, $socialPost->request_payload['fields']['image_url']);
        $this->assertArrayNotHasKey('access_token', $socialPost->request_payload['fields']);

        $publicPath = $this->publicPathFromUrl($socialPost->media_url);
        $this->assertFileExists($publicPath);
        $this->assertSame('image/png', mime_content_type($publicPath));
        @unlink($publicPath);
    }

    public function test_super_admin_can_publish_instagram_image_post_with_saved_response(): void
    {
        Http::fake([
            InstagramGraph::mediaEndpoint() => Http::response(['id' => '18337455196270855'], 200),
            InstagramGraph::mediaPublishEndpoint() => Http::response(['id' => '18088798253113211'], 200),
        ]);

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'instagram',
            'title' => 'Instagram launch',
            'message' => 'Chamu Logo',
            'audience' => 'Student leads',
            'media_url' => 'https://chamu.co.za/images/brand/chamu-logo.png',
            'status' => 'queued',
        ]);

        $publishResponse = $this->actingAs($superAdmin)->post(route('admin.instagram.posts.publish', $socialPost));
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $publishResponse->assertRedirect(route('admin.instagram.posts.show', $socialPost));
        $this->assertSame('published', $socialPost->status);
        $this->assertSame('18088798253113211', $socialPost->external_post_id);
        $this->assertSame('18337455196270855', $socialPost->request_payload['publish_fields']['creation_id']);
        $this->assertSame('18337455196270855', $socialPost->response_payload['media_container']['id']);
        $this->assertSame('18088798253113211', $socialPost->response_payload['publish']['id']);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame($socialPost->id, $responseRecord->social_post_id);
        $this->assertSame('publish', $responseRecord->response_type);
        $this->assertSame('18088798253113211', $responseRecord->external_response_id);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['fields']);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['publish_fields']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === InstagramGraph::mediaEndpoint()
                && $data['image_url'] === 'https://chamu.co.za/images/brand/chamu-logo.png'
                && $data['caption'] === 'Chamu Logo'
                && $data['access_token'] === SocialMediaConfig::accessToken('instagram');
        });
        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === InstagramGraph::mediaPublishEndpoint()
                && $data['creation_id'] === '18337455196270855'
                && $data['access_token'] === SocialMediaConfig::accessToken('instagram');
        });
    }

    public function test_super_admin_can_comment_on_published_instagram_post(): void
    {
        Http::fake([
            InstagramGraph::commentsEndpoint('18088798253113211') => Http::response(['id' => '17875570523882024'], 200),
        ]);

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'instagram',
            'title' => 'Instagram launch',
            'message' => 'Chamu Logo',
            'audience' => 'Student leads',
            'media_url' => 'https://chamu.co.za/images/brand/chamu-logo.png',
            'status' => 'published',
            'external_post_id' => '18088798253113211',
            'published_at' => now(),
        ]);

        $showResponse = $this->actingAs($superAdmin)->get(route('admin.instagram.posts.show', $socialPost));

        $showResponse->assertOk();
        $showResponse->assertSee('Instagram comments and insights');
        $showResponse->assertSee(route('admin.instagram.posts.comments.store', $socialPost), false);
        $showResponse->assertSee(route('admin.instagram.posts.insights.fetch', $socialPost), false);

        $commentResponse = $this->actingAs($superAdmin)->post(route('admin.instagram.posts.comments.store', $socialPost), [
            'comment_message' => 'Thanks for following Chamu. We can help you compare APS and bursaries.',
        ]);
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $commentResponse->assertRedirect(route('admin.instagram.posts.show', $socialPost));
        $this->assertNotNull($socialPost->last_synced_at);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame('comment', $responseRecord->response_type);
        $this->assertSame('17875570523882024', $responseRecord->external_response_id);
        $this->assertSame('Thanks for following Chamu. We can help you compare APS and bursaries.', $responseRecord->body);
        $this->assertSame(InstagramGraph::commentsEndpoint('18088798253113211'), $responseRecord->request_payload['endpoint']);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['fields']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === InstagramGraph::commentsEndpoint('18088798253113211')
                && $data['message'] === 'Thanks for following Chamu. We can help you compare APS and bursaries.'
                && $data['access_token'] === SocialMediaConfig::accessToken('instagram');
        });
    }

    public function test_super_admin_can_fetch_instagram_insights_for_published_post(): void
    {
        Http::fake([
            InstagramGraph::insightsEndpoint('18088798253113211').'*' => Http::response([
                'data' => [
                    [
                        'name' => 'views',
                        'period' => 'lifetime',
                        'values' => [['value' => 456]],
                    ],
                    [
                        'name' => 'total_interactions',
                        'period' => 'lifetime',
                        'values' => [['value' => 89]],
                    ],
                ],
            ], 200),
        ]);

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'instagram',
            'title' => 'Instagram launch',
            'message' => 'Chamu Logo',
            'audience' => 'Student leads',
            'media_url' => 'https://chamu.co.za/images/brand/chamu-logo.png',
            'status' => 'published',
            'external_post_id' => '18088798253113211',
            'published_at' => now(),
        ]);

        $insightsResponse = $this->actingAs($superAdmin)->post(route('admin.instagram.posts.insights.fetch', $socialPost));
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $insightsResponse->assertRedirect(route('admin.instagram.posts.show', $socialPost));
        $this->assertNotNull($socialPost->last_synced_at);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame('insights', $responseRecord->response_type);
        $this->assertSame('18088798253113211', $responseRecord->external_response_id);
        $this->assertSame('Instagram insights fetched.', $responseRecord->body);
        $this->assertSame(InstagramGraph::insightsEndpoint('18088798253113211'), $responseRecord->request_payload['endpoint']);
        $this->assertSame('views,reach,likes,comments,shares,saved,total_interactions', $responseRecord->request_payload['fields']['metric']);
        $this->assertSame(456, $responseRecord->response_payload['data'][0]['values'][0]['value']);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['fields']);

        Http::assertSent(function ($request) {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $data = array_merge($query, $request->data());

            return $request->method() === 'GET'
                && str_starts_with($request->url(), InstagramGraph::insightsEndpoint('18088798253113211'))
                && $data['metric'] === 'views,reach,likes,comments,shares,saved,total_interactions'
                && $data['access_token'] === SocialMediaConfig::accessToken('instagram');
        });
    }

    public function test_instagram_publish_promotes_legacy_storage_image_url_to_public_folder(): void
    {
        Storage::fake('public');
        Http::fake([
            InstagramGraph::mediaEndpoint() => Http::response(['id' => '18337455196270855'], 200),
            InstagramGraph::mediaPublishEndpoint() => Http::response(['id' => '18088798253113211'], 200),
        ]);

        $legacyFilename = 'legacy-instagram-test.png';
        $legacyStoragePath = 'social-posts/instagram/'.$legacyFilename;
        Storage::disk('public')->put($legacyStoragePath, file_get_contents(public_path('images/brand/chamu-logo.png')));
        @unlink(public_path('images/social-posts/instagram/'.$legacyFilename));

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'instagram',
            'title' => 'Legacy Instagram launch',
            'message' => 'Chamu Logo',
            'audience' => 'Student leads',
            'media_url' => url('storage/'.$legacyStoragePath),
            'status' => 'queued',
        ]);

        $this->actingAs($superAdmin)->post(route('admin.instagram.posts.publish', $socialPost));
        $socialPost->refresh();

        $this->assertSame(url('images/social-posts/instagram/'.$legacyFilename), $socialPost->media_url);
        $publicPath = $this->publicPathFromUrl($socialPost->media_url);
        $this->assertFileExists($publicPath);
        $this->assertSame('image/png', mime_content_type($publicPath));

        Http::assertSent(function ($request) use ($socialPost) {
            $data = $request->data();

            return $request->url() === InstagramGraph::mediaEndpoint()
                && $data['image_url'] === $socialPost->media_url;
        });

        @unlink($publicPath);
    }

    public function test_super_admin_can_upload_linkedin_image_and_store_post_payload(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $image = $this->uploadedLogoImage();

        $storeResponse = $this->actingAs($superAdmin)->post(route('admin.linkedin.posts.store'), [
            'title' => 'LinkedIn logo post',
            'audience' => 'Sponsors and schools',
            'message' => 'Hello We Are Chamu',
            'image_upload' => $image,
            'status' => 'draft',
            'intent' => 'queue',
        ]);

        $socialPost = SocialPost::first();

        $this->assertInstanceOf(SocialPost::class, $socialPost);
        $storeResponse->assertRedirect(route('admin.linkedin.posts.show', $socialPost));
        $this->assertSame('linkedin', $socialPost->platform);
        $this->assertSame('queued', $socialPost->status);
        $this->assertStringStartsWith(url('images/social-posts/linkedin').'/', $socialPost->media_url);
        $this->assertSame('Hello We Are Chamu', $socialPost->request_payload['fields']['commentary']);
        $this->assertSame('PUBLIC', $socialPost->request_payload['fields']['visibility']);
        $this->assertSame($socialPost->media_url, $socialPost->request_payload['fields']['image_source_url']);
        $this->assertArrayNotHasKey('access_token', $socialPost->request_payload['fields']);

        $publicPath = $this->publicPathFromUrl($socialPost->media_url);
        $this->assertFileExists($publicPath);
        $this->assertSame('image/png', mime_content_type($publicPath));
        @unlink($publicPath);
    }

    public function test_linkedin_publish_is_blocked_until_oauth_token_and_author_are_configured(): void
    {
        Http::fake();

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'linkedin',
            'title' => 'LinkedIn launch',
            'message' => 'Hello We Are Chamu',
            'audience' => 'Sponsors and schools',
            'status' => 'queued',
        ]);

        $publishResponse = $this->actingAs($superAdmin)->post(route('admin.linkedin.posts.publish', $socialPost));
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $publishResponse->assertRedirect(route('admin.linkedin.posts.show', $socialPost));
        $this->assertSame('failed', $socialPost->status);
        $this->assertSame('LinkedIn publishing needs OAuth access token and author URN.', $socialPost->error_message);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame('publish_blocked', $responseRecord->response_type);
        $this->assertSame('LinkedIn publishing needs OAuth access token and author URN.', $responseRecord->body);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['fields']);

        Http::assertNothingSent();
    }

    public function test_super_admin_can_upload_threads_image_and_store_image_payload(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $image = $this->uploadedLogoImage();

        $storeResponse = $this->actingAs($superAdmin)->post(route('admin.threads.posts.store'), [
            'title' => 'Threads logo post',
            'audience' => 'Student leads',
            'message' => 'Hello We Are Chamu',
            'image_upload' => $image,
            'status' => 'draft',
            'intent' => 'queue',
        ]);

        $socialPost = SocialPost::first();

        $this->assertInstanceOf(SocialPost::class, $socialPost);
        $storeResponse->assertRedirect(route('admin.threads.posts.show', $socialPost));
        $this->assertSame('threads', $socialPost->platform);
        $this->assertSame('queued', $socialPost->status);
        $this->assertStringStartsWith(url('images/social-posts/threads').'/', $socialPost->media_url);
        $this->assertSame('IMAGE', $socialPost->request_payload['fields']['media_type']);
        $this->assertSame('Hello We Are Chamu', $socialPost->request_payload['fields']['text']);
        $this->assertSame($socialPost->media_url, $socialPost->request_payload['fields']['image_url']);
        $this->assertArrayNotHasKey('access_token', $socialPost->request_payload['fields']);

        $publicPath = $this->publicPathFromUrl($socialPost->media_url);
        $this->assertFileExists($publicPath);
        $this->assertSame('image/png', mime_content_type($publicPath));
        @unlink($publicPath);
    }

    public function test_threads_post_message_cannot_exceed_character_limit(): void
    {
        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);

        $storeResponse = $this->actingAs($superAdmin)->post(route('admin.threads.posts.store'), [
            'title' => 'Threads long post',
            'audience' => 'Student leads',
            'message' => str_repeat('a', ThreadsGraph::maxTextLength() + 1),
            'status' => 'draft',
            'intent' => 'queue',
        ]);

        $storeResponse->assertSessionHasErrors('message');
        $this->assertDatabaseCount('social_posts', 0);
    }

    public function test_super_admin_can_publish_threads_text_post_with_saved_response(): void
    {
        Http::fake([
            ThreadsGraph::threadsEndpoint() => Http::response(['id' => '18060395891524287'], 200),
            ThreadsGraph::threadsPublishEndpoint() => Http::response(['id' => '18158273836488130'], 200),
        ]);

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'threads',
            'title' => 'Threads launch',
            'message' => 'Hello We Are Chamu',
            'audience' => 'Student leads',
            'status' => 'queued',
        ]);

        $publishResponse = $this->actingAs($superAdmin)->post(route('admin.threads.posts.publish', $socialPost));
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $publishResponse->assertRedirect(route('admin.threads.posts.show', $socialPost));
        $this->assertSame('published', $socialPost->status);
        $this->assertSame('18158273836488130', $socialPost->external_post_id);
        $this->assertSame('TEXT', $socialPost->request_payload['fields']['media_type']);
        $this->assertSame('Hello We Are Chamu', $socialPost->request_payload['fields']['text']);
        $this->assertSame('18060395891524287', $socialPost->request_payload['publish_fields']['creation_id']);
        $this->assertSame('18060395891524287', $socialPost->response_payload['thread_container']['id']);
        $this->assertSame('18158273836488130', $socialPost->response_payload['publish']['id']);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame($socialPost->id, $responseRecord->social_post_id);
        $this->assertSame('publish', $responseRecord->response_type);
        $this->assertSame('18158273836488130', $responseRecord->external_response_id);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['fields']);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['publish_fields']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === ThreadsGraph::threadsEndpoint()
                && $data['media_type'] === 'TEXT'
                && $data['text'] === 'Hello We Are Chamu'
                && $data['access_token'] === SocialMediaConfig::accessToken('threads');
        });
        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === ThreadsGraph::threadsPublishEndpoint()
                && $data['creation_id'] === '18060395891524287'
                && $data['access_token'] === SocialMediaConfig::accessToken('threads');
        });
    }

    public function test_super_admin_can_publish_threads_image_post_with_saved_response(): void
    {
        Http::fake([
            ThreadsGraph::threadsEndpoint() => Http::response(['id' => '18060395891524287'], 200),
            ThreadsGraph::threadsPublishEndpoint() => Http::response(['id' => '18158273836488130'], 200),
        ]);

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'threads',
            'title' => 'Threads image launch',
            'message' => 'Hello We Are Chamu',
            'audience' => 'Student leads',
            'media_url' => 'https://chamu.co.za/images/brand/chamu-logo.png',
            'status' => 'queued',
        ]);

        $publishResponse = $this->actingAs($superAdmin)->post(route('admin.threads.posts.publish', $socialPost));
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $publishResponse->assertRedirect(route('admin.threads.posts.show', $socialPost));
        $this->assertSame('published', $socialPost->status);
        $this->assertSame('18158273836488130', $socialPost->external_post_id);
        $this->assertSame('IMAGE', $socialPost->request_payload['fields']['media_type']);
        $this->assertSame('Hello We Are Chamu', $socialPost->request_payload['fields']['text']);
        $this->assertSame('https://chamu.co.za/images/brand/chamu-logo.png', $socialPost->request_payload['fields']['image_url']);
        $this->assertSame('18060395891524287', $socialPost->request_payload['publish_fields']['creation_id']);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame('publish', $responseRecord->response_type);
        $this->assertSame('18158273836488130', $responseRecord->external_response_id);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['fields']);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['publish_fields']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === ThreadsGraph::threadsEndpoint()
                && $data['media_type'] === 'IMAGE'
                && $data['text'] === 'Hello We Are Chamu'
                && $data['image_url'] === 'https://chamu.co.za/images/brand/chamu-logo.png'
                && $data['access_token'] === SocialMediaConfig::accessToken('threads');
        });
        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === ThreadsGraph::threadsPublishEndpoint()
                && $data['creation_id'] === '18060395891524287'
                && $data['access_token'] === SocialMediaConfig::accessToken('threads');
        });
    }

    public function test_threads_publish_stores_http_diagnostics_when_container_response_is_empty(): void
    {
        Http::fake([
            ThreadsGraph::threadsEndpoint() => Http::response('', 500, [
                'Content-Type' => 'text/plain',
                'X-FB-Trace-ID' => 'trace_12345',
            ]),
        ]);

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'threads',
            'title' => 'Threads launch',
            'message' => 'Hi',
            'audience' => 'Student leads',
            'status' => 'queued',
        ]);

        $publishResponse = $this->actingAs($superAdmin)->post(route('admin.threads.posts.publish', $socialPost));
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $publishResponse->assertRedirect(route('admin.threads.posts.show', $socialPost));
        $this->assertSame('failed', $socialPost->status);
        $this->assertSame('Threads container failed with HTTP 500.', $socialPost->error_message);
        $this->assertSame([], $socialPost->request_payload['publish_fields']);
        $this->assertSame(500, $socialPost->response_payload['thread_container']['status']);
        $this->assertFalse($socialPost->response_payload['thread_container']['successful']);
        $this->assertSame('', $socialPost->response_payload['thread_container']['body']);
        $this->assertSame('trace_12345', $socialPost->response_payload['thread_container']['headers']['x-fb-trace-id']);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame('container_error', $responseRecord->response_type);
        $this->assertSame('Threads container failed with HTTP 500.', $responseRecord->body);
        $this->assertSame(500, $responseRecord->response_payload['status']);
        $this->assertSame('trace_12345', $responseRecord->response_payload['headers']['x-fb-trace-id']);

        Http::assertSentCount(1);
    }

    public function test_super_admin_can_comment_on_published_threads_post(): void
    {
        Http::fake([
            ThreadsGraph::threadsEndpoint() => Http::response(['id' => '18060395891524287'], 200),
            ThreadsGraph::threadsPublishEndpoint() => Http::response(['id' => '18158273836488131'], 200),
        ]);

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'threads',
            'title' => 'Threads launch',
            'message' => 'Hello We Are Chamu',
            'audience' => 'Student leads',
            'status' => 'published',
            'external_post_id' => '18158273836488130',
            'published_at' => now(),
        ]);

        $showResponse = $this->actingAs($superAdmin)->get(route('admin.threads.posts.show', $socialPost));

        $showResponse->assertOk();
        $showResponse->assertSee('Threads comments and insights');
        $showResponse->assertSee(route('admin.threads.posts.comments.store', $socialPost), false);
        $showResponse->assertSee(route('admin.threads.posts.insights.fetch', $socialPost), false);
        $showResponse->assertSee('maxlength="500"', false);

        $commentResponse = $this->actingAs($superAdmin)->post(route('admin.threads.posts.comments.store', $socialPost), [
            'comment_message' => 'Thanks for the question. We can help you compare APS and bursaries.',
        ]);
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $commentResponse->assertRedirect(route('admin.threads.posts.show', $socialPost));
        $this->assertNotNull($socialPost->last_synced_at);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame('reply', $responseRecord->response_type);
        $this->assertSame('18158273836488131', $responseRecord->external_response_id);
        $this->assertSame('Thanks for the question. We can help you compare APS and bursaries.', $responseRecord->body);
        $this->assertSame(ThreadsGraph::threadsEndpoint(), $responseRecord->request_payload['endpoint']);
        $this->assertSame(ThreadsGraph::threadsPublishEndpoint(), $responseRecord->request_payload['publish_endpoint']);
        $this->assertSame('18158273836488130', $responseRecord->request_payload['fields']['reply_to_id']);
        $this->assertSame('18060395891524287', $responseRecord->request_payload['publish_fields']['creation_id']);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['fields']);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['publish_fields']);

        Http::assertSent(function ($request) use ($socialPost) {
            $data = $request->data();

            return $request->url() === ThreadsGraph::threadsEndpoint()
                && $data['media_type'] === 'TEXT'
                && $data['text'] === 'Thanks for the question. We can help you compare APS and bursaries.'
                && $data['reply_to_id'] === $socialPost->external_post_id
                && $data['access_token'] === SocialMediaConfig::accessToken('threads');
        });
        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === ThreadsGraph::threadsPublishEndpoint()
                && $data['creation_id'] === '18060395891524287'
                && $data['access_token'] === SocialMediaConfig::accessToken('threads');
        });
    }

    public function test_super_admin_can_fetch_threads_insights_for_published_post(): void
    {
        Http::fake([
            ThreadsGraph::insightsEndpoint('18158273836488130').'*' => Http::response([
                'data' => [
                    [
                        'name' => 'views',
                        'period' => 'lifetime',
                        'values' => [['value' => 123]],
                    ],
                    [
                        'name' => 'likes',
                        'period' => 'lifetime',
                        'values' => [['value' => 45]],
                    ],
                ],
            ], 200),
        ]);

        $records = $this->createRecords();
        $superAdmin = $this->createUser($records, ['is_super_admin' => true, 'email' => 'admin@example.com', 'username' => 'admin']);
        $socialPost = SocialPost::create([
            'user_id' => $superAdmin->id,
            'platform' => 'threads',
            'title' => 'Threads launch',
            'message' => 'Hello We Are Chamu',
            'audience' => 'Student leads',
            'status' => 'published',
            'external_post_id' => '18158273836488130',
            'published_at' => now(),
        ]);

        $insightsResponse = $this->actingAs($superAdmin)->post(route('admin.threads.posts.insights.fetch', $socialPost));
        $socialPost->refresh();
        $responseRecord = SocialPostResponse::first();

        $insightsResponse->assertRedirect(route('admin.threads.posts.show', $socialPost));
        $this->assertNotNull($socialPost->last_synced_at);
        $this->assertInstanceOf(SocialPostResponse::class, $responseRecord);
        $this->assertSame('insights', $responseRecord->response_type);
        $this->assertSame('18158273836488130', $responseRecord->external_response_id);
        $this->assertSame('Threads insights fetched.', $responseRecord->body);
        $this->assertSame(ThreadsGraph::insightsEndpoint('18158273836488130'), $responseRecord->request_payload['endpoint']);
        $this->assertSame('views,likes,replies,reposts,quotes,shares', $responseRecord->request_payload['fields']['metric']);
        $this->assertSame(123, $responseRecord->response_payload['data'][0]['values'][0]['value']);
        $this->assertArrayNotHasKey('access_token', $responseRecord->request_payload['fields']);

        Http::assertSent(function ($request) {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $data = array_merge($query, $request->data());

            return $request->method() === 'GET'
                && str_starts_with($request->url(), ThreadsGraph::insightsEndpoint('18158273836488130'))
                && $data['metric'] === 'views,likes,replies,reposts,quotes,shares'
                && $data['access_token'] === SocialMediaConfig::accessToken('threads');
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

    private function publicPathFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        $this->assertIsString($path);
        $this->assertStringStartsWith('/images/social-posts/', $path);

        return public_path(ltrim($path, '/'));
    }

    private function uploadedLogoImage(): UploadedFile
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'chamu-logo-');

        $this->assertIsString($temporaryPath);
        copy(public_path('images/brand/chamu-logo.png'), $temporaryPath);

        return new UploadedFile($temporaryPath, 'chamu-logo.png', 'image/png', null, true);
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
