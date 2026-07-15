<?php

namespace Database\Seeders\LifeScience\Papers;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LifeSciencePaperSeeder extends Seeder
{
    /**
     * Seed Life Sciences papers for CAPS.
     */
    public function run(): void
    {
        $papers = [
            ['name' => 'Paper 1'],
            ['name' => 'Paper 2'],
        ];

        foreach ($papers as $paper) {
            DB::table('papers')->updateOrInsert(
                ['number' => (int) str_replace('Paper ', '', $paper['name'])],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
