<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sync terms so Grade 12 uses Term 1–3 + NSC, other grades use Term 1–4.
     */
    public function up(): void
    {
        if (! Schema::hasTable('terms') || ! Schema::hasTable('grades') || ! Schema::hasTable('curriculums')) {
            return;
        }

        $standardTerms = [
            ['name' => 'Term 1', 'from_date' => '2000-01-15', 'to_date' => '2000-03-31'],
            ['name' => 'Term 2', 'from_date' => '2000-04-01', 'to_date' => '2000-06-30'],
            ['name' => 'Term 3', 'from_date' => '2000-07-01', 'to_date' => '2000-09-30'],
            ['name' => 'Term 4', 'from_date' => '2000-10-01', 'to_date' => '2000-12-15'],
        ];

        $grade12Terms = [
            ['name' => 'Term 1', 'from_date' => '2000-01-15', 'to_date' => '2000-03-31'],
            ['name' => 'Term 2', 'from_date' => '2000-04-01', 'to_date' => '2000-06-30'],
            ['name' => 'Term 3', 'from_date' => '2000-07-01', 'to_date' => '2000-09-30'],
            ['name' => 'NSC', 'from_date' => '2000-10-01', 'to_date' => '2000-12-15'],
        ];

        $curriculums = DB::table('curriculums')
            ->whereIn('abbreviation', ['CAPS', 'IEB'])
            ->get(['id']);

        foreach ($curriculums as $curriculum) {
            $grades = DB::table('grades')
                ->where('curriculum_id', $curriculum->id)
                ->get(['id', 'name']);

            foreach ($grades as $grade) {
                $terms = $grade->name === 'Grade 12' ? $grade12Terms : $standardTerms;
                $allowedNames = collect($terms)->pluck('name')->all();

                if ($grade->name === 'Grade 12') {
                    $term4 = DB::table('terms')
                        ->where('curriculum_id', $curriculum->id)
                        ->where('grade_id', $grade->id)
                        ->where('name', 'Term 4')
                        ->first(['id']);

                    $nsc = DB::table('terms')
                        ->where('curriculum_id', $curriculum->id)
                        ->where('grade_id', $grade->id)
                        ->where('name', 'NSC')
                        ->first(['id']);

                    if ($term4 !== null && $nsc === null) {
                        DB::table('terms')
                            ->where('id', $term4->id)
                            ->update([
                                'name' => 'NSC',
                                'updated_at' => now(),
                            ]);
                    }
                }

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

                $obsoleteQuery = DB::table('terms')
                    ->where('curriculum_id', $curriculum->id)
                    ->where('grade_id', $grade->id)
                    ->whereNotIn('name', $allowedNames);

                if (Schema::hasTable('user_subject_results')) {
                    $obsoleteQuery->whereNotIn('id', function ($query) {
                        $query->select('term_id')
                            ->from('user_subject_results')
                            ->whereNotNull('term_id');
                    });
                }

                $obsoleteQuery->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank: term naming is product data, not reversible schema.
    }
};
