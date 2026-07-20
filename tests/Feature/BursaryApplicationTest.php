<?php

namespace Tests\Feature;

use App\Mail\BursaryApplicationReceipt;
use App\Mail\BursaryApplicationSubmitted;
use App\Models\Bursary;
use App\Models\BursaryDocumentRequirement;
use App\Models\User;
use App\Models\UserApplicationDocument;
use Database\Seeders\BursarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
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

    public function test_application_profile_requires_id_cv_and_one_academic_document(): void
    {
        Storage::fake('local');

        $records = $this->createSignupLookups();
        $user = User::factory()->create([
            'user_type_id' => $records['user_type_id'],
            'country_id' => $records['country_id'],
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'name' => 'Validation Student',
            'first_name' => 'Validation',
            'last_name' => 'Student',
            'username' => 'validationstudent',
            'email' => 'validation-student@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('profile.application'))
            ->put(route('profile.application.update'), [
                'applicant_phone' => '071 000 0000',
                'study_level' => 'Student (University/College)',
                'institution' => 'Test University',
                'qualification' => 'BSc Computer Science',
            ]);

        $response->assertRedirect(route('profile.application'));
        $response->assertSessionHasErrors([
            'documents.id_document',
            'documents.curriculum_vitae',
            'documents.academic_record',
        ]);
    }

    public function test_application_profile_ignores_saved_document_rows_when_files_are_missing(): void
    {
        Storage::fake('local');

        $records = $this->createSignupLookups();
        $user = User::factory()->create([
            'user_type_id' => $records['user_type_id'],
            'country_id' => $records['country_id'],
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'name' => 'Missing File Student',
            'first_name' => 'Missing',
            'last_name' => 'Student',
            'username' => 'missingfilestudent',
            'email' => 'missing-file-student@example.com',
        ]);

        foreach ([
            'id_document' => ['Certified copy of ID document', 'missing-id.pdf'],
            'curriculum_vitae' => ['Curriculum Vitae', 'missing-cv.pdf'],
            'academic_transcript' => ['Full academic record or transcript', 'missing-transcript.pdf'],
        ] as $key => [$label, $originalName]) {
            UserApplicationDocument::create([
                'user_id' => $user->id,
                'document_key' => $key,
                'label' => $label,
                'original_name' => $originalName,
                'storage_disk' => 'local',
                'path' => 'application-profiles/'.$user->id.'/'.$originalName,
                'mime_type' => 'application/pdf',
                'size' => 24000,
            ]);
        }

        $profile = $this->actingAs($user)->get(route('profile.application'));

        $profile->assertOk();
        $profile->assertSee('0/3');
        $profile->assertDontSee('missing-id.pdf');
        $profile->assertDontSee('missing-cv.pdf');
        $profile->assertDontSee('missing-transcript.pdf');

        $response = $this
            ->actingAs($user)
            ->from(route('profile.application'))
            ->put(route('profile.application.update'), [
                'applicant_phone' => '071 000 0000',
                'study_level' => 'Student (University/College)',
                'institution' => 'Test University',
                'qualification' => 'BSc Computer Science',
            ]);

        $response->assertRedirect(route('profile.application'));
        $response->assertSessionHasErrors([
            'documents.id_document',
            'documents.curriculum_vitae',
            'documents.academic_record',
        ]);
    }

    public function test_user_can_store_application_profile_details_and_documents(): void
    {
        Storage::fake('local');

        $records = $this->createSignupLookups();
        $user = User::factory()->create([
            'user_type_id' => $records['user_type_id'],
            'country_id' => $records['country_id'],
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'name' => 'Ready Student',
            'first_name' => 'Ready',
            'last_name' => 'Student',
            'username' => 'readystudent',
            'email' => 'ready-student@example.com',
        ]);

        $response = $this->actingAs($user)->put(route('profile.application.update'), [
            'applicant_phone' => '071 000 0000',
            'study_level' => 'Student (University/College)',
            'institution' => 'Test University',
            'qualification' => 'BSc Computer Science',
            'current_year' => 'Second year',
            'funding_need' => 'I need funding for fees and books.',
            'household_income' => 'Less than R350 000 per year',
            'special_circumstances' => ['disability'],
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
        ]);

        $response->assertRedirect(route('profile.application'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_application_profiles', [
            'user_id' => $user->id,
            'applicant_phone' => '071 000 0000',
            'study_level' => 'Student (University/College)',
            'institution' => 'Test University',
            'qualification' => 'BSc Computer Science',
        ]);

        $documents = UserApplicationDocument::query()
            ->where('user_id', $user->id)
            ->get();

        $this->assertCount(3, $documents);

        foreach ($documents as $document) {
            Storage::disk($document->storage_disk)->assertExists($document->path);
        }

        $profile = $this->actingAs($user)->get(route('profile.application'));

        $profile->assertOk();
        $profile->assertSee('3/3');
        $profile->assertSee('certified-id.pdf');
        $profile->assertSee('academic-transcript.pdf');
    }

    public function test_chamu_application_can_use_saved_profile_documents_as_attachments(): void
    {
        Mail::fake();
        Storage::fake('local');

        $records = $this->createSignupLookups();
        $user = User::factory()->create([
            'user_type_id' => $records['user_type_id'],
            'country_id' => $records['country_id'],
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'name' => 'Saved Docs Student',
            'first_name' => 'Saved',
            'last_name' => 'Student',
            'username' => 'saveddocsstudent',
            'email' => 'saved-docs-student@example.com',
        ]);
        $bursary = $this->createEmailBursary([
            'title' => 'Saved Documents Bursary Test',
            'slug' => 'saved-documents-bursary-test',
        ]);

        $this->actingAs($user)->put(route('profile.application.update'), [
            'applicant_phone' => '072 111 2222',
            'study_level' => 'Student (University/College)',
            'institution' => 'Saved Profile University',
            'qualification' => 'BSc Information Systems',
            'current_year' => 'Third year',
            'funding_need' => 'Saved profile funding need.',
            'household_income' => 'Less than R350 000 per year',
            'documents' => [
                'id_document' => [
                    UploadedFile::fake()->create('saved-certified-id.pdf', 24, 'application/pdf'),
                ],
                'curriculum_vitae' => [
                    UploadedFile::fake()->create('saved-cv.pdf', 24, 'application/pdf'),
                ],
                'academic_transcript' => [
                    UploadedFile::fake()->create('saved-transcript.pdf', 24, 'application/pdf'),
                ],
                'covering_letter' => [
                    UploadedFile::fake()->create('saved-motivation-letter.pdf', 24, 'application/pdf'),
                ],
            ],
        ])->assertSessionHasNoErrors();

        $documentsByKey = UserApplicationDocument::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('document_key');

        $show = $this->actingAs($user)->get(route('bursaries.show', $bursary));

        $show->assertOk();
        $show->assertSee('Other saved documents');
        $show->assertSee('saved-motivation-letter.pdf');

        $response = $this->actingAs($user)->post(route('bursaries.apply', $bursary), [
            'saved_documents' => [
                'id_document' => [$documentsByKey->get('id_document')->id],
                'curriculum_vitae' => [$documentsByKey->get('curriculum_vitae')->id],
                'academic_transcript' => [$documentsByKey->get('academic_transcript')->id],
                'covering_letter' => [$documentsByKey->get('covering_letter')->id],
            ],
            'consent' => '1',
        ]);

        $response->assertRedirect(route('bursaries.show', $bursary));
        $response->assertSessionHasNoErrors();

        $application = DB::table('bursary_applications')->first();

        $this->assertNotNull($application);
        $this->assertSame('submitted', $application->status);
        $this->assertSame('072 111 2222', $application->applicant_phone);
        $this->assertSame('Saved Profile University', $application->institution);
        $this->assertSame('BSc Information Systems', $application->qualification);

        $attachedDocuments = DB::table('bursary_application_documents')
            ->where('bursary_application_id', $application->id)
            ->get();

        $this->assertCount(4, $attachedDocuments);
        $this->assertEqualsCanonicalizing(
            ['saved-certified-id.pdf', 'saved-cv.pdf', 'saved-motivation-letter.pdf', 'saved-transcript.pdf'],
            $attachedDocuments->pluck('original_name')->all()
        );

        foreach ($attachedDocuments as $document) {
            Storage::disk($document->storage_disk)->assertExists($document->path);
        }

        Mail::assertSent(BursaryApplicationSubmitted::class, function (BursaryApplicationSubmitted $mail): bool {
            return $mail->hasTo('yondlers@gmail.com')
                && count($mail->attachments()) === 4
                && $mail->application->applicant_email === 'saved-docs-student@example.com';
        });
    }

    public function test_chamu_application_can_replace_missing_saved_profile_documents_with_uploads(): void
    {
        Mail::fake();
        Storage::fake('local');

        $records = $this->createSignupLookups();
        $user = User::factory()->create([
            'user_type_id' => $records['user_type_id'],
            'country_id' => $records['country_id'],
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'name' => 'Replacement Student',
            'first_name' => 'Replacement',
            'last_name' => 'Student',
            'username' => 'replacementstudent',
            'email' => 'replacement-student@example.com',
        ]);
        $bursary = $this->createEmailBursary([
            'title' => 'Replacement Documents Bursary Test',
            'slug' => 'replacement-documents-bursary-test',
        ]);

        $missingDocuments = collect([
            'id_document' => ['Certified copy of ID document', 'missing-id.pdf'],
            'curriculum_vitae' => ['Curriculum Vitae', 'missing-cv.pdf'],
            'academic_transcript' => ['Full academic record or transcript', 'missing-transcript.pdf'],
        ])->mapWithKeys(function (array $details, string $key) use ($user): array {
            [$label, $originalName] = $details;

            return [$key => UserApplicationDocument::create([
                'user_id' => $user->id,
                'document_key' => $key,
                'label' => $label,
                'original_name' => $originalName,
                'storage_disk' => 'local',
                'path' => 'application-profiles/'.$user->id.'/'.$originalName,
                'mime_type' => 'application/pdf',
                'size' => 24000,
            ])];
        });

        $show = $this->actingAs($user)->get(route('bursaries.show', $bursary));

        $show->assertOk();
        $show->assertDontSee('missing-id.pdf');
        $show->assertDontSee('missing-cv.pdf');
        $show->assertDontSee('missing-transcript.pdf');

        $response = $this->actingAs($user)->post(route('bursaries.apply', $bursary), [
            'saved_documents' => [
                'id_document' => [$missingDocuments->get('id_document')->id],
                'curriculum_vitae' => [$missingDocuments->get('curriculum_vitae')->id],
                'academic_transcript' => [$missingDocuments->get('academic_transcript')->id],
            ],
            'documents' => [
                'id_document' => [
                    UploadedFile::fake()->create('replacement-id.pdf', 24, 'application/pdf'),
                ],
                'curriculum_vitae' => [
                    UploadedFile::fake()->create('replacement-cv.pdf', 24, 'application/pdf'),
                ],
                'academic_transcript' => [
                    UploadedFile::fake()->create('replacement-transcript.pdf', 24, 'application/pdf'),
                ],
            ],
            'consent' => '1',
        ]);

        $response->assertRedirect(route('bursaries.show', $bursary));
        $response->assertSessionHasNoErrors();

        $application = DB::table('bursary_applications')->first();
        $this->assertNotNull($application);

        $attachedDocuments = DB::table('bursary_application_documents')
            ->where('bursary_application_id', $application->id)
            ->get();

        $this->assertCount(3, $attachedDocuments);
        $this->assertEqualsCanonicalizing(
            ['replacement-cv.pdf', 'replacement-id.pdf', 'replacement-transcript.pdf'],
            $attachedDocuments->pluck('original_name')->all()
        );

        foreach ($attachedDocuments as $document) {
            Storage::disk($document->storage_disk)->assertExists($document->path);
        }

        Mail::assertSent(BursaryApplicationSubmitted::class, function (BursaryApplicationSubmitted $mail): bool {
            return $mail->hasTo('yondlers@gmail.com')
                && count($mail->attachments()) === 3
                && $mail->application->applicant_email === 'replacement-student@example.com';
        });
    }

    public function test_user_sees_chamu_bursary_application_history_after_submission(): void
    {
        Mail::fake();
        Storage::fake('local');

        $records = $this->createSignupLookups();
        $user = User::factory()->create([
            'user_type_id' => $records['user_type_id'],
            'country_id' => $records['country_id'],
            'curriculum_id' => $records['curriculum_id'],
            'grade_id' => $records['grade_id'],
            'name' => 'History Student',
            'first_name' => 'History',
            'last_name' => 'Student',
            'username' => 'historystudent',
            'email' => 'history-student@example.com',
        ]);
        $bursary = $this->createEmailBursary([
            'title' => 'Visible History Bursary Test',
            'slug' => 'visible-history-bursary-test',
            'source_url' => 'https://example.com/visible-history-bursary-test',
        ]);

        $response = $this->actingAs($user)->post(route('bursaries.apply', $bursary), [
            'applicant_phone' => '071 000 0000',
            'study_level' => 'Student (University/College)',
            'institution' => 'Test University',
            'qualification' => 'BCom Accounting',
            'current_year' => 'Second year',
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

        $dashboard = $this->actingAs($user)->get(route('dashboard.index'));

        $dashboard->assertOk();
        $dashboard->assertSee('Bursary applications');
        $dashboard->assertSee('Visible History Bursary Test');
        $dashboard->assertSee('3 documents');
        $dashboard->assertSee('receipt emailed');
        $dashboard->assertSee(route('applications.index'), false);

        $history = $this->actingAs($user)->get(route('applications.index'));

        $history->assertOk();
        $history->assertSee('My applications');
        $history->assertSee('Visible History Bursary Test');
        $history->assertSee('Sent');
        $history->assertSee('3 documents');
        $history->assertSee('Receipt emailed');
        $history->assertSee('yondlers@gmail.com');
        $history->assertSee(route('bursaries.show', $bursary), false);
    }

    public function test_chamu_prepares_postal_bursary_application_with_applicant_address(): void
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
        $bursary = $this->createEmailBursary([
            'title' => 'Alfred Nzo District Municipality Bursary Test',
            'slug' => 'alfred-nzo-district-municipality-bursary-test',
            'application_method' => 'Apply by post.',
            'application_delivery_type' => 'postal',
            'application_email' => null,
            'application_postal_address' => "The Municipal Manager\nPrivate Bag X511\nMount Ayliff\n4735",
            'contact_email' => null,
            'apply_url' => null,
            'source_url' => 'https://example.com/alfred-nzo-postal-instructions',
        ]);

        $show = $this->actingAs($user)->get(route('bursaries.show', $bursary));

        $show->assertOk();
        $show->assertSee('Apply with Postal');
        $show->assertSee('Postal submission required');
        $show->assertSee('This bursary does not get emailed by Chamu');
        $show->assertSee('Private Bag X511');
        $show->assertSee('https://example.com/alfred-nzo-postal-instructions', false);
        $show->assertDontSee('Apply with Chamu');

        $response = $this->actingAs($user)->post(route('bursaries.apply', $bursary), [
            'applicant_phone' => '071 000 0000',
            'applicant_postal_address' => '123 Chamu Street, Johannesburg, 2001',
            'study_level' => 'Student (University/College)',
            'institution' => 'Test University',
            'qualification' => 'BCom Accounting',
            'current_year' => 'Second year',
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
        $this->assertSame('postal_ready', $application->status);
        $this->assertSame('postal', $application->delivery_type);
        $this->assertSame('', $application->provider_email);
        $this->assertSame("The Municipal Manager\nPrivate Bag X511\nMount Ayliff\n4735", $application->provider_postal_address);
        $this->assertSame('123 Chamu Street, Johannesburg, 2001', $application->applicant_postal_address);
        $this->assertNotNull($application->receipt_sent_at);

        Mail::assertNotSent(BursaryApplicationSubmitted::class);
        Mail::assertSent(BursaryApplicationReceipt::class, function (BursaryApplicationReceipt $mail): bool {
            return $mail->hasTo('kekanagomolemo@gmail.com')
                && $mail->application->delivery_type === 'postal';
        });

        $confirmation = $this->actingAs($user)->get(route('bursaries.show', $bursary));

        $confirmation->assertOk();
        $confirmation->assertSee('Your postal application is ready');
        $confirmation->assertSee('Print postal pack');
        $confirmation->assertSee(route('applications.postal-pack', $application->id), false);
        $confirmation->assertSee('Source instructions');

        $pack = $this->actingAs($user)->get(route('applications.postal-pack', $application->id));

        $pack->assertOk();
        $pack->assertSee('Print postal pack');
        $pack->assertSee('Chamu has not emailed this bursary provider');
        $pack->assertSee('Private Bag X511');
        $pack->assertSee('123 Chamu Street, Johannesburg, 2001');
        $pack->assertSee('certified-id.pdf');
        $pack->assertSee('academic-transcript.pdf');
        $pack->assertSee('https://example.com/alfred-nzo-postal-instructions', false);

        $history = $this->actingAs($user)->get(route('applications.index'));

        $history->assertOk();
        $history->assertSee('Postal ready');
        $history->assertSee('Print postal pack');
        $history->assertSee(route('applications.postal-pack', $application->id), false);
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
        $response->assertSee('data-consent-input', false);
        $response->assertSee('name="consent" value="1" required disabled', false);
        $response->assertSee('data-lucide="receipt"', false);
        $response->assertDontSee('receipt-text');
        $response->assertDontSee('How to apply');
        $response->assertDontSee('Apply by email.');
        $response->assertDontSee('Source');
    }

    public function test_seeded_clockwork_email_bursary_shows_apply_with_chamu_not_apply_link(): void
    {
        $this->seed(BursarySeeder::class);

        $bursary = Bursary::where('source_url', 'https://www.zabursaries.co.za/commerce-bursaries-south-africa/clockwork-empowerment-fund-bursary/')->firstOrFail();

        $response = $this->get(route('bursaries.show', $bursary));

        $response->assertOk();
        $response->assertSee('Apply with Chamu');
        $response->assertSee('Chamu-managed email submission');
        $response->assertDontSee('Apply Link');
        $response->assertDontSee('Source');
    }

    public function test_seeded_women_in_it_email_bursary_shows_apply_with_chamu_not_apply_link(): void
    {
        $this->seed(BursarySeeder::class);

        $bursary = Bursary::where('source_url', 'https://www.zabursaries.co.za/computer-science-it-bursaries-south-africa/women-in-it-bursary/')->firstOrFail();

        $response = $this->get(route('bursaries.show', $bursary));

        $response->assertOk();
        $response->assertSee('Apply with Chamu');
        $response->assertSee('Chamu-managed email submission');
        $response->assertDontSee('Apply Link');
        $response->assertDontSee('Source');
    }

    public function test_bursary_list_requires_details_before_application_actions(): void
    {
        $chamuBursary = $this->createEmailBursary();
        $externalBursary = Bursary::create([
            'company_id' => $chamuBursary->company_id,
            'title' => 'External Provider Bursary Test',
            'slug' => 'external-provider-bursary-test',
            'category' => 'Engineering',
            'summary' => 'External application test bursary.',
            'application_delivery_type' => 'external_link',
            'chamu_apply_enabled' => false,
            'source_url' => 'https://provider.example/source',
            'apply_url' => 'https://provider.example/apply-now',
            'is_active' => true,
        ]);

        $response = $this->get(route('bursaries.index'));

        $response->assertOk();
        $response->assertSee('Details');
        $response->assertSee(route('bursaries.show', $chamuBursary), false);
        $response->assertSee(route('bursaries.show', $externalBursary), false);
        $response->assertDontSee('Apply with Chamu');
        $response->assertDontSee('Apply Link');
        $response->assertDontSee('https://provider.example/apply-now', false);
    }

    public function test_bursary_list_orders_filtered_results_by_newest_open_close_date_first(): void
    {
        Carbon::setTestNow('2026-07-18 10:00:00');

        try {
            $now = now();
            $companyId = DB::table('companies')->insertGetId([
                'name' => 'Filtered Funding Provider',
                'slug' => 'filtered-funding-provider',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ([
                ['Newest Open Searchable Bursary', 'newest-open-searchable-bursary', '2027-09-30'],
                ['Soon Open Searchable Bursary', 'soon-open-searchable-bursary', '2026-08-15'],
                ['Annual Searchable Bursary', 'annual-searchable-bursary', null],
                ['Recent Closed Searchable Bursary', 'recent-closed-searchable-bursary', '2026-01-20'],
                ['Ancient Closed Searchable Bursary', 'ancient-closed-searchable-bursary', '2017-10-30'],
            ] as [$title, $slug, $closingDate]) {
                Bursary::create([
                    'company_id' => $companyId,
                    'title' => $title,
                    'slug' => $slug,
                    'category' => 'Engineering',
                    'summary' => 'Searchable test bursary.',
                    'application_delivery_type' => 'external_link',
                    'chamu_apply_enabled' => false,
                    'source_url' => 'https://provider.example/'.$slug,
                    'apply_url' => 'https://provider.example/apply/'.$slug,
                    'closing_date' => $closingDate,
                    'closing_date_label' => $closingDate ?? 'Annual',
                    'is_active' => true,
                ]);
            }

            $response = $this->get(route('bursaries.index', [
                'search' => 'Searchable',
                'category' => 'Engineering',
                'company_id' => $companyId,
            ]));

            $response->assertOk();
            $response->assertSeeInOrder([
                'Newest Open Searchable Bursary',
                'Soon Open Searchable Bursary',
                'Annual Searchable Bursary',
                'Recent Closed Searchable Bursary',
                'Ancient Closed Searchable Bursary',
            ]);
        } finally {
            Carbon::setTestNow();
        }
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
        $response->assertDontSee('Apply Link');
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
