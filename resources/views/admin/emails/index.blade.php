@extends('layouts.app')

@section('title', 'Emails - Admin - Chamu')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Admin dashboard
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Email tracking</p>
                <h1 class="mt-1 text-3xl font-bold">Email outbox</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">Recorded welcome emails, application submissions, receipts, archive copies, and delivery outcomes.</p>
            </div>
        </div>

        <section class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Recorded emails</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalEmails) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-bold uppercase text-emerald-700">Sent</p>
                <p class="mt-2 text-3xl font-bold text-emerald-900">{{ number_format($sentEmails) }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                <p class="text-xs font-bold uppercase text-rose-700">Failed</p>
                <p class="mt-2 text-3xl font-bold text-rose-900">{{ number_format($failedEmails) }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-bold uppercase text-amber-700">Sending / unknown</p>
                <p class="mt-2 text-3xl font-bold text-amber-900">{{ number_format($sendingEmails) }}</p>
            </div>
        </section>

        <section class="mb-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-bold">Find email records</h2>
                    <p class="mt-1 text-sm text-neutral-500">
                        Archive mailbox:
                        <span class="font-bold text-neutral-800">{{ filled($archiveAddress) ? $archiveAddress : 'Not configured' }}</span>
                    </p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.emails.index') }}" class="grid gap-3 lg:grid-cols-[1fr_220px_220px_auto]">
                <input
                    type="search"
                    name="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Search subject, recipient, applicant, bursary, company"
                    class="w-full rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-semibold outline-none focus:border-[#01225E]"
                >
                <select name="status" class="rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-semibold outline-none focus:border-[#01225E]">
                    <option value="">All statuses</option>
                    @foreach (['sent' => 'Sent', 'failed' => 'Failed', 'sending' => 'Sending / unknown'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="type" class="rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-semibold outline-none focus:border-[#01225E]">
                    <option value="">All email types</option>
                    @foreach ($emailTypes as $type)
                        <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ Str::of($type)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#001A48]">
                        <i data-lucide="search" style="width:16px;height:16px;"></i> Search
                    </button>
                    <a href="{{ route('admin.emails.index') }}" class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-bold hover:bg-neutral-50">Reset</a>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold">Recorded emails</h2>
                <p class="mt-1 text-sm text-neutral-500">Showing {{ $emailLogs->firstItem() ?? 0 }}-{{ $emailLogs->lastItem() ?? 0 }} of {{ number_format($emailLogs->total()) }} records.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1120px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">Status</th>
                            <th class="px-3 py-3">Email</th>
                            <th class="px-3 py-3">Recipient</th>
                            <th class="px-3 py-3">Opened</th>
                            <th class="px-3 py-3">Application context</th>
                            <th class="px-3 py-3">Time</th>
                            <th class="py-3 pl-3 text-right">More</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($emailLogs as $emailLog)
                            @php
                                $statusClass = match ($emailLog->status) {
                                    'sent' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                    'failed' => 'border-rose-200 bg-rose-50 text-rose-700',
                                    default => 'border-amber-200 bg-amber-50 text-amber-700',
                                };
                            @endphp
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-4 pr-3">
                                    <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass }}">
                                        <i data-lucide="{{ $emailLog->status === 'sent' ? 'check-circle-2' : ($emailLog->status === 'failed' ? 'triangle-alert' : 'clock') }}" style="width:14px;height:14px;"></i>
                                        {{ $emailLog->statusLabel() }}
                                    </span>
                                    @if ($emailLog->last_error)
                                        <p class="mt-2 max-w-[180px] truncate text-xs font-semibold text-rose-700">{{ $emailLog->last_error }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-4">
                                    <p class="max-w-sm truncate font-bold text-neutral-950">{{ $emailLog->subject ?? 'No subject' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ Str::of($emailLog->email_type ?? 'unknown')->replace('_', ' ')->title() }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    <p class="text-sm font-bold text-neutral-900">{{ $emailLog->primary_recipient_name ?? 'Recipient' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $emailLog->primary_recipient_email ?? 'No email captured' }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    @if ($emailLog->hasBeenOpened())
                                        <p class="text-sm font-bold text-emerald-800">{{ $emailLog->last_opened_at?->format('d M Y H:i') ?? 'Opened' }}</p>
                                        <p class="mt-1 text-xs font-semibold text-neutral-500">{{ (int) $emailLog->open_count }} {{ Str::plural('open', (int) $emailLog->open_count) }}</p>
                                    @else
                                        <p class="text-sm font-bold text-neutral-900">Not opened yet</p>
                                    @endif
                                </td>
                                <td class="px-3 py-4">
                                    <p class="max-w-xs truncate text-sm font-bold text-neutral-900">{{ $emailLog->bursary_title ?? 'No bursary' }}</p>
                                    <p class="mt-1 max-w-xs truncate text-xs font-semibold text-neutral-500">{{ $emailLog->company_name ?? 'No company' }}</p>
                                    @if ($emailLog->applicant_email)
                                        <p class="mt-1 max-w-xs truncate text-xs font-semibold text-neutral-500">{{ $emailLog->applicant_name }} - {{ $emailLog->applicant_email }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">
                                    {{ ($emailLog->sent_at ?? $emailLog->failed_at ?? $emailLog->created_at)?->format('d M Y H:i') ?? 'N/A' }}
                                </td>
                                <td class="py-4 pl-3 text-right">
                                    <a href="{{ route('admin.emails.show', $emailLog) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                                        Open <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-sm font-semibold text-neutral-500">No emails have been recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($emailLogs->hasPages())
                <div class="mt-5">
                    {{ $emailLogs->links() }}
                </div>
            @endif
        </section>
    </main>
@endsection
