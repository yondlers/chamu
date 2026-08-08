@extends('layouts.app')

@section('title', 'Tutor application · Chamu')

@section('content')
    @php
        $sideImage = asset('images/tutors/hero-session-'.(($step % 5) ?: 5).'.png');
        $selectedLanguages = collect(old('languages', $application->languages ?? []))->filter()->values();
        $selectedModes = collect(old('teaching_modes', $application->teaching_modes ?? ['online']))->filter()->values();

        $markRows = old('marks');
        if (! is_array($markRows) || count($markRows) === 0) {
            $markRows = $application->marks->map(fn ($row) => [
                'subject_id' => $row->subject_id,
                'subject_label' => $row->displaySubjectName(),
                'subject_other' => $row->subject_other,
                'mark' => $row->mark,
                'year' => $row->year,
                'level' => $row->level,
            ])->values()->all();
        }
        if (count($markRows) === 0) {
            $markRows = [['subject_id' => '', 'subject_label' => '', 'subject_other' => '', 'mark' => '', 'year' => '', 'level' => 'high_school']];
        }

        $subjectRows = old('subjects');
        if (! is_array($subjectRows) || count($subjectRows) === 0) {
            $subjectRows = $application->subjects->map(fn ($row) => [
                'subject_id' => $row->subject_id,
                'subject_label' => $row->displaySubjectName(),
                'subject_other' => $row->subject_other,
                'hourly_rate' => $row->hourly_rate,
                'level' => $row->level,
            ])->values()->all();
        }
        if (count($subjectRows) === 0) {
            $subjectRows = [['subject_id' => '', 'subject_label' => '', 'subject_other' => '', 'hourly_rate' => '', 'level' => 'high_school']];
        }

        $availabilityRows = old('availabilities');
        if (! is_array($availabilityRows) || count($availabilityRows) === 0) {
            $availabilityRows = $application->availabilities->map(fn ($row) => [
                'day_of_week' => $row->day_of_week,
                'start_time' => substr((string) $row->start_time, 0, 5),
                'end_time' => substr((string) $row->end_time, 0, 5),
                'is_available' => $row->is_available,
            ])->values()->all();
        }
        if (count($availabilityRows) === 0) {
            $availabilityRows = [['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '12:00', 'is_available' => true]];
        }

        $profileImageUrl = $application->profile_image_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($application->profile_image_path)
            : null;

        $universityIsOther = (bool) old('university_is_other', blank($application->university_id) && filled($application->university_other));
        $qualificationIsOther = (bool) old('qualification_is_other', blank($application->qualification_id) && filled($application->qualification_other));
    @endphp

    <main class="min-h-[calc(100vh-4rem)] bg-[#f3f6fb]">
        <div class="mx-auto grid max-w-7xl gap-0 lg:grid-cols-[minmax(0,1fr)_420px]">
            <section class="px-5 py-8 sm:px-8 lg:py-10">
                <div class="mb-8">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-[#01225E]/70">Tutor application</p>
                    <h1 class="mt-2 text-3xl font-black text-neutral-950 sm:text-4xl">{{ $steps[$step]['label'] }}</h1>
                    <p class="mt-2 max-w-2xl text-neutral-600">{{ $steps[$step]['copy'] }} You can save and continue later.</p>
                </div>

                <div class="mb-8">
                    <div class="flex items-center justify-between gap-3 text-xs font-bold uppercase tracking-wide text-neutral-500">
                        <span>Step {{ $step }} of {{ count($steps) }}</span>
                        <span>{{ $steps[$step]['label'] }}</span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-neutral-200">
                        <div class="h-full rounded-full bg-[#01225E] transition-all duration-500" style="width: {{ ($step / count($steps)) * 100 }}%"></div>
                    </div>
                    <ol class="mt-4 grid gap-2 sm:grid-cols-3 xl:grid-cols-6">
                        @foreach ($steps as $number => $meta)
                            <li @class([
                                'rounded-xl px-3 py-2 text-sm font-semibold',
                                'bg-[#01225E] text-white' => $number === $step,
                                'bg-white text-neutral-700 border border-neutral-200' => $number !== $step,
                            ])>
                                <span class="block text-[11px] uppercase tracking-wide opacity-70">Step {{ $number }}</span>
                                {{ $meta['label'] }}
                            </li>
                        @endforeach
                    </ol>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('tutor.application.update') }}" enctype="multipart/form-data" class="space-y-6" id="tutor-application-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="step" value="{{ $step }}">
                    <input type="hidden" name="action" id="form-action" value="continue">

                    @if ($step === 1)
                        <div class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6">
                            <label class="block text-sm font-semibold mb-3">Tutor image</label>
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <div class="relative h-28 w-28 overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-100">
                                    <img id="tutor-image-preview" src="{{ $profileImageUrl ?: asset('images/tutors/hero-session-5.png') }}" alt="Tutor preview" class="h-full w-full object-cover {{ $profileImageUrl ? '' : 'opacity-40' }}">
                                </div>
                                <div class="flex-1">
                                    <input id="profile_image" name="profile_image" type="file" accept="image/*" class="block w-full text-sm text-neutral-600 file:mr-4 file:rounded-xl file:border-0 file:bg-[#01225E] file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-[#001A48]">
                                    <p class="mt-2 text-sm text-neutral-500">Clear headshot preferred. Max 5MB.</p>
                                    @error('profile_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6 space-y-5">
                            <div>
                                <label for="headline" class="block text-sm font-semibold mb-2">Headline</label>
                                <input id="headline" name="headline" value="{{ old('headline', $application->headline) }}" placeholder="e.g. Matric Maths & Physical Sciences tutor" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                @error('headline') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="gender" class="block text-sm font-semibold mb-2">Gender</label>
                                <select id="gender" name="gender" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                    <option value="">Optional</option>
                                    @foreach (['female' => 'Female', 'male' => 'Male', 'non-binary' => 'Non-binary', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('gender', $application->gender) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <span class="block text-sm font-semibold mb-2">Languages you teach in</span>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ($languageOptions as $language)
                                        <label class="inline-flex items-center gap-2 rounded-xl border border-neutral-200 px-3 py-2 text-sm font-semibold">
                                            <input type="checkbox" name="languages[]" value="{{ $language }}" @checked($selectedLanguages->contains($language)) class="rounded border-neutral-300 text-[#01225E] focus:ring-[#01225E]">
                                            {{ $language }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('languages') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="street" class="block text-sm font-semibold mb-2">Suburb / street</label>
                                <input id="street" name="street" value="{{ old('street', $application->street) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="city" class="block text-sm font-semibold mb-2">City</label>
                                    <input id="city" name="city" value="{{ old('city', $application->city) }}" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                    @error('city') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="province_id" class="block text-sm font-semibold mb-2">Province</label>
                                    <select id="province_id" name="province_id" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                        <option value="">Choose province</option>
                                        @foreach ($provinces as $province)
                                            <option value="{{ $province->id }}" @selected((int) old('province_id', $application->province_id) === (int) $province->id)>{{ $province->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('province_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($step === 2)
                        <div class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6 space-y-5">
                            <div>
                                <label for="high_school_syllabus" class="block text-sm font-semibold mb-2">High school syllabus completed</label>
                                <select id="high_school_syllabus" name="high_school_syllabus" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                    <option value="">Select syllabus</option>
                                    @foreach ($syllabusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('high_school_syllabus', $application->high_school_syllabus) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('high_school_syllabus') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div id="syllabus-other" class="{{ old('high_school_syllabus', $application->high_school_syllabus) === 'other' ? '' : 'hidden' }}">
                                <label for="high_school_syllabus_other" class="block text-sm font-semibold mb-2">Specify other syllabus</label>
                                <input id="high_school_syllabus_other" name="high_school_syllabus_other" value="{{ old('high_school_syllabus_other', $application->high_school_syllabus_other) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                @error('high_school_syllabus_other') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <label class="inline-flex items-center gap-2 text-sm font-semibold">
                                <input type="checkbox" name="attended_university" value="1" @checked(old('attended_university', $application->attended_university)) class="rounded border-neutral-300 text-[#01225E] focus:ring-[#01225E]" id="attended_university">
                                I attended university / college
                            </label>

                            <div id="university-fields" class="space-y-5 {{ old('attended_university', $application->attended_university) ? '' : 'hidden' }}">
                                <label class="inline-flex items-center gap-2 text-sm font-semibold">
                                    <input type="checkbox" name="graduated" value="1" @checked(old('graduated', $application->graduated)) class="rounded border-neutral-300 text-[#01225E] focus:ring-[#01225E]">
                                    I graduated
                                </label>

                                <div>
                                    <label class="block text-sm font-semibold mb-2">University / College</label>
                                    @include('tutor.partials.searchable-select', [
                                        'name' => 'university_id',
                                        'hiddenName' => 'university_id',
                                        'isOtherName' => 'university_is_other',
                                        'otherName' => 'university_other',
                                        'selectedId' => $universityIsOther ? 'other' : old('university_id', $application->university_id),
                                        'selectedLabel' => $universityIsOther
                                            ? old('university_other', $application->university_other)
                                            : old('university_label', $application->selectedUniversity?->name),
                                        'otherValue' => old('university_other', $application->university_other),
                                        'placeholder' => 'Search universities and colleges…',
                                        'searchUrl' => $universitiesUrl,
                                        'inputId' => 'university_search',
                                    ])
                                    @error('university_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    @error('university_other') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold mb-2">Course / Qualification</label>
                                    @include('tutor.partials.searchable-select', [
                                        'name' => 'qualification_id',
                                        'hiddenName' => 'qualification_id',
                                        'isOtherName' => 'qualification_is_other',
                                        'otherName' => 'qualification_other',
                                        'selectedId' => $qualificationIsOther ? 'other' : old('qualification_id', $application->qualification_id),
                                        'selectedLabel' => $qualificationIsOther
                                            ? old('qualification_other', $application->qualification_other)
                                            : old('qualification_label', $application->selectedQualification?->name),
                                        'otherValue' => old('qualification_other', $application->qualification_other),
                                        'placeholder' => 'Search courses from our database…',
                                        'searchUrl' => $qualificationsUrl,
                                        'dependsOn' => 'university_id',
                                        'inputId' => 'qualification_search',
                                    ])
                                    @error('qualification_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    @error('qualification_other') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="specialization" class="block text-sm font-semibold mb-2">Specialization</label>
                                    <input id="specialization" name="specialization" value="{{ old('specialization', $application->specialization) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                </div>
                            </div>

                            <div>
                                <label for="experience_years" class="block text-sm font-semibold mb-2">Years of tutoring experience</label>
                                <input id="experience_years" name="experience_years" type="number" min="0" max="60" value="{{ old('experience_years', $application->experience_years) }}" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                @error('experience_years') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="tutoring_bio" class="block text-sm font-semibold mb-2">Tutoring bio</label>
                                <textarea id="tutoring_bio" name="tutoring_bio" rows="5" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]" placeholder="Describe how you teach…">{{ old('tutoring_bio', $application->tutoring_bio) }}</textarea>
                                @error('tutoring_bio') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="tutoring_experience" class="block text-sm font-semibold mb-2">Tutoring experience</label>
                                <textarea id="tutoring_experience" name="tutoring_experience" rows="4" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">{{ old('tutoring_experience', $application->tutoring_experience) }}</textarea>
                                @error('tutoring_experience') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="tutoring_style" class="block text-sm font-semibold mb-2">Teaching style</label>
                                <input id="tutoring_style" name="tutoring_style" value="{{ old('tutoring_style', $application->tutoring_style) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                            </div>
                            <div>
                                <span class="block text-sm font-semibold mb-2">Teaching modes</span>
                                <div class="flex flex-wrap gap-3">
                                    @foreach ($teachingModeOptions as $value => $label)
                                        <label class="inline-flex items-center gap-2 rounded-xl border border-neutral-200 px-4 py-2.5 text-sm font-semibold">
                                            <input type="checkbox" name="teaching_modes[]" value="{{ $value }}" @checked($selectedModes->contains($value)) class="rounded border-neutral-300 text-[#01225E] focus:ring-[#01225E]">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('teaching_modes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="heard_from" class="block text-sm font-semibold mb-2">How did you hear about Chamu?</label>
                                <select id="heard_from" name="heard_from" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                    <option value="">Optional</option>
                                    @foreach ($heardFromOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('heard_from', $application->heard_from) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="heard-from-other" class="{{ old('heard_from', $application->heard_from) === 'other' ? '' : 'hidden' }}">
                                <label for="heard_from_other" class="block text-sm font-semibold mb-2">Please specify</label>
                                <input id="heard_from_other" name="heard_from_other" value="{{ old('heard_from_other', $application->heard_from_other) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                @error('heard_from_other') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif

                    @if ($step === 3)
                        <div class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-black">Your marks</h2>
                                    <p class="mt-1 text-sm text-neutral-500">Add subjects from our database and the marks you achieved.</p>
                                </div>
                                <button type="button" id="add-mark-row" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-semibold hover:bg-neutral-50">
                                    <i data-lucide="plus" style="width:16px;height:16px;"></i> Add
                                </button>
                            </div>
                            <div id="mark-rows" class="mt-5 space-y-4">
                                @foreach ($markRows as $index => $row)
                                    @include('tutor.partials.mark-row', ['index' => $index, 'row' => $row, 'levelOptions' => $levelOptions, 'subjectsUrl' => $subjectsUrl])
                                @endforeach
                            </div>
                            @error('marks') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if ($step === 4)
                        <div class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-black">Subjects you offer</h2>
                                    <p class="mt-1 text-sm text-neutral-500">Select subjects from our list and set your hourly rate in ZAR.</p>
                                </div>
                                <button type="button" id="add-subject-row" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-semibold hover:bg-neutral-50">
                                    <i data-lucide="plus" style="width:16px;height:16px;"></i> Add
                                </button>
                            </div>
                            <div id="subject-rows" class="mt-5 space-y-4">
                                @foreach ($subjectRows as $index => $row)
                                    @include('tutor.partials.subject-rate-row', ['index' => $index, 'row' => $row, 'levelOptions' => $levelOptions, 'subjectsUrl' => $subjectsUrl])
                                @endforeach
                            </div>
                            @error('subjects') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if ($step === 5)
                        <div class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-black">Booking availability</h2>
                                    <p class="mt-1 text-sm text-neutral-500">Choose Monday to Sunday slots when learners can book you.</p>
                                </div>
                                <button type="button" id="add-availability-row" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-semibold hover:bg-neutral-50">
                                    <i data-lucide="plus" style="width:16px;height:16px;"></i> Add slot
                                </button>
                            </div>
                            <div id="availability-rows" class="mt-5 space-y-4">
                                @foreach ($availabilityRows as $index => $row)
                                    @include('tutor.partials.availability-row', ['index' => $index, 'row' => $row, 'dayOptions' => $dayOptions])
                                @endforeach
                            </div>
                            @error('availabilities') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if ($step === 6)
                        <div class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6 space-y-5">
                            <div>
                                <label for="phone" class="block text-sm font-semibold mb-2">Contact number for learners</label>
                                <input id="phone" name="phone" value="{{ old('phone', $application->phone) }}" required placeholder="e.g. 082 123 4567" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                @error('phone') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm font-semibold">
                                <input type="checkbox" name="whatsapp_same_as_phone" value="1" id="whatsapp_same_as_phone" @checked(old('whatsapp_same_as_phone', $application->whatsapp_same_as_phone ?? true)) class="rounded border-neutral-300 text-[#01225E] focus:ring-[#01225E]">
                                WhatsApp is the same as my phone number
                            </label>
                            <div id="whatsapp-field" class="{{ old('whatsapp_same_as_phone', $application->whatsapp_same_as_phone ?? true) ? 'hidden' : '' }}">
                                <label for="whatsapp" class="block text-sm font-semibold mb-2">WhatsApp number</label>
                                <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $application->whatsapp) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                @error('whatsapp') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="rounded-xl bg-[#F3F7FC] px-4 py-4 text-sm text-[#01225E]">
                                <p class="font-bold">Before you submit</p>
                                <ul class="mt-2 list-disc space-y-1 pl-5 font-medium">
                                    <li>Your tutor status will be set to Pending for review</li>
                                    <li>Reviews and ratings are ready in the system, but learners will not see them yet</li>
                                    <li>Bookings will use the weekly availability you set</li>
                                </ul>
                            </div>
                            <label class="inline-flex items-start gap-2 text-sm font-semibold">
                                <input type="checkbox" name="accept_terms" value="1" @checked(old('accept_terms', $application->accept_terms)) class="mt-1 rounded border-neutral-300 text-[#01225E] focus:ring-[#01225E]" required>
                                <span>I confirm my details are accurate and I agree to be contacted about tutoring on Chamu.</span>
                            </label>
                            @error('accept_terms') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap gap-3">
                            @if ($step > 1)
                                <a href="{{ route('tutor.application.show', ['step' => $step - 1]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm font-semibold hover:bg-neutral-50">
                                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Back
                                </a>
                            @else
                                <a href="{{ route('tutor.application.welcome') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm font-semibold hover:bg-neutral-50">Welcome</a>
                            @endif
                            <button type="submit" formnovalidate class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm font-semibold hover:bg-neutral-50" onclick="document.getElementById('form-action').value='exit'">Save & exit</button>
                        </div>
                        @if ($step < 6)
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3.5 text-sm font-bold text-white hover:bg-[#001A48]" onclick="document.getElementById('form-action').value='continue'">
                                Save & continue <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                            </button>
                        @else
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3.5 text-sm font-bold text-white hover:bg-[#001A48]" onclick="document.getElementById('form-action').value='submit'">
                                Submit application <i data-lucide="check" style="width:16px;height:16px;"></i>
                            </button>
                        @endif
                    </div>
                </form>
            </section>

            <aside class="relative hidden min-h-full overflow-hidden lg:block">
                <img src="{{ $sideImage }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#07111f]/90 via-[#07111f]/35 to-transparent"></div>
                <div class="absolute inset-x-0 bottom-0 p-8 text-white">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-white/70">Qualified matches</p>
                    <p class="mt-3 text-2xl font-black leading-snug">Subjects, marks, and weekly availability help us match the right learners later.</p>
                </div>
            </aside>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        const subjectsUrl = @json($subjectsUrl);
        const levelOptions = @json($levelOptions);
        const dayOptions = @json($dayOptions);

        const imageInput = document.getElementById('profile_image');
        const imagePreview = document.getElementById('tutor-image-preview');
        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', () => {
                const file = imageInput.files?.[0];
                if (!file) return;
                imagePreview.src = URL.createObjectURL(file);
                imagePreview.classList.remove('opacity-40');
            });
        }

        const attended = document.getElementById('attended_university');
        const universityFields = document.getElementById('university-fields');
        if (attended && universityFields) {
            const syncUniversity = () => universityFields.classList.toggle('hidden', !attended.checked);
            attended.addEventListener('change', syncUniversity);
            syncUniversity();
        }

        const syllabus = document.getElementById('high_school_syllabus');
        const syllabusOther = document.getElementById('syllabus-other');
        syllabus?.addEventListener('change', () => syllabusOther?.classList.toggle('hidden', syllabus.value !== 'other'));

        const heardFrom = document.getElementById('heard_from');
        const heardFromOther = document.getElementById('heard-from-other');
        heardFrom?.addEventListener('change', () => heardFromOther?.classList.toggle('hidden', heardFrom.value !== 'other'));

        const whatsappSame = document.getElementById('whatsapp_same_as_phone');
        const whatsappField = document.getElementById('whatsapp-field');
        if (whatsappSame && whatsappField) {
            const syncWhatsapp = () => whatsappField.classList.toggle('hidden', whatsappSame.checked);
            whatsappSame.addEventListener('change', syncWhatsapp);
            syncWhatsapp();
        }

        const initSearchableSelect = (root) => {
            if (!root || root.dataset.bound === '1') return;
            root.dataset.bound = '1';

            const input = root.querySelector('.js-searchable-input');
            const results = root.querySelector('.js-searchable-results');
            const idInput = root.querySelector('.js-searchable-id');
            const isOtherInput = root.querySelector('.js-searchable-is-other');
            const otherWrap = root.querySelector('.js-searchable-other');
            const otherInput = root.querySelector('.js-searchable-other-input');
            let timer = null;

            const setOther = (enabled, label = '') => {
                if (isOtherInput) isOtherInput.value = enabled ? '1' : '0';
                if (otherWrap) otherWrap.classList.toggle('hidden', !enabled);
                if (enabled) {
                    idInput.value = '';
                    if (label && otherInput && !otherInput.value) otherInput.value = label;
                } else if (otherInput) {
                    otherInput.value = '';
                }
            };

            const render = (items, includeOther) => {
                const rows = [...items];
                if (includeOther) rows.push({ id: 'other', label: 'Other (type the name)' });
                results.innerHTML = rows.map((item) => `
                    <button type="button" class="block w-full px-4 py-2.5 text-left text-sm font-semibold hover:bg-[#F3F7FC]" data-id="${item.id}" data-label="${item.label.replace(/"/g, '&quot;')}">${item.label}</button>
                `).join('');
                results.classList.toggle('hidden', rows.length === 0);
            };

            const fetchResults = async () => {
                const url = new URL(root.dataset.searchUrl, window.location.origin);
                url.searchParams.set('q', input.value.trim());
                const dependsOn = root.dataset.dependsOn;
                if (dependsOn) {
                    const dependency = document.querySelector(`[name="${dependsOn}"]`);
                    if (dependency?.value) url.searchParams.set(dependsOn, dependency.value);
                }
                const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                const payload = await response.json();
                render(payload.results || [], Boolean(payload.other));
            };

            input.addEventListener('input', () => {
                idInput.value = '';
                setOther(false);
                clearTimeout(timer);
                timer = setTimeout(fetchResults, 220);
            });

            input.addEventListener('focus', () => {
                clearTimeout(timer);
                timer = setTimeout(fetchResults, 80);
            });

            results.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-id]');
                if (!button) return;
                const id = button.dataset.id;
                const label = button.dataset.label || '';
                if (id === 'other') {
                    setOther(true, label.startsWith('Other') ? '' : label);
                    input.value = 'Other';
                } else {
                    setOther(false);
                    idInput.value = id;
                    input.value = label;
                    if (otherInput) otherInput.value = '';
                }
                results.classList.add('hidden');
                idInput.dispatchEvent(new Event('change', { bubbles: true }));
            });

            document.addEventListener('click', (event) => {
                if (!root.contains(event.target)) results.classList.add('hidden');
            });
        };

        document.querySelectorAll('.js-searchable-select').forEach(initSearchableSelect);

        const bindRemove = (container, selector) => {
            container?.querySelectorAll(selector).forEach((button) => {
                button.onclick = () => {
                    if (container.querySelectorAll('.js-dynamic-row').length <= 1) return;
                    button.closest('.js-dynamic-row')?.remove();
                };
            });
        };

        const markRows = document.getElementById('mark-rows');
        document.getElementById('add-mark-row')?.addEventListener('click', () => {
            const index = markRows.querySelectorAll('.js-dynamic-row').length;
            const levelHtml = Object.entries(levelOptions).map(([value, label], i) => `<option value="${value}" ${i === 0 ? 'selected' : ''}>${label}</option>`).join('');
            const wrap = document.createElement('div');
            wrap.className = 'js-dynamic-row grid gap-3 rounded-xl border border-neutral-200 p-4';
            wrap.innerHTML = `
                <div class="js-searchable-select" data-search-url="${subjectsUrl}">
                    <input type="hidden" name="marks[${index}][subject_id]" class="js-searchable-id" value="">
                    <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Subject</label>
                    <div class="relative">
                        <input type="search" class="js-searchable-input w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]" placeholder="Search subjects…" autocomplete="off">
                        <div class="js-searchable-results absolute z-20 mt-1 hidden max-h-64 w-full overflow-auto rounded-xl border border-neutral-200 bg-white shadow-lg"></div>
                    </div>
                    <div class="js-searchable-other mt-3 hidden">
                        <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Other subject</label>
                        <input type="text" name="marks[${index}][subject_other]" class="js-searchable-other-input w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-[0.7fr_0.7fr_1fr_auto]">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Mark %</label>
                        <input type="number" min="0" max="100" name="marks[${index}][mark]" required class="w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Year</label>
                        <input type="number" min="1980" max="{{ (int) date('Y') + 1 }}" name="marks[${index}][year]" class="w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Level</label>
                        <select name="marks[${index}][level]" class="w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">${levelHtml}</select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="js-remove-row inline-flex h-11 w-11 items-center justify-center rounded-xl border border-neutral-300 text-neutral-600 hover:bg-neutral-50" aria-label="Remove"><i data-lucide="trash-2" style="width:16px;height:16px;"></i></button>
                    </div>
                </div>`;
            markRows.appendChild(wrap);
            initSearchableSelect(wrap.querySelector('.js-searchable-select'));
            if (window.lucide) window.lucide.createIcons();
            bindRemove(markRows, '.js-remove-row');
        });
        bindRemove(markRows, '.js-remove-row');

        const subjectRowsEl = document.getElementById('subject-rows');
        document.getElementById('add-subject-row')?.addEventListener('click', () => {
            const index = subjectRowsEl.querySelectorAll('.js-dynamic-row').length;
            const levelHtml = Object.entries(levelOptions).map(([value, label], i) => `<option value="${value}" ${i === 0 ? 'selected' : ''}>${label}</option>`).join('');
            const wrap = document.createElement('div');
            wrap.className = 'js-dynamic-row grid gap-3 rounded-xl border border-neutral-200 p-4';
            wrap.innerHTML = `
                <div class="js-searchable-select" data-search-url="${subjectsUrl}">
                    <input type="hidden" name="subjects[${index}][subject_id]" class="js-searchable-id" value="">
                    <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Subject offered</label>
                    <div class="relative">
                        <input type="search" class="js-searchable-input w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]" placeholder="Search subjects…" autocomplete="off">
                        <div class="js-searchable-results absolute z-20 mt-1 hidden max-h-64 w-full overflow-auto rounded-xl border border-neutral-200 bg-white shadow-lg"></div>
                    </div>
                    <div class="js-searchable-other mt-3 hidden">
                        <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Other subject</label>
                        <input type="text" name="subjects[${index}][subject_other]" class="js-searchable-other-input w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-[0.8fr_1fr_auto]">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Rate / hour</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-neutral-400">R</span>
                            <input type="number" min="50" max="5000" step="10" name="subjects[${index}][hourly_rate]" required class="w-full rounded-xl border border-neutral-300 py-2.5 pl-8 pr-3 outline-none focus:border-[#01225E]">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Level</label>
                        <select name="subjects[${index}][level]" required class="w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">${levelHtml}</select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="js-remove-row inline-flex h-11 w-11 items-center justify-center rounded-xl border border-neutral-300 text-neutral-600 hover:bg-neutral-50" aria-label="Remove"><i data-lucide="trash-2" style="width:16px;height:16px;"></i></button>
                    </div>
                </div>`;
            subjectRowsEl.appendChild(wrap);
            initSearchableSelect(wrap.querySelector('.js-searchable-select'));
            if (window.lucide) window.lucide.createIcons();
            bindRemove(subjectRowsEl, '.js-remove-row');
        });
        bindRemove(subjectRowsEl, '.js-remove-row');

        const availabilityRows = document.getElementById('availability-rows');
        document.getElementById('add-availability-row')?.addEventListener('click', () => {
            const index = availabilityRows.querySelectorAll('.js-dynamic-row').length;
            const dayHtml = Object.entries(dayOptions).map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
            const wrap = document.createElement('div');
            wrap.className = 'js-dynamic-row grid gap-3 rounded-xl border border-neutral-200 p-4 sm:grid-cols-[1.2fr_0.9fr_0.9fr_auto]';
            wrap.innerHTML = `
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Day</label>
                    <select name="availabilities[${index}][day_of_week]" required class="w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">${dayHtml}</select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">From</label>
                    <input type="time" name="availabilities[${index}][start_time]" value="09:00" required class="w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">To</label>
                    <input type="time" name="availabilities[${index}][end_time]" value="12:00" required class="w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">
                </div>
                <div class="flex items-end">
                    <button type="button" class="js-remove-row inline-flex h-11 w-11 items-center justify-center rounded-xl border border-neutral-300 text-neutral-600 hover:bg-neutral-50" aria-label="Remove"><i data-lucide="trash-2" style="width:16px;height:16px;"></i></button>
                </div>
                <input type="hidden" name="availabilities[${index}][is_available]" value="1">`;
            availabilityRows.appendChild(wrap);
            if (window.lucide) window.lucide.createIcons();
            bindRemove(availabilityRows, '.js-remove-row');
        });
        bindRemove(availabilityRows, '.js-remove-row');
    </script>
@endpush
