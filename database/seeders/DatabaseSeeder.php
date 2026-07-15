<?php

namespace Database\Seeders;

use Database\Seeders\LifeScience\Papers\LifeSciencePaperSeeder;
use Database\Seeders\LifeScience\Questions\LifeScienceQuestionSeeder;
use Database\Seeders\LifeScience\Topics\LifeScienceTopicSeeder;
use Database\Seeders\Universities\CPUT\RequirementSeeder as CputRequirementSeeder;
use Database\Seeders\Universities\CUT\RequirementSeeder as CutRequirementSeeder;
use Database\Seeders\Universities\DUT\RequirementSeeder as DutRequirementSeeder;
use Database\Seeders\Universities\NMU\RequirementSeeder as NmuRequirementSeeder;
use Database\Seeders\Universities\NWU\RequirementSeeder as NwuRequirementSeeder;
use Database\Seeders\Universities\RU\RequirementSeeder as RuRequirementSeeder;
use Database\Seeders\Universities\SMU\RequirementSeeder as SmuRequirementSeeder;
use Database\Seeders\Universities\SPU\RequirementSeeder as SpuRequirementSeeder;
use Database\Seeders\Universities\SU\RequirementSeeder as SuRequirementSeeder;
use Database\Seeders\Universities\TUT\RequirementSeeder as TutRequirementSeeder;
use Database\Seeders\Universities\UCT\RequirementSeeder as UctRequirementSeeder;
use Database\Seeders\Universities\UFH\RequirementSeeder as UfhRequirementSeeder;
use Database\Seeders\Universities\UJ\RequirementSeeder as UjRequirementSeeder;
use Database\Seeders\Universities\UFS\RequirementSeeder as UfsRequirementSeeder;
use Database\Seeders\Universities\UL\RequirementSeeder as UlRequirementSeeder;
use Database\Seeders\Universities\UNIZULU\RequirementSeeder as UnizuluRequirementSeeder;
use Database\Seeders\Universities\UNIVEN\RequirementSeeder as UnivenRequirementSeeder;
use Database\Seeders\Universities\UMP\RequirementSeeder as UmpRequirementSeeder;
use Database\Seeders\Universities\UKZN\RequirementSeeder as UkznRequirementSeeder;
use Database\Seeders\Universities\UP\RequirementSeeder as UpRequirementSeeder;
use Database\Seeders\Universities\UWC\RequirementSeeder as UwcRequirementSeeder;
use Database\Seeders\Universities\VC\RequirementSeeder as VcRequirementSeeder;
use Database\Seeders\Universities\VUT\RequirementSeeder as VutRequirementSeeder;
use Database\Seeders\Universities\WITS\RequirementSeeder as WitsRequirementSeeder;
use Database\Seeders\Universities\WSU\RequirementSeeder as WsuRequirementSeeder;
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
            UpRequirementSeeder::class,
            TutRequirementSeeder::class,
            UfsRequirementSeeder::class,
            NmuRequirementSeeder::class,
            NwuRequirementSeeder::class,
            UkznRequirementSeeder::class,
            UlRequirementSeeder::class,
            UnizuluRequirementSeeder::class,
            UnivenRequirementSeeder::class,
            UmpRequirementSeeder::class,
            UjRequirementSeeder::class,
            CputRequirementSeeder::class,
            CutRequirementSeeder::class,
            DutRequirementSeeder::class,
            UwcRequirementSeeder::class,
            VcRequirementSeeder::class,
            VutRequirementSeeder::class,
            WsuRequirementSeeder::class,
            RuRequirementSeeder::class,
            SmuRequirementSeeder::class,
            SpuRequirementSeeder::class,
            SuRequirementSeeder::class,
            WitsRequirementSeeder::class,
            UctRequirementSeeder::class,
            UfhRequirementSeeder::class,
            UniversityLogoSeeder::class,
            BursarySeeder::class,
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
