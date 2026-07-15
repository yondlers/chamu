@extends('layouts.app')

@section('title', 'Practice · Matric Hub')

@section('content')
    <main class="max-w-3xl mx-auto px-5 lg:px-8 py-10">
        <a href="{{ route('dashboard.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-500 hover:text-neutral-900">
            <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
            Dashboard
        </a>

        <section class="mt-8 rounded-2xl border border-neutral-200 bg-white p-8 soft-card">
            <p class="text-sm font-semibold text-[#E8425B]">{{ $quiz->subject_name }}</p>
            <h1 class="mt-2 text-3xl font-bold">{{ $quiz->title }}</h1>
            <p class="mt-3 text-neutral-600">You will answer {{ $questionCount }} {{ $questionCount === 1 ? 'question' : 'questions' }}, one screen at a time. Answers are shown at the end with your result.</p>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-semibold text-neutral-700">{{ $quiz->quiz_type === 'random' ? 'Randomized' : 'Source quiz' }}</span>
                @if ($quiz->source)
                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-semibold text-neutral-700">{{ $quiz->source }}</span>
                @endif
                <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-semibold text-neutral-700">{{ $quiz->started_at ? 'In progress' : 'Not started' }}</span>
            </div>

            <form method="POST" action="{{ route('practice.begin', $quiz->id) }}" class="mt-8">
                @csrf
                <button class="inline-flex items-center gap-2 rounded-xl bg-[#E8425B] px-5 py-3 font-semibold text-white">
                    {{ $quiz->started_at ? 'Continue quiz' : 'Start quiz' }}
                    <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                </button>
            </form>
        </section>
    </main>
@endsection
