<?php

namespace Tests\Unit;

use App\Support\Social\FacebookGraph;
use App\Support\Social\SocialMediaConfig;
use Tests\TestCase;

class FacebookGraphTest extends TestCase
{
    public function test_it_builds_feed_endpoint_payload_and_curl(): void
    {
        $token = SocialMediaConfig::accessToken('facebook');

        $this->assertNotNull($token);

        $this->assertSame('https://graph.facebook.com/v25.0/me/feed', FacebookGraph::feedEndpoint());
        $this->assertSame($token, FacebookGraph::accessToken());

        $payload = FacebookGraph::feedPayload('Hello World! We are Chamu');
        $this->assertSame('Hello World! We are Chamu', $payload['message']);
        $this->assertSame($token, $payload['access_token']);

        $curl = FacebookGraph::feedCurl('Hello World! We are Chamu');

        $this->assertStringContainsString('curl -i -X POST', $curl);
        $this->assertStringContainsString('https://graph.facebook.com/v25.0/me/feed', $curl);
        $this->assertStringContainsString('message=Hello%20World%21%20We%20are%20Chamu', $curl);
        $this->assertStringContainsString('access_token=', $curl);
    }

    public function test_it_reports_empty_tokens_for_unconfigured_platforms(): void
    {
        $this->assertNull(SocialMediaConfig::accessToken('instagram'));
        $this->assertFalse(SocialMediaConfig::hasAccessToken('linkedin'));
    }
}
