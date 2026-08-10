<?php

namespace Database\Seeders\Universities;

use App\Models\CareerQualification;
use App\Support\CareerUpsert;
use Illuminate\Support\Str;

trait SeedsCareerRelationships
{
    private ?CareerUpsert $careerUpsert = null;

    protected function syncCareerRelationships(int $qualificationId, array $qualificationData, ?string $sourceUrl = null): void
    {
        $careerDataIsPresent = $this->hasCareerData($qualificationData);

        if (! $careerDataIsPresent) {
            return;
        }

        $entries = $this->careerEntries($qualificationData, $sourceUrl);
        $currentLinks = CareerQualification::query()
            ->where('qualification_id', $qualificationId)
            ->get();
        $activeCareerIds = [];

        foreach ($entries as $index => $entry) {
            $result = $this->careerUpsert()->upsert($entry['name'], [
                'salary_expectation' => $entry['salary_expectation'],
                'description' => $entry['description'],
                // Career source_url is reserved for salary providers (PayScale).
                // University programme pages stay on the qualification.
                'is_active' => true,
            ]);

            if ($result === null) {
                continue;
            }

            $career = $result['career'];
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

    private function careerUpsert(): CareerUpsert
    {
        return $this->careerUpsert ??= new CareerUpsert;
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

        $upsert = $this->careerUpsert();

        return collect($rawCareers)
            ->map(function ($career, int $index) {
                if (is_string($career)) {
                    return [
                        'name' => $career,
                        'salary_expectation' => null,
                        'description' => null,
                        'source_url' => null,
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
                    'name' => $name,
                    'salary_expectation' => $career['salary_expectation'] ?? $career['salary'] ?? null,
                    'description' => $career['description'] ?? null,
                    // Ignore university/programme source URLs on careers.
                    'source_url' => null,
                    'sort_order' => $career['sort_order'] ?? $index + 1,
                    'notes' => $career['notes'] ?? null,
                ];
            })
            ->filter(function ($career) use ($upsert) {
                return is_array($career)
                    && is_string($career['name'] ?? null)
                    && $upsert->normalizeName($career['name']) !== null;
            })
            ->unique(fn ($career) => $upsert->matchKey($career['name']))
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
}
