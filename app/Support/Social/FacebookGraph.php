<?php

namespace App\Support\Social;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FacebookGraph
{
    public static function accessToken(): ?string
    {
        return SocialMediaConfig::accessToken('facebook');
    }

    public static function graphVersion(): string
    {
        return trim((string) SocialMediaConfig::value('facebook', 'graph_version', 'v25.0'), '/');
    }

    public static function feedNode(): string
    {
        return trim((string) SocialMediaConfig::value('facebook', 'feed_node', 'me'), '/');
    }

    public static function feedEndpoint(?string $node = null): string
    {
        $graphUrl = rtrim((string) SocialMediaConfig::value('facebook', 'graph_url', 'https://graph.facebook.com'), '/');
        $feedNode = trim($node ?? self::feedNode(), '/');

        return $graphUrl.'/'.self::graphVersion().'/'.$feedNode.'/feed';
    }

    /**
     * @return array{message: string, access_token: string}
     */
    public static function feedPayload(string $message): array
    {
        return [
            'message' => $message,
            'access_token' => self::requireAccessToken(),
        ];
    }

    public static function feedCurl(string $message, ?string $node = null): string
    {
        $query = http_build_query(self::feedPayload($message), '', '&', PHP_QUERY_RFC3986);

        return 'curl -i -X POST "'.self::feedEndpoint($node).'?'.$query.'"';
    }

    public static function postToFeed(string $message, ?string $node = null): Response
    {
        return Http::asForm()->post(self::feedEndpoint($node), self::feedPayload($message));
    }

    private static function requireAccessToken(): string
    {
        $token = self::accessToken();

        if ($token === null) {
            throw new RuntimeException('Missing Facebook access token in SocialMediaConfig.');
        }

        return $token;
    }
}
