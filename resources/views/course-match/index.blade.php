@extends('layouts.app')

@section('title', 'Course Match · Chamu')

@section('content')
    <main class="max-w-6xl mx-auto px-5 lg:px-8 py-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-8">
            <div>
                <p class="text-sm font-semibold text-[#01225E]">Admissions match</p>
                <h1 class="mt-1 text-3xl font-bold">Courses you qualify for</h1>
                <p class="mt-2 text-neutral-500">Matches use your selected term marks, APS or aggregate average, and subject requirements.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('marks.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                    <i data-lucide="line-chart" style="width:16px;height:16px;"></i>
                    Marks
                </a>
                <a href="{{ route('dashboard.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                    <i data-lucide="home" style="width:16px;height:16px;"></i>
                    Dashboard
                </a>
            </div>
        </div>

        <section class="grid gap-3 md:grid-cols-4 mb-5">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">APS Score</p>
                <p class="mt-2 text-3xl font-bold">{{ $apsTotal }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Average</p>
                <p class="mt-2 text-3xl font-bold">{{ $averageMark ? number_format($averageMark, 1) : '0.0' }}%</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Matched Courses</p>
                <p class="mt-2 text-3xl font-bold">{{ $matchedCount }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Visible Courses</p>
                <p class="mt-2 text-3xl font-bold">{{ $visibleMatchesCount }}</p>
            </div>
        </section>

        @php
            $selectedUniversity = $universities->firstWhere('id', (int) $filters['university_id']);
            $selectedFaculty = $faculties->firstWhere('id', (int) $filters['faculty_id']);
            $selectedQualificationType = $qualificationTypes->firstWhere('id', (int) $filters['qualification_type_id']);
            $universityLabel = function ($university) {
                if (! $university) {
                    return 'All universities';
                }

                return $university->abbreviation && $university->abbreviation !== $university->name
                    ? $university->abbreviation.' ('.$university->name.')'
                    : $university->name;
            };
        @endphp

        <form method="GET" action="{{ route('course-match.index') }}" class="mb-6 rounded-2xl border border-neutral-200 bg-white p-4 soft-card">
            <input type="hidden" name="page" value="1">
            <div class="grid gap-3 lg:grid-cols-4">
                <div>
                    <label for="term_id" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="calendar-days" style="width:14px;height:14px;"></i>
                        Marks
                    </label>
                    <select id="term_id" name="term_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#01225E]">
                        @foreach ($terms as $term)
                            <option value="{{ $term->id }}" @selected((int) $termId === $term->id)>{{ $term->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="relative" data-combobox>
                    <label for="university_id_search" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                        University
                    </label>
                    <input type="hidden" name="university_id" value="{{ $filters['university_id'] }}" data-combobox-value>
                    <input id="university_id_search" type="search" autocomplete="off" value="{{ $universityLabel($selectedUniversity) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 pr-10 font-semibold outline-none focus:border-[#01225E]" data-combobox-input>
                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-[42px] text-neutral-400" style="width:18px;height:18px;"></i>
                    <div class="absolute left-0 right-0 z-30 mt-2 hidden max-h-72 overflow-y-auto rounded-xl border border-neutral-200 bg-white p-1 shadow-xl" data-combobox-list>
                        <button type="button" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold hover:bg-neutral-50" data-combobox-option data-value="" data-label="All universities">All universities</button>
                        @foreach ($universities as $university)
                            @php($label = $universityLabel($university))
                            <button type="button" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold hover:bg-neutral-50" data-combobox-option data-value="{{ $university->id }}" data-label="{{ $label }}">
                                {{ $label }}
                            </button>
                        @endforeach
                        <p class="hidden px-3 py-2 text-sm font-semibold text-neutral-500" data-combobox-empty>No matches</p>
                    </div>
                </div>

                <div class="relative" data-combobox>
                    <label for="faculty_id_search" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="layers" style="width:14px;height:14px;"></i>
                        Faculty
                    </label>
                    <input type="hidden" name="faculty_id" value="{{ $filters['faculty_id'] }}" data-combobox-value>
                    <input id="faculty_id_search" type="search" autocomplete="off" value="{{ $selectedFaculty ? $selectedFaculty->university_abbreviation.' · '.$selectedFaculty->name : 'All faculties' }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 pr-10 font-semibold outline-none focus:border-[#01225E]" data-combobox-input>
                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-[42px] text-neutral-400" style="width:18px;height:18px;"></i>
                    <div class="absolute left-0 right-0 z-30 mt-2 hidden max-h-72 overflow-y-auto rounded-xl border border-neutral-200 bg-white p-1 shadow-xl" data-combobox-list>
                        <button type="button" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold hover:bg-neutral-50" data-combobox-option data-value="" data-label="All faculties">All faculties</button>
                        @foreach ($faculties as $faculty)
                            <button type="button" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold hover:bg-neutral-50" data-combobox-option data-value="{{ $faculty->id }}" data-label="{{ $faculty->university_abbreviation }} · {{ $faculty->name }}">
                                {{ $faculty->university_abbreviation }} · {{ $faculty->name }}
                            </button>
                        @endforeach
                        <p class="hidden px-3 py-2 text-sm font-semibold text-neutral-500" data-combobox-empty>No matches</p>
                    </div>
                </div>

                <div class="relative" data-combobox>
                    <label for="qualification_type_id_search" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="award" style="width:14px;height:14px;"></i>
                        Qualification type
                    </label>
                    <input type="hidden" name="qualification_type_id" value="{{ $filters['qualification_type_id'] }}" data-combobox-value>
                    <input id="qualification_type_id_search" type="search" autocomplete="off" value="{{ $selectedQualificationType ? $selectedQualificationType->name : 'All types' }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 pr-10 font-semibold outline-none focus:border-[#01225E]" data-combobox-input>
                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-[42px] text-neutral-400" style="width:18px;height:18px;"></i>
                    <div class="absolute left-0 right-0 z-30 mt-2 hidden max-h-72 overflow-y-auto rounded-xl border border-neutral-200 bg-white p-1 shadow-xl" data-combobox-list>
                        <button type="button" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold hover:bg-neutral-50" data-combobox-option data-value="" data-label="All types">All types</button>
                        @foreach ($qualificationTypes as $type)
                            <button type="button" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold hover:bg-neutral-50" data-combobox-option data-value="{{ $type->id }}" data-label="{{ $type->name }}">{{ $type->name }}</button>
                        @endforeach
                        <p class="hidden px-3 py-2 text-sm font-semibold text-neutral-500" data-combobox-empty>No matches</p>
                    </div>
                </div>

                <div>
                    <label for="search" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="search" style="width:14px;height:14px;"></i>
                        Search
                    </label>
                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Programme, university, faculty, type" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#01225E]">
                </div>

                <div>
                    <label for="per_page" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="list" style="width:14px;height:14px;"></i>
                        Per page
                    </label>
                    <select id="per_page" name="per_page" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#01225E]">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} per page</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white hover:bg-[#001A48]">
                        Filter <i data-lucide="sliders-horizontal" style="width:18px;height:18px;"></i>
                    </button>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-3 border-t border-neutral-100 pt-4 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-700">
                        <input type="hidden" name="hide_not_qualified" value="0">
                        <input type="checkbox" name="hide_not_qualified" value="1" class="h-4 w-4 accent-[#01225E]" @checked($filters['hide_not_qualified'])>
                        You qualify
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-700">
                        <input type="hidden" name="show_almost_there" value="0">
                        <input type="checkbox" name="show_almost_there" value="1" class="h-4 w-4 accent-[#01225E]" @checked($filters['show_almost_there'])>
                        Almost there
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-700">
                        <input type="hidden" name="show_not_qualified_yet" value="0">
                        <input type="checkbox" name="show_not_qualified_yet" value="1" class="h-4 w-4 accent-[#01225E]" @checked($filters['show_not_qualified_yet'])>
                        Not qualified yet
                    </label>
                </div>

                <div class="flex items-center gap-3 text-sm font-semibold text-neutral-500">
                    <span>{{ $visibleMatchesCount }} of {{ $totalMatchesBeforeFilters }} shown</span>
                    <a href="{{ route('course-match.index', ['term_id' => $termId]) }}" class="text-[#01225E] hover:underline">Reset</a>
                </div>
            </div>
        </form>

        @if ($results->isEmpty())
            <section class="rounded-2xl border border-dashed border-neutral-300 bg-white p-8 text-center">
                <h2 class="text-xl font-bold">Add marks first</h2>
                <p class="mt-2 text-neutral-500">Course matching needs your term marks so it can calculate APS and compare subject levels.</p>
                <a href="{{ route('marks.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white">
                    Add marks <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                </a>
            </section>
        @else
            <div class="mb-4 flex items-center justify-between rounded-2xl border border-neutral-200 bg-white p-4 text-sm font-semibold text-neutral-500">
                <span>
                    Showing {{ $matches->firstItem() ?? 0 }}-{{ $matches->lastItem() ?? 0 }} of {{ $matches->total() }}
                </span>
            </div>

            <section class="grid gap-4">
                @forelse ($matches as $match)
                    <article class="rounded-2xl border {{ $match->is_match ? 'border-emerald-200 bg-emerald-50/40' : ($match->requires_manual_review ? 'border-sky-200 bg-sky-50/40' : 'border-neutral-200 bg-white') }} p-5 soft-card">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full {{ $match->is_match ? 'bg-emerald-100 text-emerald-700' : ($match->requires_manual_review ? 'bg-sky-100 text-sky-700' : ($match->is_almost_there ? 'bg-amber-100 text-amber-700' : 'bg-neutral-100 text-neutral-600')) }} px-3 py-1 text-xs font-bold">
                                        {{ $match->is_match ? 'You qualify' : ($match->requires_manual_review ? 'Review requirements' : ($match->is_almost_there ? 'Almost there' : 'Not qualified yet')) }}
                                    </span>
                                    @if ($match->is_selection_programme)
                                        <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600">Selection programme</span>
                                    @endif
                                </div>
                                <h2 class="mt-3 text-xl font-bold text-neutral-950">{{ $match->name }}</h2>
                                <p class="mt-1 text-sm font-semibold text-neutral-500">
                                    <a href="{{ route('universities.programmes', $match->university_id) }}" class="text-[#01225E] hover:underline">{{ $match->university_abbreviation ?? $match->university_name }}</a>
                                    · {{ $match->faculty_name }} · {{ $match->qualification_type_name }}
                                </p>
                                @if ($match->notes)
                                    <p class="mt-3 rounded-xl bg-white/70 px-3 py-2 text-sm text-neutral-600">{{ $match->notes }}</p>
                                @endif
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <a href="{{ route('courses.show', $match->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                                        Details <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                                    </a>
                                    <a href="{{ route('universities.programmes', $match->university_id) }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                                        University <i data-lucide="building-2" style="width:16px;height:16px;"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="grid min-w-full grid-cols-2 gap-2 sm:min-w-[440px] sm:grid-cols-4">
                                <div class="rounded-xl border border-neutral-200 bg-white p-3">
                                    <p class="text-xs font-bold uppercase text-neutral-500">Required {{ $match->admission_score_label }}</p>
                                    <p class="mt-1 text-2xl font-bold">{{ $match->admission_score_required_display }}</p>
                                    @if ($match->admission_score_variant_label)
                                        <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $match->admission_score_variant_label }}</p>
                                    @endif
                                </div>
                                <div class="rounded-xl border border-neutral-200 bg-white p-3">
                                    <p class="text-xs font-bold uppercase text-neutral-500">Your {{ $match->admission_score_label }}</p>
                                    <p class="mt-1 text-2xl font-bold">{{ $match->admission_score_actual_display }}</p>
                                </div>
                                <div class="rounded-xl border border-neutral-200 bg-white p-3">
                                    <p class="text-xs font-bold uppercase text-neutral-500">{{ $match->admission_score_label }} Gap</p>
                                    <p class="mt-1 text-2xl font-bold">{{ $match->admission_score_gap_display }}</p>
                                </div>
                                <div class="rounded-xl border border-neutral-200 bg-white p-3">
                                    <p class="text-xs font-bold uppercase text-neutral-500">Closes</p>
                                    <p class="mt-1 text-sm font-bold">{{ $match->closing_label }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <div class="rounded-xl border border-neutral-200 bg-white p-4">
                                <p class="text-sm font-bold text-neutral-950">Met requirements</p>
                                @if (count($match->met_requirements) > 0)
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($match->met_requirements as $requirement)
                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ $requirement }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-2 text-sm text-neutral-500">{{ $match->requires_manual_review ? 'Structured requirements are not available for this programme yet.' : 'No subject requirements listed.' }}</p>
                                @endif
                            </div>

                            <div class="rounded-xl border border-neutral-200 bg-white p-4">
                                <p class="text-sm font-bold text-neutral-950">Still needed</p>
                                @if ($match->requires_manual_review)
                                    <p class="mt-2 text-sm font-semibold text-sky-700">Check the notes. This programme has requirements that are not fully machine-checkable yet.</p>
                                @elseif ($match->admission_score_gap === 0 && count($match->missing_requirements) === 0)
                                    <p class="mt-2 text-sm font-semibold text-emerald-700">Nothing missing based on your current marks.</p>
                                @else
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @if ($match->admission_score_gap > 0)
                                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
                                                {{ $match->admission_score_label }}
                                                {{ $match->admission_score_type === 'pass_type' ? $match->admission_score_gap_display : '+'.$match->admission_score_gap_display }}
                                            </span>
                                        @endif
                                        @foreach ($match->missing_requirements as $requirement)
                                            <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700">{{ $requirement }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <section class="rounded-2xl border border-dashed border-neutral-300 bg-white p-8 text-center">
                        <h2 class="text-xl font-bold">No programmes found</h2>
                        <p class="mt-2 text-neutral-500">Try changing your search or filters.</p>
                    </section>
                @endforelse
            </section>

            @if ($matches->hasPages())
                <div class="mt-6 rounded-2xl border border-neutral-200 bg-white p-4">
                    {{ $matches->onEachSide(1)->links() }}
                </div>
            @endif
        @endif
    </main>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-combobox]').forEach((combobox) => {
            const input = combobox.querySelector('[data-combobox-input]');
            const hidden = combobox.querySelector('[data-combobox-value]');
            const list = combobox.querySelector('[data-combobox-list]');
            const options = Array.from(combobox.querySelectorAll('[data-combobox-option]'));
            const empty = combobox.querySelector('[data-combobox-empty]');

            const open = () => list.classList.remove('hidden');
            const close = () => list.classList.add('hidden');
            const normalise = (value) => value.toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();

            const filterOptions = () => {
                const query = normalise(input.value);
                let visibleCount = 0;

                options.forEach((option) => {
                    const isVisible = normalise(option.dataset.label || option.textContent).includes(query);
                    option.classList.toggle('hidden', ! isVisible);
                    visibleCount += isVisible ? 1 : 0;
                });

                empty.classList.toggle('hidden', visibleCount > 0);
            };

            const syncValueFromLabel = () => {
                const typedLabel = normalise(input.value);
                const exactOption = options.find((option) => normalise(option.dataset.label || '') === typedLabel);
                hidden.value = exactOption ? exactOption.dataset.value : '';
            };

            input.addEventListener('focus', () => {
                input.select();
                filterOptions();
                open();
            });

            input.addEventListener('input', () => {
                syncValueFromLabel();
                filterOptions();
                open();
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    close();
                    input.blur();
                }
            });

            options.forEach((option) => {
                option.addEventListener('click', () => {
                    input.value = option.dataset.label;
                    hidden.value = option.dataset.value;
                    filterOptions();
                    close();
                });
            });

            document.addEventListener('click', (event) => {
                if (! combobox.contains(event.target)) {
                    close();
                }
            });
        });
    </script>
@endpush
