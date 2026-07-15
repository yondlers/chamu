@extends('layouts.app')

@section('title', 'Progress · Chamu')

@section('content')
    <main class="max-w-5xl mx-auto px-5 lg:px-8 py-10">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                <p class="text-sm font-semibold text-[#01225E]">Your learning history</p>
                <h1 class="text-3xl font-bold mt-1">Progress</h1>
                <p class="mt-2 text-neutral-500">Review completed quizzes and continue unfinished attempts.</p>
            </div>
            <a href="{{ route('dashboard.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                <i data-lucide="layout-dashboard" style="width:16px;height:16px;"></i>
                Dashboard
            </a>
        </div>

        <div class="grid gap-3 md:grid-cols-3 mb-8">
            <div class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <p class="text-xs font-bold uppercase text-neutral-500">Total Attempts</p>
                <p class="mt-2 text-3xl font-bold">{{ $attempts->count() }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <p class="text-xs font-bold uppercase text-neutral-500">Completed</p>
                <p class="mt-2 text-3xl font-bold">{{ $completedAttempts->count() }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <p class="text-xs font-bold uppercase text-neutral-500">Average Score</p>
                <p class="mt-2 text-3xl font-bold">{{ $averagePercentage === null ? '0%' : $averagePercentage . '%' }}</p>
            </div>
        </div>

        <section class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 class="font-bold text-xl">Quiz attempts</h2>
            </div>

            <div class="space-y-3">
                @forelse ($attempts as $attempt)
                    <article class="rounded-2xl border border-neutral-100 p-4">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $attempt->completed_at ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-[#01225E]' }}">
                                        {{ $attempt->completed_at ? 'Completed' : 'In progress' }}
                                    </span>
                                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600">{{ $attempt->quiz_type === 'random' ? 'Randomized' : 'Source' }}</span>
                                </div>
                                <h3 class="mt-3 font-bold">{{ $attempt->title }}</h3>
                                <p class="mt-1 text-sm text-neutral-500">
                                    {{ $attempt->subject_name ?? 'Subject' }}
                                    @if ($attempt->source)
                                        · {{ $attempt->source }}
                                    @endif
                                </p>
                                <p class="mt-1 text-xs text-neutral-400">Last updated {{ \Carbon\Carbon::parse($attempt->updated_at)->diffForHumans() }}</p>
                            </div>

                            <div class="flex items-center gap-3 md:justify-end">
                                @if ($attempt->completed_at)
                                    <div class="text-right">
                                        <p class="font-bold">{{ $attempt->score }}/{{ $attempt->total_marks }}</p>
                                        <p class="text-sm text-neutral-500">{{ $attempt->percentage }}%</p>
                                    </div>
                                    <a href="{{ route('practice.results', $attempt->id) }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-semibold text-white">
                                        Review <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                                    </a>
                                @else
                                    <a href="{{ route('practice.show', $attempt->id) }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-semibold text-white">
                                        Continue <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl bg-neutral-50 p-6 text-center">
                        <h3 class="font-bold">No quiz attempts yet</h3>
                        <p class="mt-2 text-sm text-neutral-500">Start a practice quiz and it will appear here.</p>
                        <a href="{{ url('/') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-semibold text-white">
                            Start learning <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                        </a>
                    </div>
                @endforelse
            </div>
        </section>
    </main>
@endsection
