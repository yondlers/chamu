@extends('layouts.app')

@section('title', 'Content - Chamu')

@php
    $availableSubjects = $availableSubjects ?? collect();
    $contentStats = $contentStats ?? [
        'subjects' => 0,
        'questions' => 0,
        'papers' => 0,
        'curriculums' => 0,
    ];
    $featuredGuides = collect(config('chamu_guides.guides', []))
        ->take(3)
        ->map(fn (array $guide, string $slug) => (object) array_merge(['slug' => $slug], $guide))
        ->values();
    $modeLabel = match ($mode) {
        'learn' => 'Learn',
        'practice' => 'Practice',
        'exam' => 'Exam',
        default => 'All Modes',
    };
    $questionsBySource = $questions->groupBy(fn ($question) => $question->source ?: 'Unspecified source');
@endphp

@push('head')
    <x-seo-meta
        title="Study Content - Chamu"
        description="Explore Chamu study content by subject, paper, mode, and source for South African learners."
        :canonical="$subject ? request()->fullUrl() : route('content.index')"
    />
@endpush

@section('content')
    <main class="max-w-6xl mx-auto px-5 lg:px-8 py-10">
        <section class="mb-8">
            <p class="text-sm font-semibold text-[#01225E]">Available content</p>
            <h1 class="mt-1 text-3xl md:text-4xl font-bold tracking-normal">{{ $subject->name ?? 'Explore study content by subject' }}</h1>
            @if ($subject === null)
                <p class="mt-3 max-w-3xl text-base leading-7 text-neutral-600">Use Chamu to move from a broad subject choice into past-paper style questions, sources, practice modes, and guides. Start with a subject, then narrow the view by paper and learning mode.</p>
            @endif
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-semibold text-neutral-700">{{ $subject->curriculum_abbreviation ?? 'Curriculum' }}</span>
                <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-semibold text-neutral-700">{{ $subject->grade_name ?? 'Grade' }}</span>
                <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-semibold text-neutral-700">{{ $paper ? 'Paper ' . $paper->number : 'All Papers' }}</span>
                <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-semibold text-neutral-700">{{ $modeLabel }}</span>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-[#01225E]">{{ $questions->count() }} grouped questions</span>
            </div>
        </section>

        @if ($subject === null)
            <section class="grid gap-4 md:grid-cols-4">
                <div class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase text-neutral-500">Subjects</p>
                    <p class="mt-2 text-3xl font-black text-neutral-950">{{ number_format($contentStats['subjects']) }}</p>
                </div>
                <div class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase text-neutral-500">Questions</p>
                    <p class="mt-2 text-3xl font-black text-neutral-950">{{ number_format($contentStats['questions']) }}</p>
                </div>
                <div class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase text-neutral-500">Papers</p>
                    <p class="mt-2 text-3xl font-black text-neutral-950">{{ number_format($contentStats['papers']) }}</p>
                </div>
                <div class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase text-neutral-500">Curriculums</p>
                    <p class="mt-2 text-3xl font-black text-neutral-950">{{ number_format($contentStats['curriculums']) }}</p>
                </div>
            </section>

            <section class="mt-8 rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-neutral-950">Start with a subject</h2>
                        <p class="mt-2 text-sm leading-6 text-neutral-600">These subjects are available for browsing. Pick one to see grouped questions, sources, papers, and practice options.</p>
                    </div>
                    <a href="{{ route('learn.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E]">
                        Use the full learner search <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                    </a>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($availableSubjects as $availableSubject)
                        <a href="{{ route('content.index', [
                            'subject_id' => $availableSubject->id,
                            'curriculum_id' => request('curriculum_id'),
                            'grade_id' => request('grade_id'),
                        ]) }}" class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 hover:border-neutral-300 hover:bg-white">
                            <div class="flex items-start justify-between gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-white" style="background: {{ $availableSubject->colour ?: '#01225E' }};">
                                    <i data-lucide="{{ $availableSubject->icon ?: 'book-open' }}" style="width:18px;height:18px;"></i>
                                </span>
                                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-neutral-600">{{ number_format($availableSubject->question_count) }} questions</span>
                            </div>
                            <h3 class="mt-4 font-bold text-neutral-950">{{ $availableSubject->name }}</h3>
                            <p class="mt-1 text-sm text-neutral-500">{{ $availableSubject->curriculum_abbreviation ?: 'Curriculum' }} - {{ $availableSubject->grade_name ?: 'Grade' }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-neutral-500">No public subjects are available yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="mt-8 grid gap-5 lg:grid-cols-[1fr_320px]">
                <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-bold text-neutral-950">How to use the library</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <div>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-[#01225E]">
                                <i data-lucide="search" style="width:20px;height:20px;"></i>
                            </span>
                            <h3 class="mt-3 font-bold">Choose a subject</h3>
                            <p class="mt-2 text-sm leading-6 text-neutral-600">Start broad, then filter by paper and source when you know what you need.</p>
                        </div>
                        <div>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                <i data-lucide="layers" style="width:20px;height:20px;"></i>
                            </span>
                            <h3 class="mt-3 font-bold">Review grouped questions</h3>
                            <p class="mt-2 text-sm leading-6 text-neutral-600">Grouped questions make it easier to practise by source, topic, or past-paper style.</p>
                        </div>
                        <div>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                                <i data-lucide="target" style="width:20px;height:20px;"></i>
                            </span>
                            <h3 class="mt-3 font-bold">Practise with intent</h3>
                            <p class="mt-2 text-sm leading-6 text-neutral-600">Logged-in learners can turn selected content into practice sessions.</p>
                        </div>
                    </div>
                </article>

                <aside class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-bold text-neutral-950">Helpful guides</h2>
                    <div class="mt-4 grid gap-3">
                        @foreach ($featuredGuides as $featuredGuide)
                            <a href="{{ route('guides.show', ['guide' => $featuredGuide->slug]) }}" class="rounded-lg border border-neutral-200 p-3 hover:bg-neutral-50">
                                <span class="text-xs font-bold uppercase text-[#01225E]">{{ $featuredGuide->minutes }} min read</span>
                                <h3 class="mt-1 font-bold text-neutral-950">{{ $featuredGuide->title }}</h3>
                            </a>
                        @endforeach
                    </div>
                </aside>
            </section>
        @elseif ($questions->isEmpty())
            <section class="rounded-2xl border border-neutral-200 bg-white p-8 soft-card">
                <h2 class="text-xl font-bold">No content found</h2>
                <p class="mt-2 text-neutral-600">There are no seeded questions for this subject and paper combination yet.</p>
            </section>
        @else
            <div class="grid lg:grid-cols-[1fr_320px] gap-6 items-start">
                <section class="space-y-5">
                    @foreach ($questionsBySource as $source => $sourceQuestions)
                        <article class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-[#01225E]">Source</p>
                                    <h2 class="text-xl font-bold mt-1">{{ $source }}</h2>
                                    <p class="mt-2 text-sm text-neutral-500">{{ $sourceQuestions->count() }} grouped questions available.</p>
                                </div>
                                @auth
                                    <form method="GET" action="{{ route('practice.setup') }}">
                                        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                                        <input type="hidden" name="paper_id" value="{{ $paperId }}">
                                        <input type="hidden" name="quiz_type" value="source">
                                        <input type="hidden" name="source" value="{{ $source }}">
                                        <button class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-semibold text-white">
                                            Do this source <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-semibold text-white">Log in to practise</a>
                                @endauth
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2 border-t border-neutral-100 pt-4">
                                @foreach ($sourceQuestions->pluck('paper_number')->filter()->unique() as $paperNumber)
                                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600">Paper {{ $paperNumber }}</span>
                                @endforeach

                                @foreach ($sourceQuestions->pluck('topic_name')->filter()->unique()->take(3) as $topicName)
                                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600">{{ $topicName }}</span>
                                @endforeach

                                @if ($sourceQuestions->pluck('topic_name')->filter()->unique()->count() > 3)
                                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600">More topics</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </section>

                <aside class="lg:sticky lg:top-24 rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                    <span class="inline-flex w-10 h-10 items-center justify-center rounded-xl bg-blue-50 text-[#01225E] mb-4">
                        <i data-lucide="shuffle" style="width:20px;height:20px;"></i>
                    </span>
                    <h2 class="font-bold text-lg">Quick practice</h2>
                    <p class="mt-2 text-sm text-neutral-500">Build a random, source, or past-question quiz from this selection.</p>
                    @auth
                        <form method="GET" action="{{ route('practice.setup') }}" class="mt-5">
                            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                            <input type="hidden" name="paper_id" value="{{ $paperId }}">
                            <input type="hidden" name="quiz_type" value="random">
                            <button class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-4 py-3 text-sm font-semibold text-white">
                                Randomize practice <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                            </button>
                        </form>
                        <form method="GET" action="{{ route('practice.setup') }}" class="mt-3">
                            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                            <input type="hidden" name="paper_id" value="{{ $paperId }}">
                            <input type="hidden" name="quiz_type" value="past">
                            <button class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-4 py-3 text-sm font-semibold hover:bg-neutral-50">
                                Past questions <i data-lucide="files" style="width:16px;height:16px;"></i>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="mt-5 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-4 py-3 text-sm font-semibold text-white">Log in to start</a>
                    @endauth
                </aside>
            </div>
        @endif
    </main>
@endsection
