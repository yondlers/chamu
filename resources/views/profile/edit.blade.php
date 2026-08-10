@extends('layouts.app')

@section('title', 'Profile · Chamu')

@section('content')
    @php
        $userTypeLabels = [
            'pupil' => 'Pupil (High School)',
            'student' => 'Student (University/College)',
            'tutor' => 'Tutor',
            'teacher' => 'Teacher',
            'parent' => 'Parent',
        ];
        $selectedRoles = collect(old('roles', $selectedRoles ?? $user->roleNames()->all()))
            ->map(fn ($role) => strtolower((string) $role))
            ->values()
            ->all();
    @endphp

    <main class="max-w-5xl mx-auto px-5 lg:px-8 py-10">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                <p class="text-sm font-semibold text-[#01225E]">Account</p>
                <h1 class="text-3xl font-bold mt-1">Profile details</h1>
                <p class="mt-2 text-neutral-500">You can be a Pupil, Student, and Tutor on one account. Shared details and documents carry across bursary and tutor applications.</p>
            </div>
            @if (session('status'))
                <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
            @endif
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                Please check the highlighted fields and try again.
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-8">
            @csrf
            @method('PUT')

            <section class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card">
                <h2 class="font-bold text-xl mb-5">Personal information</h2>

                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <label for="first_name" class="block text-sm font-semibold mb-2">First name</label>
                        <input id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('first_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-semibold mb-2">Last name</label>
                        <input id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('last_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="username" class="block text-sm font-semibold mb-2">Username</label>
                        <input id="username" name="username" value="{{ old('username', $user->username) }}" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('username') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-semibold mb-2">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card">
                <h2 class="font-bold text-xl mb-2">Account roles</h2>
                <p class="mb-5 text-sm text-neutral-500">Select every path you use. Province, contact details, institution info, and saved documents are shared across them.</p>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($userTypes as $userType)
                        @if (in_array($userType->name, ['pupil', 'student', 'tutor'], true))
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 px-4 py-3 hover:border-[#01225E]">
                                <input
                                    type="checkbox"
                                    name="roles[]"
                                    value="{{ $userType->name }}"
                                    data-role-name="{{ $userType->name }}"
                                    class="mt-1 rounded border-neutral-300 text-[#01225E] focus:ring-[#01225E] role-checkbox"
                                    @checked(in_array($userType->name, $selectedRoles, true))
                                >
                                <span>
                                    <span class="block text-sm font-bold">{{ $userTypeLabels[$userType->name] ?? Str::of($userType->name)->title() }}</span>
                                    <span class="mt-1 block text-xs text-neutral-500">
                                        @if ($userType->name === 'pupil')
                                            Subjects, marks, and undergrad lookup
                                        @elseif ($userType->name === 'student')
                                            Bursaries and university applications
                                        @else
                                            Tutor application and learner bookings
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endif
                    @endforeach
                </div>
                @error('roles') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror

                <div class="mt-6 grid md:grid-cols-2 gap-5">
                    <div>
                        <label for="province_id" class="block text-sm font-semibold mb-2">Province</label>
                        <select id="province_id" name="province_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                            <option value="">Choose province</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}" @selected((int) old('province_id', $user->province_id) === $province->id)>{{ $province->name }}</option>
                            @endforeach
                        </select>
                        @error('province_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div id="high-school-profile-fields" class="contents">
                    <div>
                        <label for="curriculum_id" class="block text-sm font-semibold mb-2">Curriculum</label>
                        <select id="curriculum_id" name="curriculum_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                            @foreach ($curriculums as $curriculum)
                                <option value="{{ $curriculum->id }}" @selected((int) old('curriculum_id', $user->curriculum_id) === $curriculum->id)>
                                    {{ $curriculum->abbreviation ?: $curriculum->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('curriculum_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="grade_id" class="block text-sm font-semibold mb-2">Grade</label>
                        <select id="grade_id" name="grade_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]"></select>
                        @error('grade_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    </div>
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-5 py-3 font-semibold hover:bg-neutral-50">Cancel</a>
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white hover:bg-[#001A48]">
                    Save profile <i data-lucide="save" style="width:18px;height:18px;"></i>
                </button>
            </div>
        </form>

        <section class="mt-8 grid gap-5 md:grid-cols-3">
            <a href="{{ route('subjects.index', ['manage' => 1]) }}" class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card hover:border-[#01225E]">
                <span class="inline-flex w-11 h-11 items-center justify-center rounded-xl bg-blue-50 text-[#01225E] mb-4">
                    <i data-lucide="list-checks" style="width:22px;height:22px;"></i>
                </span>
                <h2 class="font-bold text-xl">Subjects & marks</h2>
                <p class="mt-2 text-sm text-neutral-500">Pupil path: update grade, subjects, and marks. Used for APS and tutor mark prefill.</p>
            </a>
            <a href="{{ route('profile.application') }}" class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card hover:border-[#01225E]">
                <span class="inline-flex w-11 h-11 items-center justify-center rounded-xl bg-blue-50 text-[#01225E] mb-4">
                    <i data-lucide="folder-check" style="width:22px;height:22px;"></i>
                </span>
                <h2 class="font-bold text-xl">Shared application pack</h2>
                <p class="mt-2 text-sm text-neutral-500">Phone, address, institution, and documents reused for bursaries and tutoring.</p>
            </a>
            <a href="{{ route('tutor.application.welcome') }}" class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card hover:border-[#01225E]">
                <span class="inline-flex w-11 h-11 items-center justify-center rounded-xl bg-blue-50 text-[#01225E] mb-4">
                    <i data-lucide="presentation" style="width:22px;height:22px;"></i>
                </span>
                <h2 class="font-bold text-xl">Become a tutor</h2>
                <p class="mt-2 text-sm text-neutral-500">Start or continue your tutor application with province and contact details already filled in.</p>
            </a>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const grades = @json($grades->values());
        const curriculumSelect = document.getElementById('curriculum_id');
        const gradeSelect = document.getElementById('grade_id');
        const highSchoolProfileFields = document.getElementById('high-school-profile-fields');
        const roleCheckboxes = Array.from(document.querySelectorAll('.role-checkbox'));
        const selectedGradeId = '{{ old('grade_id', $user->grade_id) }}';

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

        const refreshLearningProfile = () => {
            const isPupil = roleCheckboxes.some((checkbox) => checkbox.checked && checkbox.value === 'pupil');

            highSchoolProfileFields.classList.toggle('hidden', !isPupil);
            highSchoolProfileFields.classList.toggle('contents', isPupil);
            curriculumSelect.required = isPupil;
            curriculumSelect.disabled = !isPupil;
            gradeSelect.disabled = !isPupil;
        };

        roleCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', refreshLearningProfile));
        refreshLearningProfile();
    </script>
@endpush
