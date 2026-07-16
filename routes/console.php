<?php

use App\Models\Country;
use App\Models\Curriculum;
use App\Models\Grade;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
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
