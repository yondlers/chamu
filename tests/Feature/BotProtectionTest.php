<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class BotProtectionTest extends TestCase
{
    public function test_search_engines_can_read_public_pages(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
            ->get('/about')
            ->assertOk();
    }

    public function test_people_can_read_public_pages(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36')
            ->get('/about')
            ->assertOk();
    }

    public function test_login_and_register_pages_include_the_hidden_honeypot(): void
    {
        $browser = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

        $this->withHeader('User-Agent', $browser)
            ->get('/login')
            ->assertOk()
            ->assertSee('name="hp_field"', false)
            ->assertSee('name="form_started_at"', false);

        $this->withHeader('User-Agent', $browser)
            ->get('/register')
            ->assertOk()
            ->assertSee('name="hp_field"', false)
            ->assertSee('name="form_started_at"', false);
    }

    public function test_ai_scrapers_are_blocked(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)')
            ->get('/about')
            ->assertForbidden();
    }

    public function test_empty_user_agents_are_blocked(): void
    {
        $this->withHeader('User-Agent', '')
            ->get('/about')
            ->assertForbidden();
    }

    public function test_registration_rejects_honeypot_and_instant_posts(): void
    {
        $startedAt = Crypt::encryptString((string) now()->subSeconds(10)->getTimestamp());

        $this->from('/register')->post('/register', [
            'hp_field' => 'http://spam.example',
            'form_started_at' => $startedAt,
            'first_name' => 'Bot',
            'username' => 'spambot',
            'email' => 'spam@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();

        $this->from('/register')->post('/register', [
            'first_name' => 'Bot',
            'username' => 'spambot',
            'email' => 'spam@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();

        $this->from('/register')->post('/register', [
            'form_started_at' => Crypt::encryptString((string) now()->getTimestamp()),
            'first_name' => 'Bot',
            'username' => 'spambot',
            'email' => 'spam@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();
    }

    public function test_robots_txt_blocks_ai_crawlers_and_keeps_the_sitemap(): void
    {
        $robots = (string) file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('User-agent: GPTBot', $robots);
        $this->assertStringContainsString('User-agent: ClaudeBot', $robots);
        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Sitemap: https://chamu.co.za/sitemap.xml', $robots);
    }
}
