@extends('layouts.app')

@section('title', 'APS Course Match - Chamu')

@push('head')
    <x-seo-meta
        title="APS Course Match - Chamu"
        description="Use Chamu to compare your APS with captured South African university and college programmes, then explore public qualification requirements."
        :canonical="route('aps.index')"
    />
@endpush

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
    $totalCourses = $courses->total();
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
    $heroSlides = [
        ['src' => asset('images/aps/graduates-smiling.png'), 'position' => 'object-[center_38%]', 'delay' => 0],
        ['src' => asset('images/aps/engineering-workshop.png'), 'position' => 'object-[center_45%]', 'delay' => 7],
        ['src' => asset('images/aps/school-learners.png'), 'position' => 'object-[center_44%]', 'delay' => 14],
        ['src' => asset('images/aps/nursing-students.png'), 'position' => 'object-[center_42%]', 'delay' => 21],
        ['src' => asset('images/aps/uct-graduate.png'), 'position' => 'object-[center_46%]', 'delay' => 28],
        ['src' => asset('images/aps/graduation-group.png'), 'position' => 'object-[center_44%]', 'delay' => 35],
        ['src' => asset('images/aps/aps-calculation.png'), 'position' => 'object-[center_50%]', 'delay' => 42],
    ];
    $featuredUniversities = $selectedUniversities->isNotEmpty()
        ? $selectedUniversities
        : $universities->take(8);
    $sortOptions = [
        'default' => 'Default',
        'closing' => 'Closing date',
        'score' => 'Required score',
        'level' => 'Qualification level',
        'duration' => 'Duration',
    ];
    $sortLabel = $sortOptions[$sort] ?? 'Default';
    $courseQuery = function (?array $universityIds = null, ?string $searchValue = null, ?string $sortValue = null) use ($selectedUniversityIds, $search, $sort): array {
        $query = [];
        $resolvedUniversityIds = $universityIds ?? $selectedUniversityIds->all();

        if ($resolvedUniversityIds !== []) {
            $query['university_ids'] = array_values($resolvedUniversityIds);
        }

        if (filled($searchValue ?? $search)) {
            $query['search'] = $searchValue ?? $search;
        }

        $resolvedSort = $sortValue ?? $sort;
        if (filled($resolvedSort) && $resolvedSort !== 'default') {
            $query['sort'] = $resolvedSort;
        }

        return $query;
    };
@endphp

