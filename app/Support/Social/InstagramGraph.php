<?php

namespace App\Support\Social;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramGraph
{
    public const COMMENT_MAX_LENGTH = 2200;

    /**
     * @var list<string>
     */
    private const MEDIA_INSIGHT_METRICS = [
        'views',
        'reach',
        'likes',
        'comments',
        'shares',
        'saved',
        'total_interactions',
    ];

    public static function accessToken(): ?string
    {
        return SocialMediaConfig::accessToken('instagram');
    }

    /**
     * @return list<string>
     */
    public static function mediaInsightMetrics(): array
    {
        return self::MEDIA_INSIGHT_METRICS;
    }

    public static function graphVersion(): string
    {
        return trim((string) SocialMediaConfig::value('instagram', 'graph_version', 'v25.0'), '/');
    }

    public static function businessAccountId(): ?string
    {
        $businessAccountId = trim((string) SocialMediaConfig::value('instagram', 'business_account_id', ''));

        return $businessAccountId !== '' ? $businessAccountId : null;
    }

    public static function mediaEndpoint(?string $businessAccountId = null): string
    {
        return self::baseEndpoint($businessAccountId).'/media';
    }

    public static function mediaPublishEndpoint(?string $businessAccountId = null): string
    {
        return self::baseEndpoint($businessAccountId).'/media_publish';
    }

    public static function commentsEndpoint(string $mediaId): string
    {
        return self::mediaObjectEndpoint($mediaId).'/comments';
    }

    public static function insightsEndpoint(string $mediaId): string
    {
        return self::mediaObjectEndpoint($mediaId).'/insights';
    }

    /**
     * @return array<string, string>
     */
    public static function mediaPayload(string $caption, string $imageUrl): array
    {
        return self::safeMediaPayload($caption, $imageUrl) + [
            'access_token' => self::requireAccessToken(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function safeMediaPayload(string $caption, string $imageUrl): array
    {
        return array_filter([
            'image_url' => $imageUrl,
            'caption' => $caption,
        ], fn ($value) => trim((string) $value) !== '');
    }

    /**
     * @return array<string, string>
     */
    public static function publishPayload(string $creationId): array
    {
        return self::safePublishPayload($creationId) + [
            'access_token' => self::requireAccessToken(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function safePublishPayload(string $creationId): array
    {
        return array_filter([
            'creation_id' => $creationId,
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
     * @param  list<string>  $metrics
     * @return array<string, string>
     */
    public static function insightsPayload(array $metrics = []): array
    {
        return self::safeInsightsPayload($metrics) + [
            'access_token' => self::requireAccessToken(),
        ];
    }

    /**
     * @param  list<string>  $metrics
     * @return array<string, string>
     */
    public static function safeInsightsPayload(array $metrics = []): array
    {
        $metrics = self::normaliseMetrics($metrics);

        return [
            'metric' => implode(',', $metrics),
        ];
    }

    public static function createMediaContainer(string $caption, string $imageUrl): Response
    {
        return Http::asForm()->post(self::mediaEndpoint(), self::mediaPayload($caption, $imageUrl));
    }

    public static function publishMediaContainer(string $creationId): Response
    {
        return Http::asForm()->post(self::mediaPublishEndpoint(), self::publishPayload($creationId));
    }

    public static function commentOnMedia(string $mediaId, string $message): Response
    {
        return Http::asForm()->post(self::commentsEndpoint($mediaId), self::commentPayload($message));
    }

    /**
     * @param  list<string>  $metrics
     */
    public static function getMediaInsights(string $mediaId, array $metrics = []): Response
    {
        return Http::get(self::insightsEndpoint($mediaId), self::insightsPayload($metrics));
    }

    private static function baseEndpoint(?string $businessAccountId = null): string
    {
        $graphUrl = rtrim((string) SocialMediaConfig::value('instagram', 'graph_url', 'https://graph.facebook.com'), '/');
        $accountId = trim((string) ($businessAccountId ?? self::requireBusinessAccountId()), '/');

        return $graphUrl.'/'.self::graphVersion().'/'.$accountId;
    }

    private static function mediaObjectEndpoint(string $mediaId): string
    {
        $graphUrl = rtrim((string) SocialMediaConfig::value('instagram', 'graph_url', 'https://graph.facebook.com'), '/');

        return $graphUrl.'/'.self::graphVersion().'/'.trim($mediaId, '/');
    }

    /**
     * @param  list<string>  $metrics
     * @return list<string>
     */
    private static function normaliseMetrics(array $metrics): array
    {
        $metrics = array_values(array_intersect(
            array_map(fn (string $metric) => trim($metric), $metrics),
            self::MEDIA_INSIGHT_METRICS,
        ));

        return $metrics !== [] ? $metrics : self::MEDIA_INSIGHT_METRICS;
    }

    private static function requireBusinessAccountId(): string
    {
        $businessAccountId = self::businessAccountId();

        if ($businessAccountId === null) {
            throw new RuntimeException('Missing Instagram business account ID in SocialMediaConfig.');
        }

        return $businessAccountId;
    }

    private static function requireAccessToken(): string
    {
        $token = self::accessToken();

        if ($token === null) {
            throw new RuntimeException('Missing Instagram access token in SocialMediaConfig.');
        }

        return $token;
    }
}
