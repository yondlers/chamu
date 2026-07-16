@extends('layouts.app')

@section('title', 'Admin - Chamu')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-bold text-[#01225E]">Super admin</p>
                <h1 class="mt-1 text-3xl font-bold">Site activity</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">The first view is who is on Chamu now. Mark-entry audits are below.</p>
            </div>
            <a href="{{ route('aps.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                APS <i data-lucide="target" style="width:16px;height:16px;"></i>
            </a>
        </div>

        <section class="grid gap-3 md:grid-cols-5">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Active now</p>
                <p class="mt-2 text-3xl font-bold">{{ $activeVisits->count() }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Last 10 minutes</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Guests active</p>
                <p class="mt-2 text-3xl font-bold">{{ $activeVisits->whereNull('user_id')->count() }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Not logged in</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Users active</p>
                <p class="mt-2 text-3xl font-bold">{{ $activeVisits->whereNotNull('user_id')->count() }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Logged in</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Mark saves</p>
                <p class="mt-2 text-3xl font-bold">{{ $markAuditLogs->count() }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Latest 100 entries</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Accounts</p>
                <p class="mt-2 text-3xl font-bold">{{ $totalAccounts }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Created in Chamu</p>
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold">Who's on the site</h2>
                    <p class="mt-1 text-sm text-neutral-500">Grouped by session where available, then IP and user agent.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Live window since {{ $activeWindow->format('H:i') }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">Visitor</th>
                            <th class="px-3 py-3">IP</th>
                            <th class="px-3 py-3">Device</th>
                            <th class="px-3 py-3">Browser</th>
                            <th class="px-3 py-3">Page</th>
                            <th class="py-3 pl-3">Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activeVisits as $visit)
                            @php
                                $pageDetail = $visit->pageDetail();
                            @endphp
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-4 pr-3">
                                    <p class="font-bold text-neutral-950">{{ $visit->user?->name ?? 'Guest visitor' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $visit->user_id ? 'Logged in' : 'Not logged in' }}</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $visit->ip_address ?? 'Unknown' }}</td>
                                <td class="px-3 py-4">
                                    <p class="text-sm font-bold capitalize text-neutral-900">{{ $visit->device_type ?? 'Unknown' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $visit->platform ?? 'Unknown' }}</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $visit->browser ?? 'Unknown' }}</td>
                                <td class="px-3 py-4">
                                    <p class="max-w-sm truncate text-sm font-semibold text-neutral-900">{{ $visit->pageLabel() }}</p>
                                    @if ($pageDetail)
                                        <p class="mt-1 max-w-sm truncate text-xs font-semibold text-neutral-500">URL {{ $pageDetail }}</p>
                                    @endif
                                    @if ($visit->referrer)
                                        <p class="mt-1 max-w-sm truncate text-xs font-semibold text-neutral-500">From {{ $visit->referrer }}</p>
                                    @endif
                                </td>
                                <td class="py-4 pl-3 text-sm font-semibold text-neutral-600">{{ $visit->visited_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm font-semibold text-neutral-500">No active visitors captured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section id="accounts" class="mt-6 scroll-mt-24 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-xl font-bold">Accounts created</h2>
                    <p class="mt-1 text-sm text-neutral-500">View every account, then open more details to inspect subjects, marks, visits, and audits.</p>
                </div>
                <form method="GET" action="{{ route('admin.index') }}#accounts" class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
                    <label for="account_search" class="sr-only">Search accounts</label>
                    <input
                        id="account_search"
                        name="account_search"
                        value="{{ $accountSearch }}"
                        placeholder="Search name, username, email"
                        class="min-w-0 rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-semibold outline-none focus:border-[#01225E] sm:w-72"
                    >
                    <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#001A48]">
                        Search <i data-lucide="search" style="width:16px;height:16px;"></i>
                    </button>
                    @if ($accountSearch !== '')
                        <a href="{{ route('admin.index') }}#accounts" class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-bold hover:bg-neutral-50">Reset</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">Account</th>
                            <th class="px-3 py-3">Context</th>
                            <th class="px-3 py-3">Progress</th>
                            <th class="px-3 py-3">Last seen</th>
                            <th class="px-3 py-3">Created</th>
                            <th class="py-3 pl-3 text-right">More</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-4 pr-3">
                                    <div class="flex items-start gap-3">
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F3F7FC] text-sm font-black text-[#01225E]">
                                            {{ Str::of($account->name ?: $account->username)->substr(0, 1)->upper() }}
                                        </span>
                                        <div>
                                            <p class="font-bold text-neutral-950">{{ $account->name ?: 'Unnamed account' }}</p>
                                            <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $account->email }}</p>
                                            <p class="mt-1 text-xs font-semibold text-neutral-500">{{ '@'.$account->username }}</p>
                                            @if ($account->is_super_admin)
                                                <span class="mt-2 inline-flex rounded-full bg-[#01225E] px-2.5 py-1 text-xs font-bold text-white">Super admin</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-4">
                                    <p class="text-sm font-bold capitalize text-neutral-900">{{ $account->userType?->name ?? 'Unknown type' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $account->curriculum?->abbreviation ?? $account->curriculum?->name ?? 'No curriculum' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $account->grade?->name ?? 'No grade' }}{{ $account->province ? ' - '.$account->province->name : '' }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    <p class="text-sm font-bold text-neutral-900">{{ $account->subjects_count }} subjects selected</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $account->marks_count }} saved marks</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $account->points ?? 0 }} points</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">
                                    {{ $account->last_seen_at ? \Illuminate\Support\Carbon::parse($account->last_seen_at)->diffForHumans() : 'Never captured' }}
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $account->created_at?->format('d M Y H:i') ?? 'Unknown' }}</td>
                                <td class="py-4 pl-3 text-right">
                                    <a href="{{ route('admin.accounts.show', $account) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                                        More <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm font-semibold text-neutral-500">No accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($accounts->hasPages())
                <div class="mt-5">
                    {{ $accounts->links() }}
                </div>
            @endif
        </section>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold">Recent visits</h2>
                <p class="mt-1 text-sm text-neutral-500">Latest captured page views, including guests.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[920px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">Visitor</th>
                            <th class="px-3 py-3">Method</th>
                            <th class="px-3 py-3">Page</th>
                            <th class="px-3 py-3">Device</th>
                            <th class="py-3 pl-3">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentVisits as $visit)
                            @php
                                $pageDetail = $visit->pageDetail();
                            @endphp
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-4 pr-3">
                                    <p class="font-bold text-neutral-950">{{ $visit->user?->name ?? 'Guest visitor' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $visit->ip_address ?? 'Unknown IP' }}</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-bold text-neutral-700">{{ $visit->method }}</td>
                                <td class="px-3 py-4">
                                    <p class="max-w-md truncate text-sm font-semibold text-neutral-900">{{ $visit->pageLabel() }}</p>
                                    @if ($pageDetail)
                                        <p class="mt-1 max-w-md truncate text-xs font-semibold text-neutral-500">URL {{ $pageDetail }}</p>
                                    @endif
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $visit->route_name ?? 'No route name' }}</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $visit->device_type ?? 'Unknown' }} - {{ $visit->browser ?? 'Unknown' }}</td>
                                <td class="py-4 pl-3 text-sm font-semibold text-neutral-600">{{ $visit->visited_at->format('d M H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-sm font-semibold text-neutral-500">No visits captured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold">Mark-entry audit log</h2>
                <p class="mt-1 text-sm text-neutral-500">For now this captures logged-in users who save term marks.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">User</th>
                            <th class="px-3 py-3">Term / Grade</th>
                            <th class="px-3 py-3">Submitted</th>
                            <th class="px-3 py-3">Changed</th>
                            <th class="px-3 py-3">IP</th>
                            <th class="py-3 pl-3">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($markAuditLogs as $log)
                            @php
                                $metadata = $log->metadata ?? [];
                                $changedMarks = $metadata['changed_marks'] ?? [];
                                $removedMarks = $metadata['removed_marks'] ?? [];
                            @endphp
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-4 pr-3">
                                    <p class="font-bold text-neutral-950">{{ $log->user?->name ?? 'Deleted user' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $log->user?->email ?? 'No email' }}</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">
                                    Term {{ $metadata['term_id'] ?? 'N/A' }} - Grade {{ $metadata['grade_id'] ?? 'N/A' }}
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $metadata['submitted_subject_count'] ?? 0 }} subjects</td>
                                <td class="px-3 py-4">
                                    <p class="text-sm font-bold text-neutral-900">{{ count($changedMarks) }} saved</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ count($removedMarks) }} removed</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $log->ip_address ?? 'Unknown' }}</td>
                                <td class="py-4 pl-3 text-sm font-semibold text-neutral-600">{{ $log->created_at->format('d M H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm font-semibold text-neutral-500">No mark audit entries captured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
@endsection
