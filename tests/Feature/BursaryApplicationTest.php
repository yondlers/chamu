<?php

namespace Tests\Feature;

use App\Mail\BursaryApplicationReceipt;
use App\Mail\BursaryApplicationSubmitted;
use App\Models\Bursary;
use App\Models\BursaryDocumentRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BursaryApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_chamu_sends_bursary_application_email_with_reply_to_and_receipt(): void
    {
        Mail::fake();
        Storage::fake('local');

        $records = $this->createSignupLookups();
        $user = User::factory()->create([
            'user_type_id' => $records['user_type_id'],
            'country_id' => $records['country_id'],
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'name' => 'Kekana Gomolemo',
            'first_name' => 'Kekana',
            'last_name' => 'Gomolemo',
            'username' => 'kekanagomolemo',
            'email' => 'kekanagomolemo@gmail.com',
        ]);
        $bursary = $this->createEmailBursary();

        $response = $this->actingAs($user)->post(route('bursaries.apply', $bursary), [
            'applicant_phone' => '071 000 0000',
            'study_level' => 'Student (University/College)',
            'institution' => 'Test University',
            'qualification' => 'BCom Accounting',
            'current_year' => 'Second year',
            'funding_need' => 'Test application for bursary email flow.',
            'household_income' => 'Less than R350 000 per year',
            'sassa_recipient' => '1',
            'documents' => [
                'id_document' => [
                    UploadedFile::fake()->create('certified-id.pdf', 24, 'application/pdf'),
                ],
                'curriculum_vitae' => [
                    UploadedFile::fake()->create('cv.pdf', 24, 'application/pdf'),
                ],
                'academic_transcript' => [
                    UploadedFile::fake()->create('academic-transcript.pdf', 24, 'application/pdf'),
                ],
            ],
            'consent' => '1',
        ]);

        $response->assertRedirect(route('bursaries.show', $bursary));
        $response->assertSessionHasNoErrors();

        $application = DB::table('bursary_applications')->first();

        $this->assertNotNull($application);
        $this->assertSame('submitted', $application->status);
        $this->assertSame('yondlers@gmail.com', $application->provider_email);
        $this->assertSame('kekanagomolemo@gmail.com', $application->applicant_email);
        $this->assertNotNull($application->submitted_at);
        $this->assertNotNull($application->receipt_sent_at);

        $documents = DB::table('bursary_application_documents')
            ->where('bursary_application_id', $application->id)
            ->get();

        $this->assertCount(3, $documents);

        foreach ($documents as $document) {
            Storage::disk($document->storage_disk)->assertExists($document->path);
        }

        Mail::assertSent(BursaryApplicationSubmitted::class, function (BursaryApplicationSubmitted $mail): bool {
            return $mail->hasTo('yondlers@gmail.com')
                && $mail->hasReplyTo('kekanagomolemo@gmail.com', 'Kekana Gomolemo')
                && count($mail->attachments()) === 3
                && $mail->application->applicant_email === 'kekanagomolemo@gmail.com';
        });

        Mail::assertSent(BursaryApplicationReceipt::class, function (BursaryApplicationReceipt $mail): bool {
            return $mail->hasTo('kekanagomolemo@gmail.com')
                && $mail->application->provider_email === 'yondlers@gmail.com';
        });
    }

    public function test_bursary_detail_page_shows_chamu_application_modal(): void
    {
        $records = $this->createSignupLookups();
        $user = User::factory()->create([
            'user_type_id' => $records['user_type_id'],
            'country_id' => $records['country_id'],
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'name' => 'Kekana Gomolemo',
            'first_name' => 'Kekana',
            'last_name' => 'Gomolemo',
            'username' => 'kekanagomolemo',
            'email' => 'kekanagomolemo@gmail.com',
        ]);
        $bursary = $this->createEmailBursary();

        $response = $this->actingAs($user)->get(route('bursaries.show', $bursary));

        $response->assertOk();
        $response->assertSee('Apply with Chamu');
        $response->assertSee('Certified copy of ID document');
        $response->assertSee('Review application');
        $response->assertSee('Confirm and send');
        $response->assertSee('Chamu-managed submission');
        $response->assertDontSee('How to apply');
        $response->assertDontSee('Apply by email.');
        $response->assertDontSee('Source');
    }

    public function test_email_submission_bursary_is_handled_by_chamu_even_without_seeded_flags(): void
    {
        $records = $this->createSignupLookups();
        $user = User::factory()->create([
            'user_type_id' => $records['user_type_id'],
            'country_id' => $records['country_id'],
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'name' => 'Kekana Gomolemo',
            'first_name' => 'Kekana',
            'last_name' => 'Gomolemo',
            'username' => 'kekanagomolemo',
            'email' => 'kekanagomolemo@gmail.com',
        ]);
        $bursary = $this->createEmailBursary([
            'application_delivery_type' => 'external_link',
            'application_email' => null,
            'chamu_apply_enabled' => false,
            'apply_url' => 'mailto:yondlers@gmail.com',
        ], false);

        $response = $this->actingAs($user)->get(route('bursaries.show', $bursary));

        $response->assertOk();
        $response->assertSee('Apply with Chamu');
        $response->assertSee('Certified copy of ID document');
        $response->assertDontSee('Apply link');
        $response->assertDontSee('Source');
    }

    /**
     * @return array<string, int>
     */
    private function createSignupLookups(): array
    {
        $now = now();

        $userTypeId = DB::table('user_types')->insertGetId([
            'name' => 'pupil',
            'description' => 'Learner',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $countryId = DB::table('countries')->insertGetId([
            'name' => 'South Africa',
            'nationality' => 'South African',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $curriculumId = DB::table('curriculums')->insertGetId([
            'country_id' => $countryId,
            'name' => 'NSC (National Senior Certificate)',
            'abbreviation' => 'CAPS',
            'is_live' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $gradeId = DB::table('grades')->insertGetId([
            'curriculum_id' => $curriculumId,
            'name' => 'Grade 12',
            'sort_order' => 12,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'user_type_id' => $userTypeId,
            'country_id' => $countryId,
            'curriculum_id' => $curriculumId,
            'grade_id' => $gradeId,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createEmailBursary(array $overrides = [], bool $withStructuredDocuments = true): Bursary
    {
        $now = now();

        $companyId = DB::table('companies')->insertGetId([
            'name' => 'A2A Kopano Incorporated Test',
            'slug' => 'a2a-kopano-incorporated-test',
            'contact_email' => 'yondlers@gmail.com',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $bursary = Bursary::create(array_merge([
            'company_id' => $companyId,
            'title' => 'A2A Kopano Inc. Bursary Test',
            'slug' => 'a2a-kopano-inc-bursary-test',
            'category' => 'Accounting',
            'summary' => 'Test bursary for email submission.',
            'fields_covered' => 'Chartered Accountancy field.',
            'coverage_value' => 'Test coverage.',
            'eligibility_requirements' => ['South African citizen'],
            'application_method' => 'Apply by email.',
            'application_delivery_type' => 'email',
            'application_email' => 'yondlers@gmail.com',
            'chamu_apply_enabled' => true,
            'supporting_documents' => [
                'Certified copy of ID document',
                'Curriculum Vitae',
                'Academic transcript',
            ],
            'closing_date_label' => '30 September annually',
            'contact_email' => 'yondlers@gmail.com',
            'source_url' => 'https://example.com/a2a-kopano-inc-bursary-test',
            'is_active' => true,
        ], $overrides));

        if (! $withStructuredDocuments) {
            return $bursary;
        }

        foreach ([
            ['id_document', 'Certified copy of ID document', true, null, 10],
            ['curriculum_vitae', 'Curriculum Vitae', true, null, 20],
            ['academic_transcript', 'Full academic record or transcript', false, 'academic_record', 30],
        ] as [$key, $label, $required, $group, $sort]) {
            BursaryDocumentRequirement::create([
                'bursary_id' => $bursary->id,
                'key' => $key,
                'label' => $label,
                'is_required' => $required,
                'requirement_group' => $group,
                'sort_order' => $sort,
            ]);
        }

        return $bursary;
    }
}
