<?php

namespace App\Support\Social;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ThreadsGraph
{
    public static function accessToken(): ?string
    {
        return SocialMediaConfig::accessToken('threads');
    }

    public static function graphVersion(): string
    {
        return trim((string) SocialMediaConfig::value('threads', 'graph_version', 'v1.0'), '/');
    }

    public static function accountId(): ?string
    {
        $accountId = trim((string) SocialMediaConfig::value('threads', 'account_id', ''));

        return $accountId !== '' ? $accountId : null;
    }

    public static function threadsEndpoint(?string $accountId = null): string
    {
        return self::baseEndpoint($accountId).'/threads';
    }

    public static function threadsPublishEndpoint(?string $accountId = null): string
    {
        return self::baseEndpoint($accountId).'/threads_publish';
    }

    /**
     * @return array<string, string>
     */
    public static function threadPayload(string $text, ?string $imageUrl = null): array
    {
        return self::safeThreadPayload($text, $imageUrl) + [
            'access_token' => self::requireAccessToken(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function safeThreadPayload(string $text, ?string $imageUrl = null): array
    {
        $imageUrl = trim((string) $imageUrl);

        return array_filter([
            'media_type' => $imageUrl !== '' ? 'IMAGE' : 'TEXT',
            'text' => $text,
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
        ], fn ($value) => $value !== null && trim((string) $value) !== '');
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

    public static function createThreadContainer(string $text, ?string $imageUrl = null): Response
    {
        return Http::asForm()->post(self::threadsEndpoint(), self::threadPayload($text, $imageUrl));
    }

    public static function publishThreadContainer(string $creationId): Response
    {
        return Http::asForm()->post(self::threadsPublishEndpoint(), self::publishPayload($creationId));
    }

    private static function baseEndpoint(?string $accountId = null): string
    {
        $graphUrl = rtrim((string) SocialMediaConfig::value('threads', 'graph_url', 'https://graph.threads.net'), '/');
        $accountId = trim((string) ($accountId ?? self::requireAccountId()), '/');

        return $graphUrl.'/'.self::graphVersion().'/'.$accountId;
    }

    private static function requireAccountId(): string
    {
        $accountId = self::accountId();

        if ($accountId === null) {
            throw new RuntimeException('Missing Threads account ID in SocialMediaConfig.');
        }

        return $accountId;
    }

    private static function requireAccessToken(): string
    {
        $token = self::accessToken();

        if ($token === null) {
            throw new RuntimeException('Missing Threads access token in SocialMediaConfig.');
        }

        return $token;
    }
}
