@extends('layouts.app')

@section('title', 'APS Calculator · Matric Hub')

@section('content')
    <main class="mx-auto max-w-7xl px-5 py-8 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold text-[#E8425B]">@auth APS from your profile @else Public student tool @endauth</p>
                <h1 class="mt-1 text-3xl font-bold">APS Calculator</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">@auth Your saved subjects and latest marks are loaded when available. @else Enter subject marks once and compare the major scoring systems side by side. @endauth</p>
            </div>
            @auth
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('marks.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                        Edit marks <i data-lucide="line-chart" style="width:16px;height:16px;"></i>
                    </a>
                    <a href="{{ route('course-match.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#E8425B] px-4 py-2 font-semibold text-white hover:bg-[#d73550]">
                        Course match <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                    </a>
                </div>
            @else
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#E8425B] px-4 py-2 font-semibold text-white hover:bg-[#d73550]">
                    Save progress <i data-lucide="user-plus" style="width:16px;height:16px;"></i>
                </a>
            @endauth
        </div>

        @auth
            <section class="mb-6 rounded-2xl border {{ $usingSavedMarks ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }} p-4 text-sm font-semibold">
                @if ($usingSavedMarks)
                    Loaded your saved subjects and latest marks{{ $savedMarksTerm ? ' from '.$savedMarksTerm : '' }}. You can still edit the rows below for a quick what-if calculation.
                @else
                    Your saved subjects are loaded, but no saved marks were found yet. Add marks once and this calculator will fill itself in next time.
                @endif
            </section>
        @endauth

        <form method="GET" action="{{ route('aps-calculator.index') }}" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <section class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Subject marks</h2>
                        <p class="mt-1 text-sm text-neutral-500">Choose a subject and add its percentage mark.</p>
                    </div>
                    <button type="button" data-add-row class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                        Add subject <i data-lucide="plus" style="width:16px;height:16px;"></i>
                    </button>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[620px] border-separate border-spacing-y-2">
                        <thead>
                            <tr class="text-left text-xs font-bold uppercase text-neutral-500">
                                <th class="px-3">Subject</th>
                                <th class="w-36 px-3">Mark</th>
                                <th class="w-20 px-3 text-right">Remove</th>
                            </tr>
                        </thead>
                        <tbody data-subject-rows>
                            @foreach ($rows as $index => $row)
                                <tr data-subject-row>
                                    <td class="rounded-l-xl border border-r-0 border-neutral-200 bg-neutral-50 px-3 py-2">
                                        <select name="subjects[{{ $index }}][subject_id]" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 font-semibold outline-none focus:border-[#E8425B]">
                                            <option value="">Choose subject</option>
                                            @foreach ($subjects as $subject)
                                                <option value="{{ $subject->id }}" @selected((int) $row->subject_id === $subject->id)>{{ $subject->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="border-y border-neutral-200 bg-neutral-50 px-3 py-2">
                                        <input name="subjects[{{ $index }}][mark]" type="number" min="0" max="100" step="0.1" value="{{ $row->mark === null ? '' : $formatNumber($row->mark, 1) }}" placeholder="%" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 font-semibold outline-none focus:border-[#E8425B]">
                                    </td>
                                    <td class="rounded-r-xl border border-l-0 border-neutral-200 bg-neutral-50 px-3 py-2 text-right">
                                        <button type="button" data-remove-row class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-neutral-300 bg-white text-neutral-500 hover:bg-neutral-100" title="Remove subject">
                                            <i data-lucide="x" style="width:16px;height:16px;"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <template data-subject-row-template>
                    <tr data-subject-row>
                        <td class="rounded-l-xl border border-r-0 border-neutral-200 bg-neutral-50 px-3 py-2">
                            <select data-name="subjects[__INDEX__][subject_id]" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 font-semibold outline-none focus:border-[#E8425B]">
                                <option value="">Choose subject</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="border-y border-neutral-200 bg-neutral-50 px-3 py-2">
                            <input data-name="subjects[__INDEX__][mark]" type="number" min="0" max="100" step="0.1" placeholder="%" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 font-semibold outline-none focus:border-[#E8425B]">
                        </td>
                        <td class="rounded-r-xl border border-l-0 border-neutral-200 bg-neutral-50 px-3 py-2 text-right">
                            <button type="button" data-remove-row class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-neutral-300 bg-white text-neutral-500 hover:bg-neutral-100" title="Remove subject">
                                <i data-lucide="x" style="width:16px;height:16px;"></i>
                            </button>
                        </td>
                    </tr>
                </template>

                <div class="mt-5 grid gap-4 border-t border-neutral-100 pt-5 md:grid-cols-4">
                    <div>
                        <label for="disadvantage_factor" class="mb-2 block text-xs font-bold uppercase text-neutral-500">UCT disadvantage factor</label>
                        <input id="disadvantage_factor" name="disadvantage_factor" type="number" min="0" max="100" step="0.1" value="{{ $formatNumber($disadvantageFactor, 1) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#E8425B]">
                    </div>
                    <div>
                        <label for="nbt_al" class="mb-2 block text-xs font-bold uppercase text-neutral-500">NBT AL</label>
                        <input id="nbt_al" name="nbt_al" type="number" min="0" max="100" step="0.1" value="{{ $nbtScores['AL'] === null ? '' : $formatNumber($nbtScores['AL'], 1) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#E8425B]">
                    </div>
                    <div>
                        <label for="nbt_ql" class="mb-2 block text-xs font-bold uppercase text-neutral-500">NBT QL</label>
                        <input id="nbt_ql" name="nbt_ql" type="number" min="0" max="100" step="0.1" value="{{ $nbtScores['QL'] === null ? '' : $formatNumber($nbtScores['QL'], 1) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#E8425B]">
                    </div>
                    <div>
                        <label for="nbt_mat" class="mb-2 block text-xs font-bold uppercase text-neutral-500">NBT MAT</label>
                        <input id="nbt_mat" name="nbt_mat" type="number" min="0" max="100" step="0.1" value="{{ $nbtScores['MAT'] === null ? '' : $formatNumber($nbtScores['MAT'], 1) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#E8425B]">
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#E8425B] px-5 py-3 font-semibold text-white hover:bg-[#d73550]">
                        Calculate scores <i data-lucide="calculator" style="width:18px;height:18px;"></i>
                    </button>
                    <a href="{{ route('aps-calculator.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-5 py-3 font-semibold hover:bg-neutral-50">
                        Reset <i data-lucide="rotate-ccw" style="width:18px;height:18px;"></i>
                    </a>
                </div>
            </section>

            <aside class="rounded-2xl border border-neutral-200 bg-neutral-950 p-5 text-white soft-card">
                <p class="text-xs font-bold uppercase text-white/55">Top scores</p>
                <div class="mt-4 grid gap-3">
                    @foreach ($scoreSummaries->take(5) as $score)
                        <div class="rounded-xl border border-white/10 bg-white/10 p-4">
                            <p class="text-xs font-bold uppercase text-white/55">{{ $score['label'] }}</p>
                            <p class="mt-1 text-3xl font-bold">{{ $score['value'] }}</p>
                            @if ($score['max'])
                                <p class="mt-1 text-xs font-semibold text-white/45">Max {{ $score['max'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </aside>
        </form>

        <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($scoreSummaries as $score)
                <article class="rounded-2xl border border-neutral-200 bg-white p-4">
                    <p class="text-xs font-bold uppercase text-neutral-500">{{ $score['label'] }}</p>
                    <div class="mt-2 flex items-end justify-between gap-3">
                        <p class="text-3xl font-bold text-neutral-950">{{ $score['value'] }}</p>
                        @if ($score['max'])
                            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600">max {{ $score['max'] }}</span>
                        @endif
                    </div>
                    <p class="mt-3 text-sm text-neutral-500">{{ $score['note'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold">Subject contribution table</h2>
                    <p class="mt-1 text-sm text-neutral-500">See how each mark turns into points across different scoring systems.</p>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full min-w-[980px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs font-bold uppercase text-neutral-500">
                            <th class="py-3 pr-3">Subject</th>
                            <th class="px-3 py-3">Mark</th>
                            <th class="px-3 py-3">NSC Level</th>
                            <th class="px-3 py-3">APS with LO</th>
                            <th class="px-3 py-3">APS without LO</th>
                            <th class="px-3 py-3">Wits APS</th>
                            <th class="px-3 py-3">UCT FPS 600</th>
                            <th class="px-3 py-3">SU average</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($subjectBreakdown as $row)
                            <tr>
                                <td class="py-3 pr-3 font-semibold text-neutral-950">{{ $row->subject_name ?: 'Choose subject' }}</td>
                                <td class="px-3 py-3">{{ $row->mark === null ? '-' : $formatNumber($row->mark, 1).'%' }}</td>
                                <td class="px-3 py-3">{{ $row->level ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $row->aps_points ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $row->is_life_orientation ? '0' : ($row->aps_points ?? '-') }}</td>
                                <td class="px-3 py-3">{{ $row->wits_points ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $row->uct_fps_points === null ? '-' : $formatNumber($row->uct_fps_points, 1) }}</td>
                                <td class="px-3 py-3">{{ $row->stellenbosch_points === null ? '-' : $formatNumber($row->stellenbosch_points, 1) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-sm text-neutral-500">Add marks to see subject contributions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const rowsContainer = document.querySelector('[data-subject-rows]');
        const rowTemplate = document.querySelector('[data-subject-row-template]');
        const addRowButton = document.querySelector('[data-add-row]');

        function renumberRows() {
            rowsContainer.querySelectorAll('[data-subject-row]').forEach((row, index) => {
                row.querySelectorAll('select, input').forEach((field) => {
                    const name = field.getAttribute('name') || field.dataset.name;
                    if (! name) return;
                    field.setAttribute('name', name.replace(/subjects\[\d+\]|subjects\[__INDEX__\]/, `subjects[${index}]`));
                });
            });
        }

        function wireRemoveButtons() {
            rowsContainer.querySelectorAll('[data-remove-row]').forEach((button) => {
                button.onclick = () => {
                    const rows = rowsContainer.querySelectorAll('[data-subject-row]');
                    if (rows.length <= 1) {
                        button.closest('[data-subject-row]').querySelectorAll('select, input').forEach((field) => field.value = '');
                        return;
                    }

                    button.closest('[data-subject-row]').remove();
                    renumberRows();
                };
            });
        }

        addRowButton?.addEventListener('click', () => {
            const fragment = rowTemplate.content.cloneNode(true);
            rowsContainer.appendChild(fragment);
            renumberRows();
            wireRemoveButtons();

            if (window.lucide) {
                lucide.createIcons();
            }
        });

        wireRemoveButtons();
    </script>
@endpush
