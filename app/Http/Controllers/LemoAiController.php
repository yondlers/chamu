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
        $chat = $this->lemoAi->createChat(Auth::user(), $this->guestToken($request));

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
                $chat = $this->lemoAi->createChat(Auth::user(), $this->guestToken($request));
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

            return response()->json([
                'message' => app()->hasDebugModeEnabled()
                    ? $exception->getMessage()
                    : 'Lemo AI hit a server problem while saving this chat. Please try again in a moment.',
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

    private function chatTablesReady(): bool
    {
        return Schema::hasTable('chats')
            && Schema::hasTable('chat_messages')
            && Schema::hasColumn('chats', 'user_id')
            && Schema::hasColumn('chats', 'guest_token')
            && Schema::hasColumn('chats', 'title');
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
