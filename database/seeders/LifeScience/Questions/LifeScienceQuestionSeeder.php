<?php

namespace Database\Seeders\LifeScience\Questions;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use stdClass;

class LifeScienceQuestionSeeder extends Seeder
{
    private const IMAGE_BASE_PATH = 'images/life-science/questions';

    /**
     * Seed Grade 12 CAPS Life Sciences questions.
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

        foreach ($this->questions() as $index => $question) {
            $topic = $this->topic($gradeId, $subjectId, $question['topic']);

            if ($topic === null) {
                continue;
            }

            DB::table('questions')->updateOrInsert(
                [
                    'topic_id' => $topic->id,
                    'subject_id' => $subjectId,
                    'question_number' => $question['question_number'],
                ],
                [
                    'skill_id' => null,
                    'paper_id' => $topic->paper_id,
                    'answer_id' => null,
                    'title' => $question['title'] ?? null,
                    'instructions' => $question['instructions'] ?? $question['question'],
                    'image' => $this->imagePath($question['image'] ?? null),
                    'hint' => $question['hint'] ?? null,
                    'source' => $question['source'] ?? null,
                    'difficulty' => $question['difficulty'] ?? null,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $savedQuestion = DB::table('questions')
                ->where('topic_id', $topic->id)
                ->where('subject_id', $subjectId)
                ->where('question_number', $question['question_number'])
                ->first(['id']);

            if ($savedQuestion === null) {
                continue;
            }

            foreach ($question['sub_questions'] ?? [] as $subIndex => $subQuestion) {
                $this->seedSubQuestion($savedQuestion->id, $subQuestion, $subIndex);
            }
        }
    }

    private function seedSubQuestion(int $questionId, array $subQuestion, int $index): void
    {
        DB::table('sub_questions')->updateOrInsert(
            [
                'question_id' => $questionId,
                'sub_question_number' => $subQuestion['sub_question_number'],
            ],
            [
                'question' => $subQuestion['question'],
                'hint' => $subQuestion['hint'] ?? null,
                'question_type' => $subQuestion['question_type'] ?? null,
                'answer_type' => $subQuestion['answer_type'] ?? null,
                'options' => $this->jsonValue($subQuestion['options'] ?? null),
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $savedSubQuestion = DB::table('sub_questions')
            ->where('question_id', $questionId)
            ->where('sub_question_number', $subQuestion['sub_question_number'])
            ->first(['id', 'answer_id']);

        if ($savedSubQuestion === null) {
            return;
        }

        DB::table('sub_questions')
            ->where('id', $savedSubQuestion->id)
            ->update([
                'answer_id' => $this->answerId($subQuestion['answer'] ?? null, $subQuestion['answer_type'] ?? null, $savedSubQuestion->answer_id),
                'updated_at' => now(),
            ]);
    }

    private function topic(int $gradeId, int $subjectId, string $topic): ?stdClass
    {
        return DB::table('topics')
            ->leftJoin('papers', 'papers.id', '=', 'topics.paper_id')
            ->where('topics.grade_id', $gradeId)
            ->where('topics.subject_id', $subjectId)
            ->where('topics.name', $topic)
            ->orderByRaw('case when papers.number = 2 then 0 else 1 end')
            ->select('topics.id', 'topics.paper_id')
            ->first();
    }

    private function imagePath(?string $image): ?string
    {
        if ($image === null) {
            return null;
        }

        return self::IMAGE_BASE_PATH . '/' . $image . '.png';
    }

    private function answerId(?array $answer, ?string $answerType, ?int $answerId): ?int
    {
        if ($answer === null) {
            return null;
        }

        $values = [
            'correct_answer' => $this->textValue($answer['correct_answer'] ?? null),
            'accepted_answers' => $this->jsonValue($answer['accepted_answers'] ?? null),
            'explanation' => $answer['explanation'] ?? null,
            'answer_type' => $answer['answer_type'] ?? $answerType,
            'is_case_sensitive' => $answer['is_case_sensitive'] ?? false,
            'requires_exact_match' => $answer['requires_exact_match'] ?? false,
            'updated_at' => now(),
        ];

        if ($answerId !== null) {
            DB::table('answers')
                ->where('id', $answerId)
                ->update($values);

            return $answerId;
        }

        return DB::table('answers')->insertGetId([
            ...$values,
            'created_at' => now(),
        ]);
    }

    private function textValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value);
    }

    private function jsonValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value);
    }

    /**
     * @return list<array{
     *     topic: string,
     *     question_number: string,
     *     title?: string|null,
     *     instructions?: string|null,
     *     question?: string,
     *     image?: string|null,
     *     hint?: string|null,
     *     difficulty?: string|null,
     *     source?: string|null,
     *     sub_questions?: list<array<string, mixed>>
     * }>
     */
    private function questions(): array
    {
        $path = __DIR__ . '/life_science_questions.json';
        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException("Unable to read Life Sciences question seed data at {$path}.");
        }

        $questions = json_decode($json, true);

        if (! is_array($questions)) {
            throw new RuntimeException('Life Sciences question seed data is not valid JSON.');
        }

        return $questions;
    }
}
