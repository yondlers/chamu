<?php

namespace App\Support\Social;

class SocialMediaConfig
{
    /**
     * Edit social media credentials and platform settings here.
     *
     * Keep access tokens out of Blade views and responses. The admin UI only
     * receives the sanitized platform metadata from adminPlatforms().
     *
     * @var array<string, array<string, mixed>>
     */
    private const PLATFORMS = [
        'facebook' => [
            'slug' => 'facebook',
            'name' => 'Facebook',
            'icon' => 'messages-square',
            'accent' => '#1877F2',
            'audience' => 'Parents, learners, and community referrals',
            'surface' => 'Page feed, comments, and message follow-up',
            'tone' => 'Community updates and direct response',
            'api_state' => 'Meta API connection pending',
            'engagement_state' => 'Comments and inbox depend on approved permissions',
            'graph_url' => 'https://graph.facebook.com',
            'graph_version' => 'v25.0',
            'feed_node' => 'me',
            'page_id' => '',
            'page_access_token' => '',
            'access_token' => '',
            'next_steps' => [
                'Create or connect the Meta app',
                'Add pages_read_engagement and pages_manage_posts',
                'Store the target Page ID and Page access token',
                'Configure webhooks for comments and messages',
            ],
        ],
        'instagram' => [
            'slug' => 'instagram',
            'name' => 'Instagram',
            'icon' => 'camera',
            'accent' => '#D62976',
            'audience' => 'Visual campaign followers and student leads',
            'surface' => 'Image posts, reels handoff, captions, and comments',
            'tone' => 'Short-form visual campaign moments',
            'api_state' => 'Instagram Graph API connection pending',
            'engagement_state' => 'Comment management depends on business account access',
            'graph_url' => 'https://graph.facebook.com',
            'graph_version' => 'v25.0',
            'business_account_id' => '',
            'access_token' => '',
            'token_source' => 'facebook',
            'next_steps' => [
                'Instagram business account ID stored',
                'Upload a public image and capture the caption',
                'Add caption, hashtag, and asset validation',
                'Configure comment moderation webhooks',
            ],
        ],
        'threads' => [
            'slug' => 'threads',
            'name' => 'Threads',
            'icon' => 'at-sign',
            'accent' => '#111111',
            'audience' => 'Short updates for students, parents, and Chamu followers',
            'surface' => 'Text threads, image posts, and conversation replies',
            'tone' => 'Short conversational updates',
            'api_state' => 'Threads API connection pending',
            'engagement_state' => 'Replies and insights depend on Threads API permissions',
            'graph_url' => 'https://graph.threads.net',
            'graph_version' => 'v1.0',
            'account_id' => '',
            'access_token' => '',
            'token_source' => 'facebook',
            'next_steps' => [
                'Threads account ID stored',
                'Create text or image media containers',
                'Publish containers with creation IDs',
                'Map replies and insights once permissions are approved',
            ],
        ],
        'linkedin' => [
            'slug' => 'linkedin',
            'name' => 'LinkedIn',
            'icon' => 'briefcase',
            'accent' => '#0A66C2',
            'audience' => 'Sponsors, partners, schools, and professional networks',
            'surface' => 'Member text posts, image posts, and campaign reporting',
            'tone' => 'Partner-facing education and funding updates',
            'api_state' => 'LinkedIn OAuth access token pending',
            'engagement_state' => 'Member authors need w_member_social. Organization authors need w_organization_social and an eligible page role.',
            'api_url' => 'https://api.linkedin.com',
            'rest_version' => '202607',
            'client_id' => '',
            'client_credential' => '',
            'author_urn' => '',
            'access_token' => '',
            'next_steps' => [
                'LinkedIn app credentials stored',
                'Generate a LinkedIn access token with the scope required by the author URN',
                'Use a member author URN with w_member_social or an organization author URN with w_organization_social',
                'Publish text posts or uploaded image posts',
            ],
        ],
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function adminPlatforms(): array
    {
        return array_map(function (array $platform) {
            $platform['has_client_credentials'] = self::value($platform['slug'], 'client_id') !== null
                && self::value($platform['slug'], 'client_credential') !== null;
            unset($platform['access_token'], $platform['page_access_token'], $platform['client_id'], $platform['client_credential'], $platform['author_urn']);
            $platform['has_access_token'] = self::hasAccessToken($platform['slug']);
            $platform['api_state'] = $platform['has_access_token']
                ? 'Access token configured'
                : $platform['api_state'];

            return $platform;
        }, self::PLATFORMS);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function adminPlatform(string $slug): ?array
    {
        return self::adminPlatforms()[$slug] ?? null;
    }

    public static function hasAccessToken(string $slug): bool
    {
        return self::accessToken($slug) !== null;
    }

    public static function accessToken(string $slug): ?string
    {
        $token = trim((string) self::value($slug, 'access_token', ''));

        if ($slug === 'facebook') {
            $pageToken = trim((string) self::value($slug, 'page_access_token', ''));
            $token = $pageToken !== '' ? $pageToken : $token;
        }

        if ($token === '' && isset(self::PLATFORMS[$slug]['token_source'])) {
            $token = trim((string) self::value(self::PLATFORMS[$slug]['token_source'], 'access_token', ''));
        }

        return $token !== '' ? $token : null;
    }

    public static function value(string $slug, string $key, mixed $default = null): mixed
    {
        $configValue = config('services.social.'.$slug.'.'.$key);

        if ($configValue !== null && trim((string) $configValue) !== '') {
            return $configValue;
        }

        $value = self::PLATFORMS[$slug][$key] ?? null;

        if ($value !== null && trim((string) $value) !== '') {
            return $value;
        }

        return $default;
    }
}
