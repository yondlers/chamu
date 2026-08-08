@extends('layouts.app')

@section('title', 'Lemo AI Chat #'.$chat->id.' - Admin - Chamu')

@section('content')
    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('admin.lemo-ai.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> All Lemo AI chats
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Read-only chat review</p>
                <h1 class="mt-1 text-3xl font-bold">{{ $chat->title ?: 'Untitled chat' }}</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">Stored conversation #{{ $chat->id }} — admin can view messages only.</p>
            </div>
        </div>

        <section class="mb-6 grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Participant</p>
                @if ($chat->user)
                    <p class="mt-2 text-lg font-bold">{{ $chat->user->name ?: 'Unnamed account' }}</p>
                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $chat->user->email }}</p>
                @else
                    <p class="mt-2 text-lg font-bold">{{ $chat->ip_address ?: 'Unknown IP' }}</p>
                    <p class="mt-1 text-xs font-semibold text-neutral-500">Not logged in</p>
                @endif
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Device</p>
                <p class="mt-2 text-lg font-bold">{{ $chat->device_type ? Str::title($chat->device_type) : 'Unknown' }}</p>
                <p class="mt-1 break-all text-xs font-semibold text-neutral-500">{{ $chat->user_agent ?: 'No user agent stored' }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Activity</p>
                <p class="mt-2 text-lg font-bold">{{ $chat->messages->count() }} messages</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Last {{ $chat->last_message_at?->format('d M Y H:i') ?? $chat->created_at?->format('d M Y H:i') ?? 'N/A' }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold">Conversation</h2>
                <p class="mt-1 text-sm text-neutral-500">Messages shown exactly as stored — user replies and Lemo AI responses.</p>
            </div>

            <div class="space-y-4">
                @forelse ($chat->messages as $message)
                    @php
                        $isUser = $message->role === 'user';
                        $isAssistant = $message->role === 'assistant';
                    @endphp
                    <article @class([
                        'rounded-2xl border px-4 py-3',
                        'border-[#01225E]/15 bg-[#01225E]/5' => $isAssistant,
                        'border-neutral-200 bg-neutral-50' => $isUser,
                        'border-amber-200 bg-amber-50' => ! $isUser && ! $isAssistant,
                    ])>
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] {{ $isAssistant ? 'text-[#01225E]' : 'text-neutral-600' }}">
                                @if ($isUser)
                                    User
                                @elseif ($isAssistant)
                                    Lemo AI
                                @else
                                    {{ Str::title($message->role) }}
                                @endif
                            </p>
                            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-neutral-500">
                                @if ($isAssistant && $message->providerLabel())
                                    <span>{{ $message->providerLabel() }}</span>
                                    <span aria-hidden="true">·</span>
                                @endif
                                <span>{{ $message->created_at?->format('d M Y H:i:s') ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="whitespace-pre-wrap text-sm font-semibold leading-relaxed text-neutral-900">{{ $message->content }}</div>
                    </article>
                @empty
                    <p class="py-8 text-center text-sm font-semibold text-neutral-500">This chat has no stored messages.</p>
                @endforelse
            </div>
        </section>
    </main>
@endsection
