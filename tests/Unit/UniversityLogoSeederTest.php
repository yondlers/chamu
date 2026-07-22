<?php

namespace Tests\Unit;

use Database\Seeders\UniversityLogoSeeder;
use PHPUnit\Framework\TestCase;

class UniversityLogoSeederTest extends TestCase
{
    public function test_cput_stale_remote_logo_is_replaced_with_local_asset(): void
    {
        $this->assertSame(
            'images/universities/cput.png',
            UniversityLogoSeeder::logoFor(
                'CPUT',
                'https://www.cput.ac.za/images/About/Brand%20ID/img_branding_logo_correct.jpg',
            ),
        );
    }

    public function test_custom_remote_logo_is_preserved(): void
    {
        $this->assertSame(
            'https://cdn.example.test/logo.png',
            UniversityLogoSeeder::logoFor('CPUT', 'https://cdn.example.test/logo.png'),
        );
    }
}
