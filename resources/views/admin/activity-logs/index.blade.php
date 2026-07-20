@extends('layouts.app')

@section('title', 'Activity Logs - Admin - Chamu')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Admin dashboard
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Activity logs</p>
                <h1 class="mt-1 text-3xl font-bold">Activity logs</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">Site visits and audit events in one operational timeline.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.site-visits.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-bold hover:bg-neutral-50">
                    Site visits <i data-lucide="mouse-pointer-click" style="width:16px;height:16px;"></i>
                </a>
                <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-bold hover:bg-neutral-50">
                    Audit log <i data-lucide="file-search" style="width:16px;height:16px;"></i>
                </a>
            </div>
        </div>

        <section class="mb-6 grid gap-3 md:grid-cols-4">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Total activity</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalActivities) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Visits plus audits</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Active now</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($activeVisitors) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Last 10 minutes</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Site visits</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalVisits) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Captured page views</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Audit records</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalAuditLogs) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Tracked events</p>
            </div>
        </section>

        <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold">Unified timeline</h2>
                    <p class="mt-1 text-sm text-neutral-500">Showing the latest {{ number_format($activityLogs->count()) }} combined events.</p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-700">
                    <i data-lucide="clock-3" style="width:14px;height:14px;"></i>
                    Latest first
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1040px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">Type</th>
                            <th class="px-3 py-3">Activity</th>
                            <th class="px-3 py-3">Actor</th>
                            <th class="px-3 py-3">Context</th>
                            <th class="px-3 py-3">Time</th>
                            <th class="py-3 pl-3 text-right">More</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activityLogs as $activity)
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-4 pr-3">
                                    <span class="{{ $activity['tone'] }} inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold">
                                        <i data-lucide="{{ $activity['icon'] }}" style="width:14px;height:14px;"></i>
                                        {{ $activity['type'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-4">
                                    <p class="max-w-md truncate text-sm font-bold text-neutral-950">{{ $activity['title'] }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $activity['meta'] }}</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $activity['actor'] }}</td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $activity['context'] }}</td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-600">{{ $activity['occurred_at']?->format('d M Y H:i') ?? 'N/A' }}</td>
                                <td class="py-4 pl-3 text-right">
                                    <a href="{{ $activity['href'] }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                                        More <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm font-semibold text-neutral-500">No activity captured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
@endsection
