@extends('layouts.app')

@section('title', 'APS · Matric Hub')

@section('content')
    <main class="mx-auto max-w-7xl px-5 py-8 lg:px-8">
        <section class="rounded-[28px] border border-neutral-200 bg-neutral-950 p-6 text-white soft-card lg:p-8">
            <div class="grid gap-6 lg:grid-cols-[1fr_380px] lg:items-center">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase text-white/70">
                        <i data-lucide="target" style="width:14px;height:14px;"></i>
                        APS and course match
                    </p>
                    <h1 class="mt-4 max-w-3xl text-4xl font-bold leading-tight">Find courses from an APS score</h1>
                    <p class="mt-3 max-w-3xl text-white/70">Enter an APS score to browse possible programmes. Sign in when you want the full subject-aware course match using your saved marks.</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('aps-calculator.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-bold text-neutral-950 hover:bg-neutral-100">
                            Calculate APS <i data-lucide="calculator" style="width:15px;height:15px;"></i>
                        </a>
                        @auth
                            <a href="{{ route('course-match.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 px-4 py-2 text-sm font-bold text-white hover:bg-white/10">
                                Full match <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 px-4 py-2 text-sm font-bold text-white hover:bg-white/10">
                                Save my marks <i data-lucide="user-plus" style="width:15px;height:15px;"></i>
                            </a>
                        @endauth
                    </div>
                </div>

                <form method="GET" action="{{ route('aps.index') }}" class="rounded-2xl border border-white/10 bg-white/10 p-4">
                    <label for="aps_score" class="mb-2 block text-xs font-bold uppercase text-white/60">Your APS score</label>
                    <input id="aps_score" name="aps_score" type="number" min="0" max="60" value="{{ $apsScore ?? '' }}" placeholder="e.g. 32" class="w-full rounded-xl border border-white/20 bg-white px-4 py-3 text-2xl font-bold text-neutral-950 outline-none focus:border-[#E8425B]">
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <input name="search" type="search" value="{{ $search }}" placeholder="Course keyword" class="rounded-xl border border-white/20 bg-white px-4 py-3 font-semibold text-neutral-950 outline-none focus:border-[#E8425B]">
                        <select name="university_id" class="rounded-xl border border-white/20 bg-white px-4 py-3 font-semibold text-neutral-950 outline-none focus:border-[#E8425B]">
                            <option value="">All universities</option>
                            @foreach ($universities as $university)
                                @php
                                    $universityLabel = $university->abbreviation && $university->abbreviation !== $university->name
                                        ? $university->abbreviation.' ('.$university->name.')'
                                        : $university->name;
                                @endphp
                                <option value="{{ $university->id }}" @selected((int) $filters['university_id'] === $university->id)>{{ $universityLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#E8425B] px-5 py-3 font-semibold text-white hover:bg-[#d73550]">
                        Find courses <i data-lucide="search" style="width:18px;height:18px;"></i>
                    </button>
                </form>
            </div>
        </section>

        @guest
            <section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">
                APS alone is a starting point. Create an account to match your actual subjects, marks, subject requirements, and progress.
            </section>
        @endguest

        <section class="mt-6 grid gap-4">
            @if ($apsScore === null)
                <article class="rounded-2xl border border-dashed border-neutral-300 bg-white p-8 text-center">
                    <h2 class="text-xl font-bold">Enter an APS score</h2>
                    <p class="mt-2 text-neutral-500">You can calculate one first, or type the score you already know.</p>
                    <a href="{{ route('aps-calculator.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#E8425B] px-5 py-3 font-semibold text-white">
                        Open calculator <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                    </a>
                </article>
            @else
                <div class="flex flex-col gap-2 rounded-2xl border border-neutral-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-semibold text-neutral-600">{{ $courses->count() }} courses found for APS {{ $apsScore }}</p>
                    <a href="{{ route('aps.index') }}" class="text-sm font-semibold text-[#E8425B] hover:underline">Reset</a>
                </div>

                @forelse ($courses as $course)
                    <article class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
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
                                    @auth
                                        <a href="{{ route('courses.show', $course->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                                            Details <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                                        </a>
                                        <a href="{{ route('course-match.index', ['university_id' => $course->university_id, 'search' => $course->name]) }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                                            Check my marks <i data-lucide="target" style="width:16px;height:16px;"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                                            Sign up for full match <i data-lucide="user-plus" style="width:16px;height:16px;"></i>
                                        </a>
                                    @endauth
                                </div>
                            </div>
                            <div class="grid min-w-full grid-cols-2 gap-2 sm:min-w-[260px]">
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
                        <p class="mt-2 text-neutral-500">Try a higher APS score or clear the filters.</p>
                    </article>
                @endforelse
            @endif
        </section>
    </main>
@endsection
