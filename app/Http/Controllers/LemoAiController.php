<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Services\LemoAi\LemoAiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:4000'],
            'chat_id' => ['nullable', 'integer', 'exists:chats,id'],
        ]);

        $chat = null;

        if (! empty($validated['chat_id'])) {
            $chat = Chat::query()->findOrFail($validated['chat_id']);
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
     * @return array{id:int, role:string, content:string, created_at:?string}
     */
    private function messagePayload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'created_at' => optional($message->created_at)?->toIso8601String(),
        ];
    }
}
