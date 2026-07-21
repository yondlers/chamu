<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Qualification;
use App\Models\QualificationType;
use App\Models\University;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SimpleXMLElement;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_xml_with_public_university_and_qualification_urls(): void
    {
        config(['app.url' => 'https://chamu.co.za']);

        $records = $this->createSitemapRecords();
        $university = $records['university'];
        $otherUniversity = $records['other_university'];
        $qualification = $records['qualification'];

        $response = $this->get('/sitemap.xml');
        $content = $response->streamedContent();
        $expectedUniversityUrl = 'https://chamu.co.za'.route('public.universities.show', ['university' => $university->slug], false);
        $expectedQualificationUrl = 'https://chamu.co.za'.route('public.qualifications.show', [
            'university' => $university->slug,
            'qualification' => $qualification->slug,
        ], false);

        $response->assertOk();
        $this->assertSame('application/xml; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<loc>https://chamu.co.za</loc>', $content);
        $this->assertStringContainsString('<loc>https://chamu.co.za/aps</loc>', $content);
        $this->assertStringContainsString('<loc>https://chamu.co.za/learn</loc>', $content);
        $this->assertStringContainsString('<loc>https://chamu.co.za/guides</loc>', $content);
        $this->assertStringContainsString('<loc>https://chamu.co.za/guides/how-aps-works</loc>', $content);
        $this->assertStringContainsString('<loc>https://chamu.co.za/about</loc>', $content);
        $this->assertStringContainsString('<loc>https://chamu.co.za/contact</loc>', $content);
        $this->assertStringContainsString('<loc>https://chamu.co.za/privacy-policy</loc>', $content);
        $this->assertStringContainsString('<loc>https://chamu.co.za/terms</loc>', $content);
        $this->assertStringContainsString(
            '<loc>'.$expectedUniversityUrl.'</loc>',
            $content,
        );
        $this->assertStringContainsString(
            '<loc>'.$expectedQualificationUrl.'</loc>',
            $content,
        );
        $this->assertStringNotContainsString('/login', $content);
        $this->assertStringNotContainsString('/register', $content);
        $this->assertStringNotContainsString('/admin', $content);
        $this->assertStringNotContainsString('/courses/', $content);
        $this->assertStringNotContainsString('/programmes', $content);
        $this->assertStringNotContainsString('/properties', $content);
        $this->assertStringNotContainsString(
            '/universities/'.$otherUniversity->slug.'/qualifications/'.$qualification->slug,
            $content,
        );

        $xml = simplexml_load_string($content);

        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
    }

    public function test_sitemap_does_not_error_when_slug_migration_has_not_run(): void
    {
        config(['app.url' => 'https://chamu.co.za']);

        Schema::table('qualifications', function (Blueprint $table) {
            $table->dropUnique('qualifications_university_slug_unique');
            $table->dropColumn('slug');
        });
        Schema::table('universities', function (Blueprint $table) {
            $table->dropUnique('universities_slug_unique');
            $table->dropColumn('slug');
        });

        $response = $this->get('/sitemap.xml');
        $content = $response->streamedContent();

        $response->assertOk();
        $this->assertSame('application/xml; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<loc>https://chamu.co.za</loc>', $content);
        $this->assertInstanceOf(SimpleXMLElement::class, simplexml_load_string($content));
    }

    /**
     * @return array<string, mixed>
     */
    private function createSitemapRecords(): array
    {
        $now = now();
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'South Africa',
            'nationality' => 'South African',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $type = QualificationType::create([
            'name' => 'Bachelor Degree',
            'abbreviation' => 'BDeg',
        ]);
        $university = University::create([
            'country_id' => $countryId,
            'name' => 'University of Pretoria',
            'abbreviation' => 'UP',
        ]);
        $otherUniversity = University::create([
            'country_id' => $countryId,
            'name' => 'University of Cape Town',
            'abbreviation' => 'UCT',
        ]);
        $faculty = Faculty::create([
            'university_id' => $university->id,
            'name' => 'Faculty of Commerce',
        ]);
        $qualification = Qualification::create([
            'university_id' => $university->id,
            'faculty_id' => $faculty->id,
            'qualification_type_id' => $type->id,
            'name' => 'Bachelor of Commerce Accounting',
            'duration_years' => 3,
            'aps_required' => 30,
            'is_selection_programme' => false,
        ]);

        return [
            'university' => $university,
            'other_university' => $otherUniversity,
            'qualification' => $qualification,
        ];
    }
}
