<?php

namespace App\Services\LemoAi;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class LemoAiChatService
{
    public const GREETING = 'HI, I am Lemo Ai. Here to help with your academic needs.';

    public function __construct(
        private readonly LemoAiKnowledgeService $knowledge,
        private readonly LemoAiRouter $router,
    ) {}

    /**
     * @param  array{ip_address?:?string, user_agent?:?string, device_type?:?string}  $visitor
     */
    public function createChat(?User $user, ?string $guestToken = null, array $visitor = []): Chat
    {
        $attributes = [
            'user_id' => $user?->id,
            'guest_token' => $user ? null : $guestToken,
            'title' => 'New chat',
            'last_message_at' => now(),
        ];

        if ($this->supportsVisitorColumns()) {
            $attributes['ip_address'] = $visitor['ip_address'] ?? null;
            $attributes['user_agent'] = $visitor['user_agent'] ?? null;
            $attributes['device_type'] = $visitor['device_type'] ?? null;
        }

        $chat = new Chat($attributes);
        $chat->save();

        $this->storeMessage($chat, [
            'role' => 'assistant',
            'content' => self::GREETING,
            'provider' => 'system',
            'model' => 'greeting',
        ]);

        return $chat->load('messages');
    }

    /**
     * @return array{user_message: ChatMessage, assistant_message: ChatMessage, chat: Chat}
     */
    public function sendMessage(Chat $chat, string $message): array
    {
        $userMessage = $this->storeMessage($chat, [
            'role' => 'user',
            'content' => $message,
        ]);

        if ($chat->title === 'New chat' || blank($chat->title)) {
            $chat->title = Str::limit($message, 60);
        }

        $chat->last_message_at = now();
        $chat->save();

        try {
            $reply = $this->generateReply($chat, $message);
        } catch (Throwable $exception) {
            report($exception);
            Log::error('[Lemo AI] Reply generation failed', [
                'error' => $exception->getMessage(),
            ]);

            $reply = [
                'content' => 'Lemo AI hit a temporary problem generating a reply. Please try again in a moment.',
                'provider' => 'system',
                'model' => 'fallback',
                'label' => 'System',
            ];
        }

        $assistantMessage = $this->storeMessage($chat, [
            'role' => 'assistant',
            'content' => $reply['content'],
            'provider' => $reply['provider'],
            'model' => $reply['model'],
        ]);

        $chat->last_message_at = now();
        $chat->save();

        return [
            'user_message' => $userMessage,
            'assistant_message' => $assistantMessage,
            'chat' => $chat->fresh(),
        ];
    }

    /**
     * @param  array{role:string, content:string, provider?:?string, model?:?string}  $attributes
     */
    private function storeMessage(Chat $chat, array $attributes): ChatMessage
    {
        $payload = [
            'chat_id' => $chat->id,
            'role' => $attributes['role'],
            'content' => $attributes['content'],
        ];

        if ($this->supportsProviderColumns()) {
            $payload['provider'] = $attributes['provider'] ?? null;
            $payload['model'] = $attributes['model'] ?? null;
        }

        $message = new ChatMessage($payload);
        $message->save();

        return $message;
    }

    private function supportsProviderColumns(): bool
    {
        return Schema::hasColumn('chat_messages', 'provider')
            && Schema::hasColumn('chat_messages', 'model');
    }

    private function supportsVisitorColumns(): bool
    {
        return Schema::hasColumn('chats', 'ip_address')
            && Schema::hasColumn('chats', 'user_agent')
            && Schema::hasColumn('chats', 'device_type');
    }

    /**
     * @return array{content:string, provider:string, model:string, label:string}
     */
    private function generateReply(Chat $chat, string $message): array
    {
        try {
            $context = $this->knowledge->buildContext($message);
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('[Lemo AI] Knowledge context failed; continuing without DB context', [
                'error' => $exception->getMessage(),
            ]);
            $context = 'No live Chamu database context was available for this question.';
        }

        $history = $this->historyForRouter($chat);

        return $this->router->generate(
            systemInstruction: $this->systemInstruction($context),
            history: $history,
            userMessage: $message,
        );
    }

    /**
     * @return list<array{role:string, content:string}>
     */
    private function historyForRouter(Chat $chat): array
    {
        $messages = $chat->messages()
            ->where('role', '!=', 'system')
            ->orderBy('id')
            ->get();

        // Exclude the user message just saved; the router adds the latest turn.
        $messages = $messages->slice(0, max(0, $messages->count() - 1))->values();

        $history = [];

        foreach ($messages as $message) {
            if (! in_array($message->role, ['user', 'assistant'], true)) {
                continue;
            }

            // Skip the initial greeting so providers get a clean conversation start.
            if (
                $message->role === 'assistant'
                && $history === []
                && $message->content === self::GREETING
            ) {
                continue;
            }

            $history[] = [
                'role' => $message->role,
                'content' => $message->content,
            ];
        }

        while ($history !== [] && ($history[0]['role'] ?? null) !== 'user') {
            array_shift($history);
        }

        return $history;
    }

    private function systemInstruction(string $context): string
    {
        return <<<PROMPT
You are Lemo AI, Chamu's free academic assistant for South African learners.
Greet and speak as Lemo AI. Be warm, clear, and practical.
Help with universities, qualifications, APS, subject requirements, bursaries, and funding.
Prefer facts from the CHAMU DATA CONTEXT below over general knowledge.
If the context is incomplete, say what is known on Chamu and suggest the learner check APS or Funding pages.
Do not invent closing dates, APS scores, or eligibility rules.
Keep answers concise unless the learner asks for detail.
Use plain language suitable for high-school and first-year students.

CHAMU DATA CONTEXT:
{$context}
PROMPT;
    }
}
