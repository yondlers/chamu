<?php

namespace Tests\Unit;

use App\Support\BotGuard;
use Tests\TestCase;

class BotGuardTest extends TestCase
{
    public function test_it_allows_search_engines_and_social_previews(): void
    {
        $guard = app(BotGuard::class);

        $this->assertFalse($guard->shouldBlockUserAgent('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'));
        $this->assertFalse($guard->shouldBlockUserAgent('Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'));
        $this->assertFalse($guard->shouldBlockUserAgent('facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'));
        $this->assertFalse($guard->shouldBlockUserAgent('Mediapartners-Google'));
        $this->assertFalse($guard->shouldBlockUserAgent('AdsBot-Google (+http://www.google.com/adsbot.html)'));
    }

    public function test_it_blocks_ai_scrapers_and_scripted_clients(): void
    {
        $guard = app(BotGuard::class);

        $this->assertTrue($guard->shouldBlockUserAgent('Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)'));
        $this->assertTrue($guard->shouldBlockUserAgent('ClaudeBot/1.0'));
        $this->assertTrue($guard->shouldBlockUserAgent('CCBot/2.0 (https://commoncrawl.org/faq/)'));
        $this->assertTrue($guard->shouldBlockUserAgent('Mozilla/5.0 (compatible; Bytespider; +https://zhanzhang.toutiao.com/)'));
        $this->assertTrue($guard->shouldBlockUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Applebot/0.1 (Applebot-Extended)'));
        $this->assertTrue($guard->shouldBlockUserAgent('curl/8.6.0'));
        $this->assertTrue($guard->shouldBlockUserAgent('python-requests/2.31.0'));
        $this->assertTrue($guard->shouldBlockUserAgent(''));
        $this->assertTrue($guard->shouldBlockUserAgent(null));
        $this->assertTrue($guard->shouldBlockUserAgent('UnknownCrawler/1.0'));
    }

    public function test_it_allows_normal_browsers(): void
    {
        $guard = app(BotGuard::class);

        $this->assertFalse($guard->shouldBlockUserAgent(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
        ));
        $this->assertFalse($guard->shouldBlockUserAgent(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36'
        ));
    }
}
