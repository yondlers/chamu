@extends('layouts.app')

@section('title', 'Reports - Admin - Chamu')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Admin dashboard
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Reports</p>
                <h1 class="mt-1 text-3xl font-bold">Generate user reports</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">Choose an account and pull the same personalised Course Matcher or bursary PDF available to the user.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-xl font-bold">Account list</h2>
                    <p class="mt-1 text-sm text-neutral-500">Showing {{ $accounts->firstItem() ?? 0 }}-{{ $accounts->lastItem() ?? 0 }} of {{ number_format($accounts->total()) }} accounts.</p>
                </div>
                <form method="GET" action="{{ route('admin.reports.index') }}" class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
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
                        <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-bold hover:bg-neutral-50">Reset</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">Account</th>
                            <th class="px-3 py-3">Context</th>
                            <th class="px-3 py-3">Course report</th>
                            <th class="px-3 py-3">Bursary report</th>
                            <th class="py-3 pl-3 text-right">Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            @php
                                $bursaryReady = (bool) ($account->bursary_report_readiness['ready'] ?? false);
                                $missingBursary = $account->bursary_report_readiness['missing'] ?? [];
                            @endphp
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-4 pr-3">
                                    <p class="font-bold text-neutral-950">{{ $account->name ?: 'Unnamed account' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $account->email }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ '@'.$account->username }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    <p class="text-sm font-bold capitalize text-neutral-900">{{ $account->userType?->name ?? 'Unknown type' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $account->curriculum?->abbreviation ?? $account->curriculum?->name ?? 'No curriculum' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $account->grade?->name ?? 'No grade' }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    <span @class([
                                        'inline-flex rounded-full px-3 py-1 text-xs font-black',
                                        'bg-emerald-50 text-emerald-700' => (int) $account->marks_count > 0,
                                        'bg-amber-50 text-amber-700' => (int) $account->marks_count === 0,
                                    ])>
                                        {{ (int) $account->marks_count > 0 ? number_format((int) $account->marks_count).' marks' : 'Marks needed' }}
                                    </span>
                                </td>
                                <td class="px-3 py-4">
                                    <span @class([
                                        'inline-flex rounded-full px-3 py-1 text-xs font-black',
                                        'bg-emerald-50 text-emerald-700' => $bursaryReady,
                                        'bg-amber-50 text-amber-700' => ! $bursaryReady,
                                    ])>
                                        {{ $bursaryReady ? 'Ready' : 'Missing '.count($missingBursary) }}
                                    </span>
                                    @if (! $bursaryReady && $missingBursary !== [])
                                        <p class="mt-2 text-xs font-semibold text-neutral-500">{{ implode(', ', $missingBursary) }}</p>
                                    @endif
                                </td>
                                <td class="py-4 pl-3">
                                    <div class="flex justify-end gap-2">
                                        @if ((int) $account->marks_count > 0)
                                            <a href="{{ route('admin.reports.users.course', $account) }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                                                Course <i data-lucide="download" style="width:15px;height:15px;"></i>
                                            </a>
                                        @endif
                                        @if ($bursaryReady)
                                            <a href="{{ route('admin.reports.users.bursaries', $account) }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-3 py-2 text-sm font-bold text-white hover:bg-[#001A48]">
                                                Bursary <i data-lucide="download" style="width:15px;height:15px;"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-sm font-semibold text-neutral-500">No accounts found.</td>
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
    </main>
@endsection
