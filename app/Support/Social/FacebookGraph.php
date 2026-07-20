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
     * @param  array<string, string|null>  $fields
     * @return array<string, string>
     */
    public static function feedPayload(string $message, array $fields = []): array
    {
        return array_filter([
            'message' => $message,
            'link' => $fields['link'] ?? null,
            'access_token' => self::requireAccessToken(),
        ], fn ($value) => $value !== null && trim((string) $value) !== '');
    }

    /**
     * @param  array<string, string|null>  $fields
     * @return array<string, string>
     */
    public static function safeFeedPayload(string $message, array $fields = []): array
    {
        return array_filter([
            'message' => $message,
            'link' => $fields['link'] ?? null,
        ], fn ($value) => $value !== null && trim((string) $value) !== '');
    }

    /**
     * @param  array<string, string|null>  $fields
     */
    public static function feedCurl(string $message, ?string $node = null, array $fields = []): string
    {
        $query = http_build_query(self::feedPayload($message, $fields), '', '&', PHP_QUERY_RFC3986);

        return 'curl -i -X POST "'.self::feedEndpoint($node).'?'.$query.'"';
    }

    /**
     * @param  array<string, string|null>  $fields
     */
    public static function postToFeed(string $message, ?string $node = null, array $fields = []): Response
    {
        return Http::asForm()->post(self::feedEndpoint($node), self::feedPayload($message, $fields));
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
