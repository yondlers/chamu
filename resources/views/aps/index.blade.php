@extends('layouts.app')

@section('title', 'APS · Chamu')

@php
    $selectedUniversityIds = collect($filters['university_ids'] ?? [])
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->unique()
        ->values();
    $universityLabel = function ($university) {
        if (! $university) {
            return 'All universities';
        }

        return $university->abbreviation && $university->abbreviation !== $university->name
            ? $university->abbreviation.' ('.$university->name.')'
            : $university->name;
    };
    $universityInitials = function ($university) {
        if ($university->abbreviation) {
            return $university->abbreviation;
        }

        return Str::of($university->name)->substr(0, 2)->upper();
    };
    $selectedUniversities = $universities
        ->filter(fn ($university) => $selectedUniversityIds->contains((int) $university->id))
        ->values();
    $previewCourses = $previewCourses ?? collect();
    $universityFilterLabel = match ($selectedUniversities->count()) {
        0 => 'All universities',
        1 => $universityLabel($selectedUniversities->first()),
        default => $selectedUniversities->count().' universities selected',
    };
    $selectedUniversityScopeLabel = match ($selectedUniversities->count()) {
        0 => '',
        1 => ' at '.$universityFilterLabel,
        default => ' across '.$selectedUniversities->count().' selected universities',
    };
    $apsRequiredMessage = $selectedUniversities->isNotEmpty()
        ? ($selectedUniversities->count() === 1
            ? 'Nice, now enter your APS to see courses at this university.'
            : 'Nice, now enter your APS to see courses at these universities.')
        : 'Enter your APS score first so Chamu can search matching courses.';
@endphp

@push('styles')
    <style>
        .university-marquee {
            mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
        }

        .university-marquee-track {
            animation: university-scroll 42s linear infinite;
            width: max-content;
        }

        .university-marquee:hover .university-marquee-track {
            animation-play-state: paused;
        }

        @keyframes university-scroll {
            from { transform: translateX(0); }
            to { transform: translateX(-50%); }
        }

        @media (prefers-reduced-motion: reduce) {
            .university-marquee {
                mask-image: none;
                overflow-x: auto;
            }

            .university-marquee-track {
                animation: none;
            }
        }
    </style>
@endpush

