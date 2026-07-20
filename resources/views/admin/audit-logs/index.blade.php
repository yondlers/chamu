@extends('layouts.app')

@section('title', 'Audit Logs - Admin - Chamu')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Admin dashboard
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Audit logs</p>
                <h1 class="mt-1 text-3xl font-bold">Audit log</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">All captured audit records. The first load is not filtered to one event type.</p>
            </div>
        </div>

        <section class="mb-6 grid gap-3 md:grid-cols-2">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Total audit records</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalAuditLogs) }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Mark updates</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($markAuditLogs) }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold">Audit records</h2>
                <p class="mt-1 text-sm text-neutral-500">Showing {{ $auditLogs->firstItem() ?? 0 }}-{{ $auditLogs->lastItem() ?? 0 }} of {{ number_format($auditLogs->total()) }} records.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">Event</th>
                            <th class="px-3 py-3">User</th>
                            <th class="px-3 py-3">Auditable</th>
                            <th class="px-3 py-3">IP</th>
                            <th class="px-3 py-3">Time</th>
                            <th class="py-3 pl-3 text-right">More</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($auditLogs as $log)
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-4 pr-3">
                                    <p class="font-bold text-neutral-950">{{ $log->name }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $log->event ?? 'No event key' }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    <p class="text-sm font-bold text-neutral-900">{{ $log->user?->name ?? 'No user' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $log->user?->email ?? 'N/A' }}</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">
                                    {{ class_basename($log->auditable_type ?? '') ?: 'N/A' }} {{ $log->auditable_id ? '#'.$log->auditable_id : '' }}
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $log->ip_address ?? 'Unknown' }}</td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $log->created_at?->format('d M Y H:i') ?? 'N/A' }}</td>
                                <td class="py-4 pl-3 text-right">
                                    <a href="{{ route('admin.audit-logs.show', $log) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                                        More <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm font-semibold text-neutral-500">No audit records captured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($auditLogs->hasPages())
                <div class="mt-5">
                    {{ $auditLogs->links() }}
                </div>
            @endif
        </section>
    </main>
@endsection
