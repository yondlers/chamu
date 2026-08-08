@extends('layouts.app')

@section('title', 'Sign up · Chamu')

@section('content')
    @php
        $defaultUserType = $userTypes->firstWhere('name', 'pupil') ?? $userTypes->first();
        $selectedUserTypeId = (int) old('user_type_id', optional($defaultUserType)->id);
        $accountTypes = [
            'pupil' => [
                'label' => 'Pupil (High School)',
                'copy' => 'Subjects, marks, APS, and course match.',
                'icon' => 'school',
            ],
            'student' => [
                'label' => 'Student (University/College)',
                'copy' => 'Bursaries, funding, and course browsing.',
                'icon' => 'graduation-cap',
            ],
        ];
    @endphp

    <main class="min-h-screen grid lg:grid-cols-[1fr_520px] bg-white">
        @include('auth.partials.campus-carousel', [
            'eyebrow' => 'Quick sign up',
            'heading' => 'Create your account first. Add grade and marks next.',
            'copy' => 'Pupils set subjects and latest marks on one screen after sign up. Courses stay open even if you skip.',
        ])

        <section class="flex items-center justify-center px-5 py-10">
            <div class="w-full max-w-md">
                <a href="{{ url('/') }}" class="lg:hidden inline-flex items-center gap-2 mb-8">
                    <img src="{{ asset('images/brand/chamu-logo.png') }}" alt="Chamu logo" class="h-10 w-10 rounded-xl object-contain">
                    <span class="font-bold text-xl">Chamu</span>
                </a>

                <h1 class="text-3xl font-bold">Create account</h1>
                <p class="mt-2 text-neutral-500">Pick pupil or student, then finish in under a minute.</p>

                <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold mb-2">Account type</label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($userTypes as $userType)
                                @php
                                    $accountType = $accountTypes[$userType->name] ?? [
                                        'label' => Str::of($userType->name)->title(),
                                        'copy' => 'Chamu account',
                                        'icon' => 'user',
                                    ];
                                @endphp
                                <label @class([
                                    'js-account-type-card cursor-pointer rounded-xl border bg-white p-4 transition',
                                    'is-selected border-[#01225E] ring-2 ring-[#01225E]/15' => $selectedUserTypeId === $userType->id,
                                    'border-neutral-300 hover:border-neutral-500' => $selectedUserTypeId !== $userType->id,
                                ])>
                                    <input
                                        type="radio"
                                        name="user_type_id"
                                        value="{{ $userType->id }}"
                                        data-user-type-name="{{ $userType->name }}"
                                        @checked($selectedUserTypeId === $userType->id)
                                        class="sr-only js-account-type-radio"
                                        required
                                    >
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[#01225E] text-white">
                                        <i data-lucide="{{ $accountType['icon'] }}" style="width:18px;height:18px;"></i>
                                    </span>
                                    <span class="mt-3 block font-bold text-neutral-950">{{ $accountType['label'] }}</span>
                                    <span class="mt-1 block text-sm font-semibold text-neutral-500">{{ $accountType['copy'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('user_type_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-semibold mb-2">First name</label>
                            <input id="first_name" name="first_name" value="{{ old('first_name') }}" required autofocus class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                            @error('first_name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-semibold mb-2">Last name</label>
                            <input id="last_name" name="last_name" value="{{ old('last_name') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                            @error('last_name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-semibold mb-2">Username</label>
                        <input id="username" name="username" value="{{ old('username') }}" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('username')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold mb-2">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="province_id" class="block text-sm font-semibold mb-2">Province</label>
                        <select id="province_id" name="province_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                            <option value="">Choose province</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}" @selected((int) old('province_id') === $province->id)>{{ $province->name }}</option>
                            @endforeach
                        </select>
                        @error('province_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold mb-2">Password</label>
                        <input id="password" name="password" type="password" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold mb-2">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                    </div>

                    <button class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3.5 font-semibold text-white hover:bg-[#001A48]">
                        Sign up <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                    </button>
                </form>

                <p class="mt-6 text-sm text-neutral-600">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-[#01225E]">Log in</a>
                </p>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const accountTypeRadios = [...document.querySelectorAll('.js-account-type-radio')];
        const accountTypeCards = [...document.querySelectorAll('.js-account-type-card')];

        const refreshAccountType = () => {
            accountTypeCards.forEach((card) => {
                const isSelected = card.querySelector('.js-account-type-radio')?.checked;
                card.classList.toggle('is-selected', Boolean(isSelected));
                card.classList.toggle('border-[#01225E]', Boolean(isSelected));
                card.classList.toggle('ring-2', Boolean(isSelected));
                card.classList.toggle('ring-[#01225E]/15', Boolean(isSelected));
                card.classList.toggle('border-neutral-300', !isSelected);
            });
        };

        accountTypeRadios.forEach((radio) => radio.addEventListener('change', refreshAccountType));
        refreshAccountType();
    </script>
@endpush
