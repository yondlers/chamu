@extends('layouts.app')

@section('title', $course->name . ' · Matric Hub')

@section('content')
    <main class="max-w-5xl mx-auto px-5 lg:px-8 py-8">
        <a href="{{ route('course-match.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-500 hover:text-neutral-900">
            <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
            Course matches
        </a>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-6 soft-card">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <a href="{{ route('universities.programmes', $course->university_id) }}" class="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-neutral-200 bg-white p-2 text-neutral-950 hover:bg-neutral-50">
                            @if ($course->university_logo)
                                <img
                                    src="{{ asset($course->university_logo) }}"
                                    alt="{{ $course->university_name }} logo"
                                    class="h-full w-full object-contain"
                                    onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
                                >
                            @endif
                            <span @class(['text-lg font-black', 'hidden' => $course->university_logo])>{{ $course->university_abbreviation ?? 'UNI' }}</span>
                        </a>
                        <div class="min-w-0">
                            <a href="{{ route('universities.programmes', $course->university_id) }}" class="text-sm font-semibold text-[#E8425B] hover:underline">{{ $course->university_abbreviation ?? $course->university_name }}</a>
                            <h1 class="mt-2 text-3xl font-bold">{{ $course->name }}</h1>
                            <p class="mt-2 text-neutral-500">{{ $course->faculty_name }} · {{ $course->qualification_type_name }}</p>
                        </div>
                    </div>
                    @if ($course->notes)
                        <p class="mt-4 rounded-xl bg-neutral-50 px-4 py-3 text-sm text-neutral-600">{{ $course->notes }}</p>
                    @endif
                </div>

                <div class="grid min-w-full grid-cols-2 gap-2 sm:min-w-[360px] sm:grid-cols-3">
                    <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                        <p class="text-xs font-bold uppercase text-neutral-500">{{ $admissionScoreLabel }}</p>
                        <p class="mt-1 text-2xl font-bold">{{ $admissionScoreDisplay }}</p>
                    </div>
                    <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                        <p class="text-xs font-bold uppercase text-neutral-500">Duration</p>
                        <p class="mt-1 text-2xl font-bold">{{ $course->duration_years ? $course->duration_years . 'y' : 'N/A' }}</p>
                    </div>
                    <div class="col-span-2 rounded-xl border border-neutral-200 bg-neutral-50 p-3 sm:col-span-1">
                        <p class="text-xs font-bold uppercase text-neutral-500">Closes</p>
                        <p class="mt-1 text-sm font-bold">{{ $closingLabel }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-6">
            <h2 class="text-xl font-bold">Subject Requirements</h2>
            <div class="mt-4 grid gap-3">
                @forelse ($requirements as $group)
                    <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                        <p class="text-sm font-bold text-neutral-950">
                            @if ($group->count() > 1)
                                One of
                            @else
                                Required
                            @endif
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($group as $requirement)
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-neutral-700">
                                    {{ $requirement->subject_name }}
                                    @if ($requirement->aps_level_required !== null)
                                        level {{ $requirement->aps_level_required }}
                                    @elseif ($requirement->minimum_mark !== null)
                                        {{ (int) $requirement->minimum_mark }}%
                                    @else
                                        required
                                    @endif
                                </span>
                            @endforeach
                        </div>
                        @foreach ($group->pluck('notes')->filter() as $note)
                            <p class="mt-2 text-sm text-neutral-500">{{ $note }}</p>
                        @endforeach
                    </div>
                @empty
                    <p class="text-sm text-neutral-500">No subject requirements listed.</p>
                @endforelse
            </div>
        </section>
    </main>
@endsection
