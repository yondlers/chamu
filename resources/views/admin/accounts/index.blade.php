@extends('layouts.app')

@section('title', 'Accounts - Admin - Chamu')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Admin dashboard
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Accounts</p>
                <h1 class="mt-1 text-3xl font-bold">Accounts created</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">All user accounts, paginated. Search only when you need to narrow the list.</p>
            </div>
        </div>

        <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-xl font-bold">Account list</h2>
                    <p class="mt-1 text-sm text-neutral-500">Showing {{ $accounts->firstItem() ?? 0 }}-{{ $accounts->lastItem() ?? 0 }} of {{ number_format($accounts->total()) }} accounts.</p>
                </div>
                <form method="GET" action="{{ route('admin.accounts.index') }}" class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
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
                        <a href="{{ route('admin.accounts.index') }}" class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-bold hover:bg-neutral-50">Reset</a>
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
                                    <p class="font-bold text-neutral-950">{{ $account->name ?: 'Unnamed account' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $account->email }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ '@'.$account->username }}</p>
                                    @if ($account->is_super_admin)
                                        <span class="mt-2 inline-flex rounded-full bg-[#01225E] px-2.5 py-1 text-xs font-bold text-white">Super admin</span>
                                    @endif
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
    </main>
@endsection
