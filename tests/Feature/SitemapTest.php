<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Qualification;
use App\Models\QualificationType;
use App\Models\University;
use Database\Seeders\AdmissionRuleSeeder;
use Database\Seeders\CapsSubjectSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\NqfLevelSeeder;
use Database\Seeders\QualificationTypeSeeder;
use Database\Seeders\SubjectCategorySeeder;
use Database\Seeders\Universities\CJC\RequirementSeeder as CjcRequirementSeeder;
use Database\Seeders\Universities\TNC\RequirementSeeder as TncRequirementSeeder;
use Database\Seeders\Universities\TSC\RequirementSeeder as TscRequirementSeeder;
use Database\Seeders\Universities\UNISA\RequirementSeeder as UnisaRequirementSeeder;
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
        $bursaryId = $records['bursary_id'];

        $response = $this->get('/sitemap.xml');
        $content = $response->streamedContent();
        $expectedUniversityUrl = 'https://chamu.co.za'.route('public.universities.show', ['university' => $university->slug], false);
        $expectedQualificationUrl = 'https://chamu.co.za'.route('public.qualifications.show', [
            'university' => $university->slug,
            'qualification' => $qualification->slug,
        ], false);

        $response->assertOk();
        $this->assertSame('application/xml; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringNotContainsString('<loc>https://chamu.co.za</loc>', $content);
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
        $this->assertStringContainsString(
            '<loc>https://chamu.co.za/bursaries/'.$bursaryId.'</loc>',
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

    public function test_sitemap_includes_seeded_colleges_and_unisa_qualification_pages(): void
    {
        config(['app.url' => 'https://chamu.co.za']);

        $this->seedPublicRequirementData();

        $response = $this->get('/sitemap.xml');
        $content = $response->streamedContent();

        $response->assertOk();

        foreach ([
            'central-johannesburg-tvet-college',
            'tshwane-north-tvet-college',
            'tshwane-south-tvet-college',
            'university-of-south-africa',
        ] as $universitySlug) {
            $this->assertStringContainsString(
                '<loc>https://chamu.co.za/universities/'.$universitySlug.'</loc>',
                $content,
            );
        }

        foreach ([
            ['CJC', 'Art and Design'],
            ['TNC', 'Mechatronics'],
            ['TSC', 'Electrician'],
            ['UNISA', 'Bachelor of Social Work'],
        ] as [$universityAbbreviation, $qualificationName]) {
            $qualification = $this->seededQualification($universityAbbreviation, $qualificationName);

            $this->assertStringContainsString(
                '<loc>https://chamu.co.za'.route('public.qualifications.show', [
                    'university' => $qualification->university_slug,
                    'qualification' => $qualification->slug,
                ], false).'</loc>',
                $content,
            );
        }

        $this->assertInstanceOf(SimpleXMLElement::class, simplexml_load_string($content));
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
        $this->assertStringNotContainsString('<loc>https://chamu.co.za</loc>', $content);
        $this->assertStringContainsString('<loc>https://chamu.co.za/aps</loc>', $content);
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
        $bursaryId = DB::table('bursaries')->insertGetId([
            'company_id' => null,
            'title' => 'Accounting Support Bursary',
            'slug' => 'accounting-support-bursary',
            'category' => 'Accounting',
            'summary' => 'Funding support for accounting students.',
            'source_url' => 'https://example.com/accounting-support-bursary',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'university' => $university,
            'other_university' => $otherUniversity,
            'qualification' => $qualification,
            'bursary_id' => $bursaryId,
        ];
    }

    private function seedPublicRequirementData(): void
    {
        $now = now();
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'South Africa',
            'nationality' => 'South African',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('curriculums')->insert([
            'country_id' => $countryId,
            'name' => 'NSC (National Senior Certificate)',
            'abbreviation' => 'CAPS',
            'is_live' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->seed([
            NqfLevelSeeder::class,
            SubjectCategorySeeder::class,
            GradeSeeder::class,
            QualificationTypeSeeder::class,
            CapsSubjectSeeder::class,
            AdmissionRuleSeeder::class,
            CjcRequirementSeeder::class,
            TncRequirementSeeder::class,
            TscRequirementSeeder::class,
            UnisaRequirementSeeder::class,
        ]);
    }

    private function seededQualification(string $universityAbbreviation, string $qualificationName): object
    {
        $qualification = DB::table('qualifications')
            ->join('universities', 'universities.id', '=', 'qualifications.university_id')
            ->where('universities.abbreviation', $universityAbbreviation)
            ->where('qualifications.name', $qualificationName)
            ->select('qualifications.slug', 'universities.slug as university_slug')
            ->first();

        $this->assertNotNull($qualification);

        return $qualification;
    }
}
