@php
    $fields = $model->getFillable();
@endphp

<section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
    <div class="mb-4">
        <p class="text-xs font-bold uppercase text-neutral-500">Table</p>
        <h2 class="mt-1 text-xl font-bold text-neutral-950">{{ $model->getTable() }}</h2>
    </div>

    <dl class="grid gap-3">
        @foreach ($fields as $field)
            @php
                $value = $model->getAttribute($field);
            @endphp
            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                <dt class="text-xs font-bold uppercase text-neutral-500">{{ str($field)->replace('_', ' ')->title() }}</dt>
                <dd class="mt-2 text-sm font-semibold text-neutral-900">
                    @if ($value instanceof \Illuminate\Support\Carbon)
                        {{ $value->format('d M Y H:i:s') }}
                    @elseif (is_array($value))
                        <pre class="max-h-80 overflow-auto whitespace-pre-wrap rounded-lg bg-white p-3 text-xs font-semibold text-neutral-700">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    @elseif (is_bool($value))
                        {{ $value ? 'true' : 'false' }}
                    @elseif ($value === null || $value === '')
                        <span class="text-neutral-400">N/A</span>
                    @else
                        <span class="break-words">{{ $value }}</span>
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
</section>
