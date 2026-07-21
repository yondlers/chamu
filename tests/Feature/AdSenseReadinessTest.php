<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdSenseReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_ads_txt_contains_google_seller_record(): void
    {
        $adsTxt = file_get_contents(public_path('ads.txt'));

        $this->assertSame(
            "google.com, pub-4352231193802470, DIRECT, f08c47fec0942fa0\n",
            $adsTxt,
        );
    }

    public function test_adsense_script_is_limited_to_content_pages(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', false)
            ->assertDontSee('data-ad-client="ca-pub-4352231193802470"', false);

        $this->get('/content')
            ->assertOk()
            ->assertSee('Explore study content by subject')
            ->assertDontSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', false)
            ->assertDontSee('data-ad-client="ca-pub-4352231193802470"', false);

        $this->get('/guides')
            ->assertOk()
            ->assertSee('Study and Application Guides')
            ->assertSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', false)
            ->assertSee('data-ad-client="ca-pub-4352231193802470"', false);
    }

    public function test_trust_and_guide_pages_are_public(): void
    {
        $this->get('/about')->assertOk()->assertSee('Chamu makes study decisions easier to compare.');
        $this->get('/contact')->assertOk()->assertSee('support@chamu.co.za');
        $this->get('/privacy-policy')->assertOk()->assertSee('Google Advertising Cookies');
        $this->get('/terms')->assertOk()->assertSee('Educational Planning Only');
        $this->get('/guides/how-aps-works')->assertOk()->assertSee('How APS Works in South Africa');
    }
}
