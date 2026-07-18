<?php

use App\Mail\WelcomeToChamu;
use App\Models\Country;
use App\Models\Curriculum;
use App\Models\Grade;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:make-super {email} {--password=} {--name=Super Admin} {--username=}', function () {
    $email = (string) $this->argument('email');
    $name = (string) $this->option('name');
    $password = (string) ($this->option('password') ?: Str::random(16).'A1!');
    $username = (string) ($this->option('username') ?: Str::of($email)->before('@')->slug('_')->toString());

    if (User::where('username', $username)->where('email', '!=', $email)->exists()) {
        $username = $username.'_admin';
    }

    $userType = UserType::firstOrCreate(
        ['name' => 'super admin'],
        ['description' => 'Platform administrator account with full system access.'],
    );
    $country = Country::firstOrCreate(['name' => 'South Africa']);
    $curriculum = Curriculum::firstOrCreate(
        ['abbreviation' => 'CAPS'],
        [
            'country_id' => $country->id,
            'name' => 'NSC (National Senior Certificate)',
            'is_live' => true,
        ],
    );
    $grade = Grade::firstOrCreate(
        [
            'curriculum_id' => $curriculum->id,
            'name' => 'Grade 12',
        ],
        ['sort_order' => 12],
    );

    $user = User::updateOrCreate(
        ['email' => $email],
        [
            'user_type_id' => $userType->id,
            'country_id' => $country->id,
            'curriculum_id' => $curriculum->id,
            'grade_id' => $grade->id,
            'name' => $name,
            'first_name' => Str::of($name)->before(' ')->toString() ?: 'Super',
            'last_name' => Str::of($name)->contains(' ') ? Str::of($name)->after(' ')->toString() : 'Admin',
            'username' => $username,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_super_admin' => true,
        ],
    );

    $this->info(($user->wasRecentlyCreated ? 'Created' : 'Updated').' super admin: '.$user->email);
    $this->line('Password: '.$password);
})->purpose('Create or promote a super admin user');

Artisan::command('mail:test-welcome {email : Email address to receive the welcome email} {--first-name= : First name to use in the greeting} {--account-type= : Account type to preview: pupil or student}', function () {
    $email = (string) $this->argument('email');

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Please provide a valid email address.');

        return Command::FAILURE;
    }

    $user = User::with('userType:id,name')->where('email', $email)->first(['id', 'user_type_id', 'first_name', 'name', 'email']);
    $firstName = trim((string) ($this->option('first-name') ?: ''));
    $accountType = strtolower(trim((string) ($this->option('account-type') ?: '')));

    if ($firstName === '' && $user !== null) {
        $firstName = trim((string) ($user->first_name ?: Str::of($user->name)->before(' ')->toString()));
    }

    if ($accountType === '' && $user !== null) {
        $accountType = strtolower((string) ($user->userType?->name ?? ''));
    }

    if ($firstName === '') {
        $firstName = Str::of($email)
            ->before('@')
            ->replace(['.', '_', '-'], ' ')
            ->title()
            ->toString();
    }

    if ($accountType === '') {
        $accountType = 'pupil';
    }

    if (! in_array($accountType, ['pupil', 'student'], true)) {
        $this->error('Account type must be pupil or student.');

        return Command::FAILURE;
    }

    try {
        Mail::to($email)->send(new WelcomeToChamu($firstName, $accountType));
    } catch (\Throwable $exception) {
        $this->error('Welcome email could not be sent: '.$exception->getMessage());

        return Command::FAILURE;
    }

    $this->info('Sent '.$accountType.' welcome email to '.$email.'.');

    return Command::SUCCESS;
})->purpose('Send a test Chamu welcome email');
