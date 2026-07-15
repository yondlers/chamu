<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeSeeder extends Seeder
{
    /**
     * Seed grades for CAPS and IEB.
     */
    public function run(): void
    {
        $grades = [
            ['Grade R', null],
            ['Grade 1', null],
            ['Grade 2', null],
            ['Grade 3', null],
            ['Grade 4', null],
            ['Grade 5', null],
            ['Grade 6', null],
            ['Grade 7', null],
            ['Grade 8', null],
            ['Grade 9', 1],
            ['Grade 10', 2],
            ['Grade 11', 3],
            ['Grade 12', 4],
        ];

        $curriculums = DB::table('curriculums')
            ->whereIn('abbreviation', ['CAPS', 'IEB'])
            ->get(['id']);

        foreach ($curriculums as $curriculum) {
            foreach ($grades as $index => [$grade, $nqfLevel]) {
                DB::table('grades')->updateOrInsert(
                    [
                        'curriculum_id' => $curriculum->id,
                        'name' => $grade,
                    ],
                    [
                        'nqf_level_id' => $nqfLevel === null
                            ? null
                            : DB::table('nqf_levels')->where('level', $nqfLevel)->value('id'),
                        'sort_order' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }
}
