<?php

namespace Database\Seeders\Universities\VC;

use Database\Seeders\Universities\UniversityRequirementSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RequirementSeeder extends UniversityRequirementSeeder
{
    public function run(): void
    {
        $this->normaliseCreativeDevelopmentLegacyRecord();

        parent::run();

        $this->pruneStaleQualifications();
    }

    protected function abbreviation(): string
    {
        return 'VC';
    }

    protected function universityName(): string
    {
        return 'Emeris';
    }

    protected function preferredUniversitySlug(): ?string
    {
        return 'the-iies-varsity-college';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/VC/Requirements/*.json';
    }

    protected function admissionRuleCode(): string
    {
        return 'nsc_pass_type';
    }

    protected function website(): ?string
    {
        return 'https://www.emeris.ac.za/';
    }

    private function normaliseCreativeDevelopmentLegacyRecord(): void
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

        $legacy = DB::table('qualifications')
            ->where('university_id', $universityId)
            ->where(function ($query) {
                $query
                    ->where('name', 'Higher Certificate in Art and Design')
                    ->when(Schema::hasColumn('qualifications', 'slug'), fn ($query) => $query->orWhere('slug', 'higher-certificate-in-art-and-design'));
            })
            ->orderBy('id')
            ->first();

        $current = DB::table('qualifications')
            ->where('university_id', $universityId)
            ->where('name', 'Higher Certificate in Creative Development')
            ->orderBy('id')
            ->first();

        if ($legacy === null && $current === null) {
            return;
        }

        $canonicalId = (int) ($legacy?->id ?? $current->id);

        if ($current !== null && (int) $current->id !== $canonicalId) {
            DB::table('qualification_subject_requirements')
                ->where('qualification_id', $current->id)
                ->delete();
            DB::table('qualification_admission_score_variants')
                ->where('qualification_id', $current->id)
                ->delete();
            DB::table('university_admission_rules')
                ->where('qualification_id', $current->id)
                ->delete();
            DB::table('qualifications')
                ->where('id', $current->id)
                ->delete();
        }

        $values = [
            'name' => 'Higher Certificate in Creative Development',
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('qualifications', 'slug')) {
            $values['slug'] = 'higher-certificate-in-art-and-design';
        }

        DB::table('qualifications')
            ->where('id', $canonicalId)
            ->update($values);
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
