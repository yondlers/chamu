@extends('layouts.app')

@section('title', 'Sign up · Matric Hub')

@section('content')
    <main class="min-h-screen grid lg:grid-cols-[1fr_520px] bg-white">
        <section class="hidden lg:flex flex-col justify-between p-10 bg-[#fff5f6] border-r border-rose-100">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[#E8425B] text-white">
                    <i data-lucide="graduation-cap" style="width:22px;height:22px;"></i>
                </span>
                <span class="font-bold text-xl">Matric Hub</span>
            </a>
            <div class="max-w-xl">
                <p class="text-sm font-semibold uppercase text-[#E8425B] mb-3">Student account</p>
                <h1 class="text-5xl font-bold tracking-normal text-neutral-950">Save your grade, subjects, points, and progress.</h1>
                <p class="mt-4 text-lg text-neutral-600">Registration uses the same curriculum and grade data seeded into the database.</p>
            </div>
            <p class="text-sm text-neutral-500">Built for South African Grade 10-12 learners.</p>
        </section>

        <section class="flex items-center justify-center px-5 py-10">
            <div class="w-full max-w-md">
                <a href="{{ url('/') }}" class="lg:hidden inline-flex items-center gap-2 mb-8">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[#E8425B] text-white">
                        <i data-lucide="graduation-cap" style="width:22px;height:22px;"></i>
                    </span>
                    <span class="font-bold text-xl">Matric Hub</span>
                </a>

                <h1 class="text-3xl font-bold">Create account</h1>
                <p class="mt-2 text-neutral-500">Choose your account type, curriculum, grade, and province once.</p>

                <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="user_type_id" class="block text-sm font-semibold mb-2">I am a</label>
                        <select id="user_type_id" name="user_type_id" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#E8425B]">
                            @foreach ($userTypes as $userType)
                                <option value="{{ $userType->id }}" @selected((int) old('user_type_id') === $userType->id)>
                                    {{ $userType->name === 'pupil' ? 'Pupil' : ucfirst($userType->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_type_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-semibold mb-2">First name</label>
                            <input id="first_name" name="first_name" value="{{ old('first_name') }}" required autofocus class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#E8425B]">
                            @error('first_name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-semibold mb-2">Last name</label>
                            <input id="last_name" name="last_name" value="{{ old('last_name') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#E8425B]">
                            @error('last_name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-semibold mb-2">Username</label>
                        <input id="username" name="username" value="{{ old('username') }}" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#E8425B]">
                        @error('username')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold mb-2">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#E8425B]">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="curriculum_id" class="block text-sm font-semibold mb-2">Curriculum</label>
                            <select id="curriculum_id" name="curriculum_id" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#E8425B]">
                                @foreach ($curriculums as $curriculum)
                                    <option value="{{ $curriculum->id }}" @selected((int) old('curriculum_id', optional($defaultCurriculum)->id) === $curriculum->id)>
                                        {{ $curriculum->abbreviation ?: $curriculum->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('curriculum_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="grade_id" class="block text-sm font-semibold mb-2">Grade</label>
                            <select id="grade_id" name="grade_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#E8425B]"></select>
                            @error('grade_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="province_id" class="block text-sm font-semibold mb-2">Province</label>
                        <select id="province_id" name="province_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#E8425B]">
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
                        <input id="password" name="password" type="password" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#E8425B]">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold mb-2">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#E8425B]">
                    </div>

                    <button class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#E8425B] px-5 py-3.5 font-semibold text-white hover:bg-[#d73550]">
                        Sign up <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                    </button>
                </form>

                <p class="mt-6 text-sm text-neutral-600">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-[#E8425B]">Log in</a>
                </p>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const grades = @json($grades->values());
        const curriculumSelect = document.getElementById('curriculum_id');
        const gradeSelect = document.getElementById('grade_id');
        const selectedGradeId = '{{ old('grade_id') }}';

        const refreshGrades = () => {
            const curriculumId = Number(curriculumSelect.value);
            const rows = grades.filter((grade) => Number(grade.curriculum_id) === curriculumId);

            gradeSelect.innerHTML = '';

            rows.forEach((grade) => {
                const option = document.createElement('option');
                option.value = grade.id;
                option.textContent = grade.name;
                option.selected = String(grade.id) === selectedGradeId || (!selectedGradeId && grade.name === 'Grade 12');
                gradeSelect.appendChild(option);
            });
        };

        curriculumSelect.addEventListener('change', refreshGrades);
        refreshGrades();
    </script>
@endpush
