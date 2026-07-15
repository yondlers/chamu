<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TermSeeder extends Seeder
{
    /**
     * Seed terms for live curricula.
     */
    public function run(): void
    {
        $terms = [
            [
                'name' => 'Term 1',
                'from_date' => '2000-01-15',
                'to_date' => '2000-03-31',
            ],
            [
                'name' => 'Term 2',
                'from_date' => '2000-04-01',
                'to_date' => '2000-06-30',
            ],
            [
                'name' => 'Term 3',
                'from_date' => '2000-07-01',
                'to_date' => '2000-09-30',
            ],
            [
                'name' => 'Term 4',
                'from_date' => '2000-10-01',
                'to_date' => '2000-12-15',
            ],
            [
                'name' => 'Terms 3/4',
                'from_date' => '2000-07-01',
                'to_date' => '2000-12-15',
            ],
        ];

        $curriculums = DB::table('curriculums')
            ->whereIn('abbreviation', ['CAPS', 'IEB'])
            ->get(['id']);

        foreach ($curriculums as $curriculum) {
            $grades = DB::table('grades')
                ->where('curriculum_id', $curriculum->id)
                ->get(['id']);

            foreach ($grades as $grade) {
                foreach ($terms as $term) {
                    DB::table('terms')->updateOrInsert(
                        [
                            'curriculum_id' => $curriculum->id,
                            'grade_id' => $grade->id,
                            'name' => $term['name'],
                        ],
                        [
                            'from_date' => $term['from_date'],
                            'to_date' => $term['to_date'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    );
                }
            }
        }
    }
}
