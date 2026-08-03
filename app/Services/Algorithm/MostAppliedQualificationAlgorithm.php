<?php

namespace App\Services\Algorithm;

use App\Models\Qualification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MostAppliedQualificationAlgorithm
{
    private const CATEGORY_WEIGHTS = [
        'computer_science_it' => 8,
        'health_sciences_nursing' => 7,
        'business_commerce' => 6,
        'engineering' => 4,
    ];

    private const CATEGORY_KEYWORDS = [
        'computer_science_it' => [
            'computer science',
            'information technology',
            'information systems',
            'informatics',
            'software',
            'data science',
            'data analytics',
            'artificial intelligence',
            'cyber security',
            'cybersecurity',
            'network',
            'ict',
            'multimedia',
            'digital technology',
        ],
        'health_sciences_nursing' => [
            'health sciences',
            'medicine',
            'medical',
            'nursing',
            'pharmacy',
            'physiotherapy',
            'occupational therapy',
            'dental',
            'dentistry',
            'oral hygiene',
            'radiography',
            'emergency medical care',
            'clinical',
            'biomedical',
            'audiology',
            'speech-language',
            'speech language',
            'dietetics',
            'nutrition',
        ],
        'business_commerce' => [
            'business',
            'commerce',
            'bcom',
            'accounting',
            'accountancy',
            'management sciences',
            'business management',
            'financial',
            'finance',
            'economics',
            'marketing',
            'human resources',
            'supply chain',
            'logistics',
            'business administration',
            'public administration',
            'office management',
            'entrepreneurship',
            'investment',
            'taxation',
            'auditing',
        ],
        'engineering' => [
            'engineering',
            'civil engineering',
            'mechanical engineering',
            'electrical engineering',
            'electronic engineering',
            'chemical engineering',
            'industrial engineering',
            'computer engineering',
            'mechatronics',
            'construction',
            'built environment',
            'quantity surveying',
        ],
    ];

    /**
     * This script is designed to bring about the most applied qualification to the first screen of the user.  The desired motive is to encourage use of system by displaying something that might draw be interested in might encourage further system usage.
     *
     * @param  Collection<int, Qualification>  $qualifications
     * @return Collection<int, Qualification>
     */
    public function rankForFirstScreen(Collection $qualifications, int $limit = 25): Collection
    {
        $selected = collect();
        $preferredQualifications = $qualifications
            ->unique('id')
            ->filter(fn (Qualification $qualification) => in_array($this->qualificationFamily($qualification), ['degree', 'diploma'], true))
            ->values();

        foreach ($this->categoryQuotas($limit) as $category => $quota) {
            if ($selected->count() >= $limit) {
                break;
            }

            $categoryMatches = $this->matchingCategory($preferredQualifications, $category)
                ->reject(fn (Qualification $qualification) => $this->alreadySelected($selected, $qualification))
                ->values();

            $selected = $selected
                ->merge($this->balancedByQualificationFamily($categoryMatches, min($quota, $limit - $selected->count())))
                ->unique('id')
                ->values();
        }

        if ($selected->count() < $limit) {
            $selected = $selected
                ->merge($this->balancedByQualificationFamily(
                    $preferredQualifications
                        ->filter(fn (Qualification $qualification) => $this->matchesAnyCategory($qualification))
                        ->reject(fn (Qualification $qualification) => $this->alreadySelected($selected, $qualification))
                        ->values(),
                    $limit - $selected->count()
                ))
                ->unique('id')
                ->values();
        }

        if ($selected->count() < $limit) {
            $selected = $selected
                ->merge($this->balancedByQualificationFamily(
                    $qualifications
                        ->unique('id')
                        ->filter(fn (Qualification $qualification) => $this->matchesAnyCategory($qualification))
                        ->reject(fn (Qualification $qualification) => $this->alreadySelected($selected, $qualification))
                        ->values(),
                    $limit - $selected->count()
                ))
                ->unique('id')
                ->values();
        }

        return $this->ensureDegreeDiplomaMix($selected, $preferredQualifications, $limit)
            ->take($limit)
            ->values();
    }

    /**
     * @return array<string, int>
     */
    private function categoryQuotas(int $limit): array
    {
        if ($limit === array_sum(self::CATEGORY_WEIGHTS)) {
            return self::CATEGORY_WEIGHTS;
        }

        $allocated = [];
        $remaining = $limit;
        $totalWeight = array_sum(self::CATEGORY_WEIGHTS);

        foreach (self::CATEGORY_WEIGHTS as $category => $weight) {
            $quota = max(1, (int) floor(($limit * $weight) / $totalWeight));
            $allocated[$category] = min($quota, $remaining);
            $remaining -= $allocated[$category];
        }

        foreach (array_keys($allocated) as $category) {
            if ($remaining <= 0) {
                break;
            }

            $allocated[$category]++;
            $remaining--;
        }

        return $allocated;
    }

    /**
     * @param  Collection<int, Qualification>  $qualifications
     * @return Collection<int, Qualification>
     */
    private function matchingCategory(Collection $qualifications, string $category): Collection
    {
        return $qualifications
            ->filter(fn (Qualification $qualification) => $this->matchesCategory($qualification, $category))
            ->shuffle()
            ->values();
    }

    /**
     * @param  Collection<int, Qualification>  $qualifications
     * @return Collection<int, Qualification>
     */
    private function balancedByQualificationFamily(Collection $qualifications, int $limit): Collection
    {
        if ($limit <= 0 || $qualifications->isEmpty()) {
            return collect();
        }

        $buckets = [
            'degree' => $qualifications
                ->filter(fn (Qualification $qualification) => $this->qualificationFamily($qualification) === 'degree')
                ->shuffle()
                ->values(),
            'diploma' => $qualifications
                ->filter(fn (Qualification $qualification) => $this->qualificationFamily($qualification) === 'diploma')
                ->shuffle()
                ->values(),
            'other' => $qualifications
                ->reject(fn (Qualification $qualification) => in_array($this->qualificationFamily($qualification), ['degree', 'diploma'], true))
                ->shuffle()
                ->values(),
        ];
        $order = random_int(0, 1) === 0
            ? ['degree', 'diploma']
            : ['diploma', 'degree'];
        $selected = collect();

        while ($selected->count() < $limit && ($buckets['degree']->isNotEmpty() || $buckets['diploma']->isNotEmpty())) {
            foreach ($order as $family) {
                if ($selected->count() >= $limit) {
                    break;
                }

                $qualification = $buckets[$family]->shift();

                if ($qualification instanceof Qualification) {
                    $selected->push($qualification);
                }
            }
        }

        if ($selected->count() < $limit) {
            $selected = $selected
                ->merge($buckets['other']->take($limit - $selected->count()))
                ->values();
        }

        return $selected->take($limit)->values();
    }

    /**
     * @param  Collection<int, Qualification>  $selected
     * @param  Collection<int, Qualification>  $preferredQualifications
     * @return Collection<int, Qualification>
     */
    private function ensureDegreeDiplomaMix(Collection $selected, Collection $preferredQualifications, int $limit): Collection
    {
        foreach (['degree', 'diploma'] as $family) {
            if ($selected->contains(fn (Qualification $qualification) => $this->qualificationFamily($qualification) === $family)) {
                continue;
            }

            $candidate = $preferredQualifications
                ->filter(fn (Qualification $qualification) => $this->qualificationFamily($qualification) === $family)
                ->reject(fn (Qualification $qualification) => $this->alreadySelected($selected, $qualification))
                ->shuffle()
                ->first();

            if (! $candidate instanceof Qualification) {
                continue;
            }

            if ($selected->count() < $limit) {
                $selected->push($candidate);
                continue;
            }

            $replaceIndex = $selected->search(fn (Qualification $qualification) => $this->qualificationFamily($qualification) !== $family);

            if ($replaceIndex !== false) {
                $selected->put($replaceIndex, $candidate);
            }
        }

        return $selected->unique('id')->values();
    }

    private function matchesAnyCategory(Qualification $qualification): bool
    {
        foreach (array_keys(self::CATEGORY_KEYWORDS) as $category) {
            if ($this->matchesCategory($qualification, $category)) {
                return true;
            }
        }

        return false;
    }

    private function matchesCategory(Qualification $qualification, string $category): bool
    {
        $haystack = $this->searchableText($qualification);

        foreach (self::CATEGORY_KEYWORDS[$category] ?? [] as $keyword) {
            if ($this->containsKeyword($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function alreadySelected(Collection $selected, Qualification $qualification): bool
    {
        return $selected->contains(fn (Qualification $selectedQualification) => (int) $selectedQualification->id === (int) $qualification->id);
    }

    private function qualificationFamily(Qualification $qualification): string
    {
        $type = $this->normalise(implode(' ', [
            (string) $qualification->qualificationType?->name,
            (string) $qualification->qualificationType?->abbreviation,
            (string) $qualification->name,
        ]));

        if (str_contains($type, 'diploma') || str_contains($type, 'ndip') || str_contains($type, 'advdip')) {
            return 'diploma';
        }

        if (
            str_contains($type, 'bachelor')
            || str_contains($type, 'degree')
            || str_contains($type, 'bsc')
            || str_contains($type, 'ba ')
            || str_contains($type, 'bcom')
            || str_contains($type, 'beng')
            || str_contains($type, 'llb')
            || str_contains($type, 'mbchb')
        ) {
            return 'degree';
        }

        return 'other';
    }

    private function containsKeyword(string $haystack, string $keyword): bool
    {
        $keyword = $this->normalise($keyword);

        if (strlen($keyword) <= 4 && ! str_contains($keyword, ' ')) {
            return preg_match('/(^|[^a-z0-9])'.preg_quote($keyword, '/').'($|[^a-z0-9])/', $haystack) === 1;
        }

        return str_contains($haystack, $keyword);
    }

    private function searchableText(Qualification $qualification): string
    {
        return $this->normalise(implode(' ', [
            (string) $qualification->name,
            (string) $qualification->abbreviation,
            (string) $qualification->qualificationType?->name,
            (string) $qualification->qualificationType?->abbreviation,
        ]));
    }

    private function normalise(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }
}
