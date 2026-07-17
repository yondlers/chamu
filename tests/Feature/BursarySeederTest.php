<?php

namespace Tests\Feature;

use Database\Seeders\BursarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BursarySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_postgraduate_sources_are_added_and_email_applications_use_chamu(): void
    {
        $this->seed(BursarySeeder::class);

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.zabursaries.co.za/government-bursaries-south-africa/department-of-environment-forestry-and-fisheries-bursary/',
            'title' => 'Department of Forestry, Fisheries and the Environment (DFFE) Bursary',
            'category' => 'Postgraduate',
        ]);

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.zabursaries.co.za/international-scholarships-bursaries-south-africa/gates-cambridge-international-scholarship/',
            'title' => 'Gates Cambridge International Scholarship',
            'category' => 'Postgraduate',
        ]);

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/science-bursaries-south-africa/african-institute-for-mathematical-sciences-bursaries/',
            'research-admin@aims.ac.za',
        );

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/government-bursaries-south-africa/department-of-tourism-bursary/',
            'Bursary2026@tourism.gov.za',
        );

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/engineering-bursaries-south-africa/council-for-geoscience-bursary/',
            'bursaries@geoscience.org.za',
        );
    }

    private function assertEmailBursaryIsChamuManaged(string $sourceUrl, string $email): void
    {
        $bursary = DB::table('bursaries')->where('source_url', $sourceUrl)->first();

        $this->assertNotNull($bursary);
        $this->assertSame('email', $bursary->application_delivery_type);
        $this->assertSame($email, $bursary->application_email);
        $this->assertSame('mailto:'.$email, $bursary->apply_url);
        $this->assertTrue((bool) $bursary->chamu_apply_enabled);
        $this->assertSame(10, DB::table('bursary_document_requirements')->where('bursary_id', $bursary->id)->count());
    }
}
