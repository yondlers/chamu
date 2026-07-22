<?php

namespace App\Support\Social;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ThreadsGraph
{
    private const MAX_TEXT_LENGTH = 500;

    /**
     * @var list<string>
     */
    private const DIAGNOSTIC_HEADERS = [
        'content-type',
        'content-length',
        'x-fb-trace-id',
        'x-fb-rev',
        'x-fb-debug',
        'x-app-usage',
        'x-page-usage',
        'x-business-use-case-usage',
    ];

    /**
     * @var list<string>
     */
    private const INSIGHT_METRICS = [
        'views',
        'likes',
        'replies',
        'reposts',
        'quotes',
        'shares',
    ];

    public static function accessToken(): ?string
    {
        return SocialMediaConfig::accessToken('threads');
    }

    public static function maxTextLength(): int
    {
        return self::MAX_TEXT_LENGTH;
    }

    /**
     * @return list<string>
     */
    public static function insightMetrics(): array
    {
        return self::INSIGHT_METRICS;
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

    public static function profileEndpoint(?string $accountId = null): string
    {
        return self::baseEndpoint($accountId);
    }

    public static function insightsEndpoint(string $threadId): string
    {
        $graphUrl = rtrim((string) SocialMediaConfig::value('threads', 'graph_url', 'https://graph.threads.net'), '/');

        return $graphUrl.'/'.self::graphVersion().'/'.trim($threadId, '/').'/insights';
    }

    /**
     * @return array<string, string>
     */
    public static function threadPayload(string $text, ?string $imageUrl = null, ?string $replyToId = null): array
    {
        return self::safeThreadPayload($text, $imageUrl, $replyToId) + [
            'access_token' => self::requireAccessToken(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function safeThreadPayload(string $text, ?string $imageUrl = null, ?string $replyToId = null): array
    {
        $imageUrl = trim((string) $imageUrl);
        $replyToId = trim((string) $replyToId);

        return array_filter([
            'media_type' => $imageUrl !== '' ? 'IMAGE' : 'TEXT',
            'text' => $text,
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
            'reply_to_id' => $replyToId !== '' ? $replyToId : null,
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

    /**
     * @return array<string, string>
     */
    public static function profilePayload(): array
    {
        return self::safeProfilePayload() + [
            'access_token' => self::requireAccessToken(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function safeProfilePayload(): array
    {
        return [
            'fields' => 'id,username,name',
        ];
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

    public static function createThreadContainer(string $text, ?string $imageUrl = null, ?string $replyToId = null): Response
    {
        return Http::asForm()->post(self::threadsEndpoint(), self::threadPayload($text, $imageUrl, $replyToId));
    }

    public static function publishThreadContainer(string $creationId): Response
    {
        return Http::asForm()->post(self::threadsPublishEndpoint(), self::publishPayload($creationId));
    }

    public static function getProfile(): Response
    {
        return Http::get(self::profileEndpoint(), self::profilePayload());
    }

    /**
     * @param  list<string>  $metrics
     */
    public static function getPostInsights(string $threadId, array $metrics = []): Response
    {
        return Http::get(self::insightsEndpoint($threadId), self::insightsPayload($metrics));
    }

    /**
     * @return array<string, mixed>
     */
    public static function responseBodyPayload(Response $response): array
    {
        $payload = $response->json();

        return is_array($payload) ? $payload : ['body' => $response->body()];
    }

    /**
     * @return array<string, mixed>
     */
    public static function responseDiagnostics(Response $response): array
    {
        $payload = $response->json();

        return array_filter([
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->body(),
            'json' => is_array($payload) ? $payload : null,
            'headers' => self::responseHeaders($response),
        ], fn ($value) => $value !== null && $value !== []);
    }

    public static function errorMessage(Response $response, string $context): string
    {
        $message = data_get(self::responseBodyPayload($response), 'error.message');

        if ($message !== null && trim((string) $message) !== '') {
            return (string) $message;
        }

        $body = trim($response->body());

        if ($body !== '' && $body !== '[]' && $body !== '{}') {
            return $body;
        }

        return $context.' failed with HTTP '.$response->status().'.';
    }

    /**
     * @return array<string, string>
     */
    private static function responseHeaders(Response $response): array
    {
        $headers = [];

        foreach (self::DIAGNOSTIC_HEADERS as $header) {
            $value = $response->header($header);

            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            if ($value !== null && trim((string) $value) !== '') {
                $headers[$header] = (string) $value;
            }
        }

        return $headers;
    }

    /**
     * @param  list<string>  $metrics
     * @return list<string>
     */
    private static function normaliseMetrics(array $metrics): array
    {
        $metrics = array_values(array_intersect(
            array_map(fn (string $metric) => trim($metric), $metrics),
            self::INSIGHT_METRICS,
        ));

        return $metrics !== [] ? $metrics : self::INSIGHT_METRICS;
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
