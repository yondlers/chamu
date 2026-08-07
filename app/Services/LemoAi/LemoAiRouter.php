<?php

namespace App\Services\LemoAi;

use Gemini\Data\Content;
use Gemini\Enums\Role;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LemoAiRouter
{
    /**
     * @param  list<array{role:string, content:string}>  $history
     * @return array{content:string, provider:string, model:string, label:string}
     */
    public function generate(string $systemInstruction, array $history, string $userMessage): array
    {
        $geminiError = null;

        foreach ($this->geminiModels() as $model) {
            try {
                $text = $this->callGemini($model, $systemInstruction, $history, $userMessage);

                if ($text !== '') {
                    return [
                        'content' => $text,
                        'provider' => 'gemini',
                        'model' => $model,
                        'label' => $this->labelFor('gemini', $model),
                    ];
                }
            } catch (Throwable $exception) {
                $geminiError = $exception;
                Log::warning('[Lemo AI] Gemini failed, trying next model/fallback', [
                    'model' => $model,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        try {
            $groqModel = (string) config('lemo.groq.model', 'llama-3.3-70b-versatile');
            $text = $this->callGroq($groqModel, $systemInstruction, $history, $userMessage);

            if ($text !== '') {
                return [
                    'content' => $text,
                    'provider' => 'groq',
                    'model' => $groqModel,
                    'label' => $this->labelFor('groq', $groqModel),
                ];
            }
        } catch (Throwable $exception) {
            Log::warning('[Lemo AI] Groq fallback failed', [
                'error' => $exception->getMessage(),
                'gemini_error' => $geminiError?->getMessage(),
            ]);
        }

        return [
            'content' => 'Both Gemini and Groq are busy right now. Please try again in a few minutes, or browse APS and Funding on Chamu in the meantime.',
            'provider' => 'system',
            'model' => 'fallback',
            'label' => 'System',
        ];
    }

    public function labelFor(?string $provider, ?string $model): string
    {
        return match ($provider) {
            'gemini' => 'Gemini · '.$this->prettyModel($model),
            'groq' => 'Groq · '.$this->prettyModel($model),
            'system' => 'System',
            default => 'Lemo AI',
        };
    }

    /**
     * @return list<string>
     */
    private function geminiModels(): array
    {
        $models = config('lemo.gemini_models', ['gemini-flash-latest']);

        return array_values(array_unique(array_filter($models)));
    }

    /**
     * @param  list<array{role:string, content:string}>  $history
     */
    private function callGemini(string $model, string $systemInstruction, array $history, string $userMessage): string
    {
        $geminiHistory = [];

        foreach ($history as $turn) {
            if (($turn['role'] ?? '') === 'user') {
                $geminiHistory[] = Content::parse(part: $turn['content'], role: Role::USER);
            } elseif (($turn['role'] ?? '') === 'assistant') {
                $geminiHistory[] = Content::parse(part: $turn['content'], role: Role::MODEL);
            }
        }

        while ($geminiHistory !== [] && ($geminiHistory[0]->role ?? null) !== Role::USER) {
            array_shift($geminiHistory);
        }

        $chatSession = Gemini::generativeModel(model: $model)
            ->withSystemInstruction(Content::parse($systemInstruction))
            ->startChat(history: $geminiHistory);

        $response = $chatSession->sendMessage($userMessage);

        return trim((string) $response->text());
    }

    /**
     * @param  list<array{role:string, content:string}>  $history
     */
    private function callGroq(string $model, string $systemInstruction, array $history, string $userMessage): string
    {
        $apiKey = (string) config('lemo.groq.api_key');

        if ($apiKey === '') {
            throw new \RuntimeException('GROQ_API_KEY is not configured.');
        }

        $messages = [
            ['role' => 'system', 'content' => $systemInstruction],
        ];

        foreach ($history as $turn) {
            if (($turn['role'] ?? '') === 'user') {
                $messages[] = ['role' => 'user', 'content' => $turn['content']];
            } elseif (($turn['role'] ?? '') === 'assistant') {
                $messages[] = ['role' => 'assistant', 'content' => $turn['content']];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $baseUrl = rtrim((string) config('lemo.groq.base_url'), '/');
        $timeout = (int) config('lemo.groq.timeout', 45);

        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post($baseUrl.'/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.4,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Groq API error: '.$response->status().' '.$response->body());
        }

        return trim((string) data_get($response->json(), 'choices.0.message.content', ''));
    }

    private function prettyModel(?string $model): string
    {
        return match ($model) {
            'llama-3.3-70b-versatile' => 'Llama 3.3',
            'gemini-flash-lite-latest' => 'Flash Lite',
            'gemini-flash-latest' => 'Flash',
            'gemini-2.5-flash' => '2.5 Flash',
            'gemini-2.0-flash-lite' => '2.0 Flash Lite',
            'gemini-2.0-flash' => '2.0 Flash',
            'gemini-1.5-flash-latest', 'gemini-1.5-flash' => '1.5 Flash',
            default => $model ? (string) $model : 'AI',
        };
    }
}
