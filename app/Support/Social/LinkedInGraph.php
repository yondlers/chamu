<?php

namespace App\Support\Social;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LinkedInGraph
{
    public static function accessToken(): ?string
    {
        return SocialMediaConfig::accessToken('linkedin');
    }

    public static function clientId(): ?string
    {
        $clientId = trim((string) SocialMediaConfig::value('linkedin', 'client_id', ''));

        return $clientId !== '' ? $clientId : null;
    }

    public static function clientCredential(): ?string
    {
        $clientCredential = trim((string) SocialMediaConfig::value('linkedin', 'client_credential', ''));

        return $clientCredential !== '' ? $clientCredential : null;
    }

    public static function restVersion(): string
    {
        return trim((string) SocialMediaConfig::value('linkedin', 'rest_version', '202401'));
    }

    public static function authorUrn(): ?string
    {
        $authorUrn = trim((string) SocialMediaConfig::value('linkedin', 'author_urn', ''));

        return $authorUrn !== '' ? $authorUrn : null;
    }

    public static function postsEndpoint(): string
    {
        return self::apiUrl().'/rest/posts';
    }

    public static function imagesInitializeUploadEndpoint(): string
    {
        return self::apiUrl().'/rest/images?action=initializeUpload';
    }

    /**
     * @return array<string, mixed>
     */
    public static function safePostPayload(string $commentary, ?string $authorUrn = null, ?string $imageUrn = null, ?string $title = null): array
    {
        $payload = [
            'author' => $authorUrn,
            'commentary' => $commentary,
            'visibility' => 'PUBLIC',
            'distribution' => [
                'feedDistribution' => 'MAIN_FEED',
                'targetEntities' => [],
                'thirdPartyDistributionChannels' => [],
            ],
            'lifecycleState' => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        ];

        if ($imageUrn !== null && trim($imageUrn) !== '') {
            $payload['content'] = [
                'media' => [
                    'title' => trim((string) $title) !== '' ? $title : 'Chamu image',
                    'id' => $imageUrn,
                ],
            ];
        }

        return array_filter($payload, fn ($value) => $value !== null);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function safeImageInitializeUploadPayload(?string $ownerUrn = null): array
    {
        return [
            'initializeUploadRequest' => array_filter([
                'owner' => $ownerUrn,
            ], fn ($value) => $value !== null && trim((string) $value) !== ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function postPayload(string $commentary, ?string $imageUrn = null, ?string $title = null): array
    {
        return self::safePostPayload($commentary, self::requireAuthorUrn(), $imageUrn, $title);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function imageInitializeUploadPayload(): array
    {
        return self::safeImageInitializeUploadPayload(self::requireAuthorUrn());
    }

    public static function initializeImageUpload(): Response
    {
        return Http::withHeaders(self::apiHeaders())
            ->post(self::imagesInitializeUploadEndpoint(), self::imageInitializeUploadPayload());
    }

    public static function uploadImage(string $uploadUrl, string $contents, string $contentType): Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.self::requireAccessToken(),
            'Content-Type' => $contentType,
        ])
            ->withBody($contents, $contentType)
            ->put($uploadUrl);
    }

    public static function createPost(string $commentary, ?string $imageUrn = null, ?string $title = null): Response
    {
        return Http::withHeaders(self::apiHeaders())
            ->post(self::postsEndpoint(), self::postPayload($commentary, $imageUrn, $title));
    }

    /**
     * @return array<string, string>
     */
    private static function apiHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.self::requireAccessToken(),
            'Linkedin-Version' => self::restVersion(),
            'X-Restli-Protocol-Version' => '2.0.0',
            'Content-Type' => 'application/json',
        ];
    }

    private static function apiUrl(): string
    {
        return rtrim((string) SocialMediaConfig::value('linkedin', 'api_url', 'https://api.linkedin.com'), '/');
    }

    private static function requireAuthorUrn(): string
    {
        $authorUrn = self::authorUrn();

        if ($authorUrn === null) {
            throw new RuntimeException('Missing LinkedIn author URN in SocialMediaConfig.');
        }

        return $authorUrn;
    }

    private static function requireAccessToken(): string
    {
        $token = self::accessToken();

        if ($token === null) {
            throw new RuntimeException('Missing LinkedIn access token in SocialMediaConfig.');
        }

        return $token;
    }
}
