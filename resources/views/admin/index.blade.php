@extends('layouts.app')

@section('title', 'Admin Dashboard - Chamu')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-bold text-[#01225E]">Super admin</p>
                <h1 class="mt-1 text-3xl font-bold">Admin dashboard</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">A focused view for platform operations, user activity, audit records, and social-media publishing preparation.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.activity-logs.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                    Activity <i data-lucide="activity" style="width:16px;height:16px;"></i>
                </a>
                <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                    Audit <i data-lucide="file-search" style="width:16px;height:16px;"></i>
                </a>
                <a href="{{ route('admin.site-visits.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                    Visits <i data-lucide="activity" style="width:16px;height:16px;"></i>
                </a>
                <a href="{{ route('aps.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                    APS <i data-lucide="target" style="width:16px;height:16px;"></i>
                </a>
            </div>
        </div>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Active now</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($activeVisitorCount) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Preview from last 10 minutes</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Total visits</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalVisits) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">All captured page views</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Audit logs</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalAuditLogs) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">All admin-visible events</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Accounts</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalAccounts) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Created in Chamu</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Social channels</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalSocialChannels) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Ready for API setup</p>
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold">Automated marketing</h2>
                    <p class="mt-1 text-sm text-neutral-500">Manage each social platform separately while the API bridge is prepared.</p>
                </div>
                @php
                    $connectedSocialChannels = collect($socialChannels)->where('has_access_token', true)->count();
                @endphp
                <span @class([
                    'inline-flex w-fit items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold',
                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $connectedSocialChannels > 0,
                    'border-amber-200 bg-amber-50 text-amber-700' => $connectedSocialChannels === 0,
                ])>
                    <i data-lucide="{{ $connectedSocialChannels > 0 ? 'key-round' : 'plug-zap' }}" style="width:14px;height:14px;"></i>
                    {{ $connectedSocialChannels > 0 ? $connectedSocialChannels.' connected' : 'API pending' }}
                </span>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                @foreach ($socialChannels as $channel)
                    <a href="{{ route('admin.'.$channel['slug'].'.index') }}" class="group rounded-xl border border-neutral-200 bg-neutral-50 p-4 transition hover:border-neutral-300 hover:bg-white">
                        <div class="flex items-start justify-between gap-4">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-white" style="background: {{ $channel['accent'] }}">
                                <i data-lucide="{{ $channel['icon'] }}" style="width:20px;height:20px;"></i>
                            </span>
                            <i data-lucide="arrow-right" class="text-neutral-400 transition group-hover:translate-x-0.5 group-hover:text-neutral-700" style="width:18px;height:18px;"></i>
                        </div>
                        <p class="mt-4 text-lg font-bold text-neutral-950">{{ $channel['name'] }}</p>
                        <p class="mt-1 text-sm font-semibold text-neutral-600">{{ $channel['api_state'] }}</p>
                        <p class="mt-3 text-xs font-bold uppercase text-neutral-500">{{ $channel['surface'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">Who's on the site</h2>
                        <p class="mt-1 text-sm text-neutral-500">Grouped by session for a small live-window preview.</p>
                    </div>
                    <a href="{{ route('admin.site-visits.index') }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                        View more <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                                <th class="py-3 pr-3">Visitor</th>
                                <th class="px-3 py-3">Page</th>
                                <th class="py-3 pl-3">Seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($activeVisits as $visit)
                                <tr class="border-b border-neutral-100 align-top">
                                    <td class="py-4 pr-3">
                                        <p class="font-bold text-neutral-950">{{ $visit->user?->name ?? 'Guest visitor' }}</p>
                                        <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $visit->ip_address ?? 'Unknown IP' }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="max-w-xs truncate text-sm font-semibold text-neutral-900">{{ $visit->pageLabel() }}</p>
                                        <a href="{{ route('admin.site-visits.show', $visit) }}" class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-[#01225E] hover:underline">
                                            More <i data-lucide="arrow-right" style="width:12px;height:12px;"></i>
                                        </a>
                                    </td>
                                    <td class="py-4 pl-3 text-sm font-semibold text-neutral-600">{{ $visit->visited_at?->diffForHumans() ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-sm font-semibold text-neutral-500">No active visitors captured in the preview window.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">Accounts created</h2>
                        <p class="mt-1 text-sm text-neutral-500">Latest accounts with a short progress snapshot.</p>
                    </div>
                    <a href="{{ route('admin.accounts.index') }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                        View more <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                                <th class="py-3 pr-3">Account</th>
                                <th class="px-3 py-3">Progress</th>
                                <th class="py-3 pl-3 text-right">More</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($accounts as $account)
                                <tr class="border-b border-neutral-100 align-top">
                                    <td class="py-4 pr-3">
                                        <p class="font-bold text-neutral-950">{{ $account->name ?: 'Unnamed account' }}</p>
                                        <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $account->email }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="text-sm font-bold text-neutral-900">{{ $account->subjects_count }} subjects</p>
                                        <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $account->marks_count }} saved marks</p>
                                    </td>
                                    <td class="py-4 pl-3 text-right">
                                        <a href="{{ route('admin.accounts.show', $account) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                                            More <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-sm font-semibold text-neutral-500">No accounts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">Recent visits</h2>
                        <p class="mt-1 text-sm text-neutral-500">Latest captured page views.</p>
                    </div>
                    <a href="{{ route('admin.site-visits.index') }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                        View more <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                                <th class="py-3 pr-3">Visitor</th>
                                <th class="px-3 py-3">Page</th>
                                <th class="py-3 pl-3">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentVisits as $visit)
                                <tr class="border-b border-neutral-100 align-top">
                                    <td class="py-4 pr-3">
                                        <p class="font-bold text-neutral-950">{{ $visit->user?->name ?? 'Guest visitor' }}</p>
                                        <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $visit->ip_address ?? 'Unknown IP' }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="max-w-xs truncate text-sm font-semibold text-neutral-900">{{ $visit->pageLabel() }}</p>
                                        <a href="{{ route('admin.site-visits.show', $visit) }}" class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-[#01225E] hover:underline">
                                            More <i data-lucide="arrow-right" style="width:12px;height:12px;"></i>
                                        </a>
                                    </td>
                                    <td class="py-4 pl-3 text-sm font-semibold text-neutral-600">{{ $visit->visited_at?->format('d M H:i') ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-sm font-semibold text-neutral-500">No visits captured yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">Audit log</h2>
                        <p class="mt-1 text-sm text-neutral-500">Latest captured admin-visible events.</p>
                    </div>
                    <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                        View more <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                                <th class="py-3 pr-3">Event</th>
                                <th class="px-3 py-3">User</th>
                                <th class="py-3 pl-3 text-right">More</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($markAuditLogs as $log)
                                <tr class="border-b border-neutral-100 align-top">
                                    <td class="py-4 pr-3">
                                        <p class="font-bold text-neutral-950">{{ $log->name }}</p>
                                        <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $log->event ?? 'No event key' }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="text-sm font-bold text-neutral-900">{{ $log->user?->name ?? 'No user' }}</p>
                                        <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $log->created_at?->format('d M H:i') ?? 'N/A' }}</p>
                                    </td>
                                    <td class="py-4 pl-3 text-right">
                                        <a href="{{ route('admin.audit-logs.show', $log) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                                            More <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-sm font-semibold text-neutral-500">No audit entries captured yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </main>
@endsection
