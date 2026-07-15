@extends('layouts.app')

@section('title', 'Practice Results · Chamu')

@section('content')
    <main class="max-w-4xl mx-auto px-5 lg:px-8 py-10">
        <section class="rounded-2xl border border-neutral-200 bg-white p-8 soft-card">
            <p class="text-sm font-semibold text-[#01225E]">Quiz complete</p>
            <h1 class="mt-2 text-3xl font-bold">{{ $quiz->title }}</h1>
            <p class="mt-3 text-neutral-600">Score: <span class="font-bold text-neutral-950">{{ $quiz->score }}/{{ $quiz->total_marks }}</span> · {{ $quiz->percentage }}%</p>
            <p class="mt-2 text-sm text-neutral-500">This attempt is saved. You can review it again from Progress.</p>
        </section>

        <section class="mt-6 space-y-4">
            @foreach ($results as $result)
                <article class="rounded-2xl border border-neutral-200 bg-white p-5">
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="font-semibold">{{ $result['number'] }} · {{ $result['question'] }}</h2>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $result['is_correct'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                            {{ $result['is_correct'] ? 'Correct' : 'Review' }}
                        </span>
                    </div>
                    <p class="mt-3 text-sm text-neutral-600"><span class="font-semibold">Your answer:</span> {{ $result['selected_answer'] ?: 'No answer' }}</p>
                    <p class="mt-1 text-sm text-neutral-600"><span class="font-semibold">Correct answer:</span> {{ $result['correct_answer'] }}</p>
                </article>
            @endforeach
        </section>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('progress.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white">
                Back to Progress <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
            </a>
            <a href="{{ route('dashboard.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-5 py-3 font-semibold hover:bg-neutral-50">
                Dashboard
            </a>
        </div>
    </main>
@endsection
