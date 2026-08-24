<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bot protection
    |--------------------------------------------------------------------------
    |
    | Search engines and social preview crawlers stay allowed so SEO and
    | link unfurls keep working. AI scrapers, SEO harvesters, and generic
    | scripted clients are blocked in middleware.
    |
    */

    'enabled' => env('BOT_PROTECTION_ENABLED', true),

    'honeypot_field' => 'hp_field',

    'form_started_at_field' => 'form_started_at',

    'register_minimum_seconds' => 2,

    'allowed_user_agents' => [
        'adsbot-google',
        'apis-google',
        'applebot',
        'baiduspider',
        'bingbot',
        'bingpreview',
        'discordbot',
        'duckduckbot',
        'facebookcatalog',
        'facebookexternalhit',
        'facebot',
        'feedfetcher-google',
        'google-inspectiontool',
        'googlebot',
        'googleimageproxy',
        'linkedinbot',
        'mediapartners-google',
        'pingdom',
        'pinterest',
        'slackbot',
        'slurp',
        'statuscake',
        'telegrambot',
        'twitterbot',
        'uptimerobot',
        'whatsapp',
        'yandex',
    ],

    'blocked_user_agents' => [
        'ahrefsbot',
        'ai2bot',
        'amazonbot',
        'anthropic-ai',
        'applebot-extended',
        'bytespider',
        'ccbot',
        'chatgpt-user',
        'claude-web',
        'claudebot',
        'cohere-ai',
        'curl/',
        'dataforseobot',
        'diffbot',
        'dotbot',
        'go-http-client',
        'google-cloudvertexbot',
        'google-extended',
        'gptbot',
        'headlesschrome',
        'imagesiftbot',
        'libwww-perl',
        'megaindex',
        'meta-externalagent',
        'meta-externalfetcher',
        'mj12bot',
        'oai-searchbot',
        'omgilibot',
        'perplexitybot',
        'petalbot',
        'phantomjs',
        'playwright',
        'puppeteer',
        'python-requests',
        'python-urllib',
        'scrapy',
        'selenium',
        'semrushbot',
        'wget',
        'youbot',
    ],

];
