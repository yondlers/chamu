@extends('layouts.app')

@section('title', 'Dashboard · Matric Hub')

@section('content')
    <main class="max-w-6xl mx-auto px-5 lg:px-8 py-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-8">
            <div>
                <p class="text-sm font-semibold text-[#E8425B]">Your three paths</p>
                <h1 class="mt-1 text-3xl font-bold">Welcome back, {{ $user->first_name }}</h1>
                <p class="mt-2 text-neutral-500">Learn, APS, and Funding are the main journeys. Subjects, marks, and progress keep those journeys personal.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('aps.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#E8425B] px-4 py-2 font-semibold text-white">
                    APS <i data-lucide="target" style="width:16px;height:16px;"></i>
                </a>
                <a href="{{ route('funding.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                    Funding <i data-lucide="badge-dollar-sign" style="width:16px;height:16px;"></i>
                </a>
            </div>
        </div>

        <section class="mb-6 grid gap-4 md:grid-cols-3">
            <a href="{{ route('learn.index') }}" class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-[#E8425B]">
                    <i data-lucide="book-open" style="width:20px;height:20px;"></i>
                </span>
                <h2 class="mt-4 text-lg font-bold">Learn</h2>
                <p class="mt-2 text-sm text-neutral-500">Progress and quiz history help you continue past papers, questions, and notes.</p>
            </a>
            <a href="{{ route('course-match.index') }}" class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                    <i data-lucide="target" style="width:20px;height:20px;"></i>
                </span>
                <h2 class="mt-4 text-lg font-bold">APS</h2>
                <p class="mt-2 text-sm text-neutral-500">Subjects and marks power course match, APS history, and admission checks.</p>
            </a>
            <a href="{{ route('funding.index') }}" class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                    <i data-lucide="badge-dollar-sign" style="width:20px;height:20px;"></i>
                </span>
                <h2 class="mt-4 text-lg font-bold">Funding</h2>
                <p class="mt-2 text-sm text-neutral-500">Your saved marks help compare bursaries when subject requirements are listed.</p>
            </a>
        </section>

        <section class="grid gap-3 md:grid-cols-4">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Subjects</p>
                <p class="mt-2 text-3xl font-bold">{{ $selectedSubjects->count() }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">APS</p>
                <p class="mt-2 text-3xl font-bold">{{ $apsTotal }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Average</p>
                <p class="mt-2 text-3xl font-bold">{{ $averageMark ? number_format($averageMark, 1) : '0.0' }}%</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Open Quizzes</p>
                <p class="mt-2 text-3xl font-bold">{{ $pendingQuizzes->count() }}</p>
            </div>
        </section>

        @php
            $chartCount = $apsProgress->count();
            $chartWidth = max(720, max(1, $chartCount) * 96);
            $chartHeight = 280;
            $chartPaddingLeft = 52;
            $chartPaddingRight = 24;
            $chartPaddingTop = 24;
            $chartPaddingBottom = 58;
            $plotWidth = $chartWidth - $chartPaddingLeft - $chartPaddingRight;
            $plotHeight = $chartHeight - $chartPaddingTop - $chartPaddingBottom;
            $maxAps = max(42, (int) ($apsProgress->max('aps_total') ?? 0));
            $xStep = $chartCount > 1 ? $plotWidth / ($chartCount - 1) : 0;
            $chartPoints = $apsProgress->values()->map(function ($point, $index) use ($chartCount, $chartPaddingLeft, $chartPaddingTop, $plotHeight, $xStep, $plotWidth, $maxAps) {
                $x = $chartCount === 1 ? $chartPaddingLeft + ($plotWidth / 2) : $chartPaddingLeft + ($index * $xStep);
                $y = $chartPaddingTop + $plotHeight - (($point->aps_total / $maxAps) * $plotHeight);

                return [
                    'x' => round($x, 2),
                    'y' => round($y, 2),
                    'label' => $point->label,
                    'grade' => $point->grade_name,
                    'term' => $point->term_name,
                    'aps' => $point->aps_total,
                    'reported_subjects' => $point->reported_subjects,
                ];
            });
            $polylinePoints = $chartPoints->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ');
            $gridLines = collect([0, 10, 20, 30, 40, $maxAps])->unique()->sort()->values();
        @endphp

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold">APS progress</h2>
                    <p class="mt-1 text-sm text-neutral-500">Reported APS totals ordered by Term 1-4, then by the next grade.</p>
                </div>
                @if ($apsProgress->isNotEmpty())
                    <span class="inline-flex items-center gap-2 rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600">
                        <i data-lucide="trending-up" style="width:14px;height:14px;"></i>
                        {{ $chartCount }} reported {{ \Illuminate\Support\Str::plural('term', $chartCount) }}
                    </span>
                @endif
            </div>

            @if ($apsProgress->isEmpty())
                <div class="mt-5 rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-6 text-center">
                    <p class="font-bold">No APS history yet</p>
                    <p class="mt-1 text-sm text-neutral-500">Add marks for your terms and your APS trend will appear here.</p>
                    <a href="{{ route('marks.index') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#E8425B] px-4 py-2.5 text-sm font-semibold text-white">
                        Add marks <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                    </a>
                </div>
            @else
                <div class="mt-5 overflow-x-auto">
                    <svg
                        role="img"
                        aria-label="APS progress from reported grades and terms"
                        viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}"
                        class="h-[280px] min-w-full"
                        style="width: {{ $chartWidth }}px;"
                    >
                        <rect x="0" y="0" width="{{ $chartWidth }}" height="{{ $chartHeight }}" rx="14" fill="#fafafa"></rect>

                        @foreach ($gridLines as $gridLine)
                            @php
                                $gridY = $chartPaddingTop + $plotHeight - (($gridLine / $maxAps) * $plotHeight);
                            @endphp
                            <line x1="{{ $chartPaddingLeft }}" y1="{{ $gridY }}" x2="{{ $chartWidth - $chartPaddingRight }}" y2="{{ $gridY }}" stroke="#e5e5e5" stroke-width="1"></line>
                            <text x="{{ $chartPaddingLeft - 12 }}" y="{{ $gridY + 4 }}" text-anchor="end" font-size="12" font-weight="700" fill="#737373">{{ $gridLine }}</text>
                        @endforeach

                        <line x1="{{ $chartPaddingLeft }}" y1="{{ $chartPaddingTop }}" x2="{{ $chartPaddingLeft }}" y2="{{ $chartPaddingTop + $plotHeight }}" stroke="#d4d4d4" stroke-width="1.5"></line>
                        <line x1="{{ $chartPaddingLeft }}" y1="{{ $chartPaddingTop + $plotHeight }}" x2="{{ $chartWidth - $chartPaddingRight }}" y2="{{ $chartPaddingTop + $plotHeight }}" stroke="#d4d4d4" stroke-width="1.5"></line>

                        <polyline points="{{ $polylinePoints }}" fill="none" stroke="#E8425B" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></polyline>

                        @foreach ($chartPoints as $point)
                            <g>
                                <line x1="{{ $point['x'] }}" y1="{{ $chartPaddingTop + $plotHeight }}" x2="{{ $point['x'] }}" y2="{{ $point['y'] }}" stroke="#f3c2ca" stroke-width="1" stroke-dasharray="4 5"></line>
                                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="6" fill="#E8425B"></circle>
                                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3" fill="#ffffff"></circle>
                                <text x="{{ $point['x'] }}" y="{{ $point['y'] - 12 }}" text-anchor="middle" font-size="12" font-weight="800" fill="#171717">{{ $point['aps'] }}</text>
                                <text x="{{ $point['x'] }}" y="{{ $chartPaddingTop + $plotHeight + 24 }}" text-anchor="middle" font-size="12" font-weight="800" fill="#404040">{{ $point['label'] }}</text>
                                <text x="{{ $point['x'] }}" y="{{ $chartPaddingTop + $plotHeight + 42 }}" text-anchor="middle" font-size="11" font-weight="600" fill="#737373">{{ $point['reported_subjects'] }} {{ \Illuminate\Support\Str::plural('subject', $point['reported_subjects']) }}</text>
                                <title>{{ $point['grade'] }} {{ $point['term'] }}: {{ $point['aps'] }} APS from {{ $point['reported_subjects'] }} {{ \Illuminate\Support\Str::plural('subject', $point['reported_subjects']) }}</title>
                            </g>
                        @endforeach
                    </svg>
                </div>
            @endif
        </section>

        <section class="mt-6 grid gap-5 lg:grid-cols-2">
            <article class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold">Subjects for APS and Funding</h2>
                    <a href="{{ route('subjects.index') }}" class="text-sm font-bold text-[#E8425B]">Edit</a>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ($selectedSubjects as $subject)
                        <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-700">{{ $subject->name }}</span>
                    @empty
                        <p class="text-sm text-neutral-500">Choose subjects to unlock marks, course match, and bursary checks.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-xl font-bold">Marks for matching</h2>
                    <a href="{{ route('marks.index') }}" class="text-sm font-bold text-[#E8425B]">Update</a>
                </div>
                <p class="mt-1 text-sm text-neutral-500">{{ optional($latestTerm)->name ?? 'No term marks yet' }}</p>
                <div class="mt-4 space-y-2">
                    @forelse ($results->take(6) as $result)
                        <div class="flex items-center justify-between rounded-xl bg-neutral-50 px-3 py-2">
                            <span class="text-sm font-semibold">{{ $result->name }}</span>
                            <span class="text-sm font-bold">{{ $result->mark }}%</span>
                        </div>
                    @empty
                        <p class="text-sm text-neutral-500">Add marks to unlock stronger course and bursary matching.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="mt-6 grid gap-5 lg:grid-cols-2">
            <article class="rounded-2xl border border-neutral-200 bg-white p-5">
                <h2 class="text-xl font-bold">Continue quiz</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($pendingQuizzes as $quiz)
                        <a href="{{ route('practice.show', $quiz->id) }}" class="block rounded-xl border border-neutral-200 px-4 py-3 hover:bg-neutral-50">
                            <p class="font-semibold">{{ $quiz->title }}</p>
                            <p class="mt-1 text-sm text-neutral-500">{{ $quiz->subject_name ?? 'Subject' }} · {{ $quiz->quiz_type }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-neutral-500">No unfinished quizzes.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-neutral-200 bg-white p-5">
                <h2 class="text-xl font-bold">Recent attempts</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($recentAttempts as $attempt)
                        <a href="{{ route('practice.results', $attempt->id) }}" class="block rounded-xl border border-neutral-200 px-4 py-3 hover:bg-neutral-50">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold">{{ $attempt->title }}</p>
                                <span class="text-sm font-bold">{{ number_format((float) $attempt->percentage, 1) }}%</span>
                            </div>
                            <p class="mt-1 text-sm text-neutral-500">{{ $attempt->subject_name ?? 'Subject' }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-neutral-500">Complete a quiz to see results here.</p>
                    @endforelse
                </div>
            </article>
        </section>
    </main>
@endsection
