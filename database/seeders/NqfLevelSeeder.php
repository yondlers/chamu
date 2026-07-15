<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NqfLevelSeeder extends Seeder
{
    /**
     * Seed the South African NQF level lookup.
     */
    public function run(): void
    {
        $levels = [
            [1, 'General Education Certificate', 'High School & Vocational', 'Grade 9 or equivalent'],
            [2, 'Grade 10 / Basic Vocational Certificate', 'High School & Vocational', 'Grade 10 / Basic Vocational Certificate'],
            [3, 'Grade 11 / Intermediate Vocational Certificate', 'High School & Vocational', 'Grade 11 / Intermediate Vocational Certificate'],
            [4, 'National Senior Certificate / NCV Level 4', 'High School & Vocational', 'Matric / National Certificate Vocational Level 4'],
            [5, 'Higher Certificate', 'College & Undergraduate', 'Higher Certificate'],
            [6, 'National Diploma / Advanced Certificate', 'College & Undergraduate', 'National Diploma / Advanced Certificate'],
            [7, 'Bachelor\'s Degree / Advanced Diploma', 'College & Undergraduate', 'Bachelor\'s Degree / Advanced Diploma'],
            [8, 'Honours Degree / Postgraduate Diploma', 'Postgraduate', 'Honours Degree / Postgraduate Diploma'],
            [9, 'Master\'s Degree', 'Postgraduate', 'Master\'s Degree'],
            [10, 'Doctoral Degree', 'Postgraduate', 'Doctoral Degree / PhD'],
        ];

        foreach ($levels as [$level, $name, $category, $description]) {
            DB::table('nqf_levels')->updateOrInsert(
                ['level' => $level],
                [
                    'name' => $name,
                    'category' => $category,
                    'description' => $description,
                    'sort_order' => $level,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
