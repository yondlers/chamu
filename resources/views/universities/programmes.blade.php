@extends('layouts.app')

@section('title', ($university->abbreviation ?: $university->name) . ' Programmes · Chamu')

@section('content')
    <main class="mx-auto max-w-7xl px-5 py-8 lg:px-8">
        <div class="mb-5 flex flex-wrap items-center gap-3 text-sm font-semibold text-neutral-500">
            <a href="{{ route('course-match.index') }}" class="inline-flex items-center gap-2 hover:text-neutral-900">
                <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                Course matches
            </a>
        </div>

        <section class="overflow-hidden rounded-[28px] border border-neutral-200 bg-neutral-950 text-white soft-card">
            <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[1fr_360px] lg:p-8">
                <div class="flex min-w-0 flex-col gap-5 sm:flex-row sm:items-start">
                    <div class="relative flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white p-3 text-neutral-950">
                        @if ($university->logo)
                            <img
                                src="{{ asset($university->logo) }}"
                                alt="{{ $university->name }} logo"
                                class="h-full w-full object-contain"
                                onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
                            >
                        @endif
                        <span @class(['text-2xl font-black', 'hidden' => $university->logo])>{{ $university->abbreviation ?: Str::of($university->name)->substr(0, 2)->upper() }}</span>
                    </div>

                    <div class="min-w-0">
                        <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase text-white/75">
                            <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                            University overview
                        </p>
                        <h1 class="mt-4 max-w-4xl text-3xl font-bold leading-tight sm:text-4xl">{{ $university->name }}</h1>
                        <p class="mt-3 max-w-3xl text-base text-white/70">
                            Browse programmes, compare faculties, and search the qualifications currently captured for {{ $university->abbreviation ?: $university->name }}.
                        </p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            @if ($university->website)
                                <a href="{{ $university->website }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-bold text-neutral-950 hover:bg-neutral-100">
                                    Website <i data-lucide="external-link" style="width:15px;height:15px;"></i>
                                </a>
                            @endif
                            <a href="{{ route('course-match.index', ['university_id' => $university->id]) }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 px-4 py-2 text-sm font-bold text-white hover:bg-white/10">
                                Match my marks <i data-lucide="target" style="width:15px;height:15px;"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <p class="text-xs font-bold uppercase text-white/55">Programmes</p>
                        <p class="mt-2 text-3xl font-bold">{{ number_format($stats['programmes']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <p class="text-xs font-bold uppercase text-white/55">Faculties</p>
                        <p class="mt-2 text-3xl font-bold">{{ number_format($stats['faculties']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <p class="text-xs font-bold uppercase text-white/55">Types</p>
                        <p class="mt-2 text-3xl font-bold">{{ number_format($stats['qualification_types']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <p class="text-xs font-bold uppercase text-white/55">Scores Listed</p>
                        <p class="mt-2 text-3xl font-bold">{{ number_format($listedScoreCount) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <form method="GET" action="{{ route('universities.programmes', $university->id) }}" class="mt-6 rounded-2xl border border-neutral-200 bg-white p-4 soft-card">
            <input type="hidden" name="page" value="1">
            <div class="grid gap-3 lg:grid-cols-[1.2fr_1fr_1fr_170px_auto]">
                <div>
                    <label for="search" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="search" style="width:14px;height:14px;"></i>
                        Search programmes
                    </label>
                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Programme, faculty, type" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#01225E]">
                </div>

                <div>
                    <label for="faculty_id" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="layers" style="width:14px;height:14px;"></i>
                        Faculty
                    </label>
                    <select id="faculty_id" name="faculty_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#01225E]">
                        <option value="">All faculties</option>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}" @selected((int) $filters['faculty_id'] === $faculty->id)>{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="qualification_type_id" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="award" style="width:14px;height:14px;"></i>
                        Type
                    </label>
                    <select id="qualification_type_id" name="qualification_type_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#01225E]">
                        <option value="">All types</option>
                        @foreach ($qualificationTypes as $type)
                            <option value="{{ $type->id }}" @selected((int) $filters['qualification_type_id'] === $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="per_page" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="list" style="width:14px;height:14px;"></i>
                        Per page
                    </label>
                    <select id="per_page" name="per_page" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#01225E]">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white hover:bg-[#001A48]">
                        Filter <i data-lucide="sliders-horizontal" style="width:18px;height:18px;"></i>
                    </button>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-2 border-t border-neutral-100 pt-4 text-sm font-semibold text-neutral-500 sm:flex-row sm:items-center sm:justify-between">
                <span>Showing {{ $qualifications->firstItem() ?? 0 }}-{{ $qualifications->lastItem() ?? 0 }} of {{ $qualifications->total() }} programmes</span>
                <a href="{{ route('universities.programmes', $university->id) }}" class="text-[#01225E] hover:underline">Reset filters</a>
            </div>
        </form>

        <section class="mt-6 grid gap-4">
            @forelse ($qualifications as $qualification)
                <article class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-700">{{ $qualification->qualification_type_name }}</span>
                                @if ($qualification->is_selection_programme)
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Selection programme</span>
                                @endif
                            </div>

                            <h2 class="mt-3 text-xl font-bold text-neutral-950">{{ $qualification->name }}</h2>
                            <p class="mt-1 text-sm font-semibold text-neutral-500">{{ $qualification->faculty_name }}</p>

                            @if ($qualification->notes)
                                <p class="mt-3 rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-600">{{ $qualification->notes }}</p>
                            @endif

                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ route('courses.show', $qualification->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                                    Details <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                                </a>
                                <a href="{{ route('course-match.index', ['university_id' => $university->id, 'faculty_id' => $qualification->faculty_id, 'search' => $qualification->name]) }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                                    Check fit <i data-lucide="target" style="width:16px;height:16px;"></i>
                                </a>
                            </div>
                        </div>

                        <div class="grid min-w-full grid-cols-2 gap-2 sm:min-w-[440px] sm:grid-cols-4">
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                <p class="text-xs font-bold uppercase text-neutral-500">{{ $qualification->admission_score_label }}</p>
                                <p class="mt-1 text-2xl font-bold">{{ $qualification->admission_score_display }}</p>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                <p class="text-xs font-bold uppercase text-neutral-500">Duration</p>
                                <p class="mt-1 text-2xl font-bold">{{ $qualification->duration_years ? $qualification->duration_years . 'y' : 'N/A' }}</p>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                <p class="text-xs font-bold uppercase text-neutral-500">Subjects</p>
                                <p class="mt-1 text-2xl font-bold">{{ number_format($qualification->subject_requirement_count) }}</p>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                <p class="text-xs font-bold uppercase text-neutral-500">Closes</p>
                                <p class="mt-1 text-sm font-bold">{{ $qualification->closing_label }}</p>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <section class="rounded-2xl border border-dashed border-neutral-300 bg-white p-8 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-500">
                        <i data-lucide="search-x" style="width:22px;height:22px;"></i>
                    </div>
                    <h2 class="mt-4 text-xl font-bold">No programmes found</h2>
                    <p class="mt-2 text-neutral-500">Try a different search term or clear the filters.</p>
                    <a href="{{ route('universities.programmes', $university->id) }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white">
                        Clear filters <i data-lucide="rotate-ccw" style="width:18px;height:18px;"></i>
                    </a>
                </section>
            @endforelse
        </section>

        @if ($qualifications->hasPages())
            <div class="mt-6 rounded-2xl border border-neutral-200 bg-white p-4">
                {{ $qualifications->onEachSide(1)->links() }}
            </div>
        @endif
    </main>
@endsection
