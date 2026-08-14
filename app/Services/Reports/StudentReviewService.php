<?php

namespace App\Services\Reports;

use App\Models\User;
use App\Services\LemoAi\LemoAiRouter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class StudentReviewService
{
    private const CACHE_TTL_SECONDS = 21600;

    public function __construct(
        private readonly LemoAiRouter $router,
    ) {}

    /**
     * @param  array<string, mixed>  $courseMatch
     */
    public function review(User $user, array $courseMatch): string
    {
        $fallback = $this->templateReview($courseMatch);

        if (! ($courseMatch['has_marks'] ?? false)) {
            return $fallback;
        }

        $payload = $this->compactPayload($user, $courseMatch);
        $cacheKey = 'student_review:'.hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($payload, $fallback): string {
            try {
                $response = $this->router->generate(
                    $this->systemInstruction(),
                    [],
                    json_encode($payload, JSON_THROW_ON_ERROR),
                );

                if (($response['provider'] ?? '') === 'system') {
                    return $fallback;
                }

                $text = trim((string) ($response['content'] ?? ''));
                $text = trim($text, " \t\n\r\0\x0B\"'");

                if ($text === '' || mb_strlen($text) > 900) {
                    return $fallback;
                }

                return $text;
            } catch (Throwable $exception) {
                Log::warning('[Student review] AI review failed', [
                    'error' => $exception->getMessage(),
                ]);

                return $fallback;
            }
        });
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
            'average_mark' => $courseMatch['average_mark'] === null ? null : round((float) $courseMatch['average_mark'], 1),
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

        if ($progress->count() > 1) {
            $latest = $progress->last();
            $previous = $progress->slice(-2, 1)->first();
            $difference = $latest && $previous ? (int) $latest->aps_total - (int) $previous->aps_total : 0;
            $trend = $difference > 0
                ? 'up by '.$difference.' APS points from the previous saved term'
                : ($difference < 0 ? 'down by '.abs($difference).' APS points from the previous saved term' : 'steady against the previous saved term');

            return 'Your '.$termLabel.' marks look '.$tone.' overall, with '.$averageLabel.' and '.$qualifiedCount.' matching qualifications. Your APS trend is '.$trend.', so use the qualified course list as a practical shortlist while keeping an eye on subject-specific requirements.';
        }

        return 'Your '.$termLabel.' marks give a '.$tone.' first picture, with '.$averageLabel.' and '.$qualifiedCount.' qualifications currently matching. Use the preview courses as hints, then add future term marks so Chamu can compare your trend instead of judging from one snapshot.';
    }

    private function systemInstruction(): string
    {
        return <<<'PROMPT'
You write short, careful academic guidance for Chamu learners.

Rules:
- Use only the JSON facts provided.
- Do not invent universities, qualifications, marks, requirements, bursaries, or guarantees.
- If term_count is greater than 1, compare latest_progress with previous_progress.
- If term_count is 1, explain that this is a single-term snapshot and give practical hints.
- Mention qualified_count and at most two preview qualifications.
- Keep it under 120 words.
- Plain text only. No markdown.
PROMPT;
    }
}
