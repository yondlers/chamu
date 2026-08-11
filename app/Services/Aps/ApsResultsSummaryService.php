<?php

namespace App\Services\Aps;

use App\Models\Qualification;
use App\Services\LemoAi\LemoAiRouter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApsResultsSummaryService
{
    private const CACHE_TTL_SECONDS = 21600;

    public function __construct(
        private readonly LemoAiRouter $router,
    ) {}

    /**
     * Build a short assistance summary from REAL search results.
     *
     * @param  Collection<int, Qualification>  $courses
     * @param  array{
     *     search?: string|null,
     *     aps?: int|null,
     *     university_labels?: list<string>,
     *     faculty_labels?: list<string>,
     *     result_count?: int
     * }  $context
     */
    public function summarise(Collection $courses, array $context = []): ?string
    {
        $resultCount = (int) ($context['result_count'] ?? $courses->count());
        $fallback = $this->templateSummary($context, $resultCount);

        if ($resultCount === 0) {
            return $fallback;
        }

        $compact = $courses
            ->take(8)
            ->map(function (Qualification $course) {
                return [
                    'name' => (string) $course->name,
                    'university' => (string) ($course->university?->abbreviation ?: $course->university?->name),
                    'faculty' => (string) ($course->faculty?->name ?? ''),
                    'aps_required' => $course->aps_required !== null ? (int) $course->aps_required : null,
                    'score' => $course->admission_score_required !== null
                        ? (float) $course->admission_score_required
                        : ($course->aggregate_average_required !== null ? (float) $course->aggregate_average_required : null),
                ];
            })
            ->values()
            ->all();

        $cacheKey = 'aps_search_summary:'.hash('sha256', json_encode([
            'context' => [
                'search' => $context['search'] ?? null,
                'aps' => $context['aps'] ?? null,
                'universities' => $context['university_labels'] ?? [],
                'faculties' => $context['faculty_labels'] ?? [],
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
                Log::warning('[APS AI] Summary failed', [
                    'error' => $exception->getMessage(),
                ]);

                return $fallback;
            }
        });
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     aps?: int|null,
     *     university_labels?: list<string>,
     *     faculty_labels?: list<string>
     * }  $context
     */
    public function templateSummary(array $context, int $resultCount): string
    {
        $parts = [];

        if (isset($context['aps']) && is_int($context['aps'])) {
            $parts[] = 'an APS of '.$context['aps'];
        }

        $universities = array_values(array_filter($context['university_labels'] ?? []));
        if ($universities !== []) {
            $parts[] = 'your interest in '.$this->naturalList($universities);
        }

        $faculties = array_values(array_filter($context['faculty_labels'] ?? []));
        if ($faculties !== []) {
            $parts[] = 'a focus on '.$this->naturalList($faculties);
        }

        if ($parts === []) {
            $lead = 'Based on your search';
        } else {
            $lead = 'Based on '.implode(' and ', $parts);
        }

        if ($resultCount === 0) {
            return $lead.', we could not find matching qualifications in our database. Try adjusting your APS, university, or keywords. Admission may also depend on individual subject requirements.';
        }

        return $lead.', these are the qualifications in our database that best match your search. Admission may also depend on individual subject requirements.';
    }

    private function systemInstruction(): string
    {
        return <<<'PROMPT'
You write a short Google-like assistance blurb above Chamu course search results.

Rules:
- Use ONLY the provided filters and REAL result rows.
- Do NOT invent programmes, universities, APS values, or admission rules.
- 1-2 sentences, plain language, max 320 characters.
- Mention that subject requirements may still apply.
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
                'aps' => $context['aps'] ?? null,
                'universities' => $context['university_labels'] ?? [],
                'faculties' => $context['faculty_labels'] ?? [],
            ],
            'result_count' => $resultCount,
            'sample_results' => $compact,
        ], JSON_THROW_ON_ERROR);

        return "Write the short summary for this Chamu search:\n{$payload}";
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
