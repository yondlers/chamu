<?php

namespace Database\Seeders;

use App\Support\CareerUpsert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CareerSeeder extends Seeder
{
    public function run(): void
    {
        $upsert = new CareerUpsert;
        $files = collect(File::glob(database_path('seeders/Careers/*.json')))->sort()->values();

        if ($files->isEmpty()) {
            $this->command?->warn('No career JSON files found in database/seeders/Careers.');

            return;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $payload = json_decode(File::get($file), true, 512, JSON_THROW_ON_ERROR);

            foreach (($payload['careers'] ?? []) as $careerData) {
                $name = trim((string) ($careerData['name'] ?? ''));

                if ($name === '') {
                    $skipped++;

                    continue;
                }

                $result = $upsert->upsert($name, [
                    'salary_expectation' => $careerData['salary_expectation'] ?? null,
                    'description' => $careerData['description'] ?? null,
                    'source_url' => $careerData['source_url'] ?? null,
                    'is_active' => $careerData['is_active'] ?? true,
                ]);

                if ($result === null) {
                    $skipped++;

                    continue;
                }

                if ($result['was_created']) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            $this->command?->info('Seeded careers from '.basename($file));
        }

        $this->command?->info("Careers upserted — created: {$created}, updated: {$updated}, skipped: {$skipped}");
    }
}
