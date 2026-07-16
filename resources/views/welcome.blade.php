@extends('layouts.app')

@section('title', 'Chamu')

@php
    $displaySubjects = $subjects;
    $featuredSubjects = $displaySubjects->take(8);
    $firstSubject = $displaySubjects->first();
@endphp

@section('content')
    <section class="w-full border-b border-neutral-100" style="background: linear-gradient(180deg, #F3F7FC 0%, #fff 72%);">
        <div class="max-w-6xl mx-auto px-5 lg:px-8 pt-12 pb-8 text-center fade-in">
            <p class="text-sm font-semibold tracking-wide uppercase mb-3 text-[#01225E]">
                {{ $defaultGrade->name ?? 'Grade 12' }} · {{ optional($defaultCurriculum)->abbreviation ?? 'CAPS' }}
            </p>
            @auth
                <h1 class="text-4xl md:text-5xl font-bold tracking-normal text-neutral-950">Welcome back, {{ auth()->user()->first_name }}</h1>
                <p class="mt-3 text-base md:text-lg text-neutral-600">Your learning dashboard is ready. Choose a subject, paper, and mode to jump back in.</p>
            @else
                <h1 class="text-4xl md:text-5xl font-bold tracking-normal text-neutral-950">Find the right learning path</h1>
                <p class="mt-3 text-base md:text-lg text-neutral-600">Explore subjects, practice questions, and course options without assuming a grade or account.</p>
            @endauth

            <form id="search-form" method="GET" action="{{ route('content.index') }}" class="surface rounded-[28px] soft-card mt-7 p-2 sm:pl-2">
                <div class="flex flex-col md:flex-row md:items-stretch">
                    @guest
                        <div class="flex-1 text-left px-5 py-3 rounded-2xl hover:bg-neutral-50">
                            <label for="f-curriculum" class="flex items-center gap-1.5 text-xs font-bold mb-1"><i data-lucide="school" style="width:14px;height:14px;"></i>Curriculum</label>
                            <select id="f-curriculum" name="curriculum_id" class="filter-select">
                                @forelse ($curriculums as $curriculum)
                                    <option value="{{ $curriculum->id }}" @selected(optional($defaultCurriculum)->id === $curriculum->id)>
                                        {{ $curriculum->abbreviation ?: $curriculum->name }}
                                    </option>
                                @empty
                                    <option value="">No curriculums seeded</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="hidden md:block w-px my-3 bg-neutral-200"></div>
                        <div class="flex-1 text-left px-5 py-3 rounded-2xl hover:bg-neutral-50">
                            <label for="f-grade" class="flex items-center gap-1.5 text-xs font-bold mb-1"><i data-lucide="badge-check" style="width:14px;height:14px;"></i>Grade</label>
                            <select id="f-grade" name="grade_id" class="filter-select">
                                @forelse ($grades as $grade)
                                    <option value="{{ $grade->id }}" @selected(optional($defaultGrade)->id === $grade->id)>{{ $grade->name }}</option>
                                @empty
                                    <option value="">No grades seeded</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="hidden md:block w-px my-3 bg-neutral-200"></div>
                    @endguest
                    <div class="flex-1 text-left px-5 py-3 rounded-2xl hover:bg-neutral-50">
                        <label for="f-subject" class="flex items-center gap-1.5 text-xs font-bold mb-1"><i data-lucide="book-open" style="width:14px;height:14px;"></i>Subject</label>
                        <input id="f-subject-search" list="subject-options" class="filter-select" placeholder="Search subject" autocomplete="off">
                        <input id="f-subject" name="subject_id" type="hidden">
                        <datalist id="subject-options">
                            @foreach ($displaySubjects as $subject)
                                <option value="{{ $subject->name }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="hidden md:block w-px my-3 bg-neutral-200"></div>
                    <div class="flex-1 text-left px-5 py-3 rounded-2xl hover:bg-neutral-50">
                        <label for="f-paper" class="flex items-center gap-1.5 text-xs font-bold mb-1"><i data-lucide="files" style="width:14px;height:14px;"></i>Paper</label>
                        <select id="f-paper" name="paper_id" class="filter-select">
                            <option value="all">All Papers</option>
                            @forelse ($papers as $paper)
                                <option value="{{ $paper->id }}">Paper {{ $paper->number }}</option>
                            @empty
                                <option value="">No papers seeded</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="hidden md:block w-px my-3 bg-neutral-200"></div>
                    <div class="flex-1 text-left px-5 py-3 rounded-2xl hover:bg-neutral-50">
                        <label for="f-mode" class="flex items-center gap-1.5 text-xs font-bold mb-1"><i data-lucide="target" style="width:14px;height:14px;"></i>Mode</label>
                        <select id="f-mode" name="mode" class="filter-select">
                            <option value="all">All Modes</option>
                            <option value="learn">Learn</option>
                            <option value="practice">Practice</option>
                            <option value="exam">Exam</option>
                        </select>
                    </div>
                    <div class="flex items-center p-2 md:pl-1">
                        <button id="start-learning" type="submit" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl font-semibold whitespace-nowrap bg-[#01225E] text-white shadow-sm hover:bg-[#001A48]">
                            Start <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </section>

    <main class="max-w-7xl mx-auto px-5 lg:px-8 pb-20">
        <section class="grid gap-4 pt-8 md:grid-cols-3 fade-in">
            <a href="{{ route('learn.index') }}" class="group rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-[#01225E]">
                    <i data-lucide="book-open" style="width:22px;height:22px;"></i>
                </span>
                <h2 class="mt-4 text-xl font-bold">Learn</h2>
                <p class="mt-2 text-sm text-neutral-500">Past papers, questions, notes, practice, and revision.</p>
                <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-[#01225E]">
                    Start learning <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                </span>
            </a>
            <a href="{{ auth()->check() ? route('course-match.index') : route('aps.index') }}" class="group rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                    <i data-lucide="target" style="width:22px;height:22px;"></i>
                </span>
                <h2 class="mt-4 text-xl font-bold">APS</h2>
                <p class="mt-2 text-sm text-neutral-500">Course match from your APS score or your saved subject marks.</p>
                <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-[#01225E]">
                    Match courses <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                </span>
            </a>
            <a href="{{ route('funding.index') }}" class="group rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                    <i data-lucide="badge-dollar-sign" style="width:22px;height:22px;"></i>
                </span>
                <h2 class="mt-4 text-xl font-bold">Funding</h2>
                <p class="mt-2 text-sm text-neutral-500">Bursary match, companies, links, closing dates, and requirements.</p>
                <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-[#01225E]">
                    Find funding <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                </span>
            </a>
        </section>

        @include('partials.adsense-home-placement', ['class' => 'my-8 fade-in'])

        <div class="pt-10 pb-2 fade-in">
            <h2 class="font-bold text-[32px]">@auth What would you like to master today? @else Start learning @endauth</h2>
            <p class="mt-1 text-lg text-neutral-500">@auth Pick up where you left off, or start something fresh. @else Pick a subject and try the app, or sign in to track streaks and points. @endauth</p>
        </div>

        @if (session('status'))
            <p class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
        @endif

        @auth
            @if ($pendingQuizzes->isNotEmpty())
                <section class="mt-8">
                    <div class="flex items-end justify-between gap-4 mb-4">
                        <div>
                            <p class="text-sm font-semibold text-[#01225E]">Continue quiz</p>
                            <h2 class="font-bold text-2xl">Open practice sessions</h2>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-3 gap-5">
                        @foreach ($pendingQuizzes as $quiz)
                            <article class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-[#01225E]">{{ $quiz->quiz_type === 'random' ? 'Randomized' : 'Source' }}</span>
                                <h3 class="mt-3 font-bold">{{ $quiz->title }}</h3>
                                <p class="mt-1 text-sm text-neutral-500">{{ $quiz->subject_name }}{{ $quiz->source ? ' · ' . $quiz->source : '' }}</p>
                                <a href="{{ route('practice.show', $quiz->id) }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-semibold text-white">
                                    Continue <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                                </a>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @endauth

        <section class="mt-8">
            <div class="flex items-end justify-between gap-4 mb-4">
                <div>
                    <p class="text-sm font-semibold text-[#01225E]">Subject library</p>
                    <h2 class="font-bold text-2xl">Browse subjects</h2>
                </div>
                <button class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-neutral-200 bg-white text-sm font-semibold hover:border-neutral-300">
                    View all <i data-lucide="chevron-right" style="width:16px;height:16px;"></i>
                </button>
            </div>
            <div class="flex gap-4 overflow-x-auto no-scrollbar pb-3 -mx-1 px-1">
                @forelse ($featuredSubjects as $subject)
                    @php
                        $colour = $subject->colour ?: '#01225E';
                        $icon = $subject->icon ?: 'book-open';
                        $progress = 25 + (crc32($subject->name) % 55);
                    @endphp
                    <article class="shrink-0 w-60 min-h-64 rounded-3xl border border-neutral-200 p-5 soft-card bg-white flex flex-col">
                        <div class="flex items-start justify-between gap-3 mb-5">
                            <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl" style="background: {{ $colour }}14; color: {{ $colour }};">
                                <i data-lucide="{{ $icon }}" style="width:22px;height:22px;"></i>
                            </span>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-neutral-100 text-neutral-600">{{ $subject->code ?? $subject->abbreviation ?? 'SUBJ' }}</span>
                        </div>
                        <h3 class="font-semibold text-[17px] line-clamp-2 min-h-[48px]">{{ $subject->name }}</h3>
                        <div class="mt-3 h-2 rounded-full bg-neutral-100 overflow-hidden">
                            <div class="h-full" style="width: {{ $progress }}%; background: {{ $colour }};"></div>
                        </div>
                        <p class="text-sm mt-2 mb-4 text-neutral-500">{{ $progress }}% complete</p>
                        <button data-action="Continuing {{ $subject->name }}" class="js-btn mt-auto w-full py-2.5 rounded-xl font-semibold text-sm bg-neutral-100 text-neutral-900 hover:bg-neutral-200">Continue</button>
                    </article>
                @empty
                    <p class="text-sm text-neutral-500">No subjects found in the database for the selected curriculum and grade.</p>
                @endforelse
            </div>
        </section>

        <section class="mt-12">
            <div class="flex items-end justify-between gap-4 mb-4">
                <div>
                    <p class="text-sm font-semibold text-[#01225E]">Next up</p>
                    <h2 class="font-bold text-2xl">Continue learning</h2>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach ($displaySubjects->take(3) as $subject)
                    <article class="rounded-3xl border border-neutral-200 p-6 soft-card bg-white flex flex-col relative overflow-hidden">
                        <div class="absolute inset-x-0 top-0 h-1" style="background: {{ $subject->colour ?: '#01225E' }};"></div>
                        <span class="self-start px-3 py-1 rounded-full text-xs font-semibold mb-3" style="background: {{ ($subject->colour ?: '#01225E') }}18; color: {{ $subject->colour ?: '#01225E' }};">{{ $subject->name }}</span>
                        <h3 class="font-semibold mb-4 text-[19px]">{{ $subject->name }}: Core revision</h3>
                        <ul class="space-y-2 mb-6 flex-1">
                            <li class="flex items-center gap-2 text-sm text-neutral-600"><i data-lucide="file-text" style="width:16px;height:16px;"></i><span>Learn notes</span></li>
                            <li class="flex items-center gap-2 text-sm text-neutral-600"><i data-lucide="layers" style="width:16px;height:16px;"></i><span>Flashcards</span></li>
                            <li class="flex items-center gap-2 text-sm text-neutral-600"><i data-lucide="circle-help" style="width:16px;height:16px;"></i><span>Practice questions</span></li>
                        </ul>
                        <button data-action="Resuming {{ $subject->name }}" class="js-btn w-full py-3 rounded-xl font-semibold text-sm bg-[#01225E] text-white hover:bg-[#001A48]">Resume</button>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="mt-12">
            <h2 class="font-bold mb-4 text-2xl">Past paper practice</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @forelse ($papers->take(2) as $paper)
                    <article class="rounded-3xl border border-neutral-200 p-6 soft-card bg-white">
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700">{{ optional($defaultCurriculum)->abbreviation ?? 'NSC' }}</span>
                            <span class="text-sm font-medium text-neutral-500">Practice set</span>
                        </div>
                        <h3 class="font-semibold mb-2 text-lg">{{ optional($firstSubject)->name ?? 'Mathematics' }} · Paper {{ $paper->number }}</h3>
                        <p class="text-sm text-neutral-500 mb-5">180 min · 150 marks</p>
                        <div class="flex gap-3">
                            <button data-action="Starting Paper {{ $paper->number }} exam" class="js-btn flex-1 py-2.5 rounded-xl font-semibold text-sm bg-[#01225E] text-white">Write Exam</button>
                            <button data-action="Random practice from Paper {{ $paper->number }}" class="js-btn flex-1 py-2.5 rounded-xl font-semibold text-sm border border-neutral-300 bg-white">Random Practice</button>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-neutral-500">No papers found in the database.</p>
                @endforelse
            </div>
        </section>

        <section class="mt-12">
            <article class="rounded-3xl p-8 soft-card border border-neutral-200 grid md:grid-cols-2 gap-8 items-center" style="background: linear-gradient(135deg, rgb(243, 247, 252) 0%, rgb(229, 237, 248) 100%);">
                <div>
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-white/70 text-[#01225E] mb-4"><i data-lucide="shuffle" style="width:24px;height:24px;"></i></span>
                    <h2 class="font-bold mb-2 text-2xl">Randomized Practice</h2>
                    <p class="text-neutral-700">Get mixed questions from past papers, topics, and study notes.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-neutral-200">
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        @foreach (['10 questions', '20 questions', 'Timed mode', 'Show answers immediately'] as $option)
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-neutral-200 cursor-pointer hover:border-[#01225E]">
                                <input type="checkbox" class="sp-opt accent-[#01225E]" @checked($loop->first || $loop->last)>
                                <span class="text-sm font-medium">{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button id="generate-practice" class="w-full py-3 rounded-xl font-semibold bg-[#01225E] text-white">Generate Practice</button>
                </div>
            </article>
        </section>

        <section class="mt-12">
            <h2 class="font-bold mb-4 text-2xl">Study notes & flashcards</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($displaySubjects->take(4) as $subject)
                    <article class="rounded-3xl border border-neutral-200 p-5 soft-card bg-white flex flex-col">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl mb-3" style="background: {{ ($subject->colour ?: '#01225E') }}14; color: {{ $subject->colour ?: '#01225E' }};"><i data-lucide="layers" style="width:20px;height:20px;"></i></span>
                        <h3 class="font-semibold mb-1">{{ $subject->name }} Key Cards</h3>
                        <p class="text-sm text-neutral-500 mb-4 flex-1">{{ 18 + (crc32($subject->name) % 24) }} cards</p>
                        <button data-action="Studying {{ $subject->name }} cards" class="js-btn w-full py-2.5 rounded-xl font-semibold text-sm bg-neutral-100">Study</button>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="mt-12">
            <article class="rounded-3xl p-8 soft-card flex flex-col md:flex-row items-center gap-6 text-center md:text-left" style="background: linear-gradient(135deg, rgb(1, 34, 94) 0%, rgb(0, 26, 72) 100%);">
                <span class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/20 text-white shrink-0"><i data-lucide="sparkles" style="width:30px;height:30px;"></i></span>
                <div class="flex-1">
                    <h2 class="font-bold mb-1 text-2xl text-white">Stuck on a question?</h2>
                    <p class="opacity-90 text-sm text-blue-50">Get step-by-step explanations based on CAPS and IEB.</p>
                </div>
                <button id="ai-tutor" class="px-7 py-3.5 rounded-xl font-semibold whitespace-nowrap bg-white text-[#01225E]">Ask AI Tutor</button>
            </article>
        </section>

        <footer class="mt-14 pt-6 border-t border-neutral-200 text-center">
            <p class="text-sm text-neutral-400">Chamu · Built for South African Grade 10-12 learners</p>
        </footer>
    </main>
@endsection

@push('scripts')
    <script>
        const grades = @json($allGrades->values());
        const subjects = @json($allSubjects->values());
        const currentSubjects = @json($displaySubjects->values());

        const curriculumSelect = document.getElementById('f-curriculum');
        const gradeSelect = document.getElementById('f-grade');
        const subjectSelect = document.getElementById('f-subject');
        const subjectSearch = document.getElementById('f-subject-search');
        const subjectOptions = document.getElementById('subject-options');

        const replaceOptions = (select, rows, emptyLabel, labelFor) => {
            select.innerHTML = '';

            if (!rows.length) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = emptyLabel;
                select.appendChild(option);
                return;
            }

            rows.forEach((row) => {
                const option = document.createElement('option');
                option.value = row.id;
                option.textContent = labelFor(row);
                select.appendChild(option);
            });
        };

        const setSubjectRows = (rows) => {
            subjectOptions.innerHTML = '';

            rows.forEach((subject) => {
                const option = document.createElement('option');
                option.value = subject.name;
                subjectOptions.appendChild(option);
            });

            subjectSearch.value = '';
            subjectSelect.value = '';
        };

        const selectedSubjectRows = () => {
            if (!curriculumSelect || !gradeSelect) {
                return currentSubjects;
            }

            const curriculumId = Number(curriculumSelect.value);
            const gradeId = Number(gradeSelect.value);
            return subjects.filter((subject) => Number(subject.curriculum_id) === curriculumId && Number(subject.grade_id) === gradeId);
        };

        const syncSubjectId = () => {
            const row = selectedSubjectRows().find((subject) => subject.name.toLowerCase() === subjectSearch.value.trim().toLowerCase());
            subjectSelect.value = row ? row.id : '';
        };

        const refreshSubjects = () => {
            setSubjectRows(selectedSubjectRows());
        };

        subjectSearch.addEventListener('input', syncSubjectId);

        if (curriculumSelect && gradeSelect) {
            curriculumSelect.addEventListener('change', () => {
                const curriculumId = Number(curriculumSelect.value);
                const rows = grades.filter((grade) => Number(grade.curriculum_id) === curriculumId);

                replaceOptions(gradeSelect, rows, 'No grades seeded', (grade) => grade.name);
                refreshSubjects();
            });

            gradeSelect.addEventListener('change', refreshSubjects);
        }

        document.getElementById('search-form').addEventListener('submit', (event) => {
            syncSubjectId();

            if (!subjectSelect.value) {
                event.preventDefault();
                showToast('Choose a subject from the searchable dropdown');
            }
        });

        document.getElementById('generate-practice').addEventListener('click', () => {
            const labels = [...document.querySelectorAll('.sp-opt')]
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.nextElementSibling.textContent.trim());

            showToast(labels.length ? `Generating: ${labels.join(', ')}` : 'Select at least one option to continue');
        });

        document.getElementById('ai-tutor').addEventListener('click', () => {
            showToast('Opening AI Tutor');
        });
    </script>
@endpush
