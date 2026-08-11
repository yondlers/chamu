<?php

namespace App\Services\Bursary;

use App\Services\LemoAi\LemoAiRouter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class BursarySearchInterpretationService
{
    private const CACHE_TTL_SECONDS = 21600;

    public function __construct(
        private readonly LemoAiRouter $router,
    ) {}

    /**
     * @param  list<array{type?:string,label?:string}>  $selectedTags
     * @return array{
     *     intent: string,
     *     categories: list<string>,
     *     companies: list<string>,
     *     query: string|null
     * }
     */
    public function interpret(string $freeText, array $selectedTags = []): array
    {
        $freeText = trim($freeText);
        $empty = $this->emptySchema();

        if ($freeText === '') {
            return $empty;
        }

        $tagContext = collect($selectedTags)
            ->map(fn (array $tag) => trim(($tag['type'] ?? 'filter').': '.($tag['label'] ?? '')))
            ->filter()
            ->values()
            ->all();

        $cacheKey = 'bursary_search_interpret:'.hash('sha256', json_encode([
            'text' => mb_strtolower($freeText),
            'tags' => $tagContext,
        ], JSON_THROW_ON_ERROR));

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($freeText, $tagContext, $empty) {
            try {
                $response = $this->router->generate(
                    $this->systemInstruction(),
                    [],
                    $this->userMessage($freeText, $tagContext),
                );

                if (($response['provider'] ?? '') === 'system') {
                    return $empty;
                }

                return $this->parseAndValidate((string) ($response['content'] ?? ''));
            } catch (Throwable $exception) {
                Log::warning('[Bursary AI] Interpretation failed', [
                    'error' => $exception->getMessage(),
                ]);

                return $empty;
            }
        });
    }

    /**
     * @return array{
     *     intent: string,
     *     categories: list<string>,
     *     companies: list<string>,
     *     query: string|null
     * }
     */
    public function emptySchema(): array
    {
        return [
            'intent' => 'bursary_search',
            'categories' => [],
            'companies' => [],
            'query' => null,
        ];
    }

    private function systemInstruction(): string
    {
        return <<<'PROMPT'
You convert South African bursary/funding search requests into STRICT JSON filters for Chamu.

Return ONLY valid JSON. No markdown. No commentary.

Schema:
{
  "intent": "bursary_search",
  "categories": [],
  "companies": [],
  "query": null
}

Rules:
- categories: funding fields/categories when clearly stated (e.g. "Engineering", "Accounting").
- companies: provider/company names when clearly stated.
- query: residual keyword for text search, or null when category/company filters are enough.
- Do NOT invent bursaries, companies, categories, closing dates, or eligibility rules.
- If already-selected tags are provided, keep them in categories/companies when relevant.
- intent must be "bursary_search".
PROMPT;
    }

    /**
     * @param  list<string>  $tagContext
     */
    private function userMessage(string $freeText, array $tagContext): string
    {
        $selected = $tagContext === []
            ? 'None'
            : implode(', ', $tagContext);

        return <<<PROMPT
Already selected filters: {$selected}

Custom search text:
{$freeText}

Return JSON only.
PROMPT;
    }

    /**
     * @return array{
     *     intent: string,
     *     categories: list<string>,
     *     companies: list<string>,
     *     query: string|null
     * }
     */
    private function parseAndValidate(string $content): array
    {
        $empty = $this->emptySchema();
        $json = $this->extractJson($content);

        if ($json === null) {
            return $empty;
        }

        $query = $json['query'] ?? null;
        if (! is_string($query)) {
            $query = null;
        } else {
            $query = trim($query);
            if ($query === '') {
                $query = null;
            }
        }

        return [
            'intent' => 'bursary_search',
            'categories' => $this->stringList($json['categories'] ?? []),
            'companies' => $this->stringList($json['companies'] ?? []),
            'query' => $query,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractJson(string $content): ?array
    {
        $content = trim($content);

        if ($content === '') {
            return null;
        }

        if (preg_match('/\{.*\}/s', $content, $matches) === 1) {
            $content = $matches[0];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(function ($item) {
            if (! is_string($item) && ! is_numeric($item)) {
                return null;
            }

            $text = trim((string) $item);

            return $text === '' ? null : $text;
        }, $value))));
    }
}
