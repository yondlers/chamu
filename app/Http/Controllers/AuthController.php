<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeToChamu;
use App\Models\User;
use App\Support\Email\EmailDeliveryLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');

    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        $login = $data['username'];
        $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([
            $loginField => $login,
            'password' => $data['password'],
        ], $request->boolean('remember'))) {
            return back()
                ->withErrors(['username' => 'These credentials do not match our records.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        $request->user()->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended(route('aps.index'));

    }

    public function showRegister(Request $request)
    {
        $showTutorEntryPoints = (bool) config('features.show_tutor_entry_points', false);
        $publicUserTypes = [
            'pupil' => 'High school learner account for studying, practice, notes, and exams.',
            'student' => 'University or college student account for funding and study planning.',
            'tutor' => 'Tutor account for offering subject tutoring to learners across South Africa.',
        ];
        $visibleUserTypeNames = array_keys($publicUserTypes);

        if (! $showTutorEntryPoints) {
            $visibleUserTypeNames = array_values(array_diff($visibleUserTypeNames, ['tutor']));
        }

        if (Schema::hasTable('user_types')) {
            foreach ($publicUserTypes as $name => $description) {
                DB::table('user_types')->updateOrInsert(
                    ['name' => $name],
                    [
                        'description' => $description,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $userTypes = Schema::hasTable('user_types')
            ? DB::table('user_types')
                ->select('id', 'name')
                ->whereIn('name', $visibleUserTypeNames)
                ->orderByRaw("case name when 'pupil' then 1 when 'student' then 2 when 'tutor' then 3 else 4 end")
                ->get()
            : collect();

        $curriculums = Schema::hasTable('curriculums')
            ? DB::table('curriculums')
                ->select('id', 'name', 'abbreviation')
                ->when(Schema::hasColumn('curriculums', 'is_live'), fn ($query) => $query->where('is_live', true))
                ->orderBy('abbreviation')
                ->get()
            : collect();

        $grades = Schema::hasTable('grades')
            ? DB::table('grades')
                ->select('id', 'curriculum_id', 'name', 'sort_order')
                ->orderBy('sort_order')
                ->get()
            : collect();

        $provinces = Schema::hasTable('provinces')
            ? DB::table('provinces')
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->get()
            : collect();

        $preferredType = strtolower((string) $request->query('type', ''));
        $preferredType = in_array($preferredType, $visibleUserTypeNames, true) ? $preferredType : '';
        $defaultUserType = $userTypes->firstWhere('name', $preferredType)
            ?? $userTypes->firstWhere('name', 'pupil')
            ?? $userTypes->first();

        return view('auth.register', [
            'userTypes' => $userTypes,
            'curriculums' => $curriculums,
            'grades' => $grades,
            'provinces' => $provinces,
            'defaultCurriculum' => $curriculums->firstWhere('abbreviation', 'CAPS') ?? $curriculums->first(),
            'defaultUserType' => $defaultUserType,
            'showTutorEntryPoints' => $showTutorEntryPoints,
        ]);

    }

    public function register(Request $request)
    {
        $showTutorEntryPoints = (bool) config('features.show_tutor_entry_points', false);
        $publicRegistrationRoles = $showTutorEntryPoints
            ? ['pupil', 'student', 'tutor']
            : ['pupil', 'student'];

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'user_type_id' => ['required', 'exists:user_types,id'],
            'curriculum_id' => ['nullable', 'exists:curriculums,id'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
        ]);

        $userType = DB::table('user_types')
            ->where('id', $data['user_type_id'])
            ->whereIn('name', $publicRegistrationRoles)
            ->first(['id', 'name']);
        $countryId = DB::table('countries')->where('name', 'South Africa')->value('id') ?? DB::table('countries')->value('id');

        if ($userType === null) {
            return back()
                ->withErrors(['user_type_id' => 'Choose a valid account type.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        if ($countryId === null) {
            return back()
                ->withErrors(['email' => 'Please run the database seeders before creating an account.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $user = User::create([
            'user_type_id' => $userType->id,
            'country_id' => $countryId,
            'province_id' => $data['province_id'] ?? null,
            'curriculum_id' => $userType->name === 'pupil' ? ($data['curriculum_id'] ?? null) : null,
            'grade_id' => $userType->name === 'pupil' ? ($data['grade_id'] ?? null) : null,
            'name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'last_login_at' => now(),
        ]);

        $user->addRole($userType->name);

        try {
            Mail::to($user->email)->send(new WelcomeToChamu($user->first_name ?: $user->name, $userType->name === 'tutor' ? 'student' : $userType->name));
        } catch (Throwable $exception) {
            EmailDeliveryLogger::markFailed($user->email, WelcomeToChamu::class, null, $exception);

            report($exception);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $redirectRoute = match ($userType->name) {
            'pupil' => 'subjects.welcome',
            'tutor' => 'tutor.application.welcome',
            default => 'bursaries.index',
        };

        $pupilStatus = $showTutorEntryPoints
            ? 'Welcome. Add your latest subjects and marks when you are ready. You can also apply for bursaries or become a tutor later — your details will be reused.'
            : 'Welcome. Add your latest subjects and marks when you are ready. You can also apply for bursaries when you are ready — your details will be reused.';
        $studentStatus = $showTutorEntryPoints
            ? 'Your student account is ready for bursary applications. You can also look up undergrad studies or become a tutor later — shared details and documents will carry over.'
            : 'Your student account is ready for bursary applications. You can also look up undergrad studies — shared details and documents will carry over.';
        $status = match ($userType->name) {
            'pupil' => $pupilStatus,
            'tutor' => 'Welcome. Complete your tutor profile when you are ready — you can save and continue later. Student and pupil tools stay available on the same account.',
            default => $studentStatus,
        };

        return redirect()
            ->route($redirectRoute)
            ->with('status', $status);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
