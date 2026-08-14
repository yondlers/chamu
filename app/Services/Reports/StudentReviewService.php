<?php

namespace App\Services\Reports;

use App\Models\User;
use App\Models\UserStudentReview;
use App\Services\LemoAi\LemoAiRouter;
use App\Services\Matching\CourseMatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StudentReviewService
{
    public const MINIMUM_SUBJECTS = 7;

    public function __construct(
        private readonly LemoAiRouter $router,
        private readonly CourseMatcher $courseMatcher,
    ) {}

    /**
     * @param  array<string, mixed>  $courseMatch
     */
    public function review(User $user, array $courseMatch): string
    {
        return $this->savedOrTemplateReview($user, $courseMatch);
    }

    /**
     * @param  array<string, mixed>|null  $courseMatch
     */
    public function savedReview(User $user, ?array $courseMatch = null): ?UserStudentReview
    {
        $courseMatch ??= $this->courseMatcher->forUser($user, null, 2);
        $snapshot = $this->snapshotForCourseMatch($user, $courseMatch);

        if (! $snapshot['requirements']['ready']) {
            return null;
        }

        return UserStudentReview::query()
            ->where('user_id', $user->id)
            ->where('snapshot_hash', $snapshot['hash'])
            ->where('status', 'generated')
            ->whereNotNull('review_text')
            ->latest('generated_at')
            ->latest()
            ->first();
    }

    /**
     * @param  array<string, mixed>|null  $courseMatch
     */
    public function savedReviewText(User $user, ?array $courseMatch = null): ?string
    {
        return $this->savedReview($user, $courseMatch)?->review_text;
    }

    /**
     * Returns saved AI text when it exists, otherwise a local template without
     * making a provider request.
     *
     * @param  array<string, mixed>  $courseMatch
     */
    public function savedOrTemplateReview(User $user, array $courseMatch): string
    {
        return $this->savedReviewText($user, $courseMatch) ?: $this->templateReview($courseMatch);
    }

    /**
     * @param  array<string, mixed>|null  $courseMatch
     */
    public function ensureReviewAfterResponse(User $user, ?array $courseMatch = null): void
    {
        if ($courseMatch !== null) {
            $requirements = $this->requirements($user, $courseMatch);

            if (! $requirements['ready']) {
                return;
            }

            if ($this->savedReview($user, $courseMatch) !== null) {
                return;
            }
        }

        $userId = $user->id;

        app()->terminating(function () use ($userId): void {
            try {
                $reviewUser = User::query()->find($userId);

                if ($reviewUser !== null) {
                    $this->ensureReviewForLatestMarks($reviewUser);
                }
            } catch (Throwable $exception) {
                Log::warning('[Student review] Deferred review generation failed', [
                    'user_id' => $userId,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }

    /**
     * @param  array<string, mixed>|null  $courseMatch
     * @return array{
     *     ready: bool,
     *     minimum_subjects: int,
     *     selected_subject_count: int,
     *     marked_subject_count: int,
     *     missing_subject_count: int,
     *     missing_mark_count: int,
     *     grade_id: int|null,
     *     term_id: int|null
     * }
     */
    public function requirements(User $user, ?array $courseMatch = null): array
    {
        $courseMatch ??= $this->courseMatcher->forUser($user, null, 2);
        $term = $courseMatch['term'] ?? null;
        $gradeId = $term?->grade_id ?? $user->grade_id;
        $termId = $term?->id ?? null;
        $selectedSubjectIds = $this->selectedSubjectIdsForGrade($user, $gradeId === null ? null : (int) $gradeId);
        $markedSubjectIds = collect($courseMatch['results'] ?? [])
            ->pluck('subject_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($selectedSubjectIds->isNotEmpty()) {
            $markedSubjectIds = $markedSubjectIds
                ->intersect($selectedSubjectIds)
                ->values();
        }

        $selectedSubjectCount = $selectedSubjectIds->count();
        $markedSubjectCount = $markedSubjectIds->count();

        return [
            'ready' => $termId !== null
                && $selectedSubjectCount >= self::MINIMUM_SUBJECTS
                && $markedSubjectCount >= self::MINIMUM_SUBJECTS,
            'minimum_subjects' => self::MINIMUM_SUBJECTS,
            'selected_subject_count' => $selectedSubjectCount,
            'marked_subject_count' => $markedSubjectCount,
            'missing_subject_count' => max(self::MINIMUM_SUBJECTS - $selectedSubjectCount, 0),
            'missing_mark_count' => max(self::MINIMUM_SUBJECTS - $markedSubjectCount, 0),
            'grade_id' => $gradeId === null ? null : (int) $gradeId,
            'term_id' => $termId === null ? null : (int) $termId,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $courseMatch
     */
    public function ensureReviewForLatestMarks(User $user, ?array $courseMatch = null): ?UserStudentReview
    {
        $courseMatch ??= $this->courseMatcher->forUser($user, null, 2);
        $snapshot = $this->snapshotForCourseMatch($user, $courseMatch);
        $requirements = $snapshot['requirements'];

        if (! $requirements['ready']) {
            return null;
        }

        $promptPayload = $this->compactPayload($user, $courseMatch);
        $baseAttributes = [
            'curriculum_id' => $user->curriculum_id,
            'grade_id' => $requirements['grade_id'],
            'term_id' => $requirements['term_id'],
            'status' => 'generating',
            'subject_count' => $requirements['selected_subject_count'],
            'marked_subject_count' => $requirements['marked_subject_count'],
            'aps_total' => (int) ($courseMatch['aps_total'] ?? 0),
            'average_mark' => ($courseMatch['average_mark'] ?? null) === null ? null : round((float) $courseMatch['average_mark'], 2),
            'qualified_count' => (int) ($courseMatch['qualified_count'] ?? 0),
            'payload' => [
                'requirements' => $requirements,
                'snapshot' => $snapshot['payload'],
                'review_payload' => $promptPayload,
            ],
        ];

        $review = UserStudentReview::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'snapshot_hash' => $snapshot['hash'],
            ],
            $baseAttributes,
        );

        if (! $review->wasRecentlyCreated && $review->status === 'generated' && filled($review->review_text)) {
            return $review;
        }

        if (
            ! $review->wasRecentlyCreated
            && $review->status === 'generating'
            && $review->updated_at !== null
            && $review->updated_at->greaterThan(now()->subMinutes(2))
        ) {
            return null;
        }

        if (! $review->wasRecentlyCreated) {
            $review->fill($baseAttributes);
            $review->save();
        }

        $generated = $this->generateReviewContent($promptPayload, $this->templateReview($courseMatch));

        $review->fill([
            'status' => 'generated',
            'review_text' => $generated['content'],
            'provider' => $generated['provider'],
            'model' => $generated['model'],
            'generated_at' => now(),
        ]);
        $review->save();

        return $review;
    }

    /**
     * @param  array<string, mixed>  $courseMatch
     * @return array<string, mixed>
     */
    private function compactPayload(User $user, array $courseMatch): array
    {
        $progress = collect($courseMatch['progress'] ?? []);
        $latest = $progress->last();
        $previous = $progress->count() > 1 ? $progress->slice(-2, 1)->first() : null;

        return [
            'student_first_name' => $user->first_name ?: $user->name,
            'selected_term' => $courseMatch['term']->label ?? null,
            'term_count' => $progress->count(),
            'aps_total' => $courseMatch['aps_total'] ?? 0,
            'average_mark' => ($courseMatch['average_mark'] ?? null) === null ? null : round((float) $courseMatch['average_mark'], 1),
            'qualified_count' => $courseMatch['qualified_count'] ?? 0,
            'latest_progress' => $latest ? [
                'label' => $latest->label,
                'aps_total' => $latest->aps_total,
                'average_mark' => $latest->average_mark === null ? null : round((float) $latest->average_mark, 1),
            ] : null,
            'previous_progress' => $previous ? [
                'label' => $previous->label,
                'aps_total' => $previous->aps_total,
                'average_mark' => $previous->average_mark === null ? null : round((float) $previous->average_mark, 1),
            ] : null,
            'preview_qualifications' => collect($courseMatch['preview'] ?? [])
                ->take(2)
                ->map(fn (array $match): array => [
                    'university' => $match['university_abbreviation'] ?: $match['university_name'],
                    'qualification' => $match['qualification_name'],
                    'score_label' => $match['score_label'],
                    'required_score' => $match['required_score'],
                    'actual_score' => $match['actual_score'],
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $courseMatch
     * @return array{
     *     hash: string,
     *     payload: array<string, mixed>,
     *     requirements: array<string, mixed>
     * }
     */
    private function snapshotForCourseMatch(User $user, array $courseMatch): array
    {
        $requirements = $this->requirements($user, $courseMatch);
        $selectedSubjectIds = $this->selectedSubjectIdsForGrade($user, $requirements['grade_id']);
        $results = collect($courseMatch['results'] ?? [])
            ->when(
                $selectedSubjectIds->isNotEmpty(),
                fn (Collection $results): Collection => $results->filter(
                    fn (object $result): bool => $selectedSubjectIds->contains((int) $result->subject_id)
                ),
            )
            ->sortBy(fn (object $result): int => (int) $result->subject_id)
            ->map(fn (object $result): array => [
                'subject_id' => (int) $result->subject_id,
                'name' => (string) $result->name,
                'mark' => (int) $result->mark,
                'aps_score' => (int) $result->aps_score,
            ])
            ->values()
            ->all();
        $progress = collect($courseMatch['progress'] ?? [])
            ->map(fn (object $point): array => [
                'grade_id' => (int) $point->grade_id,
                'term_id' => (int) $point->term_id,
                'aps_total' => (int) $point->aps_total,
                'average_mark' => $point->average_mark === null ? null : round((float) $point->average_mark, 2),
                'reported_subjects' => (int) $point->reported_subjects,
            ])
            ->values()
            ->all();
        $term = $courseMatch['term'] ?? null;
        $payload = [
            'user_id' => $user->id,
            'curriculum_id' => $user->curriculum_id,
            'grade_id' => $requirements['grade_id'],
            'term_id' => $requirements['term_id'],
            'term_label' => $term?->label,
            'selected_subject_ids' => $selectedSubjectIds->values()->all(),
            'results' => $results,
            'progress' => $progress,
        ];

        return [
            'hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            'payload' => $payload,
            'requirements' => $requirements,
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function selectedSubjectIdsForGrade(User $user, ?int $gradeId): Collection
    {
        if ($gradeId === null) {
            return collect();
        }

        return DB::table('user_subject_preferences')
            ->where('user_id', $user->id)
            ->where('grade_id', $gradeId)
            ->orderBy('sort_order')
            ->pluck('subject_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{content: string, provider: string, model: string}
     */
    private function generateReviewContent(array $payload, string $fallback): array
    {
        try {
            $response = $this->router->generate(
                $this->systemInstruction(),
                [],
                json_encode($payload, JSON_THROW_ON_ERROR),
            );

            $provider = (string) ($response['provider'] ?? 'system');
            $model = (string) ($response['model'] ?? 'fallback');

            if ($provider === 'system') {
                return [
                    'content' => $fallback,
                    'provider' => $provider,
                    'model' => $model,
                ];
            }

            $text = trim((string) ($response['content'] ?? ''));
            $text = trim($text, " \t\n\r\0\x0B\"'");

            if ($text === '' || mb_strlen($text) > 900) {
                return [
                    'content' => $fallback,
                    'provider' => $provider,
                    'model' => $model,
                ];
            }

            return [
                'content' => $text,
                'provider' => $provider,
                'model' => $model,
            ];
        } catch (Throwable $exception) {
            Log::warning('[Student review] AI review failed', [
                'error' => $exception->getMessage(),
            ]);

            return [
                'content' => $fallback,
                'provider' => 'system',
                'model' => 'fallback',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $courseMatch
     */
    private function templateReview(array $courseMatch): string
    {
        if (! ($courseMatch['has_marks'] ?? false)) {
            return 'Add your subject marks first and Chamu will review your APS trend, average, and matching qualification options.';
        }

        $average = $courseMatch['average_mark'];
        $averageLabel = $average === null ? 'your saved average' : number_format((float) $average, 1).'% average';
        $qualifiedCount = (int) ($courseMatch['qualified_count'] ?? 0);
        $progress = collect($courseMatch['progress'] ?? []);
        $termLabel = $courseMatch['term']->label ?? 'your latest saved term';
        $tone = match (true) {
            $average !== null && $average >= 75 => 'very strong',
            $average !== null && $average >= 65 => 'strong',
            $average !== null && $average >= 50 => 'workable',
            default => 'still developing',
        };

        $qualificationLabel = $qualifiedCount === 1 ? 'qualification' : 'qualifications';
        $qualificationIntro = $qualifiedCount > 0
            ? 'Big news: your '.$termLabel.' marks currently qualify for '.$qualifiedCount.' matching '.$qualificationLabel.'. That is a real milestone.'
            : 'Your '.$termLabel.' marks are saved, and Chamu has not found a qualifying course match yet.';

        if ($progress->count() > 1) {
            $latest = $progress->last();
            $previous = $progress->slice(-2, 1)->first();
            $difference = $latest && $previous ? (int) $latest->aps_total - (int) $previous->aps_total : 0;
            $trend = $difference > 0
                ? 'up by '.$difference.' APS points from the previous saved term'
                : ($difference < 0 ? 'down by '.abs($difference).' APS points from the previous saved term' : 'steady against the previous saved term');

            return $qualificationIntro.' Your overall picture is '.$tone.' at '.$averageLabel.', and your APS trend is '.$trend.'. Keep using the qualified course list as a practical shortlist while checking subject-specific requirements.';
        }

        return $qualificationIntro.' Your first snapshot looks '.$tone.' at '.$averageLabel.'. Use the preview courses as a proud starting shortlist, then add future term marks so Chamu can compare your trend.';
    }

    private function systemInstruction(): string
    {
        return <<<'PROMPT'
You write short, careful academic guidance for Chamu learners.

Rules:
- Use only the JSON facts provided.
- Do not invent universities, qualifications, marks, requirements, bursaries, or guarantees.
- Keep the tone happy, proud, and encouraging.
- When qualified_count is greater than 0, treat qualifying as a big deal and clearly celebrate it.
- If term_count is greater than 1, compare latest_progress with previous_progress.
- If term_count is 1, explain that this is a single-term snapshot and give practical hints.
- Mention qualified_count and at most two preview qualifications.
- Keep it under 120 words.
- Plain text only. No markdown.
PROMPT;
    }
}
