<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Services\LemoAi\LemoAiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LemoAiController extends Controller
{
    public function __construct(
        private readonly LemoAiChatService $lemoAi,
    ) {}

    public function index(Request $request): View
    {
        return $this->renderChat($request, null);
    }

    public function show(Request $request, Chat $chat): View
    {
        $this->authorizeChat($request, $chat);

        return $this->renderChat($request, $chat);
    }

    public function store(Request $request): JsonResponse
    {
        $chat = $this->lemoAi->createChat(Auth::user(), $this->guestToken($request), $this->visitorMeta($request));

        return response()->json([
            'chat' => [
                'id' => $chat->id,
                'title' => $chat->title,
                'url' => route('lemo-ai.show', $chat),
            ],
            'messages' => $chat->messages
                ->map(fn (ChatMessage $message) => $this->messagePayload($message))
                ->values(),
            'redirect' => route('lemo-ai.show', $chat),
        ]);
    }

    public function storeMessage(Request $request): JsonResponse
    {
        try {
            if (! $this->chatTablesReady()) {
                return response()->json([
                    'message' => 'Lemo AI storage is not ready yet. Please run database migrations on the server.',
                ], 503);
            }

            $validated = $request->validate([
                'message' => ['required', 'string', 'min:1', 'max:4000'],
                'chat_id' => ['nullable', 'integer', 'exists:chats,id'],
            ]);

            $chatId = $validated['chat_id'] ?? null;

            if (filled($chatId)) {
                $chat = Chat::query()->findOrFail($chatId);
                $this->authorizeChat($request, $chat);
            } else {
                $chat = $this->lemoAi->createChat(Auth::user(), $this->guestToken($request), $this->visitorMeta($request));
            }

            $result = $this->lemoAi->sendMessage($chat, trim($validated['message']));

            return response()->json([
                'chat' => [
                    'id' => $result['chat']->id,
                    'title' => $result['chat']->title,
                    'url' => route('lemo-ai.show', $result['chat']),
                ],
                'user_message' => $this->messagePayload($result['user_message']),
                'assistant_message' => $this->messagePayload($result['assistant_message']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            $detail = Str::lower($exception->getMessage());
            $message = 'Lemo AI hit a server problem while saving this chat. Please try again in a moment.';

            if (
                str_contains($detail, 'no such column')
                || str_contains($detail, 'unknown column')
                || str_contains($detail, 'chat_messages')
            ) {
                $message = 'Lemo AI database is missing recent chat columns. Run `php artisan migrate --force` on the server, then retry.';
            } elseif (str_contains($detail, 'groq') || str_contains($detail, 'gemini')) {
                $message = 'Lemo AI could not reach Gemini or Groq right now. Please retry in a minute.';
            }

            return response()->json([
                'message' => app()->hasDebugModeEnabled()
                    ? $exception->getMessage()
                    : $message,
            ], 500);
        }
    }

    private function renderChat(Request $request, ?Chat $chat): View
    {
        $user = Auth::user();
        $pastChats = collect();

        if ($user !== null) {
            $pastChats = Chat::query()
                ->where('user_id', $user->id)
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->limit(40)
                ->get();
        }

        if ($chat !== null) {
            $chat->load(['messages' => fn ($query) => $query->orderBy('id')]);
        }

        return view('lemo-ai.index', [
            'chat' => $chat,
            'pastChats' => $pastChats,
            'greeting' => LemoAiChatService::GREETING,
            'isAuthenticated' => $user !== null,
        ]);
    }

    private function authorizeChat(Request $request, Chat $chat): void
    {
        $user = Auth::user();

        if ($user !== null) {
            abort_unless((int) $chat->user_id === (int) $user->id, 403);

            return;
        }

        abort_unless(
            $chat->user_id === null
                && filled($chat->guest_token)
                && $chat->guest_token === $this->guestToken($request),
            403
        );
    }

    private function guestToken(Request $request): string
    {
        $token = $request->session()->get('lemo_ai_guest_token');

        if (! is_string($token) || $token === '') {
            $token = Str::random(40);
            $request->session()->put('lemo_ai_guest_token', $token);
        }

        return $token;
    }

    /**
     * @return array{ip_address:?string, user_agent:?string, device_type:string}
     */
    private function visitorMeta(Request $request): array
    {
        $userAgent = $request->userAgent();

        return [
            'ip_address' => $request->ip(),
            'user_agent' => filled($userAgent) ? Str::limit($userAgent, 1000, '') : null,
            'device_type' => $this->deviceType($userAgent),
        ];
    }

    private function deviceType(?string $userAgent): string
    {
        $agent = strtolower($userAgent ?? '');

        if ($agent === '') {
            return 'unknown';
        }

        if (str_contains($agent, 'bot') || str_contains($agent, 'crawler') || str_contains($agent, 'spider')) {
            return 'bot';
        }

        if (str_contains($agent, 'ipad') || str_contains($agent, 'tablet')) {
            return 'tablet';
        }

        if (str_contains($agent, 'mobile') || str_contains($agent, 'iphone') || str_contains($agent, 'android')) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function chatTablesReady(): bool
    {
        return Schema::hasTable('chats')
            && Schema::hasTable('chat_messages')
            && Schema::hasColumn('chats', 'user_id')
            && Schema::hasColumn('chats', 'guest_token')
            && Schema::hasColumn('chats', 'title')
            && Schema::hasColumn('chat_messages', 'chat_id')
            && Schema::hasColumn('chat_messages', 'role')
            && Schema::hasColumn('chat_messages', 'content');
    }

    /**
     * @return array{id:int, role:string, content:string, provider:?string, model:?string, provider_label:?string, created_at:?string}
     */
    private function messagePayload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'provider' => $message->provider,
            'model' => $message->model,
            'provider_label' => $message->providerLabel(),
            'created_at' => optional($message->created_at)?->toIso8601String(),
        ];
    }
}
