@extends('layouts.app')

@section('title', 'Content · Chamu')

@php
    $modeLabel = match ($mode) {
        'learn' => 'Learn',
        'practice' => 'Practice',
        'exam' => 'Exam',
        default => 'All Modes',
    };
    $questionsBySource = $questions->groupBy(fn ($question) => $question->source ?: 'Unspecified source');
@endphp

@section('content')
    <main class="max-w-6xl mx-auto px-5 lg:px-8 py-10">
        <section class="mb-8">
            <p class="text-sm font-semibold text-[#01225E]">Available content</p>
            <h1 class="mt-1 text-3xl md:text-4xl font-bold tracking-normal">{{ $subject->name ?? 'No subject selected' }}</h1>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-semibold text-neutral-700">{{ $subject->curriculum_abbreviation ?? 'Curriculum' }}</span>
                <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-semibold text-neutral-700">{{ $subject->grade_name ?? 'Grade' }}</span>
                <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-semibold text-neutral-700">{{ $paper ? 'Paper ' . $paper->number : 'All Papers' }}</span>
                <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-semibold text-neutral-700">{{ $modeLabel }}</span>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-[#01225E]">{{ $questions->count() }} grouped questions</span>
            </div>
        </section>

        @if ($subject === null)
            <section class="rounded-2xl border border-neutral-200 bg-white p-8 soft-card">
                <h2 class="text-xl font-bold">Choose one of your subjects</h2>
                <p class="mt-2 text-neutral-600">This subject is not available for your profile or search filters.</p>
                <a href="{{ url('/') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white">
                    Back to search <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                </a>
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
