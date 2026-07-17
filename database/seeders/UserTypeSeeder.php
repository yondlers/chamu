<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    /**
     * Seed the user types.
     */
    public function run(): void
    {
        $userTypes = [
            [
                'name' => 'pupil',
                'description' => 'High school learner account for studying, practice, notes, and exams.',
            ],
            [
                'name' => 'student',
                'description' => 'University or college student account for funding and study planning.',
            ],
            [
                'name' => 'teacher',
                'description' => 'Teacher account for supporting students and learning content.',
            ],
            [
                'name' => 'parent',
                'description' => 'Parent account for monitoring linked student progress.',
            ],
            [
                'name' => 'school admin',
                'description' => 'School administrator account for managing school-level users and activity.',
            ],
            [
                'name' => 'super admin',
                'description' => 'Platform administrator account with full system access.',
            ],
        ];

        foreach ($userTypes as $userType) {
            DB::table('user_types')->updateOrInsert(
                ['name' => $userType['name']],
                [
                    'description' => $userType['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
