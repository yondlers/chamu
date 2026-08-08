<div class="js-dynamic-row grid gap-3 rounded-xl border border-neutral-200 p-4 sm:grid-cols-[1.2fr_0.9fr_0.9fr_auto]">
    <div>
        <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">Day</label>
        <select name="availabilities[{{ $index }}][day_of_week]" required class="w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">
            @foreach ($dayOptions as $value => $label)
                <option value="{{ $value }}" @selected((int) ($row['day_of_week'] ?? 1) === (int) $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">From</label>
        <input type="time" name="availabilities[{{ $index }}][start_time]" value="{{ $row['start_time'] ?? '09:00' }}" required class="w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">
    </div>
    <div>
        <label class="block text-xs font-bold uppercase tracking-wide text-neutral-500 mb-1">To</label>
        <input type="time" name="availabilities[{{ $index }}][end_time]" value="{{ $row['end_time'] ?? '12:00' }}" required class="w-full rounded-xl border border-neutral-300 px-3 py-2.5 outline-none focus:border-[#01225E]">
    </div>
    <div class="flex items-end">
        <button type="button" class="js-remove-row inline-flex h-11 w-11 items-center justify-center rounded-xl border border-neutral-300 text-neutral-600 hover:bg-neutral-50" aria-label="Remove">
            <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
        </button>
    </div>
    <input type="hidden" name="availabilities[{{ $index }}][is_available]" value="1">
</div>
