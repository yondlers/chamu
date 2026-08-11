<?php

namespace App\Services\Aps;

use App\Services\LemoAi\LemoAiRouter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApsSearchInterpretationService
{
    private const CACHE_TTL_SECONDS = 21600;

    public function __construct(
        private readonly LemoAiRouter $router,
    ) {}

    /**
     * Interpret natural-language search text into a strict filter schema.
     *
     * @param  list<array{type?:string,label?:string,token?:string}>  $selectedTags
     * @return array{
     *     intent: string,
     *     universities: list<string>,
     *     faculties: list<string>,
     *     qualifications: list<string>,
     *     aps: int|null,
     *     subjects: array<string, int>,
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

        $cacheKey = 'aps_search_interpret:'.hash('sha256', json_encode([
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
                Log::warning('[APS AI] Interpretation failed', [
                    'error' => $exception->getMessage(),
                ]);

                return $empty;
            }
        });
    }

    /**
     * @return array{
     *     intent: string,
     *     universities: list<string>,
     *     faculties: list<string>,
     *     qualifications: list<string>,
     *     aps: int|null,
     *     subjects: array<string, int>,
     *     query: string|null
     * }
     */
    public function emptySchema(): array
    {
        return [
            'intent' => 'qualification_search',
            'universities' => [],
            'faculties' => [],
            'qualifications' => [],
            'aps' => null,
            'subjects' => [],
            'query' => null,
        ];
    }

    private function systemInstruction(): string
    {
        return <<<'PROMPT'
You convert South African student course-search requests into STRICT JSON filters for Chamu.

Return ONLY valid JSON. No markdown. No commentary.

Schema:
{
  "intent": "qualification_search",
  "universities": [],
  "faculties": [],
  "qualifications": [],
  "aps": null,
  "subjects": {},
  "query": null
}

Rules:
- universities: short names or abbreviations only (e.g. "UP", "UCT", "University of Pretoria").
- faculties: faculty/field names when clearly stated (e.g. "Engineering").
- qualifications: programme or qualification-type names when clearly stated.
- aps: integer APS/score the learner has, when stated. Otherwise null.
- subjects: optional map of subject_key => mark percentage (0-100). Keys like mathematics, english, physical_sciences, life_sciences, accounting. Use {} if unknown.
- query: residual keyword for text search, or null when filters/aps are enough.
- Do NOT invent universities, faculties, qualifications, APS values, or admission rules.
- If already-selected tags are provided, keep them in universities/faculties/qualifications when relevant.
- intent must be "qualification_search".
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
     *     universities: list<string>,
     *     faculties: list<string>,
     *     qualifications: list<string>,
     *     aps: int|null,
     *     subjects: array<string, int>,
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

        $aps = $json['aps'] ?? null;
        if (is_string($aps) && is_numeric($aps)) {
            $aps = (int) $aps;
        }
        if (! is_int($aps) && ! is_float($aps)) {
            $aps = null;
        } else {
            $aps = (int) $aps;
            if ($aps < 0 || $aps > 100) {
                $aps = null;
            }
        }

        $subjects = [];
        if (is_array($json['subjects'] ?? null)) {
            foreach ($json['subjects'] as $key => $value) {
                if (! is_string($key) || (! is_int($value) && ! is_float($value) && ! (is_string($value) && is_numeric($value)))) {
                    continue;
                }

                $normalisedKey = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $key) ?? $key, '_'));
                $mark = (int) $value;

                if ($normalisedKey === '' || $mark < 0 || $mark > 100) {
                    continue;
                }

                $subjects[$normalisedKey] = $mark;
            }
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
            'intent' => 'qualification_search',
            'universities' => $this->stringList($json['universities'] ?? []),
            'faculties' => $this->stringList($json['faculties'] ?? []),
            'qualifications' => $this->stringList($json['qualifications'] ?? []),
            'aps' => $aps,
            'subjects' => $subjects,
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
