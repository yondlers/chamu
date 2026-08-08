@php
    $subjectId = $row['subject_id'] ?? '';
    $subjectOther = $row['subject_other'] ?? '';
    $isOther = blank($subjectId) && filled($subjectOther);
    $subjectLabel = $row['subject_label'] ?? ($isOther ? $subjectOther : '');
@endphp

<div class="js-dynamic-row grid gap-3 rounded-xl border border-neutral-200 p-4">
    <div class="js-searchable-select" data-search-url="{{ $subjectsUrl }}">
        <input type="hidden" name="subjects[{{ $index }}][subject_id]" class="js-searchable-id" value="{{ $isOther ? '' : $subjectId }}">
        <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Subject offered</label>
        <div class="relative">
            <input type="search" class="js-searchable-input w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]" placeholder="Search subjects…" value="{{ $isOther ? 'Other' : $subjectLabel }}" autocomplete="off">
            <div class="js-searchable-results absolute z-20 mt-1 hidden max-h-64 w-full overflow-auto rounded-xl border border-neutral-200 bg-white shadow-lg"></div>
        </div>
        <div class="js-searchable-other mt-3 {{ $isOther ? '' : 'hidden' }}">
            <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Other subject</label>
            <input type="text" name="subjects[{{ $index }}][subject_other]" value="{{ $subjectOther }}" class="js-searchable-other-input w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-[0.8fr_1fr_auto]">
        <div>
            <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Rate / hour</label>
            <div class="relative">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-neutral-400">R</span>
                <input type="number" min="50" max="5000" step="10" name="subjects[{{ $index }}][hourly_rate]" value="{{ $row['hourly_rate'] ?? '' }}" required class="w-full rounded-xl border border-neutral-300 py-2.5 pl-8 pr-3 outline-none focus:border-[#01225E]">
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Level</label>
            <select name="subjects[{{ $index }}][level]" required class="w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">
                @foreach ($levelOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($row['level'] ?? 'high_school') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="button" class="js-remove-row inline-flex h-11 w-11 items-center justify-center rounded-xl border border-neutral-300 text-neutral-600 hover:bg-neutral-50" aria-label="Remove">
                <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
            </button>
        </div>
    </div>
</div>
