<?php

namespace Tests\Unit;

use App\Support\Social\FacebookGraph;
use App\Support\Social\InstagramGraph;
use App\Support\Social\LinkedInGraph;
use App\Support\Social\SocialMediaConfig;
use App\Support\Social\ThreadsGraph;
use Tests\TestCase;

class FacebookGraphTest extends TestCase
{
    public function test_it_builds_feed_endpoint_payload_and_curl(): void
    {
        $token = SocialMediaConfig::accessToken('facebook');

        $this->assertNotNull($token);

        $this->assertSame('https://graph.facebook.com/v25.0/testing-facebook-page/feed', FacebookGraph::feedEndpoint());
        $this->assertSame($token, FacebookGraph::accessToken());
        $this->assertSame('testing-facebook-page-token', $token);

        $payload = FacebookGraph::feedPayload('Hello World! We are Chamu');
        $this->assertSame('Hello World! We are Chamu', $payload['message']);
        $this->assertSame($token, $payload['access_token']);

        $this->assertSame('https://graph.facebook.com/v25.0/testing-facebook-page/photos', FacebookGraph::photosEndpoint());
        $photoPayload = FacebookGraph::photoPayload('Chamu Logo', 'https://chamu.co.za/images/brand/chamu-logo.png');
        $this->assertSame('Chamu Logo', $photoPayload['caption']);
        $this->assertSame('https://chamu.co.za/images/brand/chamu-logo.png', $photoPayload['url']);
        $this->assertSame($token, $photoPayload['access_token']);

        $this->assertSame('https://graph.facebook.com/v25.0/page_12345/comments', FacebookGraph::commentsEndpoint('page_12345'));
        $commentPayload = FacebookGraph::commentPayload('Thanks for reaching out.');
        $this->assertSame('Thanks for reaching out.', $commentPayload['message']);
        $this->assertSame($token, $commentPayload['access_token']);
        $this->assertArrayNotHasKey('access_token', FacebookGraph::safeCommentPayload('Safe comment'));
        $this->assertSame('https://graph.facebook.com/v25.0/page_12345/insights', FacebookGraph::insightsEndpoint('page_12345'));
        $this->assertSame([
            'post_impressions',
            'post_impressions_unique',
            'post_engaged_users',
            'post_clicks',
            'post_reactions_by_type_total',
        ], FacebookGraph::postInsightMetrics());
        $insightsPayload = FacebookGraph::insightsPayload(['post_impressions', 'post_clicks', 'not_a_metric']);
        $this->assertSame('post_impressions,post_clicks', $insightsPayload['metric']);
        $this->assertSame($token, $insightsPayload['access_token']);
        $this->assertArrayNotHasKey('access_token', FacebookGraph::safeInsightsPayload());

        $curl = FacebookGraph::feedCurl('Hello World! We are Chamu');

        $this->assertStringContainsString('curl -i -X POST', $curl);
        $this->assertStringContainsString('https://graph.facebook.com/v25.0/testing-facebook-page/feed', $curl);
        $this->assertStringContainsString('message=Hello%20World%21%20We%20are%20Chamu', $curl);
        $this->assertStringContainsString('access_token=', $curl);
    }

    public function test_it_builds_instagram_media_endpoints_from_stored_business_account(): void
    {
        $token = SocialMediaConfig::accessToken('instagram');

        $this->assertNotNull($token);
        $this->assertSame('11111111111111111', InstagramGraph::businessAccountId());
        $this->assertSame('https://graph.facebook.com/v25.0/11111111111111111/media', InstagramGraph::mediaEndpoint());
        $this->assertSame('https://graph.facebook.com/v25.0/11111111111111111/media_publish', InstagramGraph::mediaPublishEndpoint());

        $payload = InstagramGraph::mediaPayload('Chamu Logo', 'https://chamu.co.za/images/brand/chamu-logo.png');
        $this->assertSame('Chamu Logo', $payload['caption']);
        $this->assertSame('https://chamu.co.za/images/brand/chamu-logo.png', $payload['image_url']);
        $this->assertSame($token, $payload['access_token']);

        $safePayload = InstagramGraph::safeMediaPayload('Chamu Logo', 'https://chamu.co.za/images/brand/chamu-logo.png');
        $this->assertArrayNotHasKey('access_token', $safePayload);

        $this->assertSame('https://graph.facebook.com/v25.0/18088798253113211/comments', InstagramGraph::commentsEndpoint('18088798253113211'));
        $commentPayload = InstagramGraph::commentPayload('Thanks for the question.');
        $this->assertSame('Thanks for the question.', $commentPayload['message']);
        $this->assertSame($token, $commentPayload['access_token']);
        $this->assertArrayNotHasKey('access_token', InstagramGraph::safeCommentPayload('Safe comment'));

        $this->assertSame('https://graph.facebook.com/v25.0/18088798253113211/insights', InstagramGraph::insightsEndpoint('18088798253113211'));
        $this->assertSame(['views', 'reach', 'likes', 'comments', 'shares', 'saved', 'total_interactions'], InstagramGraph::mediaInsightMetrics());
        $insightsPayload = InstagramGraph::insightsPayload(['views', 'likes', 'not_a_metric']);
        $this->assertSame('views,likes', $insightsPayload['metric']);
        $this->assertSame($token, $insightsPayload['access_token']);
        $this->assertArrayNotHasKey('access_token', InstagramGraph::safeInsightsPayload());
    }

