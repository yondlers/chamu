<?php

namespace Database\Seeders;

use Database\Seeders\LifeScience\Papers\LifeSciencePaperSeeder;
use Database\Seeders\LifeScience\Questions\LifeScienceQuestionSeeder;
use Database\Seeders\LifeScience\Topics\LifeScienceTopicSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserTypeSeeder::class);

        DB::table('countries')->updateOrInsert(
            ['name' => 'South Africa'],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $countryId = DB::table('countries')
            ->where('name', 'South Africa')
            ->value('id');

        $this->call(ProvinceSeeder::class);

        DB::table('curriculums')->updateOrInsert(
            ['abbreviation' => 'CAPS'],
            [
                'country_id' => $countryId,
                'name' => 'NSC (National Senior Certificate)',
                'is_live' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $curriculumId = DB::table('curriculums')
            ->where('abbreviation', 'CAPS')
            ->value('id');

        DB::table('curriculums')->updateOrInsert(
            ['abbreviation' => 'IEB'],
            [
                'country_id' => $countryId,
                'name' => 'IEB (Independent Examinations Board)',
                'is_live' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $this->call([
            NqfLevelSeeder::class,
            SubjectCategorySeeder::class,
            GradeSeeder::class,
            QualificationTypeSeeder::class,
            CapsSubjectSeeder::class,
            IebSubjectSeeder::class,
            AdmissionRuleSeeder::class,
            TermSeeder::class,
            PaperSeeder::class,
            LifeSciencePaperSeeder::class,
            LifeScienceTopicSeeder::class,
            LifeScienceQuestionSeeder::class,
            UniversitySeeder::class,
            BursarySeeder::class,
            CareerSeeder::class,
        ]);

        $pupilUserTypeId = DB::table('user_types')
            ->where('name', 'pupil')
            ->value('id');

        $gradeId = DB::table('grades')
            ->where('curriculum_id', $curriculumId)
            ->where('name', 'Grade 10')
            ->value('id');

        DB::table('users')->updateOrInsert(
            ['email' => 'test@example.com'],
            [
                'user_type_id' => $pupilUserTypeId,
                'country_id' => $countryId,
                'curriculum_id' => $curriculumId,
                'grade_id' => $gradeId,
                'name' => 'Test User',
                'first_name' => 'Test',
                'last_name' => 'User',
                'username' => 'testuser',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
