@extends('layouts.app')

@section('title', 'Choose Subjects · Chamu')

@section('content')
    @php
        $selectedSubjectIds = collect(old('subjects', $selectedSubjectIds))
            ->map(fn ($id) => (int) $id)
            ->all();
    @endphp

    <main class="max-w-4xl mx-auto px-5 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                <p class="text-sm font-semibold text-[#01225E]">Profile setup</p>
                <h1 class="text-3xl font-bold mt-1">Choose your subjects</h1>
                <p class="mt-2 text-neutral-500">Selected subjects shape your search, marks, APS score, and recommendations.</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                <i data-lucide="user-cog" style="width:16px;height:16px;"></i>
                Profile
            </a>
        </div>

        @if (session('status'))
            <p class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form id="subjects-form" method="POST" action="{{ route('subjects.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <section class="sticky top-4 z-10 rounded-2xl border border-neutral-200 bg-white/95 p-4 shadow-sm backdrop-blur">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="font-bold">Selected subjects</h2>
                        <p id="subjects-count" class="text-sm font-semibold text-neutral-500">0 selected · 7 minimum</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600">A-Z</span>
                        <span id="subjects-minimum-badge" class="inline-flex w-fit items-center rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">Need 7</span>
                    </div>
                </div>
                <div id="selected-subjects-list" class="mt-4 flex flex-wrap gap-2"></div>
                <p id="selected-subjects-empty" class="mt-4 text-sm text-neutral-500">Checked subjects will appear here.</p>
            </section>

            <section class="rounded-2xl border border-neutral-200 bg-white p-3 soft-card">
                <div class="grid gap-2">
                    @forelse ($subjects as $subject)
                        <label class="subject-row flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-transparent px-4 py-3 transition hover:border-neutral-200 hover:bg-neutral-50">
                            <span class="min-w-0">
                                <span class="block truncate font-semibold text-neutral-950">{{ $subject->name }}</span>
                                <span class="mt-0.5 block text-xs font-semibold uppercase text-neutral-400">{{ $subject->code ?? $subject->abbreviation ?? 'SUBJ' }}</span>
                            </span>
                            <input
                                type="checkbox"
                                name="subjects[]"
                                value="{{ $subject->id }}"
                                class="subject-checkbox h-5 w-5 shrink-0 rounded border-neutral-300 accent-[#01225E]"
                                data-name="{{ $subject->name }}"
                                data-code="{{ $subject->code ?? $subject->abbreviation ?? 'SUBJ' }}"
                                @checked(in_array($subject->id, $selectedSubjectIds, true))
                            >
                        </label>
                    @empty
                        <div class="rounded-xl bg-neutral-50 px-4 py-6 text-sm text-neutral-500">No subjects found for your curriculum and grade. Update your profile first.</div>
                    @endforelse
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-5 py-3 font-semibold hover:bg-neutral-50">Cancel</a>
                <button id="save-subjects-button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white hover:bg-[#001A48] disabled:cursor-not-allowed disabled:bg-neutral-300 disabled:text-neutral-500">
                    Save subjects <i data-lucide="save" style="width:18px;height:18px;"></i>
                </button>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script>
        (() => {
            const minimumSubjects = 7;
            const form = document.getElementById('subjects-form');
            const checkboxes = Array.from(document.querySelectorAll('.subject-checkbox'));
            const selectedList = document.getElementById('selected-subjects-list');
            const selectedEmpty = document.getElementById('selected-subjects-empty');
            const countText = document.getElementById('subjects-count');
            const badge = document.getElementById('subjects-minimum-badge');
            const saveButton = document.getElementById('save-subjects-button');

            const renderSelectedSubjects = () => {
                const selected = checkboxes.filter((checkbox) => checkbox.checked);
                selectedList.innerHTML = '';

                selected.forEach((checkbox) => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'inline-flex items-center gap-2 rounded-full border border-[#01225E]/25 bg-white px-3 py-2 text-sm font-semibold text-neutral-900 shadow-sm hover:border-[#01225E]';
                    item.setAttribute('aria-label', `Remove ${checkbox.dataset.name}`);

                    const name = document.createElement('span');
                    name.textContent = checkbox.dataset.name;

                    const code = document.createElement('span');
                    code.className = 'text-xs font-bold text-neutral-400';
                    code.textContent = checkbox.dataset.code;

                    const remove = document.createElement('span');
                    remove.className = 'text-[#01225E]';
                    remove.setAttribute('aria-hidden', 'true');
                    remove.textContent = 'x';

                    item.append(name, code, remove);
                    item.addEventListener('click', () => {
                        checkbox.checked = false;
                        renderSelectedSubjects();
                    });
                    selectedList.appendChild(item);
                });

                const remaining = Math.max(minimumSubjects - selected.length, 0);
                selectedEmpty.classList.toggle('hidden', selected.length > 0);
                countText.textContent = `${selected.length} selected · ${minimumSubjects} minimum`;
                saveButton.disabled = selected.length < minimumSubjects;

                if (remaining > 0) {
                    badge.textContent = `Need ${remaining} more`;
                    badge.className = 'inline-flex w-fit items-center rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700';
                } else {
                    badge.textContent = 'Ready';
                    badge.className = 'inline-flex w-fit items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700';
                }

                checkboxes.forEach((checkbox) => {
                    checkbox.closest('.subject-row')?.classList.toggle('border-[#01225E]', checkbox.checked);
                    checkbox.closest('.subject-row')?.classList.toggle('bg-[#01225E]/5', checkbox.checked);
                });
            };

            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', renderSelectedSubjects));
            form.addEventListener('submit', (event) => {
                const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
                if (selectedCount < minimumSubjects) {
                    event.preventDefault();
                    showToast(`Select at least ${minimumSubjects} subjects before saving.`);
                }
            });

            renderSelectedSubjects();
        })();
    </script>
@endpush
