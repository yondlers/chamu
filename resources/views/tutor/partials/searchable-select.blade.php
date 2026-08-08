@php
    $inputId = $inputId ?? $name;
    $hiddenName = $hiddenName ?? $name;
    $otherName = $otherName ?? null;
    $isOtherName = $isOtherName ?? null;
    $selectedId = $selectedId ?? null;
    $selectedLabel = $selectedLabel ?? '';
    $otherValue = $otherValue ?? '';
    $placeholder = $placeholder ?? 'Search…';
    $searchUrl = $searchUrl ?? '';
    $dependsOn = $dependsOn ?? null;
@endphp

<div
    class="js-searchable-select"
    data-search-url="{{ $searchUrl }}"
    @if ($dependsOn) data-depends-on="{{ $dependsOn }}" @endif
>
    <input type="hidden" name="{{ $hiddenName }}" class="js-searchable-id" value="{{ $selectedId && $selectedId !== 'other' ? $selectedId : '' }}">
    @if ($isOtherName)
        <input type="hidden" name="{{ $isOtherName }}" class="js-searchable-is-other" value="{{ $selectedId === 'other' || filled($otherValue) ? 1 : 0 }}">
    @endif

    <div class="relative">
        <input
            id="{{ $inputId }}"
            type="search"
            class="js-searchable-input w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]"
            placeholder="{{ $placeholder }}"
            value="{{ $selectedLabel }}"
            autocomplete="off"
        >
        <div class="js-searchable-results absolute z-20 mt-1 hidden max-h-64 w-full overflow-auto rounded-xl border border-neutral-200 bg-white shadow-lg"></div>
    </div>

    @if ($otherName)
        <div class="js-searchable-other mt-3 {{ ($selectedId === 'other' || filled($otherValue)) ? '' : 'hidden' }}">
            <label class="block text-sm font-semibold mb-2">Specify other</label>
            <input
                type="text"
                name="{{ $otherName }}"
                value="{{ $otherValue }}"
                class="js-searchable-other-input w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]"
                placeholder="Type the name"
            >
        </div>
    @endif
</div>
