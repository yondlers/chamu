<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaperSeeder extends Seeder
{
    /**
     * Seed papers.
     */
    public function run(): void
    {
        foreach ([1, 2] as $number) {
            DB::table('papers')->updateOrInsert(
                ['number' => $number],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
