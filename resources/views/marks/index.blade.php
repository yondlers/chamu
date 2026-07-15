@extends('layouts.app')

@section('title', 'Marks · Chamu')

@section('content')
    <main class="max-w-5xl mx-auto px-5 lg:px-8 py-10">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                <p class="text-sm font-semibold text-[#01225E]">Results tracker</p>
                <h1 class="text-3xl font-bold mt-1">Add term marks</h1>
                <p class="mt-2 text-neutral-500">Marks are saved by grade and term. APS is calculated automatically.</p>
            </div>
            <a href="{{ route('subjects.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                <i data-lucide="list-checks" style="width:16px;height:16px;"></i>
                Subjects
            </a>
        </div>

        @if (session('status'))
            <p class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
        @endif

        <form method="GET" action="{{ route('marks.index') }}" class="mb-5 rounded-2xl border border-neutral-200 bg-white p-4">
            <label for="term_id" class="block text-sm font-semibold mb-2">Term</label>
            <div class="flex gap-3">
                <select id="term_id" name="term_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                    @foreach ($terms as $term)
                        <option value="{{ $term->id }}" @selected((int) $termId === $term->id)>{{ $term->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl border border-neutral-300 px-5 py-3 font-semibold hover:bg-neutral-50">Load</button>
            </div>
        </form>

        <form method="POST" action="{{ route('marks.update') }}" class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card">
            @csrf
            @method('PUT')
            <input type="hidden" name="term_id" value="{{ $termId }}">

            <div class="mb-6 grid gap-3 md:grid-cols-3">
                <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4">
                    <p class="text-xs font-bold uppercase text-neutral-500">APS Count</p>
                    <p id="aps-total" class="mt-2 text-3xl font-bold text-neutral-950">0</p>
                </div>
                <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4">
                    <p class="text-xs font-bold uppercase text-neutral-500">Aggregate Average</p>
                    <p id="aggregate-average" class="mt-2 text-3xl font-bold text-neutral-950">0%</p>
                </div>
                <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4">
                    <p class="text-xs font-bold uppercase text-neutral-500">Subjects Counted</p>
                    <p id="subjects-counted" class="mt-2 text-3xl font-bold text-neutral-950">0</p>
                </div>
            </div>

            <p class="mb-4 rounded-xl bg-blue-50 px-4 py-3 text-sm font-semibold text-[#01225E]">
                Note: Life Orientation (LO) is excluded from the APS count and aggregate average.
            </p>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">Subject</th>
                            <th class="py-3 px-3 w-40">Mark %</th>
                            <th class="py-3 pl-3 w-32">APS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjects as $subject)
                            @php
                                $result = $results->get($subject->id);
                                $mark = old("marks.{$subject->id}", optional($result)->mark);
                                $subjectCode = strtoupper($subject->code ?? $subject->abbreviation ?? '');
                                $excludeFromAggregate = $subjectCode === 'LO' || strcasecmp($subject->name, 'Life Orientation') === 0;
                            @endphp
                            <tr class="border-b border-neutral-100">
                                <td class="py-4 pr-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold">{{ $subject->name }}</p>
                                        @if ($excludeFromAggregate)
                                            <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-bold text-neutral-500">Excluded</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-neutral-500">{{ $subject->code ?? $subject->abbreviation ?? 'SUBJ' }}</p>
                                </td>
                                <td class="py-4 px-3">
                                    <input name="marks[{{ $subject->id }}]" type="number" min="0" max="100" value="{{ $mark }}" class="mark-input w-full rounded-xl border border-neutral-300 px-3 py-2 outline-none focus:border-[#01225E]" data-aps-target="aps-{{ $subject->id }}" data-exclude-summary="{{ $excludeFromAggregate ? '1' : '0' }}">
                                </td>
                                <td class="py-4 pl-3">
                                    <input id="aps-{{ $subject->id }}" value="{{ optional($result)->aps_score }}" readonly class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-2 text-neutral-500">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-5 py-3 font-semibold hover:bg-neutral-50">Cancel</a>
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white hover:bg-[#001A48]">
                    Save marks <i data-lucide="save" style="width:18px;height:18px;"></i>
                </button>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script>
        const apsFor = (mark) => {
            if (mark === '') return '';
            const value = Number(mark);
            if (value >= 80) return 7;
            if (value >= 70) return 6;
            if (value >= 60) return 5;
            if (value >= 50) return 4;
            if (value >= 40) return 3;
            if (value >= 30) return 2;
            return 1;
        };

        const markInputs = Array.from(document.querySelectorAll('.mark-input'));
        const apsTotal = document.getElementById('aps-total');
        const aggregateAverage = document.getElementById('aggregate-average');
        const subjectsCounted = document.getElementById('subjects-counted');

        const syncSummary = () => {
            let apsSum = 0;
            let markSum = 0;
            let counted = 0;

            markInputs.forEach((input) => {
                if (input.dataset.excludeSummary === '1' || input.value === '') {
                    return;
                }

                const mark = Number(input.value);
                if (Number.isNaN(mark)) {
                    return;
                }

                apsSum += apsFor(input.value);
                markSum += mark;
                counted++;
            });

            apsTotal.textContent = apsSum;
            aggregateAverage.textContent = counted > 0 ? `${(markSum / counted).toFixed(1)}%` : '0%';
            subjectsCounted.textContent = counted;
        };

        markInputs.forEach((input) => {
            const sync = () => {
                document.getElementById(input.dataset.apsTarget).value = apsFor(input.value);
                syncSummary();
            };

            input.addEventListener('input', sync);
            sync();
        });

        syncSummary();
    </script>
@endpush
