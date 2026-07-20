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
            'access_token' => 'EAAeFz3w2lusBSLDwRktUqmdjVNERQBa6oH0baFDSZChT4B6iIvrH86dsG9PYTeZBxZCWPafdZCq0tQjTHrUXq6xG0Fe7dThs5zSVoBCRVLbw6mJ3ZCROOOgWWqzuwYyb1p6blUxR9K2urhzjG7Wi368G2Xq6RZCVxBv3E6if7G78we3wbOcqdzsAyEFgjlZAPbZALOkQAGkzU9teO0mWuVF0ZCSOWTZAOHQyLAIDoom0O9uWYZD',
            'next_steps' => [
                'Create or connect the Meta app',
                'Add page publishing permissions',
                'Store long-lived page access tokens',
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
            'access_token' => '',
            'next_steps' => [
                'Connect an Instagram business account',
                'Map media upload and publish flow',
                'Add caption, hashtag, and asset validation',
                'Configure comment moderation webhooks',
            ],
        ],
        'linkedin' => [
            'slug' => 'linkedin',
            'name' => 'LinkedIn',
            'icon' => 'briefcase',
            'accent' => '#0A66C2',
            'audience' => 'Sponsors, partners, schools, and professional networks',
            'surface' => 'Company page updates and campaign reporting',
            'tone' => 'Partner-facing education and funding updates',
            'api_state' => 'LinkedIn API connection pending',
            'engagement_state' => 'Organization social actions depend on API product access',
            'access_token' => '',
            'next_steps' => [
                'Create or connect the LinkedIn app',
                'Request organization posting access',
                'Store organization URN and OAuth tokens',
                'Map reactions and comments if products are approved',
            ],
        ],
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function adminPlatforms(): array
    {
        return array_map(function (array $platform) {
            unset($platform['access_token']);
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
        $token = trim((string) (self::PLATFORMS[$slug]['access_token'] ?? ''));

        return $token !== '' ? $token : null;
    }

    public static function value(string $slug, string $key, mixed $default = null): mixed
    {
        return self::PLATFORMS[$slug][$key] ?? $default;
    }
}
