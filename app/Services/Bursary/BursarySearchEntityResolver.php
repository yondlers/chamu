<?php

namespace App\Services\Bursary;

use Illuminate\Support\Collection;

class BursarySearchEntityResolver
{
    /**
     * @param  array{
     *     categories?: list<string>,
     *     companies?: list<string>,
     *     query?: string|null
     * }  $interpretation
     * @param  Collection<int, string>  $categories
     * @param  Collection<int, object{id:int|string, name:string}>  $companies
     * @return array{
     *     categories: list<string>,
     *     company_ids: list<int>,
     *     query: string|null
     * }
     */
    public function resolve(array $interpretation, Collection $categories, Collection $companies): array
    {
        $resolvedCategories = [];
        foreach ($interpretation['categories'] ?? [] as $name) {
            $category = $this->resolveCategory((string) $name, $categories);
            if ($category !== null) {
                $resolvedCategories[] = $category;
            }
        }

        $companyIds = [];
        foreach ($interpretation['companies'] ?? [] as $name) {
            $id = $this->resolveCompanyId((string) $name, $companies);
            if ($id !== null) {
                $companyIds[] = $id;
            }
        }

        $query = $interpretation['query'] ?? null;
        if (! is_string($query) || trim($query) === '') {
            $query = null;
        } else {
            $query = trim($query);
        }

        return [
            'categories' => array_values(array_unique($resolvedCategories)),
            'company_ids' => array_values(array_unique($companyIds)),
            'query' => $query,
        ];
    }

    /**
     * @param  Collection<int, string>  $categories
     * @param  Collection<int, object{id:int|string, name:string}>  $companies
     */
    public function matchesIndexedOption(string $search, Collection $categories, Collection $companies): bool
    {
        $needle = $this->normalise($search);

        if ($needle === '') {
            return false;
        }

        foreach ($categories as $category) {
            if ($this->normalise((string) $category) === $needle) {
                return true;
            }
        }

        foreach ($companies as $company) {
            if ($this->normalise((string) ($company->name ?? '')) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, string>  $categories
     */
    private function resolveCategory(string $name, Collection $categories): ?string
    {
        $needle = $this->normalise($name);

        if ($needle === '') {
            return null;
        }

        $exact = $categories->first(
            fn ($category) => $this->normalise((string) $category) === $needle
        );

        if ($exact !== null) {
            return (string) $exact;
        }

        $partial = $categories->filter(function ($category) use ($needle) {
            $categoryNorm = $this->normalise((string) $category);

            return $categoryNorm !== ''
                && (str_contains($categoryNorm, $needle) || str_contains($needle, $categoryNorm));
        });

        return $partial->count() === 1 ? (string) $partial->first() : null;
    }

    /**
     * @param  Collection<int, object{id:int|string, name:string}>  $companies
     */
    private function resolveCompanyId(string $name, Collection $companies): ?int
    {
        $needle = $this->normalise($name);

        if ($needle === '') {
            return null;
        }

        $exact = $companies->first(
            fn ($company) => $this->normalise((string) ($company->name ?? '')) === $needle
        );

        if ($exact !== null) {
            return (int) $exact->id;
        }

        $partial = $companies->filter(function ($company) use ($needle) {
            $companyNorm = $this->normalise((string) ($company->name ?? ''));

            return $companyNorm !== ''
                && (str_contains($companyNorm, $needle) || str_contains($needle, $companyNorm));
        });

        return $partial->count() === 1 ? (int) $partial->first()->id : null;
    }

    private function normalise(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
