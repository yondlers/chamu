<?php

namespace App\Services\Bursary;

use App\Services\LemoAi\LemoAiRouter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class BursaryResultsSummaryService
{
    private const CACHE_TTL_SECONDS = 21600;

    public function __construct(
        private readonly LemoAiRouter $router,
    ) {}

    /**
     * @param  Collection<int, object>  $bursaries
     * @param  array{
     *     search?: string|null,
     *     category_labels?: list<string>,
     *     company_labels?: list<string>,
     *     result_count?: int
     * }  $context
     */
    public function summarise(Collection $bursaries, array $context = []): ?string
    {
        $resultCount = (int) ($context['result_count'] ?? $bursaries->count());
        $fallback = $this->templateSummary($context, $resultCount);

        if ($resultCount === 0) {
            return $fallback;
        }

        $compact = $bursaries
            ->take(8)
            ->map(function ($bursary) {
                return [
                    'title' => (string) ($bursary->title ?? ''),
                    'company' => (string) ($bursary->company_name ?? ''),
                    'category' => (string) ($bursary->category ?? ''),
                    'closing_date' => $bursary->closing_date ?? null,
                ];
            })
            ->values()
            ->all();

        $cacheKey = 'bursary_search_summary:'.hash('sha256', json_encode([
            'context' => [
                'search' => $context['search'] ?? null,
                'categories' => $context['category_labels'] ?? [],
                'companies' => $context['company_labels'] ?? [],
                'result_count' => $resultCount,
            ],
            'results' => $compact,
        ], JSON_THROW_ON_ERROR));

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($compact, $context, $resultCount, $fallback) {
            try {
                $response = $this->router->generate(
                    $this->systemInstruction(),
                    [],
                    $this->userMessage($compact, $context, $resultCount),
                );

                if (($response['provider'] ?? '') === 'system') {
                    return $fallback;
                }

                $text = trim((string) ($response['content'] ?? ''));
                $text = trim($text, " \t\n\r\0\x0B\"'");

                if ($text === '' || mb_strlen($text) > 420) {
                    return $fallback;
                }

                return $text;
            } catch (Throwable $exception) {
                Log::warning('[Bursary AI] Summary failed', [
                    'error' => $exception->getMessage(),
                ]);

                return $fallback;
            }
        });
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     category_labels?: list<string>,
     *     company_labels?: list<string>
     * }  $context
     */
    public function templateSummary(array $context, int $resultCount): string
    {
        $parts = [];

        $categories = array_values(array_filter($context['category_labels'] ?? []));
        if ($categories !== []) {
            $parts[] = 'your interest in '.$this->naturalList($categories);
        }

        $companies = array_values(array_filter($context['company_labels'] ?? []));
        if ($companies !== []) {
            $parts[] = 'providers like '.$this->naturalList($companies);
        }

        $lead = $parts === []
            ? 'Based on your search'
            : 'Based on '.implode(' and ', $parts);

        if ($resultCount === 0) {
            return $lead.', we could not find matching bursaries in our database. Try a broader category, another provider, or different keywords. Always check eligibility and closing dates before applying.';
        }

        return $lead.', these are the bursaries in our database that best match your search. Always check eligibility and closing dates before applying.';
    }

    private function systemInstruction(): string
    {
        return <<<'PROMPT'
You write a short Google-like assistance blurb above Chamu bursary search results.

Rules:
- Use ONLY the provided filters and REAL result rows.
- Do NOT invent bursaries, companies, amounts, or eligibility rules.
- 1-2 sentences, plain language, max 320 characters.
- Mention that eligibility and closing dates should still be checked.
- Return plain text only.
PROMPT;
    }

    /**
     * @param  list<array<string, mixed>>  $compact
     * @param  array<string, mixed>  $context
     */
    private function userMessage(array $compact, array $context, int $resultCount): string
    {
        $payload = json_encode([
            'filters' => [
                'search' => $context['search'] ?? null,
                'categories' => $context['category_labels'] ?? [],
                'companies' => $context['company_labels'] ?? [],
            ],
            'result_count' => $resultCount,
            'sample_results' => $compact,
        ], JSON_THROW_ON_ERROR);

        return "Write the short summary for this Chamu bursary search:\n{$payload}";
    }

    /**
     * @param  list<string>  $items
     */
    private function naturalList(array $items): string
    {
        $items = array_values($items);

        if (count($items) === 1) {
            return $items[0];
        }

        if (count($items) === 2) {
            return $items[0].' and '.$items[1];
        }

        $last = array_pop($items);

        return implode(', ', $items).', and '.$last;
    }
}
