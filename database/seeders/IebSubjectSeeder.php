<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IebSubjectSeeder extends Seeder
{
    /**
     * Seed the IEB subjects.
     */
    public function run(): void
    {
        $curriculumId = DB::table('curriculums')
            ->where('abbreviation', 'IEB')
            ->value('id');

        if ($curriculumId === null) {
            return;
        }

        SubjectSeedData::seedSubjects($curriculumId, SubjectSeedData::iebSubjectsByCategory());
    }
}
