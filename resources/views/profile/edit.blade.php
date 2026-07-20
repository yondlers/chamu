@extends('layouts.app')

@section('title', 'Profile · Chamu')

@section('content')
    @php
        $userTypeLabels = [
            'pupil' => 'Pupil (High School)',
            'student' => 'Student (University/College)',
            'teacher' => 'Teacher',
            'parent' => 'Parent',
        ];
        $selectedUserTypeId = (int) old('user_type_id', $user->user_type_id);
    @endphp

    <main class="max-w-5xl mx-auto px-5 lg:px-8 py-10">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                <p class="text-sm font-semibold text-[#01225E]">Account</p>
                <h1 class="text-3xl font-bold mt-1">Profile details</h1>
                <p class="mt-2 text-neutral-500">Update personal information, school context, subjects, marks, and APS scores.</p>
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
                <h2 class="font-bold text-xl mb-5">Learning profile</h2>

                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <label for="user_type_id" class="block text-sm font-semibold mb-2">User type</label>
                        <select id="user_type_id" name="user_type_id" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
	                            @foreach ($userTypes as $userType)
	                                <option value="{{ $userType->id }}" @selected((int) old('user_type_id', $user->user_type_id) === $userType->id)>
	                                    {{ $userTypeLabels[$userType->name] ?? Str::of($userType->name)->title() }}
	                                </option>
	                            @endforeach
	                        </select>
                        @error('user_type_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
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
            <a href="{{ route('subjects.index') }}" class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card hover:border-[#01225E]">
                <span class="inline-flex w-11 h-11 items-center justify-center rounded-xl bg-blue-50 text-[#01225E] mb-4">
                    <i data-lucide="list-checks" style="width:22px;height:22px;"></i>
                </span>
                <h2 class="font-bold text-xl">Choose subjects</h2>
                <p class="mt-2 text-sm text-neutral-500">Select only the subjects you take. Search will use this list after login.</p>
            </a>
            <a href="{{ route('marks.index') }}" class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card hover:border-[#01225E]">
                <span class="inline-flex w-11 h-11 items-center justify-center rounded-xl bg-blue-50 text-[#01225E] mb-4">
                    <i data-lucide="line-chart" style="width:22px;height:22px;"></i>
                </span>
                <h2 class="font-bold text-xl">Add term marks</h2>
                <p class="mt-2 text-sm text-neutral-500">Capture marks by term. APS is calculated automatically.</p>
            </a>
            <a href="{{ route('profile.application') }}" class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card hover:border-[#01225E]">
                <span class="inline-flex w-11 h-11 items-center justify-center rounded-xl bg-blue-50 text-[#01225E] mb-4">
                    <i data-lucide="folder-check" style="width:22px;height:22px;"></i>
                </span>
                <h2 class="font-bold text-xl">Application profile</h2>
                <p class="mt-2 text-sm text-neutral-500">Save bursary details and documents so Apply with Chamu is quicker.</p>
            </a>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const grades = @json($grades->values());
        const userTypes = @json($userTypes->values());
        const curriculumSelect = document.getElementById('curriculum_id');
        const gradeSelect = document.getElementById('grade_id');
        const userTypeSelect = document.getElementById('user_type_id');
        const highSchoolProfileFields = document.getElementById('high-school-profile-fields');
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
            const selectedUserType = userTypes.find((userType) => Number(userType.id) === Number(userTypeSelect.value));
            const isPupil = selectedUserType?.name === 'pupil';

            highSchoolProfileFields.classList.toggle('hidden', !isPupil);
            highSchoolProfileFields.classList.toggle('contents', isPupil);
            curriculumSelect.required = isPupil;
            curriculumSelect.disabled = !isPupil;
            gradeSelect.disabled = !isPupil;
        };

        userTypeSelect.addEventListener('change', refreshLearningProfile);
        refreshLearningProfile();
    </script>
@endpush
