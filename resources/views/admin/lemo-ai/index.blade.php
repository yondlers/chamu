@extends('layouts.app')

@section('title', 'Lemo AI Chats - Admin - Chamu')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Admin dashboard
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Lemo AI</p>
                <h1 class="mt-1 text-3xl font-bold">All chats</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">Read-only review of every stored Lemo AI conversation, including guest and logged-in users.</p>
            </div>
        </div>

        <section class="mb-6 grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Total chats</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalChats) }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Logged-in</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($userChats) }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Guests</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($guestChats) }}</p>
            </div>
        </section>

        <section class="mb-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.lemo-ai.index') }}" class="grid gap-3 md:grid-cols-[1fr_180px_auto]">
                <div>
                    <label for="search" class="text-xs font-bold uppercase text-neutral-500">Search</label>
                    <input
                        id="search"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Title, user, email, or IP"
                        class="mt-1 w-full rounded-xl border border-neutral-300 px-3 py-2 text-sm font-semibold text-neutral-900 outline-none focus:border-[#01225E]"
                    >
                </div>
                <div>
                    <label for="audience" class="text-xs font-bold uppercase text-neutral-500">Audience</label>
                    <select
                        id="audience"
                        name="audience"
                        class="mt-1 w-full rounded-xl border border-neutral-300 px-3 py-2 text-sm font-semibold text-neutral-900 outline-none focus:border-[#01225E]"
                    >
                        <option value="" @selected($filters['audience'] === '')>All</option>
                        <option value="users" @selected($filters['audience'] === 'users')>Logged-in</option>
                        <option value="guests" @selected($filters['audience'] === 'guests')>Guests</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#01225E] px-4 py-2 text-sm font-bold text-white hover:bg-[#011a47] md:w-auto">
                        Filter <i data-lucide="search" style="width:15px;height:15px;"></i>
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold">Chat list</h2>
                <p class="mt-1 text-sm text-neutral-500">Showing {{ $chats->firstItem() ?? 0 }}-{{ $chats->lastItem() ?? 0 }} of {{ number_format($chats->total()) }} chats.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">Participant</th>
                            <th class="px-3 py-3">Title</th>
                            <th class="px-3 py-3">Device</th>
                            <th class="px-3 py-3">Messages</th>
                            <th class="px-3 py-3">Last activity</th>
                            <th class="py-3 pl-3 text-right">Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($chats as $chat)
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-4 pr-3">
                                    @if ($chat->user)
                                        <p class="font-bold text-neutral-950">{{ $chat->user->name ?: 'Unnamed account' }}</p>
                                        <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $chat->user->email }}</p>
                                    @else
                                        <p class="font-bold text-neutral-950">{{ $chat->ip_address ?: 'Guest visitor' }}</p>
                                        <p class="mt-1 text-xs font-semibold text-neutral-500">Not logged in</p>
                                    @endif
                                </td>
                                <td class="px-3 py-4">
                                    <p class="max-w-md truncate text-sm font-semibold text-neutral-900">{{ $chat->title ?: 'Untitled chat' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">Chat #{{ $chat->id }}</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $chat->device_type ? Str::title($chat->device_type) : 'Unknown' }}</td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ number_format($chat->messages_count) }}</td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $chat->last_message_at?->format('d M Y H:i') ?? $chat->created_at?->format('d M Y H:i') ?? 'N/A' }}</td>
                                <td class="py-4 pl-3 text-right">
                                    <a href="{{ route('admin.lemo-ai.show', $chat) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                                        Open <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm font-semibold text-neutral-500">No Lemo AI chats stored yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($chats->hasPages())
                <div class="mt-5">
                    {{ $chats->links() }}
                </div>
            @endif
        </section>
    </main>
@endsection
