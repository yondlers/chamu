@php
    $honeypot = config('bots.honeypot_field', 'hp_field');
    $startedAt = config('bots.form_started_at_field', 'form_started_at');
@endphp

<div class="pointer-events-none absolute left-0 top-0 h-0 w-0 overflow-hidden opacity-0" aria-hidden="true">
    <label for="{{ $honeypot }}">Leave this field empty</label>
    <input id="{{ $honeypot }}" name="{{ $honeypot }}" type="text" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ $startedAt }}" value="{{ Illuminate\Support\Facades\Crypt::encryptString((string) now()->getTimestamp()) }}">