@push('styles')
    <style>
        .aps-hero-slide {
            opacity: 0;
            transform: scale(1.03);
            animation: aps-hero-fade 49s infinite;
            animation-delay: var(--aps-slide-delay, 0s);
        }

        @keyframes aps-hero-fade {
            0% { opacity: 0; transform: scale(1.03); }
            4% { opacity: 1; }
            15% { opacity: 1; }
            19% { opacity: 0; transform: scale(1.10); }
            100% { opacity: 0; transform: scale(1.10); }
        }

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
            .aps-hero-slide {
                animation: none;
                opacity: 0;
                transform: none;
            }

            .aps-hero-slide:first-child {
                opacity: 1;
            }

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
    <main class="bg-[#f5f7fb] pb-16 text-neutral-950">
        <section class="relative isolate bg-[#07111f] text-white">
            <div class="absolute inset-0 -z-10 overflow-hidden">
                @foreach ($heroSlides as $slide)
                    <img src="{{ $slide['src'] }}" alt="" class="aps-hero-slide absolute inset-0 h-full w-full object-cover {{ $slide['position'] }}" style="--aps-slide-delay: {{ $slide['delay'] }}s;">
                @endforeach
                <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(4,10,22,.96)_0%,rgba(4,10,22,.80)_45%,rgba(4,10,22,.42)_100%)]"></div>
                <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-[#f5f7fb] via-[#f5f7fb]/45 to-transparent"></div>
            </div>

            <div class="mx-auto max-w-7xl px-5 pb-10 pt-8 sm:pb-16 sm:pt-16 lg:px-8 lg:pb-20 lg:pt-20">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold uppercase text-white/85 backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-sky-300"></span>
                        APS match
                    </div>
                    <h1 class="mt-4 max-w-3xl text-3xl font-black leading-[1.02] text-white sm:mt-5 sm:text-6xl">
                        Find your Course
                    </h1>

                    <div class="mt-6 grid max-w-2xl grid-cols-3 divide-x divide-white/15 border-y border-white/15 py-3 sm:mt-8 sm:py-4">
                        <div class="pr-4">
                            <p class="text-xl font-black sm:text-2xl">{{ number_format($universities->count()) }}</p>
                            <p class="mt-1 text-xs font-bold uppercase text-white/55">University & Colleges</p>
                        </div>
                        <div class="px-4">
                            <p class="text-xl font-black sm:text-2xl">{{ number_format($qualificationCount) }}</p>
                            <p class="mt-1 text-xs font-bold uppercase text-white/55">Programmes</p>
                        </div>
                        <div class="pl-4">
                            <p class="text-xl font-black sm:text-2xl">{{ number_format($bursaryCount) }}</p>
                            <p class="mt-1 text-xs font-bold uppercase text-white/55">Bursaries</p>
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ route('aps.index') }}#search-results" class="mt-6 rounded-lg border border-white/15 bg-white p-3 text-neutral-950 shadow-[0_24px_70px_rgba(0,0,0,0.22)] sm:mt-8">
                    @if ($sort !== 'default')
                        <input type="hidden" name="sort" value="{{ $sort }}">
                    @endif
                    <div class="grid gap-2 lg:grid-cols-[1.35fr_1.15fr_auto]">
                        <div class="relative rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2.5 sm:px-4 sm:py-3" data-university-multiselect>
                            <label id="university_filter_label" class="flex items-center gap-1.5 text-xs font-black uppercase text-neutral-500">
                                <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                                Universities
                            </label>
                            <button type="button" aria-labelledby="university_filter_label" aria-expanded="false" class="mt-1.5 flex w-full items-center justify-between gap-3 bg-transparent text-left text-base font-black text-neutral-950 outline-none sm:mt-2" data-university-trigger>
                                <span class="min-w-0 truncate" data-university-summary>{{ $universityFilterLabel }}</span>
                                <i data-lucide="chevron-down" class="shrink-0 text-neutral-400" style="width:18px;height:18px;"></i>
                            </button>

                            <div class="absolute left-0 right-0 z-50 mt-4 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white text-neutral-950 shadow-2xl" data-university-panel>
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
                                            <span class="block text-xs font-semibold text-neutral-500">Every captured programme</span>
                                        </span>
                                    </button>
                                    @foreach ($universities as $university)
                                        @php
                                            $label = $universityLabel($university);
                                        @endphp
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

                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2.5 sm:px-4 sm:py-3">
                            <label for="search" class="flex items-center gap-1.5 text-xs font-black uppercase text-neutral-500">
                                <i data-lucide="search" style="width:14px;height:14px;"></i>
                                Course keyword
                            </label>
                            <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Engineering, medicine, accounting" class="mt-1.5 w-full bg-transparent text-base font-black text-neutral-950 outline-none placeholder:text-neutral-400 sm:mt-2">
                        </div>

                        <button class="inline-flex min-h-14 items-center justify-center gap-2 rounded-lg bg-[#01225E] px-6 text-base font-black text-white shadow-[0_12px_28px_rgba(1,34,94,0.28)] hover:bg-[#001A48] sm:min-h-[76px]">
                            Find courses <i data-lucide="search" style="width:18px;height:18px;"></i>
                        </button>
                    </div>

                    <div class="mt-3 border-t border-neutral-100 px-1 pt-3 text-xs font-bold text-neutral-500 sm:text-sm">
                        <span>{{ number_format($totalCourses) }} courses found{{ $selectedUniversityScopeLabel }}</span>
                    </div>
                </form>

                @if ($featuredUniversities->isNotEmpty())
                    <div class="no-scrollbar mt-4 flex gap-2 overflow-x-auto pb-1">
                        @foreach ($featuredUniversities as $university)
                            @php
                                $isSelectedFeaturedUniversity = $selectedUniversityIds->contains((int) $university->id);
                                $featuredUniversityClass = $isSelectedFeaturedUniversity
                                    ? 'border-sky-300 bg-sky-300 text-[#07111f]'
                                    : 'border-white/20 bg-white/10 text-white/80 hover:bg-white/20';
                            @endphp
                            <a
                                href="{{ route('aps.index', $courseQuery([$university->id])) }}#search-results"
                                class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-black transition {{ $featuredUniversityClass }}"
                            >
                                <span class="grid h-5 w-5 place-items-center overflow-hidden rounded-full bg-white/90 text-[10px] text-[#01225E]">
                                    @if ($university->logo)
                                        <img src="{{ asset($university->logo) }}" alt="" class="h-full w-full object-contain p-0.5">
                                    @else
                                        {{ $universityInitials($university) }}
                                    @endif
                                </span>
                                {{ $university->abbreviation ?: $university->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section id="search-results" tabindex="-1" class="mx-auto mt-8 scroll-mt-24 max-w-7xl px-5 focus:outline-none lg:px-8">
            <div class="mb-5 flex flex-col gap-3 rounded-lg border border-neutral-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-neutral-700">
                    <p class="inline-flex items-center gap-1.5 font-semibold">
                        <span class="font-black text-neutral-950">{{ number_format($totalCourses) }}</span>
                        results
                        <i data-lucide="info" class="text-neutral-400" style="width:14px;height:14px;" title="{{ number_format($totalCourses) }} courses found{{ $selectedUniversityScopeLabel }}"></i>
                    </p>
                    <span class="hidden h-4 w-px bg-neutral-200 sm:block" aria-hidden="true"></span>
                    <p class="font-semibold">
                        <span class="font-black text-neutral-950">{{ number_format($universities->count()) }}</span>
                        universities
                    </p>
                    <span class="hidden h-4 w-px bg-neutral-200 sm:block" aria-hidden="true"></span>
                    <p class="font-semibold">
                        <span class="font-black text-neutral-950">{{ number_format($qualificationCount) }}</span>
                        programmes
                    </p>
                </div>

                <div class="relative shrink-0" data-course-sort>
                    <button
                        type="button"
                        class="inline-flex w-full items-center justify-between gap-2 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm font-semibold text-neutral-800 hover:bg-neutral-100 sm:w-auto"
                        data-course-sort-trigger
                        aria-expanded="false"
                        aria-haspopup="listbox"
                    >
                        <span>
                            <span class="font-black text-neutral-950">Sort by:</span>
                            <span data-course-sort-label>{{ $sortLabel }}</span>
                        </span>
                        <i data-lucide="chevron-down" class="text-neutral-400" style="width:16px;height:16px;"></i>
                    </button>

                    <div class="absolute right-0 z-20 mt-2 hidden min-w-[14rem] overflow-hidden rounded-lg border border-neutral-200 bg-white py-1 shadow-xl" data-course-sort-panel role="listbox">
                        @foreach ($sortOptions as $value => $label)
                            <a
                                href="{{ route('aps.index', $courseQuery(null, $search ?: null, $value)) }}#search-results"
                                class="flex items-center justify-between gap-3 px-3 py-2 text-sm font-semibold hover:bg-neutral-50 {{ $sort === $value ? 'bg-neutral-50 text-[#01225E]' : 'text-neutral-700' }}"
                                data-course-sort-option
                                data-value="{{ $value }}"
                                role="option"
                                aria-selected="{{ $sort === $value ? 'true' : 'false' }}"
                            >
                                <span>{{ $label }}</span>
                                @if ($sort === $value)
                                    <i data-lucide="check" style="width:14px;height:14px;"></i>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <section class="grid gap-4">
                @forelse ($courses as $course)
                    @php
                        $logoSrc = null;

                        if ($course->university_logo) {
                            $logoSrc = Str::startsWith($course->university_logo, ['http://', 'https://', '/'])
                                ? $course->university_logo
                                : asset($course->university_logo);
                        }

                        $initials = $course->university_abbreviation
                            ?: Str::of($course->university_name)->substr(0, 3)->upper();
                        $publicCourseUrl = ($course->university_slug && $course->qualification_slug)
                            ? route('public.qualifications.show', [
                                'university' => $course->university_slug,
                                'qualification' => $course->qualification_slug,
                            ])
                            : null;
                    @endphp
                    <article class="group overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-[0_16px_45px_rgba(15,23,42,0.06)] transition hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-[0_22px_60px_rgba(15,23,42,0.10)]">
                        <div class="grid lg:grid-cols-[minmax(0,1fr)_320px]">
                            <div class="relative p-5 sm:p-6">
                                <div class="absolute inset-y-0 left-0 w-1.5 bg-sky-500"></div>
                                <div class="flex gap-4 pl-2">
                                    <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-lg border border-neutral-200 bg-neutral-50 text-sm font-black text-[#01225E]">
                                        @if ($logoSrc)
                                            <img src="{{ $logoSrc }}" alt="{{ $course->university_name }} logo" class="h-full w-full object-contain p-2">
                                        @else
                                            {{ $initials }}
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-[#01225E]">{{ $course->admission_score_badge }}</span>
                                            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-black text-neutral-700">{{ $course->qualification_type_name }}</span>
                                            @if ($course->is_selection_programme)
                                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">Selection programme</span>
                                            @endif
                                        </div>
                                        <h3 class="mt-3 text-xl font-black leading-tight text-neutral-950 sm:text-2xl">
                                            @if ($publicCourseUrl)
                                                <a href="{{ $publicCourseUrl }}" class="hover:text-[#01225E]">{{ $course->name }}</a>
                                            @else
                                                {{ $course->name }}
                                            @endif
                                        </h3>
                                        <p class="mt-1 text-sm font-bold text-neutral-500">
                                            {{ $course->university_abbreviation ?? $course->university_name }} · {{ $course->faculty_name }}
                                        </p>
                                        @if ($publicCourseUrl)
                                            <a href="{{ $publicCourseUrl }}" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-black text-neutral-950 hover:bg-neutral-50">
                                                View requirements <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <dl class="divide-y divide-neutral-200 border-t border-neutral-200 bg-neutral-50/70 p-5 lg:border-l lg:border-t-0">
                                <div class="flex items-start justify-between gap-4 py-3 first:pt-0">
                                    <dt class="flex items-center gap-2 text-xs font-black uppercase text-neutral-500">
                                        <i data-lucide="gauge" style="width:14px;height:14px;"></i>
                                        Required score
                                    </dt>
                                    <dd class="text-right">
                                        <span class="block text-2xl font-black text-neutral-950">{{ $course->admission_score_display }}</span>
                                        @if ($course->admission_score_display !== 'N/A' && $course->admission_score_label !== 'Score')
                                            <span class="mt-0.5 block text-xs font-bold uppercase text-neutral-500">{{ $course->admission_score_label }}</span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-3">
                                    <dt class="flex items-center gap-2 text-xs font-black uppercase text-neutral-500">
                                        <i data-lucide="clock-3" style="width:14px;height:14px;"></i>
                                        Duration
                                    </dt>
                                    <dd class="text-right text-lg font-black text-neutral-950">{{ $course->duration_years ? $course->duration_years . ' years' : 'N/A' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-3 last:pb-0">
                                    <dt class="flex items-center gap-2 text-xs font-black uppercase text-neutral-500">
                                        <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                                        University
                                    </dt>
                                    <dd class="max-w-[150px] text-right text-sm font-black text-neutral-950">{{ $course->university_abbreviation ?? $course->university_name }}</dd>
                                </div>
                            </dl>
                        </div>
                    </article>
                @empty
                    <article class="rounded-lg border border-dashed border-neutral-300 bg-white p-10 text-center shadow-sm">
                        <div class="mx-auto grid h-12 w-12 place-items-center rounded-lg bg-neutral-100 text-neutral-500">
                            <i data-lucide="search-x" style="width:22px;height:22px;"></i>
                        </div>
                        <h2 class="mt-4 text-xl font-black">No courses found</h2>
                        <p class="mt-2 text-sm font-semibold text-neutral-500">Try a different university or a broader keyword.</p>
                    </article>
                @endforelse
            </section>

            @if ($courses->hasPages())
                <div class="mt-6">
                    {{ $courses->links() }}
                </div>
            @endif
        </section>

        <section class="mx-auto mt-8 max-w-7xl px-4 sm:px-5 lg:px-8">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-bold text-[#01225E]">Universities in Chamu</p>
                    <h2 class="mt-1 text-2xl font-bold text-neutral-950">Browse every captured university</h2>
                </div>
                <p class="text-sm font-semibold text-neutral-500">Choose one to prefill the university filter.</p>
            </div>

            @if ($universities->isEmpty())
                <div class="rounded-lg border border-dashed border-neutral-300 bg-white p-6 text-center text-sm font-semibold text-neutral-500">
                    No universities have been added yet.
                </div>
            @else
                <div class="university-marquee overflow-hidden rounded-lg border border-neutral-200 bg-white py-4 shadow-sm">
                    <div class="university-marquee-track flex gap-3 px-4">
                        @foreach ([false, true] as $duplicate)
                            @foreach ($universities as $university)
                                <a href="{{ route('aps.index', ['university_ids' => [$university->id]]) }}#search-results" @if ($duplicate) aria-hidden="true" tabindex="-1" @endif class="flex w-64 shrink-0 items-center gap-3 rounded-lg border border-neutral-200 bg-white px-4 py-3 hover:border-[#01225E]/40 hover:bg-blue-50/50">
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-neutral-200 bg-white text-xs font-black text-[#01225E]">
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

        const searchResultsTarget = document.getElementById('search-results');

        if (window.location.hash === '#search-results' && searchResultsTarget) {
            window.requestAnimationFrame(() => {
                try {
                    searchResultsTarget.focus({ preventScroll: true });
                } catch (error) {
                    searchResultsTarget.focus();
                }
            });
        }

        (() => {
            const sort = document.querySelector('[data-course-sort]');
            if (! sort) return;

            const trigger = sort.querySelector('[data-course-sort-trigger]');
            const panel = sort.querySelector('[data-course-sort-panel]');
            if (! trigger || ! panel) return;

            const open = () => {
                panel.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');
            };
            const close = () => {
                panel.classList.add('hidden');
                trigger.setAttribute('aria-expanded', 'false');
            };

            trigger.addEventListener('click', () => {
                const isOpen = ! panel.classList.contains('hidden');
                if (isOpen) {
                    close();
                    return;
                }
                open();
            });

            document.addEventListener('click', (event) => {
                if (! sort.contains(event.target)) {
                    close();
                }
            });
        })();
    </script>
@endpush
