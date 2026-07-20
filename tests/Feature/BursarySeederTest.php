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

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.zabursaries.co.za/government-bursaries-south-africa/alfred-nzo-district-municipality-bursary/',
            'title' => 'Alfred Nzo District Municipality Bursary',
            'category' => 'Government',
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

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/government-bursaries-south-africa/department-of-mineral-resources-bursary/',
            'GirlLearnerBursary@dmre.gov.za',
        );

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/government-bursaries-south-africa/national-agricultural-marketing-council-namc-bursary/',
            'hrrecruitment@namc.co.za',
        );

        $this->assertPostalBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/government-bursaries-south-africa/alfred-nzo-district-municipality-bursary/',
        );

        $this->assertPostalBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/government-bursaries-south-africa/department-of-correctional-services-bursary/',
        );

        $this->assertPostalBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/government-bursaries-south-africa/national-department-of-social-development-bursary/',
        );

        $this->assertExternalBursaryIsNotChamuManaged(
            'https://www.zabursaries.co.za/government-bursaries-south-africa/national-disaster-management-centre-ndmc-bursary/',
        );

        $this->assertExternalBursaryIsNotChamuManaged(
            'https://www.zabursaries.co.za/international-scholarships-bursaries-south-africa/nfvf-international-bursary/',
        );
    }

    public function test_computer_science_it_sources_are_seeded_without_duplicates(): void
    {
        $this->seed(BursarySeeder::class);

        $sourceUrls = [
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/academy-of-digital-arts-bursaries/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/advance-africa-scholarship/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/amazon-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/bbd-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/bet-bursary/',
            'https://studytrust.org.za/cisco/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/csg-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/dalitso-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/dynamics-corporate-consulting-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/easypay-everywhere-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/entersekt-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/exness-fintech-scholarship/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/forge-academy-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/friends-of-design-bursaries/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/games-global-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/generation-google-scholarship/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/generation-google-scholarship-women-in-gaming/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/girlcodeza-scholarship/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/hewlett-packard-enterprise-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/hp-inc-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/iitpsa-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/investec-scholarship/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/isaca-scholarship/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/internet-service-providers-association-ispa-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/khulisa-academy-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/knowbe4-women-of-colour-in-cybersecurity-scholarship/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/korbicom-education-trust-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/metacom-foundation-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/mondia-media-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/motus-mobility-solutions-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/mukuru-bursary-for-foreign-nationals/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/multichoice-sa-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/natasha-joubert-collective-x-asus-coding-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/old-mutual-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/openserve-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/pretune-sim-card-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/probe-imt-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/sage-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/sanlam-it-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/sasseta-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/signa-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/sisekelo-institute-of-business-and-technology-scholarship/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/sita-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/tbwa-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/tech-capital-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/thales-southern-africa-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/the-document-warehouse-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/saps-tracker-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/uct-department-of-information-systems-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/vodacom-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/volkswagen-group-south-africa-vwsa-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/wethinkcode-bursaries/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/women-in-it-bursary/',
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/world-sports-betting-bursary/',
        ];

        $this->assertCount(54, $sourceUrls);
        $this->assertSame($sourceUrls, array_values(array_unique($sourceUrls)));

        foreach ($sourceUrls as $sourceUrl) {
            $this->assertSame(
                1,
                DB::table('bursaries')->where('source_url', $sourceUrl)->count(),
                $sourceUrl,
            );
        }

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/academy-of-digital-arts-bursaries/',
            'title' => 'Academy of Digital Arts Bursary',
            'category' => 'Computer Science and IT',
        ]);

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/bbd-bursary/',
            'title' => 'BBD Bursary',
            'category' => 'Computer Science and IT',
        ]);

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/openserve-bursary/',
            'title' => 'Openserve Bursary',
            'category' => 'Computer Science and IT',
            'application_delivery_type' => 'external_link',
            'apply_url' => 'https://telkom.erecruit.co/candidateapp/Jobs/Categories/Openserve_CoE_Undergraduate_Bursary_Programme/6dfe3be2b9a84203b4f3e46f12027442',
            'chamu_apply_enabled' => false,
        ]);

        $openserve = DB::table('bursaries')
            ->where('source_url', 'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/openserve-bursary/')
            ->first();
        $openserveEligibility = json_decode($openserve->eligibility_requirements, true);
        $openserveDocuments = json_decode($openserve->supporting_documents, true);

        $this->assertContains('South African citizen', $openserveEligibility);
        $this->assertContains('Currently in 2nd or 3rd year of study', $openserveEligibility);
        $this->assertContains('2nd year applicants need at least 70% for Technology or Engineering subjects and at least 65% overall average in 1st year', $openserveEligibility);
        $this->assertContains('3rd year applicants need at least 70% for Technology and Engineering subjects and at least 65% overall average in 2nd year', $openserveEligibility);
        $this->assertContains('Certified copy of ID document', $openserveDocuments);
        $this->assertContains('Full academic transcript or record on institution letterhead', $openserveDocuments);
        $this->assertDatabaseHas('bursary_subject_requirements', [
            'bursary_id' => $openserve->id,
            'subject_name' => 'Overall average',
            'minimum_mark' => 65,
            'requirement_type' => 'minimum_average',
        ]);

        $this->assertSame(
            0,
            DB::table('bursaries')
                ->where('source_url', 'http://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/bbd-bursary/')
                ->count(),
        );

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/uct-department-of-information-systems-bursary/',
            'generalawardsapplications@uct.ac.za',
        );

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/women-in-it-bursary/',
            'bursary@iitpsa.org.za',
        );

        $this->assertExternalBursaryIsNotChamuManaged(
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/bbd-bursary/',
        );

        $this->assertExternalBursaryIsNotChamuManaged(
            'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/academy-of-digital-arts-bursaries/',
        );
    }

    public function test_studytrust_sources_are_seeded_and_email_pages_use_chamu(): void
    {
        $this->seed(BursarySeeder::class);

        $studyTrustApplyUrl = 'https://studytrust.kycdd.co.za/client/-/insert/subscription_id/9ff211fb-b816-470a-a41f-300fea952f78/workflow_id/a117b2f8-4574-482f-a323-fac07ef53661';
        $externalSources = [
            'https://studytrust.org.za/standardbank/' => 'Standard Bank Group Bursary',
            'https://studytrust.org.za/toyota/' => 'Toyota SA Motors Bursary',
            'https://studytrust.org.za/cisco/' => 'Cisco Charitable Foundation Trust Bursary',
            'https://studytrust.org.za/promaths-bursary-fund/' => 'Promaths Bursary Fund',
            'https://studytrust.org.za/tfg-data-science/' => 'TFG Data Science and Leadership Fellowship',
            'https://studytrust.org.za/matla-a-bakone-solar/' => 'Matla A Bokone Solar Bursary Programme',
            'https://studytrust.org.za/kathu-solar-park/' => 'Kathu Solar Park Bursary Programme',
            'https://studytrust.org.za/lilitha-solar-pv-bursary' => 'Lilitha Solar PV Bursary Programme',
            'https://studytrust.org.za/solar-capital-orange/' => 'Solar Capital Orange Bursary Programme',
            'https://studytrust.org.za/enel-green-power-rsa-bursaries/' => 'Enel Green Power Bursary',
            'https://studytrust.org.za/kangnas-wind-farm/' => 'Kangnas Wind Farm Bursary Programme',
        ];

        foreach ($externalSources as $sourceUrl => $title) {
            $this->assertDatabaseHas('bursaries', [
                'source_url' => $sourceUrl,
                'title' => $title,
                'application_delivery_type' => 'external_link',
                'apply_url' => $studyTrustApplyUrl,
                'chamu_apply_enabled' => false,
            ]);
        }

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://studytrust.org.za/dafi-scholarship/',
            'title' => 'DAFI Scholarship Programme',
            'category' => 'International Scholarships',
            'application_delivery_type' => 'external_link',
            'apply_url' => 'https://unhcr-dafi-tertiary.scholarshipsplatform.com/',
            'chamu_apply_enabled' => false,
        ]);

        foreach ([
            'https://studytrust.org.za/bursaries/amandla-omoya-trust-bursary-programme',
            'https://studytrust.org.za/aurora-wind-power',
            'https://studytrust.org.za/khobab-wind-farm',
            'https://studytrust.org.za/letsatsi-borutho-trust-bursary-programme',
            'https://studytrust.org.za/roggeveld-wind-power',
            'https://studytrust.org.za/sibona-ilanga-trust-bursary-programme',
            'https://studytrust.org.za/sishen-solar-facility',
        ] as $sourceUrl) {
            $this->assertEmailBursaryIsChamuManaged($sourceUrl, 'b.ndlovu@studytrust.org.za');
        }

        $this->assertSame(
            0,
            DB::table('bursaries')
                ->whereIn('source_url', [
                    'https://www.zabursaries.co.za/general-bursaries-south-africa/amandla-omoya-trust-bursary/',
                    'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/cisco-charitable-foundation-trust-bursary/',
                    'https://www.zabursaries.co.za/general-bursaries-south-africa/enel-bursary/',
                ])
                ->count(),
        );
    }

    public function test_science_sources_are_seeded_without_duplicates_and_email_applications_use_chamu(): void
    {
        $this->seed(BursarySeeder::class);

        $sourceUrls = [
            'https://www.zabursaries.co.za/science-bursaries-south-africa/absa-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/african-exploration-mining-and-finance-corporation-aemfc-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/african-institute-for-mathematical-sciences-bursaries/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/agriseta-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/agtechnwk-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/alexander-forbes-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/amsol-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/aspen-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/astrazeneca-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/basf-south-africa-trust-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/beefmaster-group-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/bester-bursary-graduate-development-programme/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/black-management-forum-bmf-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/cape-wools-sa-bursary-fund/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/chieta-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/citrus-academy-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/corteva-agriscience-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/digby-wells-environmental-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/dvd-quality-mining-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/east-london-idz-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/energy-mobility-education-trust-scholarship/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/esri-south-africa-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/ewseta-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/firstrand-foundation-trust-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/food-for-mzansi-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/financial-sector-conduct-authority-fsca-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/fsca-study-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/garden-cities-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/gemalto-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/globeleq-education-trust-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/gwk-ben-scholtz-bursary-fund/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/heraeus-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/hortgro-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/hwseta-bursary-competition/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/impala-platinum-implats-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/indalo-education-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/isimangaliso-wetland-park-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/kirstenbosch-scholarship/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/komatsu-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/land-bank-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/liberty-life-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/maize-trust-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/marula-platinum-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/metrohm-sa-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/mezzanine-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/milliman-scholarship/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/mmi-holdings-limited-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/mondi-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/monocle-foundation-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/mutfsco-basf-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/naspers-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/ngodwana-energy-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/national-research-foundation-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/old-mutual-actuarial-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/omnia-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/potato-industry-development-trust-bursaries/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/pps-pioneer-scholarship/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/puma-energy-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/pwc-honours-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/quantum-foods-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/saafost-aubrey-parsons-study-grant/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/saafost-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/saafost-foundation-bursary-for-part-time-students/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/saafost-bursary-for-matric-students/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/saafost-bursary-for-undergraduate-students/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/sacccs-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/sa-cultivar-technology-agency-sacta-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/sakata-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/south-african-astronomical-observatory-saao-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/samac-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/samsa-south-african-maritime-safety-authority-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/sanlam-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/sanparks-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/santam-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/sars-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/sasol-agriculture-trust-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/shell-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/sugar-industry-trust-fund-for-education-sitfe-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/siza-water-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/slr-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/soil-science-society-of-south-africa-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/sorghum-trust-scholarship/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/saimi-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/south-africa-wine-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/south-african-actuaries-development-programme-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/south-african-association-of-botanists-scholarship/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/south-african-civil-aviation-authority-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/south-african-landscapers-institute-sali-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/south-african-nursery-association-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/sascp-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/south-african-statistical-association-sasa-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/south-african-table-grape-industry-sati-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/southern-african-weed-science-society-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/southern-farms-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/starke-ayres-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/subtrop-lindsey-milne-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/subtrop-saaga-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/taylors-halt-quarry-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/thermo-fisher-scientific-phambili-trust-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/tongaat-hulett-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/trans-hex-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/tronox-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/tshipi-e-ntle-manganese-mining-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/twk-agri-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/vermaak-and-partners-pathologists-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/waaw-foundation-scholarship/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/wesolve4x-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/western-cape-department-of-agriculture-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/westfalia-fruit-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/winter-cereal-trust-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/wiphold-bursary/',
            'https://www.zabursaries.co.za/science-bursaries-south-africa/xylem-sa-trust-bursary/',
        ];

        $this->assertCount(112, $sourceUrls);
        $this->assertSame($sourceUrls, array_values(array_unique($sourceUrls)));

        foreach ($sourceUrls as $sourceUrl) {
            $this->assertSame(
                1,
                DB::table('bursaries')->where('source_url', $sourceUrl)->count(),
                $sourceUrl,
            );
        }

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.zabursaries.co.za/science-bursaries-south-africa/absa-bursary/',
            'title' => 'Absa Bursary',
            'category' => 'Science',
        ]);

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/science-bursaries-south-africa/african-exploration-mining-and-finance-corporation-aemfc-bursary/',
            'Bursaries@aemfc.co.za',
        );

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/science-bursaries-south-africa/slr-bursary/',
            'bursaryapplications@slrconsulting.com',
        );

        $this->assertExternalBursaryIsNotChamuManaged(
            'https://www.zabursaries.co.za/science-bursaries-south-africa/xylem-sa-trust-bursary/',
        );
    }

    public function test_bmw_bursary_uses_provider_apply_link(): void
    {
        $this->seed(BursarySeeder::class);

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.zabursaries.co.za/engineering-bursaries-south-africa/bmw-bursary/',
            'title' => 'BMW Bursary',
            'category' => 'Engineering',
            'apply_url' => 'https://www.bmwgroup.jobs/za/en/opportunities/student.html',
        ]);
    }

    public function test_clockwork_empowerment_fund_is_chamu_email_application(): void
    {
        $this->seed(BursarySeeder::class);

        $sourceUrl = 'https://www.zabursaries.co.za/commerce-bursaries-south-africa/clockwork-empowerment-fund-bursary/';

        $this->assertDatabaseHas('bursaries', [
            'source_url' => $sourceUrl,
            'title' => 'Clockwork Bursary',
            'category' => 'Commerce',
            'application_delivery_type' => 'email',
            'application_email' => 'jobs@clockworkmedia.co.za',
            'apply_url' => 'mailto:jobs@clockworkmedia.co.za',
            'chamu_apply_enabled' => true,
        ]);

        $this->assertEmailBursaryIsChamuManaged($sourceUrl, 'jobs@clockworkmedia.co.za');
    }

    public function test_dhet_international_scholarships_use_real_apply_links(): void
    {
        $this->seed(BursarySeeder::class);

        $sourceUrls = [
            'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/110-netherlands-the-nl-scholarships-26-27',
            'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/328-germany-helmut-schmidt-programme-masters-scholarships-for-public-policy-and-good-governance-2027',
            'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/53-erasmus-erasmus-mundus-joint-masters-scholarship-programme',
            'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/111-united-kingdom-the-rhodes-scholarships-for-southern-africa-for-2027',
            'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/329-russia-mba-programme-in-subsoil-mineral-resources-management-2026',
            'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/114-united-states-the-fulbright-south-african-research-scholar-programme-2027-2028',
            'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/330-thailand-the-thailand-scholarships-for-international-students-in-the-academic-year-2026',
            'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/331-united-states-the-hubert-h-humphrey-fellowship-programme-2027-2028',
            'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/117-france-french-institutes-for-advanced-study-fellowship-programme-2027-2028',
            'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/332-ireland-kader-asmal-fellowship-programme-2027',
        ];

        $this->assertCount(10, $sourceUrls);
        $this->assertSame($sourceUrls, array_values(array_unique($sourceUrls)));
        $this->assertSame(
            10,
            DB::table('bursaries')
                ->whereIn('source_url', $sourceUrls)
                ->where('category', 'International Scholarships')
                ->count(),
        );

        foreach ($sourceUrls as $sourceUrl) {
            $this->assertSame(1, DB::table('bursaries')->where('source_url', $sourceUrl)->count(), $sourceUrl);
            $this->assertExternalBursaryIsNotChamuManaged($sourceUrl);
        }

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/110-netherlands-the-nl-scholarships-26-27',
            'title' => 'Netherlands: The NL Scholarships 2026/27',
            'apply_url' => 'https://www.studyinnl.org/finances/nl-scholarship',
            'closing_date_label' => 'Varies by institution',
        ]);

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/328-germany-helmut-schmidt-programme-masters-scholarships-for-public-policy-and-good-governance-2027',
            'apply_url' => 'https://www2.daad.de/deutschland/stipendium/datenbank/en/21148-scholarship-database/?status=&origin=&subjectGrps=&daad=&intention=&q=helmut&page=1&detail=50026397',
            'closing_date' => '2026-07-31',
        ]);

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/111-united-kingdom-the-rhodes-scholarships-for-southern-africa-for-2027',
            'apply_url' => 'https://www.rhodeshouse.ox.ac.uk/scholarships/applications/',
            'closing_date' => '2026-08-03',
        ]);

        $russia = DB::table('bursaries')
            ->where('source_url', 'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/329-russia-mba-programme-in-subsoil-mineral-resources-management-2026')
            ->first();

        $this->assertNotNull($russia);
        $this->assertSame('https://docs.google.com/forms/d/e/1FAIpQLSct7hCmKFPNxx0d4QfFg367Za6n7M-tF1F-Xi_eJhtljxnC1w/viewform?usp=send_form', $russia->apply_url);
        $this->assertNull($russia->application_email);
        $this->assertStringContainsString('RussiaApplications@dhet.gov.za', $russia->application_method);

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.internationalscholarships.dhet.gov.za/index.php/scholarships/undergraduate-scholarships/332-ireland-kader-asmal-fellowship-programme-2027',
            'apply_url' => 'https://www.gapgrants.com/bslgap/DseatFFX.jsp?formset=canon&formid=18',
            'closing_date' => '2026-07-27',
        ]);

        $this->assertSame(
            0,
            DB::table('bursaries')
                ->whereIn('source_url', $sourceUrls)
                ->whereColumn('apply_url', 'source_url')
                ->count(),
        );
    }

    public function test_health_medical_sources_are_seeded_with_audited_application_methods(): void
    {
        $this->seed(BursarySeeder::class);

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.zabursaries.co.za/medical-bursaries-south-africa/adsa-bursary/',
            'title' => 'ADSA Bursary',
            'category' => 'Medical',
            'application_delivery_type' => 'external_link',
            'apply_url' => 'https://adsa.org.za/bursary-application/',
            'chamu_apply_enabled' => false,
        ]);

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.zabursaries.co.za/general-bursaries-south-africa/idb-education-trust-bursary-loan/',
            'title' => 'IDB Education Trust Bursary Loan',
            'category' => 'Medical',
            'application_delivery_type' => 'external_link',
            'apply_url' => 'https://idb.org.za/index.php/applications',
            'chamu_apply_enabled' => false,
        ]);

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/medical-bursaries-south-africa/denosa-bursary/',
            'leahr@denosa.org.za',
        );

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/medical-bursaries-south-africa/life-healthcare-bursary/',
            'LHCBursary@lifehealthcare.co.za',
        );

        $this->assertPostalBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/government-bursaries-south-africa/gauteng-department-of-health-bursary/',
        );
    }

    public function test_up_funding_sources_are_seeded_with_chamu_email_and_postal_handling(): void
    {
        $this->seed(BursarySeeder::class);

        $sourceUrls = [
            'https://www.up.ac.za/funding-site/funding/evander-mines-bursary-opportunity-2026',
            'https://www.up.ac.za/funding-site/funding/sarao-freestanding-scholarship-2027',
            'https://www.up.ac.za/funding-site/funding/actom-bursary-opportunity-2027',
            'https://www.up.ac.za/funding-site/funding/rhodes-scholarships-southern-africa',
            'https://www.up.ac.za/funding-site/funding/sarao-freestanding-bsc-and-beng-undergraduate-scholarships-2027',
            'https://www.up.ac.za/funding-site/funding/sarao-freestanding-honours-scholarships-2027',
            'https://www.up.ac.za/funding-site/funding/nedbank-external-bursary-programme',
            'https://www.up.ac.za/funding-site/funding/2027-margaret-mcnamara-education-grants',
            'https://www.up.ac.za/funding-site/funding/2027-protein-research-foundation-postgraduate-bursary',
            'https://www.up.ac.za/funding-site/funding/2027-oil-and-protein-seeds-development-trust-postgraduate-bursary',
            'https://www.up.ac.za/funding-site/funding/mathsup-masters-programme-mathematics-and-applied-mathematics-2027-academic-year',
            'https://www.up.ac.za/funding-site/funding/masters-and-doctoral-bursary-conditions',
            'https://www.up.ac.za/funding-site/funding/honour-merit-bursary-conditions',
            'https://www.up.ac.za/funding-site/funding/2027-amazon-recruitment-bursary',
            'https://www.up.ac.za/funding-site/funding/south-african-reserve-bank-external-bursary-scheme-economics-students-2027',
            'https://www.up.ac.za/funding-site/funding/tomorrow-trust-bursary-south-africa-2027',
            'https://www.up.ac.za/funding-site/funding/2027-south-african-reserve-bank-master-bursary',
            'https://www.up.ac.za/funding-site/funding/2027-postgraduate-nrf-scholarships',
            'https://www.up.ac.za/funding-site/funding/compensation-fund-bursary-scheme-2026-academic-year',
            'https://www.up.ac.za/funding-site/funding/2027-new-application-doctoral-research-bursary',
            'https://www.up.ac.za/funding-site/funding/beefmaster-group-bursary-opportunity',
            'https://www.up.ac.za/funding-site/funding/moshal-program',
            'https://www.up.ac.za/funding-site/funding/isaac-joffe-fellowship-bursary-scheme-2026',
            'https://www.up.ac.za/funding-site/funding/2026-dept-of-sport-arts-and-culture-scholarship',
            'https://www.up.ac.za/funding-site/funding/kn-inzuzo-trust-2026',
            'https://www.up.ac.za/funding-site/funding/erwin-robert-balde-bursary-scheme-cape-gate-pty-ltd',
            'https://www.up.ac.za/funding-site/funding/2026-hci-foundation-funding-hons-and-pgd',
            'https://www.up.ac.za/funding-site/funding/2026-ruth-first-education-trust-funding-master-level',
            'https://www.up.ac.za/funding-site/funding/coca-cola-beverages-south-africa-ccbsa',
            'https://www.up.ac.za/funding-site/funding/southernsphere-platinum-bursary-programme',
            'https://www.up.ac.za/funding-site/funding/fulbright-foreign-scholarship-2027-2028',
            'https://www.up.ac.za/funding-site/funding/daad-country-msc-mathematics-scholarship-2027',
            'https://www.up.ac.za/funding-site/funding/metropolitan-health-corporate-bursary-opportunity-2026',
        ];

        $this->assertCount(33, $sourceUrls);

        foreach ($sourceUrls as $sourceUrl) {
            $this->assertSame(
                1,
                DB::table('bursaries')->where('source_url', $sourceUrl)->count(),
                $sourceUrl,
            );
        }

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.up.ac.za/funding-site/funding/evander-mines-bursary-opportunity-2026',
            'sibulelo.madlolo@emines.co.za',
        );

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.up.ac.za/funding-site/funding/actom-bursary-opportunity-2027',
            'epc.hc@actom.co.za',
        );

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.up.ac.za/funding-site/funding/kn-inzuzo-trust-2026',
            'info.kninzuzotrust@kuehne-nagel.com',
        );

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.up.ac.za/funding-site/funding/southernsphere-platinum-bursary-programme',
            'bursaries@southernsphere.co.za',
        );

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.up.ac.za/funding-site/funding/metropolitan-health-corporate-bursary-opportunity-2026',
            'HealthBursaries@momentum.co.za',
        );

        $this->assertPostalBursaryIsChamuManaged(
            'https://www.up.ac.za/funding-site/funding/south-african-reserve-bank-external-bursary-scheme-economics-students-2027',
        );

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.up.ac.za/funding-site/funding/2027-amazon-recruitment-bursary',
            'application_delivery_type' => 'external_link',
            'apply_url' => 'https://studytrust.org.za/amazon-recruitment-bursaries/',
            'chamu_apply_enabled' => false,
        ]);

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.up.ac.za/funding-site/funding/rhodes-scholarships-southern-africa',
            'application_delivery_type' => 'external_link',
            'apply_url' => 'https://www.rhodeshouse.ox.ac.uk/scholarships/applications/southern-africa/',
            'chamu_apply_enabled' => false,
        ]);

        $this->assertSame(
            0,
            DB::table('bursaries')
                ->whereIn('source_url', $sourceUrls)
                ->whereColumn('apply_url', 'source_url')
                ->count(),
        );
    }

    public function test_audited_application_methods_are_seeded_correctly(): void
    {
        $this->seed(BursarySeeder::class);

        $this->assertEmailBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/engineering-bursaries-south-africa/hulamin-bursary/',
            'training@hulamin.co.za',
        );

        $this->assertPostalBursaryIsChamuManaged(
            'https://www.zabursaries.co.za/general-bursaries-south-africa/south-african-road-federation-bursary/',
        );

        $this->assertDatabaseHas('bursaries', [
            'source_url' => 'https://www.zabursaries.co.za/science-bursaries-south-africa/xylem-sa-trust-bursary/',
            'application_delivery_type' => 'external_link',
            'apply_url' => 'https://forms.gle/T5kVzBzkCYhXQbJy8',
            'chamu_apply_enabled' => false,
        ]);
    }

    public function test_apply_links_do_not_point_back_to_source_pages(): void
    {
        $this->seed(BursarySeeder::class);

        $this->assertSame(
            0,
            DB::table('bursaries')
                ->whereNotNull('apply_url')
                ->whereColumn('apply_url', 'source_url')
                ->count(),
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

    private function assertPostalBursaryIsChamuManaged(string $sourceUrl): void
    {
        $bursary = DB::table('bursaries')->where('source_url', $sourceUrl)->first();

        $this->assertNotNull($bursary);
        $this->assertSame('postal', $bursary->application_delivery_type);
        $this->assertNotEmpty($bursary->application_postal_address);
        $this->assertTrue((bool) $bursary->chamu_apply_enabled);
        $this->assertSame(10, DB::table('bursary_document_requirements')->where('bursary_id', $bursary->id)->count());
    }

    private function assertExternalBursaryIsNotChamuManaged(string $sourceUrl): void
    {
        $bursary = DB::table('bursaries')->where('source_url', $sourceUrl)->first();

        $this->assertNotNull($bursary);
        $this->assertSame('external_link', $bursary->application_delivery_type);
        $this->assertFalse((bool) $bursary->chamu_apply_enabled);
    }
}
