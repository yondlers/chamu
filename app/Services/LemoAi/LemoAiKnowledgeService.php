<?php

namespace App\Services\LemoAi;

use App\Models\Bursary;
use App\Models\Qualification;
use App\Models\University;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LemoAiKnowledgeService
{
    public function buildContext(string $query): string
    {
        $tokens = $this->tokens($query);

        $universities = $this->matchingUniversities($tokens);
        $bursaries = $this->matchingBursaries($tokens, $query);
        $qualifications = $this->matchingQualifications($tokens, $query, $universities);

        $sections = [
            $this->universityDirectory(),
            $this->formatUniversities($universities),
            $this->formatQualifications($qualifications),
            $this->formatBursaries($bursaries),
        ];

        return collect($sections)
            ->filter()
            ->implode("\n\n");
    }

    /**
     * @return list<string>
     */
    private function tokens(string $query): array
    {
        return Str::of($query)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->explode(' ')
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => strlen($token) >= 3)
            ->unique()
            ->values()
            ->all();
    }

    private function universityDirectory(): string
    {
        $lines = University::query()
            ->orderBy('name')
            ->get(['name', 'abbreviation', 'website'])
            ->map(function (University $university) {
                $label = $university->abbreviation
                    ? "{$university->name} ({$university->abbreviation})"
                    : $university->name;

                return '- '.$label.($university->website ? " | {$university->website}" : '');
            })
            ->all();

        return "UNIVERSITY DIRECTORY ON CHAMU:\n".implode("\n", $lines);
    }

    /**
     * @param  list<string>  $tokens
     * @return Collection<int, University>
     */
    private function matchingUniversities(array $tokens): Collection
    {
        if ($tokens === []) {
            return University::query()->orderBy('name')->limit(8)->get();
        }

        return University::query()
            ->where(function ($query) use ($tokens) {
                foreach ($tokens as $token) {
                    $query->orWhere('name', 'like', "%{$token}%")
                        ->orWhere('abbreviation', 'like', "%{$token}%");
                }
            })
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    /**
     * @param  list<string>  $tokens
     * @return Collection<int, Bursary>
     */
    private function matchingBursaries(array $tokens, string $query): Collection
    {
        $builder = Bursary::query()
            ->with('company:id,name')
            ->where('is_active', true)
            ->latest('closing_date');

        $asksAboutFunding = Str::contains(Str::lower($query), ['bursar', 'fund', 'nsfas', 'scholarship', 'money', 'sponsor']);

        if ($tokens !== []) {
            $builder->where(function ($queryBuilder) use ($tokens) {
                foreach ($tokens as $token) {
                    $queryBuilder->orWhere('title', 'like', "%{$token}%")
                        ->orWhere('summary', 'like', "%{$token}%")
                        ->orWhere('category', 'like', "%{$token}%")
                        ->orWhere('fields_covered', 'like', "%{$token}%");
                }
            });
        } elseif (! $asksAboutFunding) {
            return collect();
        }

        return $builder->limit(12)->get();
    }

    /**
     * @param  list<string>  $tokens
     * @param  Collection<int, University>  $universities
     * @return Collection<int, Qualification>
     */
    private function matchingQualifications(array $tokens, string $query, Collection $universities): Collection
    {
        $builder = Qualification::query()
            ->with(['university:id,name,abbreviation', 'faculty:id,name'])
            ->orderByDesc('aps_required');

        $universityIds = $universities->pluck('id')->filter()->all();

        if ($tokens !== []) {
            $builder->where(function ($queryBuilder) use ($tokens, $universityIds) {
                foreach ($tokens as $token) {
                    $queryBuilder->orWhere('name', 'like', "%{$token}%")
                        ->orWhere('abbreviation', 'like', "%{$token}%")
                        ->orWhere('notes', 'like', "%{$token}%");
                }

                if ($universityIds !== []) {
                    $queryBuilder->orWhereIn('university_id', $universityIds);
                }
            });
        } elseif ($universityIds !== []) {
            $builder->whereIn('university_id', $universityIds);
        } elseif (Str::contains(Str::lower($query), ['aps', 'course', 'degree', 'diploma', 'programme', 'program'])) {
            $builder->whereNotNull('aps_required');
        } else {
            return collect();
        }

        return $builder->limit(15)->get();
    }

    /**
     * @param  Collection<int, University>  $universities
     */
    private function formatUniversities(Collection $universities): string
    {
        if ($universities->isEmpty()) {
            return '';
        }

        $lines = $universities->map(function (University $university) {
            return sprintf(
                '- %s | abbr: %s | website: %s',
                $university->name,
                $university->abbreviation ?: 'n/a',
                $university->website ?: 'n/a',
            );
        })->all();

        return "RELEVANT UNIVERSITIES:\n".implode("\n", $lines);
    }

    /**
     * @param  Collection<int, Qualification>  $qualifications
     */
    private function formatQualifications(Collection $qualifications): string
    {
        if ($qualifications->isEmpty()) {
            return '';
        }

        $lines = $qualifications->map(function (Qualification $qualification) {
            $university = $qualification->university;
            $faculty = $qualification->faculty;

            return sprintf(
                '- %s at %s%s | APS: %s | notes: %s',
                $qualification->name,
                $university?->abbreviation ?: ($university?->name ?: 'Unknown'),
                $faculty ? " / {$faculty->name}" : '',
                $qualification->aps_required !== null ? (string) $qualification->aps_required : 'n/a',
                Str::limit((string) ($qualification->notes ?: 'n/a'), 180),
            );
        })->all();

        return "RELEVANT QUALIFICATIONS / PROGRAMMES:\n".implode("\n", $lines);
    }

    /**
     * @param  Collection<int, Bursary>  $bursaries
     */
    private function formatBursaries(Collection $bursaries): string
    {
        if ($bursaries->isEmpty()) {
            return '';
        }

        $lines = $bursaries->map(function (Bursary $bursary) {
            $eligibility = is_array($bursary->eligibility_requirements)
                ? implode('; ', array_slice($bursary->eligibility_requirements, 0, 4))
                : (string) $bursary->eligibility_requirements;

            return sprintf(
                '- %s (%s) | company: %s | closes: %s | coverage: %s | eligibility: %s | apply: %s',
                $bursary->title,
                $bursary->category ?: 'general',
                $bursary->company?->name ?: 'n/a',
                $bursary->closing_date_label
                    ?: ($bursary->closing_date?->toDateString() ?: 'n/a'),
                Str::limit((string) ($bursary->coverage_value ?: 'n/a'), 120),
                Str::limit($eligibility ?: 'n/a', 180),
                $bursary->apply_url ?: ($bursary->source_url ?: 'n/a'),
            );
        })->all();

        return "RELEVANT BURSARIES / FUNDING:\n".implode("\n", $lines);
    }
}