@section('content')
    <main class="bg-[#f8fafc] pb-16">
        <section class="border-b border-neutral-200" style="background: linear-gradient(180deg, #eef6ff 0%, #ffffff 58%, #f8fafc 100%);">
            <div class="mx-auto max-w-7xl px-4 py-7 sm:px-5 sm:py-9 lg:px-8">
                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-stretch">
                    <form method="GET" action="{{ route('aps.index') }}#search-results" class="rounded-[28px] border border-neutral-200 bg-white p-4 shadow-[0_18px_60px_rgba(15,23,42,0.10)] sm:p-5 lg:p-6">
                        <p class="inline-flex items-center gap-2 rounded-full bg-[#01225E]/10 px-3 py-1 text-xs font-bold uppercase text-[#01225E]">
                            <i data-lucide="target" style="width:14px;height:14px;"></i>
                            APS course finder
                        </p>
                        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h1 class="max-w-3xl text-3xl font-bold leading-tight tracking-normal text-neutral-950 sm:text-4xl">Find courses from your APS score</h1>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600 sm:text-base">Search programmes by APS, one or more universities, and keyword. Use the quick finder freely, then add an account only when you want subject-aware matching.</p>
                            </div>
                            <a href="{{ route('funding.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-800 hover:bg-emerald-100">
                                Funding <i data-lucide="badge-dollar-sign" style="width:16px;height:16px;"></i>
                            </a>
                        </div>

                        <div class="mt-5 grid gap-3 lg:grid-cols-[150px_minmax(0,1fr)_minmax(0,1fr)]">
                            <div>
                                <label for="aps_score" class="mb-2 flex items-center justify-between gap-2 text-xs font-bold uppercase text-neutral-500">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="gauge" style="width:14px;height:14px;"></i>
                                        APS score
                                    </span>
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black text-amber-800">Required first</span>
                                </label>
                                <input id="aps_score" name="aps_score" type="number" inputmode="numeric" min="0" max="60" value="{{ $apsScore ?? '' }}" placeholder="32" @class([
                                    'h-14 w-full rounded-2xl border bg-white px-4 text-2xl font-black text-neutral-950 outline-none transition',
                                    'border-amber-300 focus:border-amber-500 focus:ring-4 focus:ring-amber-100' => $apsScore === null,
                                    'border-neutral-300 focus:border-[#01225E] focus:ring-4 focus:ring-[#01225E]/10' => $apsScore !== null,
                                ])>
                            </div>

                            <div class="relative" data-university-multiselect>
                                <label id="university_filter_label" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                                    <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                                    Universities
                                </label>
                                <button type="button" aria-labelledby="university_filter_label" aria-expanded="false" class="flex h-14 w-full items-center justify-between gap-3 rounded-2xl border border-neutral-300 bg-white px-4 text-left text-sm font-bold text-neutral-950 outline-none transition hover:bg-neutral-50 focus:border-[#01225E] focus:ring-4 focus:ring-[#01225E]/10" data-university-trigger>
                                    <span class="min-w-0 truncate" data-university-summary>{{ $universityFilterLabel }}</span>
                                    <i data-lucide="chevron-down" class="shrink-0 text-neutral-400" style="width:18px;height:18px;"></i>
                                </button>

                                @if ($selectedUniversities->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach ($selectedUniversities as $university)
                                            <span class="rounded-full bg-[#01225E]/10 px-2.5 py-1 text-xs font-bold text-[#01225E]">{{ $university->abbreviation ?: $university->name }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="absolute left-0 right-0 z-30 mt-2 hidden overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-2xl" data-university-panel>
                                    <div class="border-b border-neutral-100 bg-white p-2">
                                        <input type="search" autocomplete="off" placeholder="Search university" class="h-11 w-full rounded-xl border border-neutral-200 px-3 text-sm font-semibold outline-none focus:border-[#01225E]" data-university-search>
                                        <div class="mt-2 flex items-center justify-between gap-3 px-1">
                                            <span class="text-xs font-bold text-neutral-500" data-university-count>{{ $selectedUniversities->count() }} selected</span>
                                            <button type="button" class="text-xs font-bold text-[#01225E] hover:underline" data-university-clear>Clear</button>
                                        </div>
                                    </div>
                                    <div class="max-h-80 overflow-y-auto p-2">
                                        <button type="button" class="mb-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold hover:bg-neutral-50" data-university-clear data-university-option data-search="all universities">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-xs font-black text-neutral-700">ALL</span>
                                            <span>
                                                <span class="block text-neutral-950">All universities</span>
                                                <span class="block text-xs font-semibold text-neutral-500">Search every captured programme</span>
                                            </span>
                                        </button>
                                        @foreach ($universities as $university)
                                            @php($label = $universityLabel($university))
                                            <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold hover:bg-neutral-50" data-university-option data-search="{{ $label }} {{ $university->name }} {{ $university->abbreviation }}">
                                                <input type="checkbox" name="university_ids[]" value="{{ $university->id }}" class="peer sr-only" data-university-checkbox data-label="{{ $label }}" @checked($selectedUniversityIds->contains((int) $university->id))>
                                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border border-neutral-300 bg-white text-white peer-checked:border-[#01225E] peer-checked:bg-[#01225E]">
                                                    <i data-lucide="check" style="width:14px;height:14px;"></i>
                                                </span>
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-neutral-200 bg-white text-xs font-black text-[#01225E]">
                                                    @if ($university->logo)
                                                        <img src="{{ asset($university->logo) }}" alt="{{ $university->name }} logo" class="h-full w-full object-contain p-1">
                                                    @else
                                                        {{ $universityInitials($university) }}
                                                    @endif
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block truncate text-neutral-950">{{ $university->abbreviation ?: $university->name }}</span>
                                                    <span class="block truncate text-xs font-semibold text-neutral-500">{{ $university->name }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                        <p class="hidden px-3 py-2 text-sm font-semibold text-neutral-500" data-university-empty>No universities found</p>
                                    </div>
                                    <div class="border-t border-neutral-100 bg-neutral-50 p-2">
                                        <button type="button" class="flex h-10 w-full items-center justify-center rounded-xl bg-[#01225E] text-sm font-bold text-white hover:bg-[#001A48]" data-university-done>Done</button>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="search" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                                    <i data-lucide="search" style="width:14px;height:14px;"></i>
                                    Course keyword
                                </label>
                                <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Engineering, medicine, accounting" class="h-14 w-full rounded-2xl border border-neutral-300 bg-white px-4 text-sm font-bold text-neutral-950 outline-none transition focus:border-[#01225E] focus:ring-4 focus:ring-[#01225E]/10">
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap gap-2 text-xs font-bold text-neutral-600">
                                <span class="rounded-full bg-neutral-100 px-3 py-1.5">{{ $universities->count() }} universities</span>
                                <span class="rounded-full bg-neutral-100 px-3 py-1.5">{{ number_format($qualificationCount) }} programmes</span>
                                <span class="rounded-full bg-neutral-100 px-3 py-1.5">{{ number_format($bursaryCount) }} bursaries</span>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('aps.index') }}" class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-4 py-3 text-sm font-bold text-neutral-700 hover:bg-neutral-50">Reset</a>
                                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-[#001A48]">
                                    Find courses <i data-lucide="search" style="width:18px;height:18px;"></i>
                                </button>
                            </div>
                        </div>

                        @if ($apsScore === null)
                            <div class="mt-4 flex flex-col gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900 sm:flex-row sm:items-center sm:justify-between">
                                <span>{{ $apsRequiredMessage }}</span>
                                <a href="{{ route('aps-calculator.index') }}" class="inline-flex items-center gap-2 text-[#01225E] hover:underline">
                                    Calculate APS <i data-lucide="calculator" style="width:16px;height:16px;"></i>
                                </a>
                            </div>
                        @else
                            <div class="mt-4 flex flex-col gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-900 sm:flex-row sm:items-center sm:justify-between">
                                <span>
                                    {{ $courses->count() }} courses found for APS {{ $apsScore }}
                                    {{ $selectedUniversityScopeLabel }}
                                </span>
                                <a href="#search-results" class="inline-flex items-center gap-2 text-[#01225E] hover:underline">
                                    View results <i data-lucide="arrow-down" style="width:16px;height:16px;"></i>
                                </a>
                            </div>
                        @endif
                    </form>

                    <aside class="grid gap-3">
                        <a href="{{ route('aps-calculator.index') }}" class="flex min-h-[150px] flex-col justify-between rounded-[28px] border border-neutral-200 bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.08)] hover:bg-neutral-50">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-[#01225E]">
                                <i data-lucide="calculator" style="width:22px;height:22px;"></i>
                            </span>
                            <span>
                                <span class="block text-lg font-bold text-neutral-950">Need your APS?</span>
                                <span class="mt-1 block text-sm font-semibold leading-5 text-neutral-500">Calculate it first, then come back to match programmes.</span>
                            </span>
                        </a>
                        <a href="{{ route('funding.index') }}" class="flex min-h-[150px] flex-col justify-between rounded-[28px] border border-emerald-200 bg-emerald-50 p-5 shadow-[0_18px_45px_rgba(16,185,129,0.12)] hover:bg-emerald-100">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-emerald-700">
                                <i data-lucide="badge-dollar-sign" style="width:22px;height:22px;"></i>
                            </span>
                            <span>
                                <span class="block text-lg font-bold text-neutral-950">Funding is ready too</span>
                                <span class="mt-1 block text-sm font-semibold leading-5 text-emerald-900/70">Explore bursaries alongside course options.</span>
                            </span>
                        </a>
                    </aside>
                </div>
            </div>
        </section>

        @if ($apsScore !== null)
            <section id="search-results" class="mx-auto mt-5 grid scroll-mt-24 max-w-7xl gap-4 px-4 sm:px-5 lg:px-8">
                <div class="flex flex-col gap-2 rounded-2xl border border-neutral-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-bold text-neutral-700">
                        {{ $courses->count() }} courses found for APS {{ $apsScore }}
                        {{ $selectedUniversityScopeLabel }}
                    </p>
                    <a href="{{ route('aps.index') }}" class="text-sm font-bold text-[#01225E] hover:underline">Reset search</a>
                </div>

                @forelse ($courses as $course)
                    <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">APS {{ $course->aps_required }}</span>
                                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-700">{{ $course->qualification_type_name }}</span>
                                    @if ($course->is_selection_programme)
                                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Selection programme</span>
                                    @endif
                                </div>
                                <h2 class="mt-3 text-xl font-bold text-neutral-950">{{ $course->name }}</h2>
                                <p class="mt-1 text-sm font-semibold text-neutral-500">
                                    {{ $course->university_abbreviation ?? $course->university_name }} · {{ $course->faculty_name }}
                                </p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <a href="{{ route('funding.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2 text-sm font-bold text-white hover:bg-[#001A48]">
                                        Check funding <i data-lucide="badge-dollar-sign" style="width:16px;height:16px;"></i>
                                    </a>
                                    @auth
                                        <a href="{{ route('course-match.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-bold hover:bg-neutral-50">
                                            Full subject match <i data-lucide="target" style="width:16px;height:16px;"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-bold hover:bg-neutral-50">
                                            Optional full match <i data-lucide="user-plus" style="width:16px;height:16px;"></i>
                                        </a>
                                    @endauth
                                </div>
                            </div>
                            <div class="grid min-w-full grid-cols-2 gap-2 sm:min-w-[280px]">
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                    <p class="text-xs font-bold uppercase text-neutral-500">Required APS</p>
                                    <p class="mt-1 text-2xl font-bold">{{ $course->aps_required }}</p>
                                </div>
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                    <p class="text-xs font-bold uppercase text-neutral-500">Duration</p>
                                    <p class="mt-1 text-2xl font-bold">{{ $course->duration_years ? $course->duration_years . 'y' : 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <article class="rounded-2xl border border-dashed border-neutral-300 bg-white p-8 text-center">
                        <h2 class="text-xl font-bold">No courses found</h2>
                        <p class="mt-2 text-neutral-500">Try a higher APS score, a different university, or a broader keyword.</p>
                    </article>
                @endforelse
            </section>
        @endif

        @guest
            <section class="mx-auto mt-6 max-w-7xl px-4 sm:px-5 lg:px-8">
                <div class="grid gap-4 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                    <div>
                        <p class="text-sm font-bold text-[#01225E]">No account needed for APS</p>
                        <h2 class="mt-1 text-xl font-bold text-neutral-950">Compare courses first, save marks later</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-neutral-600">Use APS and university filters freely. When you want subject-aware matching against your marks, you can save your profile then.</p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a href="{{ route('aps-calculator.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-bold text-neutral-900 hover:bg-neutral-50">
                            Calculate APS <i data-lucide="calculator" style="width:16px;height:16px;"></i>
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-bold text-neutral-900 hover:bg-neutral-50">
                            Save marks later <i data-lucide="user-plus" style="width:16px;height:16px;"></i>
                        </a>
                    </div>
                </div>
            </section>
        @endguest

        <section class="mx-auto mt-8 max-w-7xl px-4 sm:px-5 lg:px-8">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-bold text-[#01225E]">Universities in Chamu</p>
                    <h2 class="mt-1 text-2xl font-bold text-neutral-950">Browse every captured university</h2>
                </div>
                <p class="text-sm font-semibold text-neutral-500">Choose one to prefill the APS filter.</p>
            </div>

            @if ($universities->isEmpty())
                <div class="rounded-2xl border border-dashed border-neutral-300 bg-white p-6 text-center text-sm font-semibold text-neutral-500">
                    No universities have been added yet.
                </div>
            @else
                <div class="university-marquee overflow-hidden rounded-[28px] border border-neutral-200 bg-white py-4 shadow-sm">
                    <div class="university-marquee-track flex gap-3 px-4">
                        @foreach ([false, true] as $duplicate)
                            @foreach ($universities as $university)
                                <a href="{{ route('aps.index', ['university_ids' => [$university->id]]) }}" @if ($duplicate) aria-hidden="true" tabindex="-1" @endif class="flex w-64 shrink-0 items-center gap-3 rounded-2xl border border-neutral-200 bg-white px-4 py-3 hover:border-[#01225E]/40 hover:bg-blue-50/50">
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-neutral-200 bg-white text-xs font-black text-[#01225E]">
                                        @if ($university->logo)
                                            <img src="{{ asset($university->logo) }}" alt="{{ $university->name }} logo" class="h-full w-full object-contain p-1.5">
                                        @else
                                            {{ $universityInitials($university) }}
                                        @endif
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-black text-neutral-950">{{ $university->abbreviation ?: $university->name }}</span>
                                        <span class="mt-0.5 block truncate text-xs font-semibold text-neutral-500">{{ $university->name }}</span>
                                    </span>
                                </a>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        @if ($apsScore === null)
            <section id="search-results" class="mx-auto mt-6 grid scroll-mt-24 max-w-7xl gap-4 px-4 sm:px-5 lg:px-8">
                <article class="rounded-[28px] border border-neutral-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-center">
                        <div>
                            <p class="text-sm font-bold text-[#01225E]">{{ $previewCourses->isNotEmpty() ? 'Qualification preview' : 'Ready when you are' }}</p>
                            <h2 class="mt-1 text-2xl font-bold text-neutral-950">{{ $previewCourses->isNotEmpty() ? 'A quick look before APS' : 'APS is needed before course search' }}</h2>
                            <p class="mt-2 text-sm leading-6 text-neutral-600">
                                @if ($previewCourses->isNotEmpty())
                                    These sample programmes mix lower, middle and higher APS requirements{{ $selectedUniversityScopeLabel }}. Enter your APS to unlock the full course list, then log in when you want subject-aware matching.
                                @else
                                    Add your APS score above to search programmes. You can still choose universities and keywords first, then run the search once the score is in.
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <a href="#aps_score" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 text-sm font-bold text-white hover:bg-[#001A48]">
                                Enter APS to view more <i data-lucide="arrow-up" style="width:18px;height:18px;"></i>
                            </a>
                            <a href="{{ route('aps-calculator.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-5 py-3 text-sm font-bold text-neutral-900 hover:bg-neutral-50">
                                Open APS calculator <i data-lucide="calculator" style="width:18px;height:18px;"></i>
                            </a>
                        </div>
                    </div>
                </article>

                @if ($previewCourses->isNotEmpty())
                    <div class="grid gap-3">
                        @foreach ($previewCourses as $course)
                            <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-[#01225E]">Preview</span>
                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">APS {{ $course->aps_required }}</span>
                                            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-700">{{ $course->qualification_type_name }}</span>
                                            @if ($course->is_selection_programme)
                                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Selection programme</span>
                                            @endif
                                        </div>
                                        <h3 class="mt-3 text-lg font-bold text-neutral-950">{{ $course->name }}</h3>
                                        <p class="mt-1 text-sm font-semibold text-neutral-500">
                                            {{ $course->university_abbreviation ?? $course->university_name }} · {{ $course->faculty_name }}
                                        </p>
                                    </div>
                                    <div class="flex min-w-full flex-col gap-2 sm:min-w-[260px]">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                                <p class="text-xs font-bold uppercase text-neutral-500">Required APS</p>
                                                <p class="mt-1 text-2xl font-bold">{{ $course->aps_required }}</p>
                                            </div>
                                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                                <p class="text-xs font-bold uppercase text-neutral-500">Duration</p>
                                                <p class="mt-1 text-2xl font-bold">{{ $course->duration_years ? $course->duration_years . 'y' : 'N/A' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-2 sm:flex-row">
                                            <a href="#aps_score" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-4 py-2 text-sm font-bold text-white hover:bg-[#001A48]">
                                                View more with APS <i data-lucide="gauge" style="width:16px;height:16px;"></i>
                                            </a>
                                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-bold hover:bg-neutral-50">
                                                Log in for full match <i data-lucide="log-in" style="width:16px;height:16px;"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        @include('partials.adsense-home-placement', ['class' => 'mx-auto mt-6 max-w-7xl px-4 sm:px-5 lg:px-8'])
    </main>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-university-multiselect]').forEach((multiselect) => {
            const trigger = multiselect.querySelector('[data-university-trigger]');
            const panel = multiselect.querySelector('[data-university-panel]');
            const search = multiselect.querySelector('[data-university-search]');
            const summary = multiselect.querySelector('[data-university-summary]');
            const countLabel = multiselect.querySelector('[data-university-count]');
            const empty = multiselect.querySelector('[data-university-empty]');
            const done = multiselect.querySelector('[data-university-done]');
            const checkboxes = Array.from(multiselect.querySelectorAll('[data-university-checkbox]'));
            const options = Array.from(multiselect.querySelectorAll('[data-university-option]'));
            const clearButtons = Array.from(multiselect.querySelectorAll('[data-university-clear]'));

            const normalise = (value) => String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
            const open = () => {
                panel.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');
            };
            const close = () => {
                panel.classList.add('hidden');
                trigger.setAttribute('aria-expanded', 'false');
            };

            const filterOptions = () => {
                const query = normalise(search?.value);
                let visibleCount = 0;

                options.forEach((option) => {
                    const haystack = normalise(option.dataset.search || option.textContent);
                    const isVisible = query === '' || haystack.includes(query);
                    option.classList.toggle('hidden', ! isVisible);
                    visibleCount += isVisible ? 1 : 0;
                });

                empty.classList.toggle('hidden', visibleCount > 0);
            };

            const updateSummary = () => {
                const selected = checkboxes.filter((checkbox) => checkbox.checked);
                const selectedCount = selected.length;

                summary.textContent = selectedCount === 0
                    ? 'All universities'
                    : (selectedCount === 1 ? selected[0].dataset.label : `${selectedCount} universities selected`);
                countLabel.textContent = `${selectedCount} selected`;
            };

            const clearSelection = () => {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = false;
                });
                updateSummary();
            };

            trigger.addEventListener('click', () => {
                const isOpen = ! panel.classList.contains('hidden');

                if (isOpen) {
                    close();
                    return;
                }

                open();
                filterOptions();
                search?.focus();
            });

            search?.addEventListener('input', filterOptions);
            search?.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    close();
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', updateSummary);
            });

            clearButtons.forEach((button) => {
                button.addEventListener('click', clearSelection);
            });

            done?.addEventListener('click', close);

            document.addEventListener('click', (event) => {
                if (! multiselect.contains(event.target)) {
                    close();
                }
            });

            updateSummary();
        });
    </script>
@endpush
