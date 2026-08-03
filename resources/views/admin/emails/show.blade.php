@extends('layouts.app')

@section('title', 'Email Record - Admin - Chamu')

@section('content')
    @php
        $statusClass = match ($emailLog->status) {
            'sent' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'failed' => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-amber-200 bg-amber-50 text-amber-700',
        };
        $addressBlock = function (?array $addresses): string {
            return collect($addresses ?? [])
                ->map(fn (array $address): string => trim(($address['name'] ?? '').' <'.($address['email'] ?? '').'>'))
                ->filter(fn (string $address): bool => trim($address, ' <>') !== '')
                ->implode(', ');
        };
    @endphp

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('admin.emails.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Email outbox
                </a>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <p class="text-sm font-bold text-[#01225E]">Email record #{{ $emailLog->id }}</p>
                    <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass }}">
                        <i data-lucide="{{ $emailLog->status === 'sent' ? 'check-circle-2' : ($emailLog->status === 'failed' ? 'triangle-alert' : 'clock') }}" style="width:14px;height:14px;"></i>
                        {{ $emailLog->statusLabel() }}
                    </span>
                </div>
                <h1 class="mt-2 text-3xl font-bold">{{ $emailLog->subject ?? 'No subject' }}</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">{{ Str::of($emailLog->email_type ?? 'unknown')->replace('_', ' ')->title() }} sent to {{ $emailLog->primary_recipient_email ?? 'unknown recipient' }}.</p>
            </div>
        </div>

        <section class="grid gap-4 lg:grid-cols-3">
            <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm lg:col-span-2">
                <h2 class="text-xl font-bold">Message details</h2>
                <dl class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">From</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ $addressBlock($emailLog->from) ?: 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">To</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ $addressBlock($emailLog->to) ?: 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Reply-To</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ $addressBlock($emailLog->reply_to) ?: 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">BCC</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ $addressBlock($emailLog->bcc) ?: 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Mailable</dt>
                        <dd class="mt-1 break-all text-sm font-semibold text-neutral-900">{{ $emailLog->mailable ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Message ID</dt>
                        <dd class="mt-1 break-all text-sm font-semibold text-neutral-900">{{ $emailLog->transport_message_id ?? $emailLog->message_id ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Created</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ $emailLog->created_at?->format('d M Y H:i:s') ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Sent / failed</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ ($emailLog->sent_at ?? $emailLog->failed_at)?->format('d M Y H:i:s') ?? 'Pending' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Open count</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ (int) $emailLog->open_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Last opened</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ $emailLog->last_opened_at?->format('d M Y H:i:s') ?? 'Not opened yet' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Last open IP</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ $emailLog->last_open_ip_address ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Last open user agent</dt>
                        <dd class="mt-1 break-all text-sm font-semibold text-neutral-900">{{ $emailLog->last_open_user_agent ?? 'N/A' }}</dd>
                    </div>
                </dl>

                @if ($emailLog->last_error)
                    <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4">
                        <p class="text-xs font-bold uppercase text-rose-700">Failure reason</p>
                        <p class="mt-2 text-sm font-semibold text-rose-900">{{ $emailLog->last_error }}</p>
                    </div>
                @endif
            </article>

            <aside class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-bold">Application context</h2>
                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Company</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ $emailLog->company_name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Bursary</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ $emailLog->bursary_title ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Applicant</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ $emailLog->applicant_name ?? 'N/A' }}</dd>
                        <dd class="mt-1 text-xs font-semibold text-neutral-500">{{ $emailLog->applicant_email ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-neutral-500">Archive mailbox</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ filled($archiveAddress) ? $archiveAddress : 'Not configured' }}</dd>
                    </div>
                    @if ($emailLog->attachments)
                        <div>
                            <dt class="text-xs font-bold uppercase text-neutral-500">Attachments</dt>
                            <dd class="mt-2 space-y-1">
                                @foreach ($emailLog->attachments as $attachment)
                                    <p class="text-sm font-semibold text-neutral-900">{{ $attachment['filename'] ?? 'Unnamed attachment' }}</p>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            </aside>
        </section>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold">Email preview</h2>
                <p class="mt-1 text-sm text-neutral-500">The body captured at send time.</p>
            </div>

            @if ($emailLog->html_body)
                <iframe
                    title="Email preview"
                    sandbox=""
                    srcdoc="{{ $emailLog->html_body }}"
                    class="h-[720px] w-full rounded-xl border border-neutral-200 bg-white"
                ></iframe>
            @elseif ($emailLog->text_body)
                <pre class="max-h-[720px] overflow-auto whitespace-pre-wrap rounded-xl border border-neutral-200 bg-neutral-50 p-4 text-sm leading-6 text-neutral-800">{{ $emailLog->text_body }}</pre>
            @else
                <p class="rounded-xl border border-neutral-200 bg-neutral-50 p-6 text-center text-sm font-semibold text-neutral-500">No message body was captured.</p>
            @endif
        </section>
    </main>
@endsection
