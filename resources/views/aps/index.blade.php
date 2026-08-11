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
    $selectedUniversityIds = collect($selectedUniversityIds ?? $filters['university_ids'] ?? [])
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->unique()
        ->values();
    $selectedFacultyIds = collect($selectedFacultyIds ?? [])
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->unique()
        ->values();
    $selectedQualificationTypeIds = collect($selectedQualificationTypeIds ?? [])
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->unique()
        ->values();
    $selectedFilters = collect($selectedFilters ?? []);
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
    $aps = isset($aps) && is_numeric($aps) ? (int) $aps : null;
    $aiAssisted = (bool) ($aiAssisted ?? false);
    $aiSummary = filled($aiSummary ?? null) ? (string) $aiSummary : null;
    $sortOptions = [
        'default' => 'Default',
        'closing' => 'Closing date',
        'score' => 'Required score',
        'level' => 'Qualification level',
        'duration' => 'Duration',
    ];
    $sortLabel = $sortOptions[$sort] ?? 'Default';
    $typeBadgeClass = [
        'university' => 'bg-sky-100 text-sky-800',
        'faculty' => 'bg-violet-100 text-violet-800',
        'qualification' => 'bg-amber-100 text-amber-800',
    ];
    $typeLabel = [
        'university' => 'University',
        'faculty' => 'Faculty',
        'qualification' => 'Qualification',
    ];
    $courseQuery = function (?array $tokens = null, ?string $searchValue = null, ?string $sortValue = null, mixed $apsValue = '__keep') use ($selectedFilters, $search, $sort, $aps): array {
        $query = [];
        $resolvedTokens = $tokens ?? $selectedFilters->pluck('token')->all();

        if ($resolvedTokens !== []) {
            $query['filter'] = array_values($resolvedTokens);
        }

        if (filled($searchValue ?? $search)) {
            $query['search'] = $searchValue ?? $search;
        }

        $resolvedSort = $sortValue ?? $sort;
        if (filled($resolvedSort) && $resolvedSort !== 'default') {
            $query['sort'] = $resolvedSort;
        }

        $resolvedAps = $apsValue === '__keep' ? $aps : $apsValue;
        if ($resolvedAps !== null && $resolvedAps !== '') {
            $query['aps'] = (int) $resolvedAps;
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
                </div>

                <form method="GET" action="{{ route('aps.index') }}#search-results" class="mt-6 rounded-lg border border-white/15 bg-white p-3 text-neutral-950 shadow-[0_24px_70px_rgba(0,0,0,0.22)] sm:mt-8" data-course-filter-form>
                    @if ($sort !== 'default')
                        <input type="hidden" name="sort" value="{{ $sort }}">
                    @endif
                    @if ($aps !== null)
                        <input type="hidden" name="aps" value="{{ $aps }}" data-course-aps-preserve>
                    @endif
                    <div class="grid gap-2 lg:grid-cols-[minmax(0,1fr)_auto]">
                        <div class="relative rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3" data-course-filter>
                            <label for="course-filter-input" class="flex items-center gap-1.5 text-xs font-black uppercase tracking-[0.14em] text-neutral-500">
                                <i data-lucide="search" style="width:14px;height:14px;"></i>
                                Search
                            </label>

                            <div class="mt-2 flex min-h-[28px] flex-wrap items-center gap-2" data-course-filter-control>
                                <div class="contents" data-course-filter-tags>
                                    @foreach ($selectedFilters as $selectedFilter)
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-2.5 py-1 text-xs font-black text-neutral-800"
                                            data-course-filter-tag
                                            data-token="{{ $selectedFilter['token'] }}"
                                            data-type="{{ $selectedFilter['type'] }}"
                                            data-value="{{ $selectedFilter['value'] }}"
                                            @if (! empty($selectedFilter['university_id'])) data-university-id="{{ $selectedFilter['university_id'] }}" @endif
                                            @if (! empty($selectedFilter['faculty_ids'])) data-faculty-ids="{{ implode(',', $selectedFilter['faculty_ids']) }}" @endif
                                        >
                                            <span class="rounded-full px-1.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] {{ $typeBadgeClass[$selectedFilter['type']] ?? 'bg-neutral-100 text-neutral-700' }}">
                                                {{ $typeLabel[$selectedFilter['type']] ?? 'Filter' }}
                                            </span>
                                            <span>{{ $selectedFilter['label'] }}</span>
                                            <button type="button" class="grid h-4 w-4 place-items-center rounded-full text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700" data-course-filter-remove aria-label="Remove {{ $selectedFilter['label'] }}">
                                                <i data-lucide="x" style="width:12px;height:12px;"></i>
                                            </button>
                                            <input type="hidden" name="filter[]" value="{{ $selectedFilter['token'] }}">
                                        </span>
                                    @endforeach
                                </div>

                                <input
                                    id="course-filter-input"
                                    name="search"
                                    type="search"
                                    value="{{ $search }}"
                                    autocomplete="off"
                                    placeholder="{{ $selectedFilters->isEmpty() ? 'University, faculty, or describe what you want to study' : 'Add a filter or describe your marks…' }}"
                                    class="min-w-[12rem] flex-1 bg-transparent text-base font-bold outline-none placeholder:text-neutral-400"
                                    data-course-filter-input
                                    aria-expanded="false"
                                    aria-controls="course-filter-panel"
                                    role="combobox"
                                >
                            </div>

                            <div id="course-filter-panel" class="absolute left-0 right-0 top-[calc(100%+0.5rem)] z-50 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white text-neutral-950 shadow-2xl" data-course-filter-panel>
                                <div class="max-h-80 overflow-y-auto p-2">
                                    <div data-course-filter-group data-group="university">
                                        <p class="px-3 py-2 text-[11px] font-black uppercase tracking-[0.16em] text-neutral-400">Universities</p>
                                        <p class="px-3 pb-2 text-xs font-semibold text-neutral-500">Leave empty for all universities</p>
                                        @foreach ($universities as $university)
                                            @php
                                                $token = $filterTypeUniversity.':'.$university->id;
                                                $isSelected = $selectedUniversityIds->contains((int) $university->id);
                                                $label = $university->abbreviation ?: $university->name;
                                            @endphp
                                            <button
                                                type="button"
                                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold hover:bg-neutral-50 {{ $isSelected ? 'bg-sky-50' : '' }}"
                                                data-course-filter-option
                                                data-index="{{ $filterTypeUniversity }}"
                                                data-type="university"
                                                data-value="{{ $university->id }}"
                                                data-label="{{ $label }}"
                                                data-token="{{ $token }}"
                                                data-search="{{ $university->name }} {{ $university->abbreviation }}"
                                                data-selected="{{ $isSelected ? 'true' : 'false' }}"
                                            >
                                                <span class="min-w-0">
                                                    <span class="block truncate text-neutral-950">{{ $label }}</span>
                                                    <span class="mt-0.5 block truncate text-xs font-semibold text-neutral-500">{{ $university->name }}</span>
                                                    <span class="mt-0.5 inline-flex rounded-full bg-sky-100 px-1.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] text-sky-800">University · {{ $filterTypeUniversity }}</span>
                                                </span>
                                                <i data-lucide="check" class="{{ $isSelected ? '' : 'hidden' }} shrink-0 text-sky-700" style="width:16px;height:16px;" data-course-filter-check></i>
                                            </button>
                                        @endforeach
                                    </div>

                                    <div class="mt-1 border-t border-neutral-100 pt-1" data-course-filter-group data-group="faculty">
                                        <p class="px-3 py-2 text-[11px] font-black uppercase tracking-[0.16em] text-neutral-400">Faculties</p>
                                        <p class="px-3 pb-2 text-xs font-semibold text-neutral-500 {{ $selectedUniversityIds->isNotEmpty() ? 'hidden' : '' }}" data-course-filter-locked data-locked-for="faculty">
                                            Select a university to unlock faculties
                                        </p>
                                        @foreach ($faculties as $faculty)
                                            @php
                                                $token = $filterTypeFaculty.':'.$faculty->id;
                                                $isSelected = $selectedFacultyIds->contains((int) $faculty->id);
                                                $label = ($faculty->university?->abbreviation ? $faculty->university->abbreviation.' · ' : '').$faculty->name;
                                                $isUnlocked = $selectedUniversityIds->contains((int) $faculty->university_id);
                                            @endphp
                                            <button
                                                type="button"
                                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold hover:bg-neutral-50 {{ $isSelected ? 'bg-violet-50' : '' }} {{ $isUnlocked ? '' : 'hidden' }}"
                                                data-course-filter-option
                                                data-index="{{ $filterTypeFaculty }}"
                                                data-type="faculty"
                                                data-value="{{ $faculty->id }}"
                                                data-label="{{ $label }}"
                                                data-token="{{ $token }}"
                                                data-search="{{ $faculty->name }} {{ $faculty->university?->abbreviation }} {{ $faculty->university?->name }}"
                                                data-university-id="{{ $faculty->university_id }}"
                                                data-requires="university"
                                                data-selected="{{ $isSelected ? 'true' : 'false' }}"
                                            >
                                                <span class="min-w-0">
                                                    <span class="block truncate text-neutral-950">{{ $label }}</span>
                                                    <span class="mt-0.5 inline-flex rounded-full bg-violet-100 px-1.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] text-violet-800">Faculty · {{ $filterTypeFaculty }}</span>
                                                </span>
                                                <i data-lucide="check" class="{{ $isSelected ? '' : 'hidden' }} shrink-0 text-violet-700" style="width:16px;height:16px;" data-course-filter-check></i>
                                            </button>
                                        @endforeach
                                    </div>

                                    <div class="mt-1 border-t border-neutral-100 pt-1" data-course-filter-group data-group="qualification">
                                        <p class="px-3 py-2 text-[11px] font-black uppercase tracking-[0.16em] text-neutral-400">Qualifications</p>
                                        <p class="px-3 pb-2 text-xs font-semibold text-neutral-500 {{ $selectedFacultyIds->isNotEmpty() ? 'hidden' : '' }}" data-course-filter-locked data-locked-for="qualification">
                                            Select a faculty to unlock qualification types
                                        </p>
                                        @foreach ($qualificationTypes as $type)
                                            @php
                                                $token = $filterTypeQualification.':'.$type->id;
                                                $isSelected = $selectedQualificationTypeIds->contains((int) $type->id);
                                                $facultyIdsForType = collect($qualificationTypeFacultyIds->get($type->id, []));
                                                $isUnlocked = $selectedFacultyIds->intersect($facultyIdsForType)->isNotEmpty();
                                            @endphp
                                            <button
                                                type="button"
                                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold hover:bg-neutral-50 {{ $isSelected ? 'bg-amber-50' : '' }} {{ $isUnlocked ? '' : 'hidden' }}"
                                                data-course-filter-option
                                                data-index="{{ $filterTypeQualification }}"
                                                data-type="qualification"
                                                data-value="{{ $type->id }}"
                                                data-label="{{ $type->name }}"
                                                data-token="{{ $token }}"
                                                data-search="{{ $type->name }}"
                                                data-faculty-ids="{{ $facultyIdsForType->implode(',') }}"
                                                data-requires="faculty"
                                                data-selected="{{ $isSelected ? 'true' : 'false' }}"
                                            >
                                                <span class="min-w-0">
                                                    <span class="block truncate text-neutral-950">{{ $type->name }}</span>
                                                    <span class="mt-0.5 inline-flex rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] text-amber-800">Qualification · {{ $filterTypeQualification }}</span>
                                                </span>
                                                <i data-lucide="check" class="{{ $isSelected ? '' : 'hidden' }} shrink-0 text-amber-700" style="width:16px;height:16px;" data-course-filter-check></i>
                                            </button>
                                        @endforeach
                                    </div>

                                    <p class="hidden px-3 py-2 text-sm font-semibold text-neutral-500" data-course-filter-empty>No matches</p>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="inline-flex min-h-[76px] items-center justify-center gap-2 rounded-lg bg-[#01225E] px-6 text-base font-black text-white shadow-[0_12px_28px_rgba(1,34,94,0.28)] hover:bg-[#001A48]">
                            Search <i data-lucide="search" style="width:18px;height:18px;"></i>
                        </button>
                    </div>

                    <div class="mt-3 flex flex-col gap-3 border-t border-neutral-100 px-1 pt-3 text-sm font-bold text-neutral-500 sm:flex-row sm:items-center sm:justify-between">
                        <span>{{ number_format($totalCourses) }} courses found{{ $selectedUniversityScopeLabel }}</span>
                        <a href="{{ route('aps.index') }}" class="inline-flex items-center gap-1.5 text-[#01225E] hover:text-[#001A48]">
                            Reset filters <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i>
                        </a>
                    </div>
                </form>

                @if ($featuredUniversities->isNotEmpty())
                    <div class="no-scrollbar mt-4 flex gap-2 overflow-x-auto pb-1" data-course-filter-pills>
                        @foreach ($featuredUniversities as $university)
                            @php
                                $universityToken = $filterTypeUniversity.':'.$university->id;
                                $isSelectedFeaturedUniversity = $selectedUniversityIds->contains((int) $university->id);
                                if ($isSelectedFeaturedUniversity) {
                                    $remainingFacultyIds = $selectedFilters
                                        ->where('type', 'faculty')
                                        ->reject(fn ($filter) => (int) ($filter['university_id'] ?? 0) === (int) $university->id)
                                        ->pluck('value')
                                        ->map(fn ($id) => (int) $id)
                                        ->values();
                                    $pillTokens = $selectedFilters
                                        ->reject(function ($filter) use ($universityToken, $university, $remainingFacultyIds) {
                                            if ($filter['token'] === $universityToken) {
                                                return true;
                                            }

                                            if (($filter['type'] ?? '') === 'faculty' && (int) ($filter['university_id'] ?? 0) === (int) $university->id) {
                                                return true;
                                            }

                                            if (($filter['type'] ?? '') === 'qualification') {
                                                return collect($filter['faculty_ids'] ?? [])->intersect($remainingFacultyIds)->isEmpty();
                                            }

                                            return false;
                                        })
                                        ->pluck('token')
                                        ->values()
                                        ->all();
                                } else {
                                    $pillTokens = $selectedFilters->pluck('token')->push($universityToken)->unique()->values()->all();
                                }
                                $featuredUniversityClass = $isSelectedFeaturedUniversity
                                    ? 'border-sky-300 bg-sky-300 text-[#07111f]'
                                    : 'border-white/20 bg-white/10 text-white/80 hover:bg-white/20';
                            @endphp
                            <a
                                href="{{ route('aps.index', $courseQuery($pillTokens)) }}#search-results"
                                data-course-filter-pill
                                data-token="{{ $universityToken }}"
                                data-type="university"
                                data-value="{{ $university->id }}"
                                data-label="{{ $university->abbreviation ?: $university->name }}"
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
            <div class="mb-5 flex flex-col gap-3 rounded-lg border border-neutral-200 bg-white px-4 py-3 shadow-sm lg:flex-row lg:items-center lg:justify-between">
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

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                    <form method="GET" action="{{ route('aps.index') }}#search-results" class="flex flex-wrap items-center gap-2" data-course-aps-filter>
                        @foreach ($selectedFilters as $selectedFilter)
                            <input type="hidden" name="filter[]" value="{{ $selectedFilter['token'] }}">
                        @endforeach
                        @if (filled($search))
                            <input type="hidden" name="search" value="{{ $search }}">
                        @endif
                        @if ($sort !== 'default')
                            <input type="hidden" name="sort" value="{{ $sort }}">
                        @endif
                        <label for="course-aps-input" class="text-sm font-black text-neutral-950">APS / Marks</label>
                        <input
                            id="course-aps-input"
                            type="number"
                            name="aps"
                            min="0"
                            max="100"
                            step="1"
                            value="{{ $aps }}"
                            placeholder="e.g. 28"
                            class="w-24 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm font-semibold text-neutral-800 outline-none focus:border-[#01225E]"
                            data-course-aps-input
                        >
                        <button type="submit" class="rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm font-semibold text-neutral-800 hover:bg-neutral-100">
                            Apply
                        </button>
                        @if ($aps !== null)
                            <a href="{{ route('aps.index', array_merge($courseQuery(null, $search ?: null, $sort, null), ['aps' => ''])) }}#search-results" class="text-sm font-semibold text-[#01225E] hover:text-[#001A48]">
                                Clear
                            </a>
                        @endif
                    </form>

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
            </div>

            @if ($aiSummary)
                <aside class="mb-5 rounded-lg border border-sky-200 bg-gradient-to-br from-sky-50 to-white px-4 py-4 shadow-sm sm:px-5" data-ai-search-summary>
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#01225E] text-white">
                            <i data-lucide="sparkles" style="width:18px;height:18px;"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.14em] text-[#01225E]">AI search assistance</p>
                            <p class="mt-1 text-sm font-semibold leading-relaxed text-neutral-700">{{ $aiSummary }}</p>
                        </div>
                    </div>
                </aside>
            @endif

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
                                <a href="{{ route('aps.index', $courseQuery([$filterTypeUniversity.':'.$university->id])) }}#search-results" @if ($duplicate) aria-hidden="true" tabindex="-1" @endif class="flex w-64 shrink-0 items-center gap-3 rounded-lg border border-neutral-200 bg-white px-4 py-3 hover:border-[#01225E]/40 hover:bg-blue-50/50">
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
        (() => {
            const root = document.querySelector('[data-course-filter]');
            const form = document.querySelector('[data-course-filter-form]');
            if (! root || ! form) return;

            const input = root.querySelector('[data-course-filter-input]');
            const panel = root.querySelector('[data-course-filter-panel]');
            const tags = root.querySelector('[data-course-filter-tags]');
            const empty = root.querySelector('[data-course-filter-empty]');
            const options = Array.from(root.querySelectorAll('[data-course-filter-option]'));
            const groups = Array.from(root.querySelectorAll('[data-course-filter-group]'));
            const pills = Array.from(document.querySelectorAll('[data-course-filter-pill]'));
            const badgeClasses = {
                university: 'bg-sky-100 text-sky-800',
                faculty: 'bg-violet-100 text-violet-800',
                qualification: 'bg-amber-100 text-amber-800',
            };
            const typeLabels = {
                university: 'University',
                faculty: 'Faculty',
                qualification: 'Qualification',
            };
            const selectedClass = {
                university: 'bg-sky-50',
                faculty: 'bg-violet-50',
                qualification: 'bg-amber-50',
            };

            const normalise = (value) => String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
            const refreshIcons = () => {
                if (window.lucide) window.lucide.createIcons();
            };
            const tagNodes = () => Array.from(tags.querySelectorAll('[data-course-filter-tag]'));
            const selectedTokens = () => tagNodes().map((tag) => tag.dataset.token);
            const selectedValues = (type) => tagNodes()
                .filter((tag) => tag.dataset.type === type)
                .map((tag) => String(tag.dataset.value));

            const open = () => {
                panel.classList.remove('hidden');
                input.setAttribute('aria-expanded', 'true');
                syncCascade();
                filterOptions();
            };
            const close = () => {
                panel.classList.add('hidden');
                input.setAttribute('aria-expanded', 'false');
            };

            const syncOptionState = (token, selected) => {
                const option = options.find((item) => item.dataset.token === token);
                if (! option) return;

                option.dataset.selected = selected ? 'true' : 'false';
                Object.values(selectedClass).forEach((className) => option.classList.remove(className));
                if (selected && selectedClass[option.dataset.type]) {
                    option.classList.add(selectedClass[option.dataset.type]);
                }
                option.querySelector('[data-course-filter-check]')?.classList.toggle('hidden', ! selected);
            };

            const syncPillState = (token, selected) => {
                const pill = pills.find((item) => item.dataset.token === token);
                if (! pill) return;

                pill.classList.toggle('border-sky-300', selected);
                pill.classList.toggle('bg-sky-300', selected);
                pill.classList.toggle('text-[#07111f]', selected);
                pill.classList.toggle('border-white/20', ! selected);
                pill.classList.toggle('bg-white/10', ! selected);
                pill.classList.toggle('text-white/80', ! selected);
                pill.classList.toggle('hover:bg-white/20', ! selected);
            };

            const updatePlaceholder = () => {
                input.placeholder = selectedTokens().length === 0
                    ? 'University, faculty, or describe what you want to study'
                    : 'Add a filter or describe your marks…';
            };

            const isExactIndexedMatch = (option, query) => {
                if (! query) return false;

                const label = normalise(option.dataset.label);
                const searchText = normalise(option.dataset.search);

                if (label === query || searchText === query) {
                    return true;
                }

                // Full multi-word name match against the start of data-search.
                if (
                    query.includes(' ')
                    && searchText.startsWith(query)
                    && (searchText.length === query.length || searchText.charAt(query.length) === ' ')
                ) {
                    return true;
                }

                // Short abbreviation-style tokens must equal the option label (e.g. "up").
                return query.length <= 6 && label === query;
            };

            const findExactOption = () => {
                const query = normalise(input.value);
                if (! query) return null;

                return options.find((option) => ! option.classList.contains('hidden') && isExactIndexedMatch(option, query));
            };

            const pruneDependentTags = () => {
                const universityIds = new Set(selectedValues('university'));
                const facultyIds = new Set(
                    tagNodes()
                        .filter((tag) => tag.dataset.type === 'faculty' && universityIds.has(String(tag.dataset.universityId)))
                        .map((tag) => String(tag.dataset.value))
                );
                const staleTokens = tagNodes()
                    .filter((tag) => {
                        if (tag.dataset.type === 'faculty') {
                            return ! universityIds.has(String(tag.dataset.universityId));
                        }

                        if (tag.dataset.type === 'qualification') {
                            const allowedFacultyIds = String(tag.dataset.facultyIds || '')
                                .split(',')
                                .filter(Boolean);

                            return ! allowedFacultyIds.some((id) => facultyIds.has(String(id)));
                        }

                        return false;
                    })
                    .map((tag) => tag.dataset.token);

                staleTokens.forEach((token) => removeTag(token, false));
            };
            const syncCascade = () => {
                const universityIds = new Set(selectedValues('university'));
                const facultyIds = new Set(selectedValues('faculty'));

                options.forEach((option) => {
                    let unlocked = true;

                    if (option.dataset.requires === 'university') {
                        unlocked = universityIds.has(String(option.dataset.universityId));
                    }

                    if (option.dataset.requires === 'faculty') {
                        const allowedFacultyIds = String(option.dataset.facultyIds || '')
                            .split(',')
                            .filter(Boolean);
                        unlocked = allowedFacultyIds.some((id) => facultyIds.has(String(id)));
                    }

                    option.dataset.unlocked = unlocked ? 'true' : 'false';
                    if (! unlocked && option.dataset.selected === 'true') {
                        removeTag(option.dataset.token, false);
                    }
                });

                root.querySelectorAll('[data-course-filter-locked]').forEach((lock) => {
                    const lockedFor = lock.dataset.lockedFor;
                    if (lockedFor === 'faculty') {
                        lock.classList.toggle('hidden', universityIds.size > 0);
                    }
                    if (lockedFor === 'qualification') {
                        lock.classList.toggle('hidden', facultyIds.size > 0);
                    }
                });
            };

            const addTag = (optionLike) => {
                const dataset = optionLike.dataset || optionLike;
                const token = dataset.token;
                if (! token || selectedTokens().includes(token)) return;

                const type = dataset.type || 'university';
                const tag = document.createElement('span');
                tag.className = 'inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-2.5 py-1 text-xs font-black text-neutral-800';
                tag.dataset.courseFilterTag = '';
                tag.dataset.token = token;
                tag.dataset.type = type;
                tag.dataset.value = dataset.value || '';
                if (dataset.universityId) tag.dataset.universityId = dataset.universityId;
                if (dataset.facultyIds) tag.dataset.facultyIds = dataset.facultyIds;

                tag.innerHTML = `
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] ${badgeClasses[type] || 'bg-neutral-100 text-neutral-700'}">${typeLabels[type] || 'Filter'}</span>
                    <span></span>
                    <button type="button" class="grid h-4 w-4 place-items-center rounded-full text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700" data-course-filter-remove aria-label="Remove filter">
                        <i data-lucide="x" style="width:12px;height:12px;"></i>
                    </button>
                    <input type="hidden" name="filter[]" value="">
                `;
                tag.querySelector('span:nth-child(2)').textContent = dataset.label || '';
                tag.querySelector('input[type="hidden"]').value = token;
                tags.appendChild(tag);

                syncOptionState(token, true);
                syncPillState(token, true);
                pruneDependentTags();
                syncCascade();
                updatePlaceholder();
                refreshIcons();
            };

            const removeTag = (token, cascade = true) => {
                const tag = tagNodes().find((item) => item.dataset.token === token);
                if (! tag) return;

                const type = tag.dataset.type;
                const universityId = tag.dataset.universityId;
                tag.remove();
                syncOptionState(token, false);
                syncPillState(token, false);

                if (cascade && type === 'university') {
                    tagNodes()
                        .filter((item) => item.dataset.type === 'faculty' && String(item.dataset.universityId) === String(universityId))
                        .forEach((item) => removeTag(item.dataset.token, false));
                    pruneDependentTags();
                }

                if (cascade) {
                    pruneDependentTags();
                    syncCascade();
                }

                updatePlaceholder();
            };

            const toggleOption = (option) => {
                const token = option.dataset.token;
                if (! token) return;

                if (selectedTokens().includes(token)) {
                    removeTag(token);
                    return;
                }

                if (option.dataset.requires && option.dataset.unlocked === 'false') {
                    return;
                }

                addTag(option);
            };

            const filterOptions = () => {
                const query = normalise(input.value);
                let visibleCount = 0;

                options.forEach((option) => {
                    const unlocked = option.dataset.unlocked !== 'false';
                    const haystack = normalise(option.dataset.search || option.dataset.label);
                    const matchesQuery = query === '' || haystack.includes(query);
                    const isVisible = unlocked && matchesQuery;
                    option.classList.toggle('hidden', ! isVisible);
                    if (isVisible) visibleCount += 1;
                });

                groups.forEach((group) => {
                    const hasVisible = Array.from(group.querySelectorAll('[data-course-filter-option]'))
                        .some((option) => ! option.classList.contains('hidden'));
                    const lock = group.querySelector('[data-course-filter-locked]');
                    const lockVisible = lock && ! lock.classList.contains('hidden');
                    group.classList.toggle('hidden', ! hasVisible && ! lockVisible);
                });

                empty.classList.toggle('hidden', visibleCount > 0 || groups.some((group) => {
                    const lock = group.querySelector('[data-course-filter-locked]');
                    return lock && ! lock.classList.contains('hidden') && ! group.classList.contains('hidden');
                }));
            };

            root.querySelector('[data-course-filter-control]')?.addEventListener('click', (event) => {
                if (event.target.closest('[data-course-filter-remove]')) return;
                open();
                input.focus();
            });

            input.addEventListener('focus', open);
            input.addEventListener('input', () => {
                open();
                filterOptions();
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    close();
                    return;
                }

                if (event.key === 'Backspace' && input.value === '') {
                    const current = selectedTokens();
                    const last = current[current.length - 1];
                    if (last) {
                        event.preventDefault();
                        removeTag(last);
                        filterOptions();
                    }
                    return;
                }

                if (event.key === 'Enter') {
                    // Only auto-select indexed options on exact matches.
                    // Free-text / natural-language queries must submit for Laravel (and AI when needed).
                    const option = findExactOption();
                    if (option && ! panel.classList.contains('hidden')) {
                        event.preventDefault();
                        toggleOption(option);
                        input.value = '';
                        filterOptions();
                    }
                }
            });

            options.forEach((option) => {
                option.addEventListener('click', () => {
                    toggleOption(option);
                    input.value = '';
                    filterOptions();
                    input.focus();
                });
            });

            tags.addEventListener('click', (event) => {
                const button = event.target.closest('[data-course-filter-remove]');
                if (! button) return;

                const tag = button.closest('[data-course-filter-tag]');
                if (! tag) return;

                event.preventDefault();
                removeTag(tag.dataset.token);
                filterOptions();
                input.focus();
            });

            pills.forEach((pill) => {
                pill.addEventListener('click', (event) => {
                    event.preventDefault();
                    const option = options.find((item) => item.dataset.token === pill.dataset.token);
                    if (option) {
                        toggleOption(option);
                    } else if (selectedTokens().includes(pill.dataset.token)) {
                        removeTag(pill.dataset.token);
                    } else {
                        addTag({
                            dataset: {
                                token: pill.dataset.token,
                                type: pill.dataset.type,
                                value: pill.dataset.value,
                                label: pill.dataset.label,
                            },
                        });
                    }

                    input.value = '';
                    form.requestSubmit();
                });
            });

            document.addEventListener('click', (event) => {
                if (! root.contains(event.target)) {
                    close();
                }
            });

            syncCascade();
            updatePlaceholder();
        })();

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
