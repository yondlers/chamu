<?php

namespace App\Services\LemoAi;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Gemini\Data\Content;
use Gemini\Enums\Role;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Str;
use Throwable;

class LemoAiChatService
{
    public const GREETING = 'HI, I am Lemo Ai. Here to help with your academic needs.';

    public function __construct(
        private readonly LemoAiKnowledgeService $knowledge,
    ) {}

    public function createChat(?User $user, ?string $guestToken = null): Chat
    {
        $chat = new Chat([
            'user_id' => $user?->id,
            'guest_token' => $user ? null : $guestToken,
            'title' => 'New chat',
            'last_message_at' => now(),
        ]);
        $chat->save();

        $greeting = new ChatMessage([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => self::GREETING,
        ]);
        $greeting->save();

        return $chat->load('messages');
    }

    /**
     * @return array{user_message: ChatMessage, assistant_message: ChatMessage, chat: Chat}
     */
    public function sendMessage(Chat $chat, string $message): array
    {
        $userMessage = new ChatMessage([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $message,
        ]);
        $userMessage->save();

        if ($chat->title === 'New chat' || blank($chat->title)) {
            $chat->title = Str::limit($message, 60);
        }

        $chat->last_message_at = now();
        $chat->save();

        $reply = $this->generateReply($chat, $message);

        $assistantMessage = new ChatMessage([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => $reply,
        ]);
        $assistantMessage->save();

        $chat->last_message_at = now();
        $chat->save();

        return [
            'user_message' => $userMessage,
            'assistant_message' => $assistantMessage,
            'chat' => $chat->fresh(),
        ];
    }

    private function generateReply(Chat $chat, string $message): string
    {
        $context = $this->knowledge->buildContext($message);
        $history = $this->historyForGemini($chat);

        try {
            $model = Gemini::generativeModel(model: 'gemini-2.0-flash')
                ->withSystemInstruction(Content::parse($this->systemInstruction($context)));

            $chatSession = $model->startChat(history: $history);
            $response = $chatSession->sendMessage($message);
            $text = trim((string) $response->text());

            return $text !== ''
                ? $text
                : 'I could not generate a clear answer just now. Please try asking again about a university, APS requirement, or bursary.';
        } catch (Throwable $exception) {
            report($exception);

            $detail = Str::lower($exception->getMessage());

            if (str_contains($detail, 'prepayment') || str_contains($detail, 'billing') || str_contains($detail, 'credit')) {
                return 'Lemo AI cannot reach Gemini right now because the project billing credits need attention in Google AI Studio. Browse APS and Funding on Chamu in the meantime.';
            }

            return 'Lemo AI is temporarily unavailable. Please try again in a moment, or browse APS and Funding on Chamu while I reconnect.';
        }
    }

    /**
     * @return list<Content>
     */
    private function historyForGemini(Chat $chat): array
    {
        $messages = $chat->messages()
            ->where('role', '!=', 'system')
            ->orderBy('id')
            ->get();

        // Exclude the user message just saved; sendMessage adds the latest turn.
        $messages = $messages->slice(0, max(0, $messages->count() - 1))->values();

        $history = [];

        foreach ($messages as $message) {
            if ($message->role === 'user') {
                $history[] = Content::parse(part: $message->content, role: Role::USER);
            } elseif ($message->role === 'assistant') {
                $history[] = Content::parse(part: $message->content, role: Role::MODEL);
            }
        }

        // Gemini chat history must start with a user turn when non-empty.
        while ($history !== [] && ($history[0]->role ?? null) !== Role::USER) {
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
