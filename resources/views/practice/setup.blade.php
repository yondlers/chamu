@extends('layouts.app')

@section('title', 'Build Quiz · Matric Hub')

@section('content')
    <main class="max-w-5xl mx-auto px-5 lg:px-8 py-8">
        <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-500 hover:text-neutral-900">
            <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
            Back
        </a>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-6 soft-card">
            <p class="text-sm font-semibold text-[#E8425B]">Quiz setup</p>
            <h1 class="mt-1 text-3xl font-bold">{{ $subject->name }}</h1>
            <p class="mt-2 text-neutral-500">Choose the content pool, amount of questions, and optional duration before starting.</p>
        </section>

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="GET" action="{{ route('practice.setup') }}" class="mt-5 rounded-2xl border border-neutral-200 bg-white p-5">
            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
            <div class="grid gap-3 md:grid-cols-4">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-neutral-500">Mode</label>
                    <select name="quiz_type" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold">
                        <option value="random" @selected($quizType === 'random')>Random questions</option>
                        <option value="source" @selected($quizType === 'source')>Source quiz</option>
                        <option value="past" @selected($quizType === 'past')>Past questions</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-neutral-500">Paper</label>
                    <select name="paper_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold">
                        <option value="all">All papers</option>
                        @foreach ($papers as $paper)
                            <option value="{{ $paper->id }}" @selected((string) $paperId === (string) $paper->id)>Paper {{ $paper->number }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-neutral-500">Topic</label>
                    <select name="topic_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold">
                        <option value="all">All topics</option>
                        @foreach ($topics as $topic)
                            <option value="{{ $topic->id }}" @selected((string) $topicId === (string) $topic->id)>{{ $topic->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-neutral-500">Source</label>
                    <select name="source" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold">
                        <option value="">Choose source</option>
                        @foreach ($sources as $sourceName)
                            <option value="{{ $sourceName }}" @selected($source === $sourceName)>{{ $sourceName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-5 py-3 font-semibold hover:bg-neutral-50">
                    Refresh counts <i data-lucide="refresh-cw" style="width:16px;height:16px;"></i>
                </button>
            </div>
        </form>

        <section class="mt-5 grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Random Pool</p>
                <p class="mt-2 text-3xl font-bold">{{ $availableQuestionCount }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Selected Source</p>
                <p class="mt-2 text-3xl font-bold">{{ $sourceQuestionCount }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Past Questions</p>
                <p class="mt-2 text-3xl font-bold">{{ $pastQuestionCount }}</p>
            </div>
        </section>

        <form method="POST" action="{{ route('practice.store') }}" class="mt-5 rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
            @csrf
            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
            <input type="hidden" name="paper_id" value="{{ $paperId }}">
            <input type="hidden" name="topic_id" value="{{ $topicId }}">
            <input type="hidden" name="quiz_type" value="{{ $quizType }}">
            <input type="hidden" name="source" value="{{ $source }}">

            <div class="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-neutral-500">Question amount</label>
                    <input name="question_count" type="number" min="1" max="{{ max($selectedAvailableCount, 1) }}" value="{{ min(max($selectedAvailableCount, 1), 5) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold">
                    <p class="mt-2 text-xs font-semibold text-neutral-500">{{ $selectedAvailableCount }} available for this selection.</p>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-neutral-500">Duration minutes</label>
                    <input name="duration_minutes" type="number" min="1" max="300" value="30" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold">
                </div>
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#E8425B] px-5 py-3 font-semibold text-white hover:bg-[#d73550]" @disabled($selectedAvailableCount < 1)>
                    Start quiz <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                </button>
            </div>
        </form>
    </main>
@endsection
