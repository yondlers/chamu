<?php

namespace Database\Seeders\LifeScience\Topics;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LifeScienceTopicSeeder extends Seeder
{
    /**
     * Seed Grade 12 CAPS Life Sciences paper topics.
     */
    public function run(): void
    {
        $curriculumId = DB::table('curriculums')
            ->where('abbreviation', 'CAPS')
            ->value('id');

        if ($curriculumId === null) {
            return;
        }

        $gradeId = DB::table('grades')
            ->where('curriculum_id', $curriculumId)
            ->where('name', 'Grade 12')
            ->value('id');

        if ($gradeId === null) {
            return;
        }

        $subjectId = DB::table('subjects')
            ->where('curriculum_id', $curriculumId)
            ->where('grade_id', $gradeId)
            ->where('name', 'Life Sciences')
            ->value('id');

        if ($subjectId === null) {
            return;
        }

        $topics = [
            [
                'paper' => 'Paper 1',
                'term' => 'Term 1',
                'topic' => 'Meiosis',
                'weighting_percentage' => 7,
                'weighting_marks' => 11,
            ],
            [
                'paper' => 'Paper 1',
                'term' => 'Term 1',
                'topic' => 'Reproduction in Vertebrates',
                'weighting_percentage' => 4,
                'weighting_marks' => 6,
            ],
            [
                'paper' => 'Paper 1',
                'term' => 'Term 1',
                'topic' => 'Human Reproduction',
                'weighting_percentage' => 21,
                'weighting_marks' => 31,
            ],
            [
                'paper' => 'Paper 1',
                'term' => 'Term 2',
                'topic' => 'Responding to the Environment (Humans)',
                'weighting_percentage' => 27,
                'weighting_marks' => 40,
            ],
            [
                'paper' => 'Paper 1',
                'term' => 'Term 3',
                'topic' => 'Human Endocrine System',
                'weighting_percentage' => 10,
                'weighting_marks' => 15,
            ],
            [
                'paper' => 'Paper 1',
                'term' => 'Term 3',
                'topic' => 'Homeostasis in Humans',
                'weighting_percentage' => 7,
                'weighting_marks' => 11,
            ],
            [
                'paper' => 'Paper 1',
                'term' => 'Term 3',
                'topic' => 'Responding to the Environment (Plants)',
                'weighting_percentage' => 7,
                'weighting_marks' => 11,
            ],
            [
                'paper' => 'Paper 1',
                'term' => 'Term 4',
                'topic' => 'Human Impact (Grade 11)',
                'weighting_percentage' => 17,
                'weighting_marks' => 25,
            ],
            [
                'paper' => 'Paper 2',
                'term' => 'Term 1',
                'topic' => 'DNA: Code of Life',
                'weighting_percentage' => 19,
                'weighting_marks' => 27,
            ],
            [
                'paper' => 'Paper 2',
                'term' => 'Term 1',
                'topic' => 'Meiosis',
                'weighting_percentage' => 7,
                'weighting_marks' => 12,
            ],
            [
                'paper' => 'Paper 2',
                'term' => 'Term 2',
                'topic' => 'Genetics and Inheritance',
                'weighting_percentage' => 30,
                'weighting_marks' => 45,
            ],
            [
                'paper' => 'Paper 2',
                'term' => 'Terms 3',
                'topic' => 'Evolution',
                'weighting_percentage' => 44,
                'weighting_marks' => 66,
            ],
        ];

        foreach ($topics as $index => $topic) {
            $paperId = $this->paperId($topic['paper']);
            $termId = $this->termId($curriculumId, $gradeId, $topic['term']);

            if ($paperId === null || $termId === null) {
                continue;
            }

            DB::table('topics')->updateOrInsert(
                [
                    'grade_id' => $gradeId,
                    'term_id' => $termId,
                    'subject_id' => $subjectId,
                    'paper_id' => $paperId,
                    'name' => $topic['topic'],
                ],
                [
                    'sort_order' => $index + 1,
                    'weighting_percentage' => $topic['weighting_percentage'],
                    'weighting_marks' => $topic['weighting_marks'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function paperId(string $paper): ?int
    {
        $number = (int) str_replace('Paper ', '', $paper);

        DB::table('papers')->updateOrInsert(
            ['number' => $number],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return DB::table('papers')
            ->where('number', $number)
            ->value('id');
    }

    private function termId(int $curriculumId, int $gradeId, string $term): ?int
    {
        $dates = match ($term) {
            'Term 1' => ['from_date' => '2000-01-15', 'to_date' => '2000-03-31'],
            'Term 2' => ['from_date' => '2000-04-01', 'to_date' => '2000-06-30'],
            'Term 3' => ['from_date' => '2000-07-01', 'to_date' => '2000-09-30'],
            'Term 4' => ['from_date' => '2000-10-01', 'to_date' => '2000-12-15'],
            default => ['from_date' => null, 'to_date' => null],
        };

        DB::table('terms')->updateOrInsert(
            [
                'curriculum_id' => $curriculumId,
                'grade_id' => $gradeId,
                'name' => $term,
            ],
            [
                'from_date' => $dates['from_date'],
                'to_date' => $dates['to_date'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return DB::table('terms')
            ->where('curriculum_id', $curriculumId)
            ->where('grade_id', $gradeId)
            ->where('name', $term)
            ->value('id');
    }
}