    public function test_it_builds_threads_endpoints_from_stored_account(): void
    {
        $token = SocialMediaConfig::accessToken('threads');

        $this->assertNotNull($token);
        $this->assertSame('testing-threads-token', $token);
        $this->assertSame('22222222222222222', ThreadsGraph::accountId());
        $this->assertSame(500, ThreadsGraph::maxTextLength());
        $this->assertSame('https://graph.threads.net/v1.0/22222222222222222/threads', ThreadsGraph::threadsEndpoint());
        $this->assertSame('https://graph.threads.net/v1.0/22222222222222222/threads_publish', ThreadsGraph::threadsPublishEndpoint());
        $this->assertSame('https://graph.threads.net/v1.0/22222222222222222', ThreadsGraph::profileEndpoint());
        $this->assertSame('https://graph.threads.net/v1.0/18158273836488130/insights', ThreadsGraph::insightsEndpoint('18158273836488130'));
        $this->assertSame(['views', 'likes', 'replies', 'reposts', 'quotes', 'shares'], ThreadsGraph::insightMetrics());

        $textPayload = ThreadsGraph::threadPayload('Hello We Are Chamu');
        $this->assertSame('TEXT', $textPayload['media_type']);
        $this->assertSame('Hello We Are Chamu', $textPayload['text']);
        $this->assertSame($token, $textPayload['access_token']);

        $imagePayload = ThreadsGraph::safeThreadPayload('Hello We Are Chamu', 'https://chamu.co.za/images/brand/chamu-logo.png');
        $this->assertSame('IMAGE', $imagePayload['media_type']);
        $this->assertSame('https://chamu.co.za/images/brand/chamu-logo.png', $imagePayload['image_url']);
        $this->assertArrayNotHasKey('access_token', $imagePayload);

        $replyPayload = ThreadsGraph::threadPayload('Thanks for the question.', null, '18158273836488130');
        $this->assertSame('TEXT', $replyPayload['media_type']);
        $this->assertSame('Thanks for the question.', $replyPayload['text']);
        $this->assertSame('18158273836488130', $replyPayload['reply_to_id']);
        $this->assertSame($token, $replyPayload['access_token']);

        $profilePayload = ThreadsGraph::profilePayload();
        $this->assertSame('id,username,name', $profilePayload['fields']);
        $this->assertSame($token, $profilePayload['access_token']);
        $this->assertArrayNotHasKey('access_token', ThreadsGraph::safeProfilePayload());

        $insightsPayload = ThreadsGraph::insightsPayload(['views', 'likes', 'not_a_metric']);
        $this->assertSame('views,likes', $insightsPayload['metric']);
        $this->assertSame($token, $insightsPayload['access_token']);
        $this->assertArrayNotHasKey('access_token', ThreadsGraph::safeInsightsPayload());
    }

    public function test_threads_does_not_reuse_facebook_token_when_unconfigured(): void
    {
        config(['services.social.threads.access_token' => null]);

        $this->assertNull(SocialMediaConfig::accessToken('threads'));
    }

    public function test_it_builds_linkedin_endpoints_and_payloads_from_stored_app_credentials(): void
    {
        $this->assertSame('testing-linkedin-client-id', LinkedInGraph::clientId());
        $this->assertNotNull(LinkedInGraph::clientCredential());
        $this->assertSame('202607', LinkedInGraph::restVersion());
        $this->assertSame('member', LinkedInGraph::authorType('urn:li:person:abc123'));
        $this->assertSame('organization', LinkedInGraph::authorType('urn:li:organization:135344228'));
        $this->assertSame('w_member_social', LinkedInGraph::requiredPostingPermission('urn:li:person:abc123'));
        $this->assertSame('w_organization_social', LinkedInGraph::requiredPostingPermission('urn:li:organization:135344228'));
        $this->assertSame('https://api.linkedin.com/rest/posts', LinkedInGraph::postsEndpoint());
        $this->assertSame('https://api.linkedin.com/rest/images?action=initializeUpload', LinkedInGraph::imagesInitializeUploadEndpoint());

        $textPayload = LinkedInGraph::safePostPayload('Hello We Are Chamu', 'urn:li:person:abc123');
        $this->assertSame('urn:li:person:abc123', $textPayload['author']);
        $this->assertSame('Hello We Are Chamu', $textPayload['commentary']);
        $this->assertSame('PUBLIC', $textPayload['visibility']);
        $this->assertSame('MAIN_FEED', $textPayload['distribution']['feedDistribution']);
        $this->assertArrayNotHasKey('access_token', $textPayload);

        $imagePayload = LinkedInGraph::safePostPayload('Hello We Are Chamu', 'urn:li:person:abc123', 'urn:li:image:C4E10AQFoyyAjHPMQuQ', 'Chamu image');
        $this->assertSame('urn:li:image:C4E10AQFoyyAjHPMQuQ', $imagePayload['content']['media']['id']);
        $this->assertSame('Chamu image', $imagePayload['content']['media']['title']);

        $uploadPayload = LinkedInGraph::safeImageInitializeUploadPayload('urn:li:person:abc123');
        $this->assertSame('urn:li:person:abc123', $uploadPayload['initializeUploadRequest']['owner']);
    }

    public function test_it_normalizes_linkedin_date_style_rest_versions_to_month_versions(): void
    {
        $originalSocialConfig = config('services.social');

        try {
            config(['services.social.linkedin.rest_version' => '20260701']);

            $this->assertSame('202607', LinkedInGraph::restVersion());
        } finally {
            config(['services.social' => $originalSocialConfig]);
        }
    }

    public function test_it_reports_empty_tokens_for_unconfigured_platforms(): void
    {
        $this->assertFalse(SocialMediaConfig::hasAccessToken('linkedin'));
    }
}
