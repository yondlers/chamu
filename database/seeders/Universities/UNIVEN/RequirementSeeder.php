<?php

namespace Database\Seeders\Universities\UNIVEN;

use Database\Seeders\Universities\UniversityRequirementSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RequirementSeeder extends UniversityRequirementSeeder
{
    public function run(): void
    {
        parent::run();

        $this->pruneStaleQualifications();
    }

    protected function abbreviation(): string
    {
        return 'UNIVEN';
    }

    protected function universityName(): string
    {
        return 'University of Venda';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UNIVEN/Requirements/*.json';
    }

    protected function website(): ?string
    {
        return 'https://www.univen.ac.za';
    }

    private function pruneStaleQualifications(): void
    {
        if (! Schema::hasTable('universities') || ! Schema::hasTable('qualifications')) {
            return;
        }

        $universityId = DB::table('universities')
            ->where('abbreviation', $this->abbreviation())
            ->value('id');

        if ($universityId === null) {
            return;
        }

        [$allowedSlugs, $allowedNames] = $this->currentRequirementIdentifiers();

        if ($allowedSlugs === [] && $allowedNames === []) {
            return;
        }

        $staleIds = DB::table('qualifications')
            ->where('university_id', $universityId)
            ->where(function ($query) use ($allowedNames, $allowedSlugs): void {
                if (Schema::hasColumn('qualifications', 'slug') && $allowedSlugs !== []) {
                    $query->whereNotIn('slug', $allowedSlugs)
                        ->orWhereNull('slug');

                    return;
                }

                $query->whereNotIn('name', $allowedNames);
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($staleIds === []) {
            return;
        }

        DB::transaction(function () use ($staleIds): void {
            DB::table('qualification_subject_requirements')
                ->whereIn('qualification_id', $staleIds)
                ->delete();
            DB::table('qualification_admission_score_variants')
                ->whereIn('qualification_id', $staleIds)
                ->delete();
            DB::table('university_admission_rules')
                ->whereIn('qualification_id', $staleIds)
                ->delete();
            DB::table('qualifications')
                ->whereIn('id', $staleIds)
                ->delete();
        });
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function currentRequirementIdentifiers(): array
    {
        $slugs = [];
        $names = [];
        $files = glob(database_path($this->requirementsPath())) ?: [];

        foreach ($files as $file) {
            $facultyData = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

            foreach (($facultyData['qualifications'] ?? []) as $qualificationData) {
                $name = (string) ($qualificationData['name'] ?? '');

                if ($name === '') {
                    continue;
                }

                $names[] = $name;
                $slugs[] = Str::slug((string) ($qualificationData['slug'] ?? $name));
            }
        }

        return [
            array_values(array_unique(array_filter($slugs))),
            array_values(array_unique(array_filter($names))),
        ];
    }
}
