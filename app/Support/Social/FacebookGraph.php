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
        $pageId = trim((string) SocialMediaConfig::value('facebook', 'page_id', ''));

        return trim($pageId !== '' ? $pageId : (string) SocialMediaConfig::value('facebook', 'feed_node', 'me'), '/');
    }

    public static function feedEndpoint(?string $node = null): string
    {
        $graphUrl = rtrim((string) SocialMediaConfig::value('facebook', 'graph_url', 'https://graph.facebook.com'), '/');
        $feedNode = trim($node ?? self::feedNode(), '/');

        return $graphUrl.'/'.self::graphVersion().'/'.$feedNode.'/feed';
    }

    public static function photosEndpoint(?string $node = null): string
    {
        $graphUrl = rtrim((string) SocialMediaConfig::value('facebook', 'graph_url', 'https://graph.facebook.com'), '/');
        $feedNode = trim($node ?? self::feedNode(), '/');

        return $graphUrl.'/'.self::graphVersion().'/'.$feedNode.'/photos';
    }

    public static function commentsEndpoint(string $postId): string
    {
        $graphUrl = rtrim((string) SocialMediaConfig::value('facebook', 'graph_url', 'https://graph.facebook.com'), '/');
        $postId = trim($postId, '/');

        return $graphUrl.'/'.self::graphVersion().'/'.$postId.'/comments';
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
     * @return array<string, string>
     */
    public static function photoPayload(string $caption, string $imageUrl): array
    {
        return self::safePhotoPayload($caption, $imageUrl) + [
            'access_token' => self::requireAccessToken(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function safePhotoPayload(string $caption, string $imageUrl): array
    {
        return array_filter([
            'caption' => $caption,
            'url' => $imageUrl,
        ], fn ($value) => trim((string) $value) !== '');
    }

    /**
     * @return array<string, string>
     */
    public static function commentPayload(string $message): array
    {
        return self::safeCommentPayload($message) + [
            'access_token' => self::requireAccessToken(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function safeCommentPayload(string $message): array
    {
        return array_filter([
            'message' => $message,
        ], fn ($value) => trim((string) $value) !== '');
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

    public static function postPhoto(string $caption, string $imageUrl, ?string $node = null): Response
    {
        return Http::asForm()->post(self::photosEndpoint($node), self::photoPayload($caption, $imageUrl));
    }

    public static function commentOnPost(string $postId, string $message): Response
    {
        return Http::asForm()->post(self::commentsEndpoint($postId), self::commentPayload($message));
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
