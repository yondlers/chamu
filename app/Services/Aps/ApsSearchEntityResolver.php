<?php

namespace App\Services\Aps;

use App\Models\Faculty;
use App\Models\Qualification;
use App\Models\QualificationType;
use App\Models\University;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ApsSearchEntityResolver
{
    /**
     * Resolve AI filter strings against Chamu database records.
     *
     * @param  array{
     *     universities?: list<string>,
     *     faculties?: list<string>,
     *     qualifications?: list<string>,
     *     aps?: int|null,
     *     subjects?: array<string, int>,
     *     query?: string|null
     * }  $interpretation
     * @param  Collection<int, University>  $universities
     * @param  Collection<int, Faculty>  $faculties
     * @param  Collection<int, QualificationType>  $qualificationTypes
     * @return array{
     *     university_ids: list<int>,
     *     faculty_ids: list<int>,
     *     qualification_type_ids: list<int>,
     *     aps: int|null,
     *     subjects: array<string, int>,
     *     query: string|null
     * }
     */
    public function resolve(
        array $interpretation,
        Collection $universities,
        Collection $faculties,
        Collection $qualificationTypes,
    ): array {
        $universityIds = [];
        foreach ($interpretation['universities'] ?? [] as $name) {
            $id = $this->resolveUniversityId((string) $name, $universities);
            if ($id !== null) {
                $universityIds[] = $id;
            }
        }
        $universityIds = array_values(array_unique($universityIds));

        $facultyIds = [];
        foreach ($interpretation['faculties'] ?? [] as $name) {
            $id = $this->resolveFacultyId((string) $name, $faculties, $universityIds);
            if ($id !== null) {
                $facultyIds[] = $id;
            }
        }
        $facultyIds = array_values(array_unique($facultyIds));

        $qualificationTypeIds = [];
        $unresolvedQualificationTerms = [];
        foreach ($interpretation['qualifications'] ?? [] as $name) {
            $typeId = $this->resolveQualificationTypeId((string) $name, $qualificationTypes);
            if ($typeId !== null) {
                $qualificationTypeIds[] = $typeId;
                continue;
            }

            if ($this->qualificationNameExists((string) $name)) {
                $unresolvedQualificationTerms[] = trim((string) $name);
            }
        }
        $qualificationTypeIds = array_values(array_unique($qualificationTypeIds));

        $query = $interpretation['query'] ?? null;
        if (! is_string($query) || trim($query) === '') {
            $query = null;
        } else {
            $query = trim($query);
        }

        if ($unresolvedQualificationTerms !== []) {
            $extra = implode(' ', $unresolvedQualificationTerms);
            $query = $query === null ? $extra : trim($query.' '.$extra);
        }

        $aps = $interpretation['aps'] ?? null;
        if (! is_int($aps) && ! (is_string($aps) && is_numeric($aps))) {
            $aps = null;
        } else {
            $aps = (int) $aps;
            if ($aps < 0 || $aps > 100) {
                $aps = null;
            }
        }

        $subjects = [];
        if (is_array($interpretation['subjects'] ?? null)) {
            foreach ($interpretation['subjects'] as $key => $value) {
                if (! is_string($key) || (! is_int($value) && ! is_float($value) && ! (is_string($value) && is_numeric($value)))) {
                    continue;
                }

                $mark = (int) $value;
                if ($mark < 0 || $mark > 100) {
                    continue;
                }

                $subjects[Str::snake(strtolower($key))] = $mark;
            }
        }

        return [
            'university_ids' => $universityIds,
            'faculty_ids' => $facultyIds,
            'qualification_type_ids' => $qualificationTypeIds,
            'aps' => $aps,
            'subjects' => $subjects,
            'query' => $query,
        ];
    }

    /**
     * @param  Collection<int, University>  $universities
     */
    public function matchesIndexedOption(
        string $search,
        Collection $universities,
        Collection $faculties,
        Collection $qualificationTypes,
    ): bool {
        $needle = $this->normalise($search);

        if ($needle === '') {
            return false;
        }

        foreach ($universities as $university) {
            if (
                $this->normalise((string) $university->name) === $needle
                || $this->normalise((string) $university->abbreviation) === $needle
            ) {
                return true;
            }
        }

        foreach ($faculties as $faculty) {
            if ($this->normalise((string) $faculty->name) === $needle) {
                return true;
            }
        }

        foreach ($qualificationTypes as $type) {
            if (
                $this->normalise((string) $type->name) === $needle
                || $this->normalise((string) ($type->abbreviation ?? '')) === $needle
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, University>  $universities
     */
    private function resolveUniversityId(string $name, Collection $universities): ?int
    {
        $needle = $this->normalise($name);

        if ($needle === '') {
            return null;
        }

        $exact = $universities->first(function (University $university) use ($needle) {
            return $this->normalise((string) $university->abbreviation) === $needle
                || $this->normalise((string) $university->name) === $needle;
        });

        if ($exact !== null) {
            return (int) $exact->id;
        }

        $partial = $universities->filter(function (University $university) use ($needle) {
            $nameNorm = $this->normalise((string) $university->name);
            $abbrNorm = $this->normalise((string) $university->abbreviation);

            return ($abbrNorm !== '' && ($abbrNorm === $needle || str_starts_with($needle, $abbrNorm)))
                || ($nameNorm !== '' && (str_contains($nameNorm, $needle) || str_contains($needle, $nameNorm)));
        });

        if ($partial->count() === 1) {
            return (int) $partial->first()->id;
        }

        return null;
    }

    /**
     * @param  Collection<int, Faculty>  $faculties
     * @param  list<int>  $universityIds
     */
    private function resolveFacultyId(string $name, Collection $faculties, array $universityIds): ?int
    {
        $needle = $this->normalise($name);

        if ($needle === '') {
            return null;
        }

        $candidates = $faculties->filter(function (Faculty $faculty) use ($needle, $universityIds) {
            if ($this->normalise((string) $faculty->name) !== $needle) {
                return false;
            }

            if ($universityIds === []) {
                return true;
            }

            return in_array((int) $faculty->university_id, $universityIds, true);
        });

        if ($candidates->count() === 1) {
            return (int) $candidates->first()->id;
        }

        if ($candidates->count() > 1 && $universityIds !== []) {
            $scoped = $candidates->filter(
                fn (Faculty $faculty) => in_array((int) $faculty->university_id, $universityIds, true)
            );

            if ($scoped->count() === 1) {
                return (int) $scoped->first()->id;
            }
        }

        // Ambiguous faculty names without a reliable university scope are ignored.
        return null;
    }

    /**
     * @param  Collection<int, QualificationType>  $qualificationTypes
     */
    private function resolveQualificationTypeId(string $name, Collection $qualificationTypes): ?int
    {
        $needle = $this->normalise($name);

        if ($needle === '') {
            return null;
        }

        $exact = $qualificationTypes->first(function (QualificationType $type) use ($needle) {
            return $this->normalise((string) $type->name) === $needle
                || $this->normalise((string) ($type->abbreviation ?? '')) === $needle;
        });

        return $exact !== null ? (int) $exact->id : null;
    }

    private function qualificationNameExists(string $name): bool
    {
        $needle = trim($name);

        if ($needle === '') {
            return false;
        }

        return Qualification::query()
            ->where('name', 'like', $needle)
            ->exists();
    }

    private function normalise(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
