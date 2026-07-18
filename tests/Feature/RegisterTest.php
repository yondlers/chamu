<?php

namespace Tests\Feature;

use App\Mail\WelcomeToChamu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_offers_pupil_and_student_accounts(): void
    {
        $this->createSignupLookups();
        DB::table('user_types')->insert([
            [
                'name' => 'teacher',
                'description' => 'Teacher',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'parent',
                'description' => 'Parent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('data-auth-campus-carousel', false);
        $response->assertSee('images/auth-campus/rhodes-uni.jpg', false);
        $response->assertSee('images/auth-campus/wits-great-hall.png', false);
        $response->assertSee('Pupil (High School)');
        $response->assertSee('Student (University/College)');
        $response->assertDontSee('Teacher');
        $response->assertDontSee('Parent');
    }

    public function test_new_user_can_register_and_is_redirected_to_subject_setup(): void
    {
        Mail::fake();

        $lookups = $this->createSignupLookups();

        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'Learner',
            'username' => 'testlearner',
            'email' => 'learner@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'user_type_id' => $lookups['user_type_id'],
            'curriculum_id' => $lookups['curriculum_id'],
            'grade_id' => $lookups['grade_id'],
        ]);

        $response->assertRedirect(route('subjects.index'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name' => 'Test Learner',
            'first_name' => 'Test',
            'last_name' => 'Learner',
            'username' => 'testlearner',
            'email' => 'learner@example.com',
            'user_type_id' => $lookups['user_type_id'],
            'country_id' => $lookups['country_id'],
            'curriculum_id' => $lookups['curriculum_id'],
            'grade_id' => $lookups['grade_id'],
        ]);

        Mail::assertSent(WelcomeToChamu::class, function (WelcomeToChamu $mail): bool {
            return $mail->hasTo('learner@example.com')
                && $mail->firstName === 'Test'
                && $mail->accountType === 'pupil';
        });
    }

    public function test_university_student_can_register_without_high_school_grade(): void
    {
        Mail::fake();

        $lookups = $this->createSignupLookups();

        $response = $this->post(route('register.store'), [
            'first_name' => 'Tertiary',
            'last_name' => 'Student',
            'username' => 'tertiarystudent',
            'email' => 'student@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'user_type_id' => $lookups['student_user_type_id'],
        ]);

        $response->assertRedirect(route('bursaries.index'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name' => 'Tertiary Student',
            'email' => 'student@example.com',
            'user_type_id' => $lookups['student_user_type_id'],
            'country_id' => $lookups['country_id'],
            'curriculum_id' => null,
            'grade_id' => null,
        ]);

        Mail::assertSent(WelcomeToChamu::class, function (WelcomeToChamu $mail): bool {
            return $mail->hasTo('student@example.com')
                && $mail->firstName === 'Tertiary'
                && $mail->accountType === 'student';
        });

        $html = (new WelcomeToChamu('Tertiary', 'student'))->render();

        $this->assertStringContainsString('find bursaries', $html);
        $this->assertStringContainsString('Apply with Chamu', $html);
        $this->assertStringContainsString('Track your history', $html);
    }

    public function test_welcome_email_test_command_sends_template_to_requested_address(): void
    {
        Mail::fake();

        $this->artisan('mail:test-welcome', [
            'email' => 'yondlers@example.com',
            '--first-name' => 'Yondlers',
            '--account-type' => 'student',
        ])
            ->expectsOutput('Sent student welcome email to yondlers@example.com.')
            ->assertExitCode(0);

        Mail::assertSent(WelcomeToChamu::class, function (WelcomeToChamu $mail): bool {
            return $mail->hasTo('yondlers@example.com')
                && $mail->firstName === 'Yondlers'
                && $mail->accountType === 'student';
        });

        $html = (new WelcomeToChamu('Yondlers', 'pupil'))->render();

        $this->assertStringContainsString('Welcome to Chamu, Yondlers!', $html);
        $this->assertStringContainsString('Explore funding', $html);
        $this->assertStringContainsString('bursaries before applications get busy', $html);
        $this->assertStringNotContainsString('welcome-to-chamu.png', $html);
        $this->assertStringNotContainsString('<img', $html);
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

        $studentUserTypeId = DB::table('user_types')->insertGetId([
            'name' => 'student',
            'description' => 'Student',
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
            'student_user_type_id' => $studentUserTypeId,
            'country_id' => $countryId,
            'curriculum_id' => $curriculumId,
            'grade_id' => $gradeId,
        ];
    }
}
