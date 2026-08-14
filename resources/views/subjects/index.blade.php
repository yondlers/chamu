@extends('layouts.app')

@section('title', 'Subjects & marks · Chamu')

@section('content')
    @php
        $selectedSubjectIds = collect(old('subjects', $selectedSubjectIds))
            ->map(fn ($id) => (int) $id)
            ->all();
        $selectedCurriculumId = (int) old('curriculum_id', $curriculumId);
        $selectedGradeId = (int) old('grade_id', $gradeId);
        $selectedTermId = (int) old('term_id', $termId);
    @endphp

    <main class="max-w-5xl mx-auto px-5 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                <p class="text-sm font-semibold text-[#01225E]">{{ $manage ? 'Profile' : 'Pupil setup' }}</p>
                <h1 class="text-3xl font-bold mt-1">Subjects & latest marks</h1>
                <p class="mt-2 text-neutral-500">Choose your grade and term, pick subjects, then enter your most recent marks on this one screen.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('aps.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                    <i data-lucide="graduation-cap" style="width:16px;height:16px;"></i>
                    Courses
                </a>
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                    <i data-lucide="user-cog" style="width:16px;height:16px;"></i>
                    Profile
                </a>
            </div>
        </div>

        @if (session('status'))
            <p class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form id="context-form" method="GET" action="{{ route('subjects.index') }}" class="mb-5 rounded-2xl border border-neutral-200 bg-white p-4 soft-card">
            @if ($manage)
                <input type="hidden" name="manage" value="1">
            @elseif ($continue)
                <input type="hidden" name="continue" value="1">
            @endif
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="curriculum_id" class="block text-sm font-semibold mb-2">Curriculum</label>
                    <select id="curriculum_id" name="curriculum_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @foreach ($curriculums as $curriculum)
                            <option value="{{ $curriculum->id }}" @selected($selectedCurriculumId === (int) $curriculum->id)>
                                {{ $curriculum->abbreviation ?: $curriculum->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="grade_id" class="block text-sm font-semibold mb-2">Grade</label>
                    <select id="grade_id" name="grade_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]"></select>
                </div>
                <div>
                    <label for="term_id" class="block text-sm font-semibold mb-2">Most recent term</label>
                    <div class="flex gap-3">
                        <select id="term_id" name="term_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                            @forelse ($terms as $term)
                                <option value="{{ $term->id }}" @selected($selectedTermId === (int) $term->id)>{{ $term->name }}</option>
                            @empty
                                <option value="">Choose grade first</option>
                            @endforelse
                        </select>
                        <button class="rounded-xl border border-neutral-300 px-5 py-3 font-semibold hover:bg-neutral-50">Load</button>
                    </div>
                </div>
            </div>
            <p class="mt-3 text-xs font-semibold text-neutral-500">Grade 12 terms are Term 1, Term 2, Term 3, and NSC. Other grades use Term 1–4.</p>
        </form>

        <form id="subjects-form" method="POST" action="{{ route('subjects.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            @if ($manage)
                <input type="hidden" name="manage" value="1">
            @endif
            @if ($continue)
                <input type="hidden" name="continue" value="1">
            @endif
            <input type="hidden" name="curriculum_id" value="{{ $selectedCurriculumId }}">
            <input type="hidden" name="grade_id" value="{{ $selectedGradeId }}">
            <input type="hidden" name="term_id" value="{{ $selectedTermId }}">

            <section class="sticky top-4 z-10 rounded-2xl border border-neutral-200 bg-white/95 p-4 shadow-sm backdrop-blur">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="font-bold">Selected subjects</h2>
                        <p id="subjects-count" class="text-sm font-semibold text-neutral-500">0 selected · 7 minimum</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="autosave-status" class="inline-flex w-fit items-center rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-500">Saved automatically</span>
                        <span id="subjects-minimum-badge" class="inline-flex w-fit items-center rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">Need 7</span>
                        <span class="inline-flex items-center rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600">APS <span id="aps-total" class="ml-1">0</span></span>
                        <span class="inline-flex items-center rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600">Avg <span id="aggregate-average" class="ml-1">0%</span></span>
                    </div>
                </div>
                <div id="selected-subjects-list" class="mt-4 flex flex-wrap gap-2"></div>
                <p id="selected-subjects-empty" class="mt-4 text-sm text-neutral-500">Checked subjects and marks will appear here.</p>
                <p class="mt-3 text-xs font-semibold text-[#01225E]">Life Orientation is excluded from APS and average.</p>
            </section>

            <section class="rounded-2xl border border-neutral-200 bg-white p-3 soft-card">
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <label for="subject-search" class="relative block sm:max-w-sm sm:flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400" style="width:18px;height:18px;"></i>
                        <input
                            id="subject-search"
                            type="search"
                            autocomplete="off"
                            placeholder="Search subjects"
                            class="w-full rounded-xl border border-neutral-300 bg-white py-3 pl-10 pr-10 text-sm font-semibold outline-none transition focus:border-[#01225E] focus:ring-2 focus:ring-[#01225E]/15"
                        >
                        <button
                            id="subject-search-clear"
                            type="button"
                            class="hidden absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-900"
                            aria-label="Clear subject search"
                        >
                            <i data-lucide="x" style="width:16px;height:16px;"></i>
                        </button>
                    </label>
                    <p id="subject-search-count" class="text-sm font-semibold text-neutral-500">{{ $subjects->count() }} subjects</p>
                </div>

                <div id="subject-list" class="grid gap-2">
                    @forelse ($subjects as $subject)
                        @php
                            $result = $results->get($subject->id);
                            $mark = old("marks.{$subject->id}", optional($result)->mark);
                            $subjectCode = strtoupper($subject->code ?? $subject->abbreviation ?? '');
                            $excludeFromAggregate = $subjectCode === 'LO' || strcasecmp($subject->name, 'Life Orientation') === 0;
                            $isSelected = in_array((int) $subject->id, $selectedSubjectIds, true);
                        @endphp
                        <div class="subject-row rounded-xl border border-transparent px-4 py-3 transition hover:border-neutral-200 hover:bg-neutral-50" data-selected="{{ $isSelected ? '1' : '0' }}" data-original-index="{{ $loop->index }}">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <label class="flex min-w-0 cursor-pointer items-center gap-3">
                                    <input
                                        type="checkbox"
                                        name="subjects[]"
                                        value="{{ $subject->id }}"
                                        class="subject-checkbox h-5 w-5 shrink-0 rounded border-neutral-300 accent-[#01225E]"
                                        data-name="{{ $subject->name }}"
                                        data-code="{{ $subject->code ?? $subject->abbreviation ?? 'SUBJ' }}"
                                        data-exclude-summary="{{ $excludeFromAggregate ? '1' : '0' }}"
                                        @checked($isSelected)
                                    >
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold text-neutral-950">
                                            {{ $subject->name }}
                                            @if ($excludeFromAggregate)
                                                <span class="ml-1 rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-bold uppercase text-neutral-500">Excluded</span>
                                            @endif
                                        </span>
                                        <span class="mt-0.5 block text-xs font-semibold uppercase text-neutral-400">{{ $subject->code ?? $subject->abbreviation ?? 'SUBJ' }}</span>
                                    </span>
                                </label>
                                <div class="flex items-center gap-2 sm:w-56">
                                    <input
                                        name="marks[{{ $subject->id }}]"
                                        type="number"
                                        min="0"
                                        max="100"
                                        value="{{ $mark }}"
                                        placeholder="Mark %"
                                        class="mark-input w-full rounded-xl border border-neutral-300 px-3 py-2 text-sm font-semibold outline-none focus:border-[#01225E] disabled:bg-neutral-50 disabled:text-neutral-400"
                                        data-aps-target="aps-{{ $subject->id }}"
                                        data-exclude-summary="{{ $excludeFromAggregate ? '1' : '0' }}"
                                        @disabled(! $isSelected)
                                    >
                                    <input id="aps-{{ $subject->id }}" value="{{ optional($result)->aps_score }}" readonly class="w-16 rounded-xl border border-neutral-200 bg-neutral-50 px-2 py-2 text-center text-sm text-neutral-500" title="APS">
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl bg-neutral-50 px-4 py-6 text-sm text-neutral-500">No subjects found for this curriculum and grade. Choose another grade above.</div>
                    @endforelse
                </div>
                @if ($subjects->isNotEmpty())
                    <p id="subject-search-empty" class="hidden rounded-xl bg-neutral-50 px-4 py-6 text-sm font-semibold text-neutral-500">No subjects match your search.</p>
                @endif
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('aps.index') }}" class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-5 py-3 font-semibold hover:bg-neutral-50">Browse courses</a>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script>
        (() => {
            const grades = @json($grades->values());
            const minimumSubjects = 7;
            const curriculumSelect = document.getElementById('curriculum_id');
            const gradeSelect = document.getElementById('grade_id');
            const selectedGradeId = '{{ $selectedGradeId }}';
            const form = document.getElementById('subjects-form');
            const checkboxes = Array.from(document.querySelectorAll('.subject-checkbox'));
            const selectedList = document.getElementById('selected-subjects-list');
            const selectedEmpty = document.getElementById('selected-subjects-empty');
            const countText = document.getElementById('subjects-count');
            const badge = document.getElementById('subjects-minimum-badge');
            const autosaveStatus = document.getElementById('autosave-status');
            const searchInput = document.getElementById('subject-search');
            const searchClear = document.getElementById('subject-search-clear');
            const searchCount = document.getElementById('subject-search-count');
            const searchEmpty = document.getElementById('subject-search-empty');
            const subjectList = document.getElementById('subject-list');
            const apsTotal = document.getElementById('aps-total');
            const aggregateAverage = document.getElementById('aggregate-average');

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

            const subjectRows = checkboxes.map((checkbox) => ({
                checkbox,
                row: checkbox.closest('.subject-row'),
                text: `${checkbox.dataset.name ?? ''} ${checkbox.dataset.code ?? ''}`.toLowerCase(),
                markInput: checkbox.closest('.subject-row')?.querySelector('.mark-input'),
                originalIndex: Number(checkbox.closest('.subject-row')?.dataset.originalIndex ?? 0),
            }));

            const sortSubjectRows = () => {
                subjectRows
                    .slice()
                    .sort((first, second) => {
                        const selectedDifference = Number(second.checkbox.checked) - Number(first.checkbox.checked);

                        return selectedDifference || first.originalIndex - second.originalIndex;
                    })
                    .forEach(({ row, checkbox }) => {
                        if (!row || !subjectList) return;

                        row.dataset.selected = checkbox.checked ? '1' : '0';
                        subjectList.appendChild(row);
                    });
            };

            let autosaveTimer = null;
            let autosaveRequestId = 0;
            let lastSavedSnapshot = null;

            const setAutosaveStatus = (message, tone = 'idle') => {
                if (!autosaveStatus) return;

                const classes = {
                    idle: 'inline-flex w-fit items-center rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-500',
                    saving: 'inline-flex w-fit items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700',
                    saved: 'inline-flex w-fit items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700',
                    error: 'inline-flex w-fit items-center rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700',
                };

                autosaveStatus.textContent = message;
                autosaveStatus.className = classes[tone] ?? classes.idle;
            };

            const subjectStateSnapshot = () => JSON.stringify({
                curriculum_id: form.querySelector('[name="curriculum_id"]')?.value ?? '',
                grade_id: form.querySelector('[name="grade_id"]')?.value ?? '',
                term_id: form.querySelector('[name="term_id"]')?.value ?? '',
                subjects: checkboxes.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value),
                marks: subjectRows
                    .filter(({ checkbox }) => checkbox.checked)
                    .map(({ checkbox, markInput }) => [checkbox.value, markInput?.value ?? '']),
            });

            const selectedMarkInputsAreValid = () => subjectRows.every(({ checkbox, markInput }) => {
                if (!checkbox.checked || !markInput) return true;

                return markInput.checkValidity();
            });

            const saveSubjects = async () => {
                clearTimeout(autosaveTimer);

                if (!selectedMarkInputsAreValid()) {
                    setAutosaveStatus('Marks must be 0-100', 'error');
                    return;
                }

                const snapshot = subjectStateSnapshot();

                if (snapshot === lastSavedSnapshot) {
                    setAutosaveStatus('Saved', 'saved');
                    return;
                }

                const requestId = ++autosaveRequestId;
                const body = new FormData(form);
                body.set('autosave', '1');

                setAutosaveStatus('Saving...', 'saving');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body,
                    });

                    if (!response.ok) {
                        const errorPayload = await response.json().catch(() => ({}));
                        const firstError = Object.values(errorPayload.errors ?? {})?.[0]?.[0];

                        throw new Error(firstError || 'Could not save changes.');
                    }

                    if (requestId === autosaveRequestId && subjectStateSnapshot() === snapshot) {
                        lastSavedSnapshot = snapshot;
                        setAutosaveStatus('Saved', 'saved');
                    }
                } catch (error) {
                    if (requestId === autosaveRequestId && subjectStateSnapshot() === snapshot) {
                        setAutosaveStatus(error.message || 'Save failed', 'error');
                    }
                }
            };

            const scheduleAutosave = (delay = 700) => {
                clearTimeout(autosaveTimer);
                setAutosaveStatus('Saving soon...', 'saving');
                autosaveTimer = setTimeout(saveSubjects, delay);
            };

            const refreshGrades = () => {
                const curriculumId = Number(curriculumSelect.value);
                const rows = grades.filter((grade) => Number(grade.curriculum_id) === curriculumId);

                gradeSelect.innerHTML = '';

                rows.forEach((grade) => {
                    const option = document.createElement('option');
                    option.value = grade.id;
                    option.textContent = grade.name;
                    option.selected = String(grade.id) === selectedGradeId || (!selectedGradeId && grade.name === 'Grade 12');
                    gradeSelect.appendChild(option);
                });
            };

            const syncMarkInputs = () => {
                subjectRows.forEach(({ checkbox, markInput, row }) => {
                    if (!markInput) return;
                    markInput.disabled = !checkbox.checked;
                    if (!checkbox.checked) {
                        // Keep existing value for UX when toggling, but exclude from submit by clearing name? Keep value for when re-checked.
                    }
                    row?.classList.toggle('border-[#01225E]', checkbox.checked);
                    row?.classList.toggle('bg-[#01225E]/5', checkbox.checked);
                });
            };

            const syncSummary = () => {
                let apsSum = 0;
                let markSum = 0;
                let counted = 0;

                subjectRows.forEach(({ checkbox, markInput }) => {
                    if (!checkbox.checked || !markInput || markInput.dataset.excludeSummary === '1' || markInput.value === '') {
                        return;
                    }

                    const mark = Number(markInput.value);
                    if (Number.isNaN(mark)) {
                        return;
                    }

                    apsSum += apsFor(markInput.value);
                    markSum += mark;
                    counted++;
                });

                apsTotal.textContent = apsSum;
                aggregateAverage.textContent = counted > 0 ? `${(markSum / counted).toFixed(1)}%` : '0%';
            };

            const renderSelectedSubjects = () => {
                const selected = checkboxes.filter((checkbox) => checkbox.checked);
                selectedList.innerHTML = '';

                selected.forEach((checkbox) => {
                    const row = checkbox.closest('.subject-row');
                    const markInput = row?.querySelector('.mark-input');
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'inline-flex items-center gap-2 rounded-full border border-[#01225E]/25 bg-white px-3 py-2 text-sm font-semibold text-neutral-900 shadow-sm hover:border-[#01225E]';
                    item.setAttribute('aria-label', `Remove ${checkbox.dataset.name}`);

                    const name = document.createElement('span');
                    name.textContent = checkbox.dataset.name;

                    const mark = document.createElement('span');
                    mark.className = 'text-xs font-bold text-neutral-400';
                    mark.textContent = markInput?.value ? `${markInput.value}%` : 'No mark';

                    const remove = document.createElement('span');
                    remove.className = 'text-[#01225E]';
                    remove.setAttribute('aria-hidden', 'true');
                    remove.textContent = 'x';

                    item.append(name, mark, remove);
                    item.addEventListener('click', () => {
                        checkbox.checked = false;
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    selectedList.appendChild(item);
                });

                const remaining = Math.max(minimumSubjects - selected.length, 0);
                selectedEmpty.classList.toggle('hidden', selected.length > 0);
                countText.textContent = `${selected.length} selected · ${minimumSubjects} minimum`;

                if (remaining > 0) {
                    badge.textContent = `Need ${remaining} more`;
                    badge.className = 'inline-flex w-fit items-center rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700';
                } else {
                    badge.textContent = 'Ready';
                    badge.className = 'inline-flex w-fit items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700';
                }

                syncMarkInputs();
                syncSummary();
            };

            const renderSubjectSearch = () => {
                const query = (searchInput?.value ?? '').trim().toLowerCase();
                let visibleCount = 0;

                subjectRows.forEach(({ row, text }) => {
                    const visible = query === '' || text.includes(query);
                    row?.classList.toggle('hidden', !visible);
                    visibleCount += visible ? 1 : 0;
                });

                if (searchClear) {
                    searchClear.classList.toggle('hidden', query === '');
                }

                if (searchCount) {
                    const total = subjectRows.length;
                    searchCount.textContent = query === ''
                        ? `${total} ${total === 1 ? 'subject' : 'subjects'}`
                        : `${visibleCount} of ${total} ${total === 1 ? 'subject' : 'subjects'}`;
                }

                searchEmpty?.classList.toggle('hidden', visibleCount > 0 || query === '');
            };

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    sortSubjectRows();
                    renderSelectedSubjects();
                    renderSubjectSearch();
                    saveSubjects();
                });
            });
            subjectRows.forEach(({ markInput }) => {
                markInput?.addEventListener('input', () => {
                    const apsTarget = document.getElementById(markInput.dataset.apsTarget);
                    if (apsTarget) {
                        apsTarget.value = apsFor(markInput.value);
                    }
                    renderSelectedSubjects();
                    scheduleAutosave();
                });
                if (markInput) {
                    const apsTarget = document.getElementById(markInput.dataset.apsTarget);
                    if (apsTarget) {
                        apsTarget.value = apsFor(markInput.value);
                    }
                }
            });

            searchInput?.addEventListener('input', renderSubjectSearch);
            searchClear?.addEventListener('click', () => {
                searchInput.value = '';
                searchInput.focus();
                renderSubjectSearch();
            });

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                saveSubjects();
            });

            curriculumSelect.addEventListener('change', () => {
                refreshGrades();
                document.getElementById('context-form').submit();
            });
            gradeSelect.addEventListener('change', () => {
                document.getElementById('context-form').submit();
            });

            refreshGrades();
            sortSubjectRows();
            renderSelectedSubjects();
            renderSubjectSearch();
            lastSavedSnapshot = subjectStateSnapshot();
            setAutosaveStatus('Saved', 'saved');
        })();
    </script>
@endpush
