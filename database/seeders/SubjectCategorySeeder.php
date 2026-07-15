<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectCategorySeeder extends Seeder
{
    /**
     * Seed the subject categories.
     */
    public function run(): void
    {
        $categories = [
            'Languages',
            'Mathematics',
            'Sciences',
            'Agriculture',
            'Business & Commerce',
            'Technology',
            'Humanities',
            'Creative Arts',
            'Services',
            'Engineering',
            'Life Orientation',
        ];

        foreach ($categories as $index => $name) {
            DB::table('subject_categories')->updateOrInsert(
                ['name' => $name],
                [
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
