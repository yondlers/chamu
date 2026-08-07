@extends('layouts.app')

@section('title', 'Lemo AI - Chamu')

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-seo-meta
        title="Lemo AI - Chamu"
        description="Chat free with Lemo AI about South African universities, APS requirements, and bursary funding on Chamu."
        :canonical="route('lemo-ai.index')"
    />
@endpush

@section('hide_footer', true)

@push('styles')
    <style>
        body { background: #f7f7f8; }
        .lemo-shell { min-height: calc(100vh - 4rem); }
        .lemo-sidebar { background: #f0f2f5; border-right: 1px solid #e5e7eb; }
        .lemo-thread { background: #ffffff; }
        .lemo-composer {
            box-shadow: 0 -8px 30px rgba(1, 34, 94, 0.06);
            border: 1px solid #e5e7eb;
            background: #fff;
        }
        .lemo-bubble-user {
            background: #01225E;
            color: #fff;
            border-radius: 1.25rem 1.25rem 0.35rem 1.25rem;
        }
        .lemo-bubble-assistant {
            background: #f3f4f6;
            color: #171717;
            border-radius: 1.25rem 1.25rem 1.25rem 0.35rem;
        }
        .lemo-message-body {
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.65;
        }
        .lemo-typing span {
            display: inline-block;
            width: 6px;
            height: 6px;
            margin-right: 3px;
            border-radius: 999px;
            background: #6b7280;
            animation: lemoPulse 1.2s infinite ease-in-out;
        }
        .lemo-typing span:nth-child(2) { animation-delay: .15s; }
        .lemo-typing span:nth-child(3) { animation-delay: .3s; }
        @keyframes lemoPulse {
            0%, 80%, 100% { opacity: .35; transform: translateY(0); }
            40% { opacity: 1; transform: translateY(-2px); }
        }
    </style>
@endpush

@section('content')
    @php
        $initialMessages = $chat
            ? $chat->messages->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
            ])->values()
            : collect([[
                'id' => 'greeting',
                'role' => 'assistant',
                'content' => $greeting,
            ]]);
    @endphp

    <main class="lemo-shell mx-auto flex max-w-7xl">
        @if ($isAuthenticated)
            <aside class="lemo-sidebar hidden w-72 shrink-0 flex-col md:flex">
                <div class="flex items-center justify-between gap-3 border-b border-neutral-200 px-4 py-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#01225E]">Lemo AI</p>
                        <p class="mt-1 text-sm font-semibold text-neutral-700">Past chats</p>
                    </div>
                    <a
                        href="{{ route('lemo-ai.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-[#01225E] px-3 py-2 text-xs font-bold text-white hover:bg-[#001A48]"
                    >
                        <i data-lucide="plus" style="width:14px;height:14px;"></i>
                        New
                    </a>
                </div>

                <div class="flex-1 space-y-1 overflow-y-auto px-2 py-3">
                    @forelse ($pastChats as $pastChat)
                        <a
                            href="{{ route('lemo-ai.show', $pastChat) }}"
                            @class([
                                'block rounded-xl px-3 py-3 text-sm transition',
                                'bg-white shadow-sm ring-1 ring-[#01225E]/15' => $chat && (int) $chat->id === (int) $pastChat->id,
                                'hover:bg-white/80' => ! ($chat && (int) $chat->id === (int) $pastChat->id),
                            ])
                        >
                            <p class="line-clamp-2 font-semibold text-neutral-900">{{ $pastChat->title ?: 'New chat' }}</p>
                            <p class="mt-1 text-xs text-neutral-500">
                                {{ optional($pastChat->last_message_at ?? $pastChat->updated_at)->diffForHumans() }}
                            </p>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-neutral-300 px-3 py-6 text-center text-sm text-neutral-500">
                            Your saved conversations will appear here.
                        </div>
                    @endforelse
                </div>
            </aside>
        @endif

        <section class="lemo-thread flex min-w-0 flex-1 flex-col">
            <div class="border-b border-neutral-200 px-4 py-4 sm:px-6">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[#01225E] text-white">
                            <i data-lucide="sparkles" style="width:20px;height:20px;"></i>
                        </span>
                        <div>
                            <h1 class="text-lg font-bold text-neutral-950 sm:text-xl">Lemo AI</h1>
                            <p class="text-sm text-neutral-500">Free help with universities, APS, and bursaries.</p>
                        </div>
                    </div>
                    @unless ($isAuthenticated)
                        <a href="{{ route('login') }}" class="hidden text-sm font-semibold text-[#01225E] hover:underline sm:inline">
                            Log in to save chats
                        </a>
                    @else
                        <a
                            href="{{ route('lemo-ai.index') }}"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-semibold hover:bg-neutral-50 md:hidden"
                        >
                            <i data-lucide="plus" style="width:14px;height:14px;"></i>
                            New
                        </a>
                    @endunless
                </div>
            </div>

            @if ($isAuthenticated && $pastChats->isNotEmpty())
                <div class="no-scrollbar flex gap-2 overflow-x-auto border-b border-neutral-200 px-4 py-3 md:hidden">
                    @foreach ($pastChats->take(8) as $pastChat)
                        <a
                            href="{{ route('lemo-ai.show', $pastChat) }}"
                            @class([
                                'shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold',
                                'bg-[#01225E] text-white' => $chat && (int) $chat->id === (int) $pastChat->id,
                                'bg-neutral-100 text-neutral-700' => ! ($chat && (int) $chat->id === (int) $pastChat->id),
                            ])
                        >
                            {{ Str::limit($pastChat->title ?: 'New chat', 22) }}
                        </a>
                    @endforeach
                </div>
            @endif

            <div id="lemo-messages" class="flex-1 space-y-5 overflow-y-auto px-4 py-6 sm:px-6" style="max-height: calc(100vh - 15rem);">
                @foreach ($initialMessages as $message)
                    <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}" data-role="{{ $message['role'] }}">
                        <div class="max-w-[min(42rem,92%)] px-4 py-3 text-sm sm:text-[15px] {{ $message['role'] === 'user' ? 'lemo-bubble-user' : 'lemo-bubble-assistant' }}">
                            @if ($message['role'] === 'assistant')
                                <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.14em] text-[#01225E]/80">Lemo AI</p>
                            @endif
                            <div class="lemo-message-body">{{ $message['content'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="px-4 pb-5 pt-2 sm:px-6">
                <form id="lemo-form" class="lemo-composer mx-auto flex max-w-3xl items-end gap-2 rounded-2xl p-2 sm:p-3">
                    <label for="lemo-input" class="sr-only">Message Lemo AI</label>
                    <textarea
                        id="lemo-input"
                        name="message"
                        rows="1"
                        placeholder="Ask about APS, a university, or bursaries..."
                        class="max-h-40 min-h-[48px] flex-1 resize-none bg-transparent px-3 py-3 text-[15px] outline-none"
                    ></textarea>
                    <button
                        id="lemo-send"
                        type="submit"
                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#01225E] text-white hover:bg-[#001A48] disabled:cursor-not-allowed disabled:opacity-50"
                        aria-label="Send message"
                    >
                        <i data-lucide="send" style="width:18px;height:18px;"></i>
                    </button>
                </form>
                <p class="mx-auto mt-3 max-w-3xl text-center text-xs text-neutral-500">
                    Lemo AI uses Chamu university and bursary data. Always confirm official closing dates and requirements.
                </p>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        (() => {
            const messagesEl = document.getElementById('lemo-messages');
            const form = document.getElementById('lemo-form');
            const input = document.getElementById('lemo-input');
            const sendBtn = document.getElementById('lemo-send');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let chatId = @json(optional($chat)->id);
            let sending = false;

            const escapeHtml = (value) => String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const appendMessage = (role, content) => {
                const wrap = document.createElement('div');
                wrap.className = `flex ${role === 'user' ? 'justify-end' : 'justify-start'}`;
                wrap.innerHTML = `
                    <div class="max-w-[min(42rem,92%)] px-4 py-3 text-sm sm:text-[15px] ${role === 'user' ? 'lemo-bubble-user' : 'lemo-bubble-assistant'}">
                        ${role === 'assistant' ? '<p class="mb-1 text-[11px] font-bold uppercase tracking-[0.14em] text-[#01225E]/80">Lemo AI</p>' : ''}
                        <div class="lemo-message-body">${escapeHtml(content)}</div>
                    </div>
                `;
                messagesEl.appendChild(wrap);
                messagesEl.scrollTop = messagesEl.scrollHeight;
                return wrap;
            };

            const setTyping = (on) => {
                const existing = document.getElementById('lemo-typing');
                if (existing) existing.remove();
                if (!on) return;

                const wrap = document.createElement('div');
                wrap.id = 'lemo-typing';
                wrap.className = 'flex justify-start';
                wrap.innerHTML = `
                    <div class="lemo-bubble-assistant px-4 py-3">
                        <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.14em] text-[#01225E]/80">Lemo AI</p>
                        <div class="lemo-typing"><span></span><span></span><span></span></div>
                    </div>
                `;
                messagesEl.appendChild(wrap);
                messagesEl.scrollTop = messagesEl.scrollHeight;
            };

            const autosize = () => {
                input.style.height = 'auto';
                input.style.height = Math.min(input.scrollHeight, 160) + 'px';
            };

            input.addEventListener('input', autosize);
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    form.requestSubmit();
                }
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (sending) return;

                const message = input.value.trim();
                if (!message) return;

                sending = true;
                sendBtn.disabled = true;
                appendMessage('user', message);
                input.value = '';
                autosize();
                setTyping(true);

                try {
                    const response = await fetch(@json(route('lemo-ai.messages.store')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            message,
                            chat_id: chatId,
                        }),
                    });

                    const data = await response.json();
                    setTyping(false);

                    if (!response.ok) {
                        appendMessage('assistant', data.message || 'Something went wrong. Please try again.');
                    } else {
                        chatId = data.chat.id;
                        appendMessage('assistant', data.assistant_message.content);
                        if (window.history && data.chat.url) {
                            window.history.replaceState({}, '', data.chat.url);
                        }
                    }
                } catch (error) {
                    setTyping(false);
                    appendMessage('assistant', 'Lemo AI could not reach the server. Please try again.');
                } finally {
                    sending = false;
                    sendBtn.disabled = false;
                    input.focus();
                }
            });

            messagesEl.scrollTop = messagesEl.scrollHeight;
            input.focus();
        })();
    </script>
@endpush
