<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

class SubjectSeedData
{
    /**
     * Get the shared NSC/CAPS subject list.
     *
     * @return array<string, list<string>>
     */
    public static function capsSubjectsByCategory(): array
    {
        return [
            'Languages' => [
                'Afrikaans Home Language',
                'Afrikaans First Additional Language',
                'English Home Language',
                'English First Additional Language',
                'isiNdebele Home Language',
                'isiNdebele First Additional Language',
                'isiXhosa Home Language',
                'isiXhosa First Additional Language',
                'isiZulu Home Language',
                'isiZulu First Additional Language',
                'Sepedi Home Language',
                'Sepedi First Additional Language',
                'Sesotho Home Language',
                'Sesotho First Additional Language',
                'Setswana Home Language',
                'Setswana First Additional Language',
                'SiSwati Home Language',
                'SiSwati First Additional Language',
                'Tshivenda Home Language',
                'Tshivenda First Additional Language',
                'Xitsonga Home Language',
                'Xitsonga First Additional Language',
            ],
            'Mathematics' => [
                'Mathematics',
                'Mathematical Literacy',
                'Technical Mathematics',
            ],
            'Sciences' => [
                'Physical Sciences',
                'Life Sciences',
                'Technical Sciences',
                'Marine Sciences',
            ],
            'Agriculture' => [
                'Agricultural Sciences',
                'Agricultural Technology',
                'Agricultural Management Practices',
            ],
            'Business & Commerce' => [
                'Accounting',
                'Business Studies',
                'Economics',
            ],
            'Technology' => [
                'Computer Applications Technology',
                'Information Technology',
            ],
            'Humanities' => [
                'Geography',
                'History',
                'Religion Studies',
            ],
            'Creative Arts' => [
                'Dance Studies',
                'Design',
                'Dramatic Arts',
                'Music',
                'Visual Arts',
            ],
            'Engineering' => [
                'Civil Technology',
                'Electrical Technology',
                'Mechanical Technology',
                'Engineering Graphics and Design',
            ],
            'Services' => [
                'Consumer Studies',
                'Hospitality Studies',
                'Tourism',
            ],
            'Life Orientation' => [
                'Life Orientation',
            ],
        ];
    }

    /**
     * Get the IEB subject list.
     *
     * @return array<string, list<string>>
     */
    public static function iebSubjectsByCategory(): array
    {
        $subjectsByCategory = self::capsSubjectsByCategory();

        $subjectsByCategory['Languages'] = [
            ...$subjectsByCategory['Languages'],
            'Arabic',
            'French',
            'German',
            'Gujarati',
            'Hebrew',
            'Hindi',
            'Italian',
            'Latin',
            'Mandarin',
            'Modern Greek',
            'Portuguese',
            'Serbian',
            'Spanish',
            'Tamil',
            'Telugu',
            'Urdu',
        ];

        $subjectsByCategory['Agriculture'][] = 'Equine Studies';
        $subjectsByCategory['Business & Commerce'][] = 'Maritime Economics';
        $subjectsByCategory['Sciences'][] = 'Nautical Science';
        $subjectsByCategory['Sciences'][] = 'Sports and Exercise Science';

        return $subjectsByCategory;
    }

    /**
     * Seed subjects for a curriculum.
     *
     * @param array<string, list<string>> $subjectsByCategory
     */
    public static function seedSubjects(int $curriculumId, array $subjectsByCategory): void
    {
        $grades = DB::table('grades')
            ->where('curriculum_id', $curriculumId)
            ->orderBy('sort_order')
            ->get(['id']);

        foreach ($grades as $grade) {
            foreach ($subjectsByCategory as $categoryName => $subjects) {
                $categoryId = DB::table('subject_categories')
                    ->where('name', $categoryName)
                    ->value('id');

                if ($categoryId === null) {
                    continue;
                }

                foreach ($subjects as $index => $subject) {
                    $metadata = self::subjectMetadata($subject, $categoryName);

                    DB::table('subjects')->updateOrInsert(
                        [
                            'curriculum_id' => $curriculumId,
                            'grade_id' => $grade->id,
                            'name' => $subject,
                        ],
                        [
                            'subject_category_id' => $categoryId,
                            'code' => $metadata['code'],
                            'abbreviation' => $metadata['abbreviation'],
                            'colour' => $metadata['colour'],
                            'icon' => $metadata['icon'],
                            'sort_order' => $index + 1,
                            'is_live' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    );
                }
            }
        }
    }

    /**
     * Get display metadata for a subject.
     *
     * @return array{code: string, abbreviation: string, colour: string, icon: string}
     */
    private static function subjectMetadata(string $subject, string $category): array
    {
        $overrides = [
            'Mathematics' => ['code' => 'MATH', 'colour' => '#2563EB'],
            'Life Sciences' => ['code' => 'LIFE', 'colour' => '#16A34A'],
            'Geography' => ['code' => 'GEO', 'colour' => '#EA580C'],
            'Tourism' => ['code' => 'TOUR', 'colour' => '#F59E0B'],
            'Accounting' => ['code' => 'ACC', 'colour' => '#7C3AED'],
        ];

        $categoryDefaults = [
            'Languages' => ['colour' => '#DC2626', 'icon' => 'languages'],
            'Mathematics' => ['colour' => '#2563EB', 'icon' => 'calculator'],
            'Sciences' => ['colour' => '#16A34A', 'icon' => 'flask-conical'],
            'Agriculture' => ['colour' => '#65A30D', 'icon' => 'sprout'],
            'Business & Commerce' => ['colour' => '#7C3AED', 'icon' => 'briefcase-business'],
            'Technology' => ['colour' => '#0891B2', 'icon' => 'monitor'],
            'Humanities' => ['colour' => '#EA580C', 'icon' => 'landmark'],
            'Creative Arts' => ['colour' => '#DB2777', 'icon' => 'palette'],
            'Services' => ['colour' => '#F59E0B', 'icon' => 'concierge-bell'],
            'Engineering' => ['colour' => '#475569', 'icon' => 'hard-hat'],
            'Life Orientation' => ['colour' => '#14B8A6', 'icon' => 'heart-pulse'],
        ];

        $code = $overrides[$subject]['code'] ?? self::generateSubjectCode($subject);
        $default = $categoryDefaults[$category] ?? ['colour' => '#64748B', 'icon' => 'book-open'];

        return [
            'code' => $code,
            'abbreviation' => $code,
            'colour' => $overrides[$subject]['colour'] ?? $default['colour'],
            'icon' => $default['icon'],
        ];
    }

    private static function generateSubjectCode(string $subject): string
    {
        $words = preg_split('/[^A-Za-z]+/', $subject, -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || $words === []) {
            return 'SUBJ';
        }

        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 4));
        }

        return strtoupper(substr(implode('', array_map(
            static fn (string $word): string => $word[0],
            $words,
        )), 0, 6));
    }
}
