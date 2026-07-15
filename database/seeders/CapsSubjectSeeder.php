<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CapsSubjectSeeder extends Seeder
{
    /**
     * Seed the NSC/CAPS subjects.
     */
    public function run(): void
    {
        $curriculumId = DB::table('curriculums')
            ->where('abbreviation', 'CAPS')
            ->value('id');

        if ($curriculumId === null) {
            return;
        }

        SubjectSeedData::seedSubjects($curriculumId, SubjectSeedData::capsSubjectsByCategory());
    }
}
