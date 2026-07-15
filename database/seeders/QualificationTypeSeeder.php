<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QualificationTypeSeeder extends Seeder
{
    /**
     * Seed qualification type lookup values used by imports.
     */
    public function run(): void
    {
        $types = [
            ['General Education Certificate', 'GEC', 1],
            ['Basic Vocational Certificate', null, 2],
            ['Intermediate Vocational Certificate', null, 3],
            ['National Senior Certificate', 'NSC', 4],
            ['National Certificate Vocational', 'NCV', 4],
            ['Higher Certificate', 'HC', 5],
            ['Advanced Certificate', 'AdvCert', 6],
            ['National Diploma', 'NDip', 6],
            ['Diploma', 'Dip', 6],
            ['Advanced Diploma', 'AdvDip', 7],
            ['Bachelor', null, 7],
            ['Bachelor Degree', null, 7],
            ['Extended Bachelor Degree', null, 7],
            ['Bachelor\'s Degree', null, 7],
            ['Honours Degree', null, 8],
            ['Postgraduate Diploma', 'PGDip', 8],
            ['Master\'s Degree', null, 9],
            ['Doctoral Degree', 'PhD', 10],
        ];

        foreach ($types as $index => [$name, $abbreviation, $nqfLevel]) {
            DB::table('qualification_types')->updateOrInsert(
                ['name' => $name],
                [
                    'nqf_level_id' => DB::table('nqf_levels')->where('level', $nqfLevel)->value('id'),
                    'abbreviation' => $abbreviation,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
