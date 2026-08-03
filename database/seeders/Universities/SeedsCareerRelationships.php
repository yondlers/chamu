<?php

namespace Database\Seeders\Universities;

use App\Models\Career;
use App\Models\CareerQualification;
use Illuminate\Support\Str;

trait SeedsCareerRelationships
{
    /**
     * @var array<string, Career>
     */
    private array $careerCacheByName = [];

    protected function syncCareerRelationships(int $qualificationId, array $qualificationData, ?string $sourceUrl = null): void
    {
        $careerDataIsPresent = $this->hasCareerData($qualificationData);

        if (! $careerDataIsPresent) {
            return;
        }

        $entries = $this->careerEntries($qualificationData, $sourceUrl);
        $currentLinks = CareerQualification::all()
            ->where('qualification_id', $qualificationId)
            ->values();
        $activeCareerIds = [];

        foreach ($entries as $index => $entry) {
            $career = $this->careerFor($entry);
            $activeCareerIds[] = (int) $career->id;

            $link = $currentLinks->first(fn (CareerQualification $currentLink) => (int) $currentLink->career_id === (int) $career->id)
                ?? new CareerQualification;

            $link->fill([
                'career_id' => $career->id,
                'qualification_id' => $qualificationId,
                'sort_order' => $entry['sort_order'] ?? $index + 1,
                'notes' => $entry['notes'] ?? null,
            ]);
            $link->save();
        }

        $currentLinks
            ->reject(fn (CareerQualification $currentLink) => in_array((int) $currentLink->career_id, $activeCareerIds, true))
            ->each(fn (CareerQualification $currentLink) => $currentLink->delete());
    }

    private function hasCareerData(array $qualificationData): bool
    {
        foreach (['possible_careers', 'careers', 'career_paths', 'career_opportunities'] as $key) {
            if (array_key_exists($key, $qualificationData)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{name: string, salary_expectation: string|null, description: string|null, source_url: string|null, sort_order?: int, notes?: string|null}>
     */
    private function careerEntries(array $qualificationData, ?string $sourceUrl): array
    {
        $rawCareers = $qualificationData['possible_careers']
            ?? $qualificationData['careers']
            ?? $qualificationData['career_paths']
            ?? $qualificationData['career_opportunities']
            ?? [];

        if (is_string($rawCareers)) {
            $rawCareers = $this->splitCareerText($rawCareers);
        }

        if (! is_array($rawCareers)) {
            return [];
        }

        return collect($rawCareers)
            ->map(function ($career, int $index) use ($sourceUrl) {
                if (is_string($career)) {
                    return [
                        'name' => $this->cleanCareerName($career),
                        'salary_expectation' => null,
                        'description' => null,
                        'source_url' => $sourceUrl,
                        'sort_order' => $index + 1,
                    ];
                }

                if (! is_array($career)) {
                    return null;
                }

                $name = $career['name'] ?? $career['title'] ?? $career['career'] ?? null;

                if (! is_string($name)) {
                    return null;
                }

                return [
                    'name' => $this->cleanCareerName($name),
                    'salary_expectation' => $career['salary_expectation'] ?? $career['salary'] ?? null,
                    'description' => $career['description'] ?? null,
                    'source_url' => $career['source_url'] ?? $sourceUrl,
                    'sort_order' => $career['sort_order'] ?? $index + 1,
                    'notes' => $career['notes'] ?? null,
                ];
            })
            ->filter(fn ($career) => is_array($career) && $this->careerNameIsUsable($career['name']))
            ->unique(fn ($career) => Str::lower($career['name']))
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function splitCareerText(string $careerText): array
    {
        $careerText = Str::squish(strip_tags($careerText));
        $careerText = str_replace(['...', "\u{2026}"], '', $careerText);
        $careerText = str_replace('specialiste-business', 'specialist | e-business', $careerText);
        $careerText = str_replace("\u{2022}", '|', $careerText);
        $careerText = preg_replace('/(?<=\))(?=[A-Z])/', ' | ', $careerText) ?? $careerText;
        $careerText = preg_replace('/\be-business/i', 'e-business', $careerText) ?? $careerText;

        if (preg_match('/(?:for example|such as|include(?:s)?(?: the following)?|careers?)\s*:\s*(.+)$/i', $careerText, $matches) === 1) {
            $careerText = $matches[1];
        }

        $careerText = preg_replace('/(?<=[a-z)])\s+(?=(?:[A-Z][a-z]|IT\b|ICT\b|AI\b|e-business\b))/', ' | ', $careerText) ?? $careerText;
        $careerText = str_replace([' and/or ', ' / '], ', ', $careerText);

        return $this->splitCareerList($careerText);
    }

    /**
     * @return array<int, string>
     */
    private function splitCareerList(string $careerText): array
    {
        $parts = [];
        $part = '';
        $parenthesesDepth = 0;

        foreach (str_split($careerText) as $character) {
            if ($character === '(') {
                $parenthesesDepth++;
                $part .= $character;

                continue;
            }

            if ($character === ')') {
                $parenthesesDepth = max(0, $parenthesesDepth - 1);
                $part .= $character;

                continue;
            }

            if ($parenthesesDepth === 0 && in_array($character, [',', ';', '|'], true)) {
                $parts[] = $part;
                $part = '';

                continue;
            }

            $part .= $character;
        }

        $parts[] = $part;

        return collect($parts)
            ->map(fn (string $part) => Str::squish($part))
            ->filter()
            ->values()
            ->all();
    }

    private function cleanCareerName(string $name): string
    {
        $name = Str::squish($name);
        $name = preg_replace('/^(careers?|career opportunities?|graduates (can )?(become|work as|are employed as|follow careers in)|roles? include)\s*:?\s*/i', '', $name) ?? $name;
        $name = trim($name, " \t\n\r\0\x0B.-");

        return ucfirst($name);
    }

    private function careerNameIsUsable(string $name): bool
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

    /**
     * @param  array{name: string, salary_expectation: string|null, description: string|null, source_url: string|null}  $entry
     */
    private function careerFor(array $entry): Career
    {
        if ($this->careerCacheByName === []) {
            $this->careerCacheByName = Career::all()
                ->keyBy(fn (Career $career) => Str::lower($career->name))
                ->all();
        }

        $cacheKey = Str::lower($entry['name']);
        $career = $this->careerCacheByName[$cacheKey] ?? new Career(['name' => $entry['name']]);
        $career->fill([
            'name' => $entry['name'],
            'salary_expectation' => $entry['salary_expectation'] ?: $career->salary_expectation,
            'description' => $entry['description'] ?: $career->description,
            'source_url' => $entry['source_url'] ?: $career->source_url,
            'is_active' => true,
        ]);
        $career->save();

        $this->careerCacheByName[$cacheKey] = $career;

        return $career;
    }
}
