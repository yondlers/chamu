<?php

namespace App\Support;

use App\Models\Career;
use App\Models\CareerQualification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CareerUpsert
{
    /**
     * @var array<string, Career>|null
     */
    private ?array $cacheByMatchKey = null;

    /**
     * Normalize a career name through the dedupe pipeline, then update or create.
     *
     * @param  array{
     *     salary_expectation?: string|null,
     *     description?: string|null,
     *     source_url?: string|null,
     *     is_active?: bool|null
     * }  $attributes
     * @return array{id: int, career: Career, was_created: bool, match_key: string}|null
     */
    public function upsert(string $name, array $attributes = []): ?array
    {
        $displayName = $this->normalizeName($name);

        if ($displayName === null) {
            return null;
        }

        $matchKey = $this->matchKey($displayName);
        $existing = $this->findByMatchKey($matchKey);
        $wasCreated = $existing === null;
        $career = $existing ?? new Career;

        $career->fill([
            'name' => $this->preferredDisplayName($displayName, $career->name),
            'salary_expectation' => $this->preferFilled($attributes['salary_expectation'] ?? null, $career->salary_expectation),
            'description' => $this->preferFilled($attributes['description'] ?? null, $career->description),
            'source_url' => $this->resolveSourceUrl($attributes['source_url'] ?? null, $career->source_url),
            'is_active' => $attributes['is_active'] ?? true,
        ]);
        $career->save();

        $this->remember($career);

        return [
            'id' => (int) $career->id,
            'career' => $career,
            'was_created' => $wasCreated,
            'match_key' => $matchKey,
        ];
    }

    /**
     * Update an existing career row via the same normalize + match pipeline.
     * If the new name collides with another career, merges into that career and returns it.
     *
     * @param  array{
     *     salary_expectation?: string|null,
     *     description?: string|null,
     *     source_url?: string|null,
     *     is_active?: bool|null
     * }  $attributes
     * @return array{id: int, career: Career, was_created: bool, match_key: string, merged_from_id?: int}|null
     */
    public function update(Career $career, string $name, array $attributes = []): ?array
    {
        $displayName = $this->normalizeName($name);

        if ($displayName === null) {
            return null;
        }

        $matchKey = $this->matchKey($displayName);
        $existing = $this->findByMatchKey($matchKey);

        if ($existing !== null && (int) $existing->id !== (int) $career->id) {
            $this->mergeInto($existing, $career);

            $existing->fill([
                'name' => $this->preferredDisplayName($displayName, $existing->name),
                'salary_expectation' => $this->preferFilled($attributes['salary_expectation'] ?? null, $existing->salary_expectation),
                'description' => $this->preferFilled($attributes['description'] ?? null, $existing->description),
                'source_url' => $this->resolveSourceUrl($attributes['source_url'] ?? null, $existing->source_url),
                'is_active' => $attributes['is_active'] ?? $existing->is_active,
            ]);
            $existing->save();
            $this->remember($existing);

            return [
                'id' => (int) $existing->id,
                'career' => $existing->fresh() ?? $existing,
                'was_created' => false,
                'match_key' => $matchKey,
                'merged_from_id' => (int) $career->id,
            ];
        }

        $career->fill([
            'name' => $this->preferredDisplayName($displayName, $career->name),
            'salary_expectation' => array_key_exists('salary_expectation', $attributes)
                ? ($attributes['salary_expectation'] ?: $career->salary_expectation)
                : $career->salary_expectation,
            'description' => array_key_exists('description', $attributes)
                ? ($attributes['description'] ?: $career->description)
                : $career->description,
            'source_url' => array_key_exists('source_url', $attributes)
                ? $this->resolveSourceUrl($attributes['source_url'] ?? null, $career->source_url)
                : $this->resolveSourceUrl(null, $career->source_url),
            'is_active' => $attributes['is_active'] ?? $career->is_active,
        ]);
        $career->save();
        $this->remember($career);

        return [
            'id' => (int) $career->id,
            'career' => $career,
            'was_created' => false,
            'match_key' => $matchKey,
        ];
    }

    public function normalizeName(string $name): ?string
    {
        $name = $this->stripNoise($name);
        $name = $this->stripCareerPrefixes($name);
        $name = $this->stripLeadingParticles($name);
        $name = $this->stripTrailingConnectors($name);
        $name = $this->collapseWhitespace($name);

        if (! $this->nameIsUsable($name)) {
            return null;
        }

        return $this->formatDisplayName($name);
    }

    public function matchKey(string $name): string
    {
        $normalized = $this->normalizeName($name) ?? $this->collapseWhitespace($name);
        $key = Str::lower($normalized);
        $key = str_replace(['/', '\\', '-', '_'], ' ', $key);
        $key = preg_replace('/[^a-z0-9\s]/', '', $key) ?? $key;
        $key = preg_replace('/\b(a|an|the|and|or|of|in|for|as|to|on)\b/', ' ', $key) ?? $key;
        $key = $this->collapseWhitespace($key);

        $words = $key === '' ? [] : explode(' ', $key);
        $words = array_map(fn (string $word) => $this->singularizeWord($word), $words);
        $words = array_values(array_filter($words, fn (string $word) => $word !== ''));

        return implode(' ', $words);
    }

    public function findByMatchKey(string $matchKey): ?Career
    {
        $this->warmCache();

        return $this->cacheByMatchKey[$matchKey] ?? null;
    }

    public function mergeInto(Career $keeper, Career $duplicate): void
    {
        if ((int) $keeper->id === (int) $duplicate->id) {
            return;
        }

        DB::transaction(function () use ($keeper, $duplicate): void {
            $duplicateLinks = CareerQualification::query()
                ->where('career_id', $duplicate->id)
                ->get();

            foreach ($duplicateLinks as $link) {
                $existingLink = CareerQualification::query()
                    ->where('career_id', $keeper->id)
                    ->where('qualification_id', $link->qualification_id)
                    ->first();

                if ($existingLink !== null) {
                    if (blank($existingLink->notes) && filled($link->notes)) {
                        $existingLink->notes = $link->notes;
                        $existingLink->save();
                    }
                    $link->delete();

                    continue;
                }

                $link->career_id = $keeper->id;
                $link->save();
            }

            $keeper->fill([
                'salary_expectation' => $this->preferFilled($keeper->salary_expectation, $duplicate->salary_expectation),
                'description' => $this->preferFilled($keeper->description, $duplicate->description),
                'source_url' => $this->resolveSourceUrl($duplicate->source_url, $keeper->source_url),
                'is_active' => $keeper->is_active || $duplicate->is_active,
            ]);
            $keeper->save();

            $this->forget($duplicate);
            $duplicate->delete();
            $this->remember($keeper);
        });
    }

    public function forgetCache(): void
    {
        $this->cacheByMatchKey = null;
    }

    /**
     * Career source_url is only for salary providers (PayScale today).
     * University programme pages belong on the qualification, not the career.
     */
    public function isSalarySourceUrl(?string $url): bool
    {
        if (! filled($url)) {
            return false;
        }

        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        return str_contains($host, 'payscale.com');
    }

    /**
     * Keep / accept only salary-provider URLs. Non-salary URLs (university pages, PDFs) are ignored.
     */
    public function resolveSourceUrl(?string $incoming, ?string $existing): ?string
    {
        if ($this->isSalarySourceUrl($incoming)) {
            return $incoming;
        }

        if ($this->isSalarySourceUrl($existing)) {
            return $existing;
        }

        return null;
    }

    /**
     * Clear university / non-salary source URLs from existing career rows.
     */
    public function clearNonSalarySources(): int
    {
        $cleared = 0;

        Career::query()
            ->whereNotNull('source_url')
            ->where('source_url', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($careers) use (&$cleared): void {
                foreach ($careers as $career) {
                    if ($this->isSalarySourceUrl($career->source_url)) {
                        continue;
                    }

                    $career->source_url = null;
                    $career->save();
                    $cleared++;
                }
            });

        $this->forgetCache();

        return $cleared;
    }

    private function preferFilled(mixed $primary, mixed $fallback): mixed
    {
        return filled($primary) ? $primary : $fallback;
    }

    private function warmCache(): void
    {
        if ($this->cacheByMatchKey !== null) {
            return;
        }

        $this->cacheByMatchKey = [];

        Career::query()->orderBy('id')->get()->each(function (Career $career): void {
            $key = $this->matchKey($career->name);
            if ($key === '' || isset($this->cacheByMatchKey[$key])) {
                return;
            }

            $this->cacheByMatchKey[$key] = $career;
        });
    }

    private function remember(Career $career): void
    {
        $this->warmCache();
        $this->cacheByMatchKey[$this->matchKey($career->name)] = $career;
    }

    private function forget(Career $career): void
    {
        if ($this->cacheByMatchKey === null) {
            return;
        }

        $key = $this->matchKey($career->name);
        if (($this->cacheByMatchKey[$key]->id ?? null) === $career->id) {
            unset($this->cacheByMatchKey[$key]);
        }
    }

    private function stripNoise(string $name): string
    {
        $name = strip_tags($name);
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = str_replace(['...', "\u{2026}", "\u{2022}"], ' ', $name);

        return $this->collapseWhitespace($name);
    }

    private function stripCareerPrefixes(string $name): string
    {
        $name = preg_replace(
            '/^(careers?|career opportunities?|career paths?|possible careers?|graduates (can )?(become|work as|are employed as|follow careers in)|roles? include)\s*:?\s*/i',
            '',
            $name,
        ) ?? $name;

        return trim($name, " \t\n\r\0\x0B.-");
    }

    private function stripLeadingParticles(string $name): string
    {
        $name = preg_replace('/^(in|a|an|the)\s+/i', '', $name) ?? $name;

        return trim($name);
    }

    private function stripTrailingConnectors(string $name): string
    {
        // Broken list fragments from seed splitting: "Manager and", "Design or", "Master of the"
        do {
            $previous = $name;
            $name = preg_replace('/\s+(and|or|of|in|the|for|to|on)$/i', '', $name) ?? $name;
            $name = trim($name, " \t\n\r\0\x0B.-");
        } while ($name !== $previous);

        return $name;
    }

    private function collapseWhitespace(string $value): string
    {
        return Str::squish($value);
    }

    private function nameIsUsable(string $name): bool
    {
        if ($name === '' || Str::length($name) < 3 || Str::length($name) > 90) {
            return false;
        }

        return ! Str::contains(Str::lower($name), [
            'applicants',
            'course prepares',
            'degree programme',
            'employment opportunities',
            'graduates ',
            'programme ',
            'students ',
            'the course',
        ]);
    }

    private function formatDisplayName(string $name): string
    {
        // Keep source casing; only ensure the first character is capitalized.
        // Full Title Case is too aggressive for acronyms (FET, CAD, EEG) and seeded names.
        return ucfirst($name);
    }

    private function preferredDisplayName(string $incoming, ?string $existing): string
    {
        if (blank($existing)) {
            return $incoming;
        }

        // Prefer the existing display name when it already matches the same role.
        if ($this->matchKey((string) $existing) === $this->matchKey($incoming)) {
            $existingNormalized = $this->normalizeName((string) $existing);

            return $existingNormalized ?? $incoming;
        }

        return $incoming;
    }

    private function singularizeWord(string $word): string
    {
        $aliases = [
            'systems' => 'system',
            'sports' => 'sport',
            'resources' => 'resource',
            'services' => 'service',
            'analysts' => 'analyst',
            'officers' => 'officer',
            'accountants' => 'accountant',
            'administrators' => 'administrator',
            'managers' => 'manager',
            'marketers' => 'marketer',
            'horticulturists' => 'horticulturist',
            'researchers' => 'researcher',
            'guides' => 'guide',
            'events' => 'event',
            'communications' => 'communication',
            'studies' => 'study',
        ];

        if (isset($aliases[$word])) {
            return $aliases[$word];
        }

        // Words that look plural but should stay intact for matching.
        if (in_array($word, ['business', 'analysis', 'mathematics', 'physics', 'economics', 'news'], true)) {
            return $word;
        }

        if (strlen($word) > 4 && str_ends_with($word, 'ies')) {
            return substr($word, 0, -3).'y';
        }

        if (strlen($word) > 4 && str_ends_with($word, 's') && ! str_ends_with($word, 'ss')) {
            return substr($word, 0, -1);
        }

        return $word;
    }
}
