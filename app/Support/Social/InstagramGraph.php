<?php

namespace App\Support\Social;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramGraph
{
    public static function accessToken(): ?string
    {
        return SocialMediaConfig::accessToken('instagram');
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

    public static function createMediaContainer(string $caption, string $imageUrl): Response
    {
        return Http::asForm()->post(self::mediaEndpoint(), self::mediaPayload($caption, $imageUrl));
    }

    public static function publishMediaContainer(string $creationId): Response
    {
        return Http::asForm()->post(self::mediaPublishEndpoint(), self::publishPayload($creationId));
    }

    private static function baseEndpoint(?string $businessAccountId = null): string
    {
        $graphUrl = rtrim((string) SocialMediaConfig::value('instagram', 'graph_url', 'https://graph.facebook.com'), '/');
        $accountId = trim((string) ($businessAccountId ?? self::requireBusinessAccountId()), '/');

        return $graphUrl.'/'.self::graphVersion().'/'.$accountId;
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
