<?php

namespace App\Support;

use Illuminate\Support\Collection;

class ProgrammeNotes
{
    private const LABELS = [
        'Programme scope',
        'Faculty',
        'Campus',
        'Presentation',
        'Selection format',
        'Eligibility explanation',
        'Academic requirement',
        'Selection context',
        'Space context',
        'Stream detail',
        'Stream-placement context',
        'Transfer and graduate context',
        'Foreign applicant context',
        'Professional registration',
        'Professional context',
        'Career context',
        'Career pointers',
        'Graduate scoring context',
        'Alternative routes',
        'Curriculum context',
        'Exposure context',
        'Practical context',
        'Source note',
        'Recommended subjects',
        'Other campuses',
        'Possible further studies',
        'Possible careers',
        'Document checklist',
        'Application method',
        'Closing-date context',
        'Application safety',
        'Source reviewed',
        'Source confidence',
    ];

    /**
     * @return Collection<string, string>
     */
    public static function sections(?string $notes): Collection
    {
        return collect(self::labelledMatches($notes))
            ->mapWithKeys(fn (array $match) => [$match['label'] => $match['body']]);
    }

    /**
     * @param array<int, string> $exceptLabels
     * @return Collection<int, string>
     */
    public static function lines(?string $notes, array $exceptLabels = []): Collection
    {
        $notesText = self::text($notes);

        if ($notesText === '') {
            return collect();
        }

        $except = array_flip($exceptLabels);
        $matches = self::labelledMatches($notesText);

        if ($matches !== []) {
            return collect($matches)
                ->reject(fn (array $match) => isset($except[$match['label']]))
                ->map(fn (array $match) => $match['label'].': '.$match['body'])
                ->values();
        }

        return collect(preg_split('/\R+/', $notesText) ?: [])
            ->map(fn (string $note) => self::normalise($note))
            ->filter()
            ->values();
    }

    /**
     * @param Collection<string, string>|array<string, string> $sections
     * @param array<int, string> $labels
     */
    public static function first(Collection|array $sections, array $labels): ?string
    {
        $sections = $sections instanceof Collection ? $sections : collect($sections);

        foreach ($labels as $label) {
            $value = $sections->get($label);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{label: string, body: string}>
     */
    private static function labelledMatches(?string $notes): array
    {
        $notesText = self::text($notes);

        if ($notesText === '') {
            return [];
        }

        $labels = self::labelAlternation();
        $matchCount = preg_match_all(
            '/(?:^|\s)('.$labels.'):\s*(.*?)(?=\s*(?:'.$labels.'):\s*|$)/s',
            $notesText,
            $matches,
            PREG_SET_ORDER
        );

        if ($matchCount === false || $matchCount === 0) {
            return [];
        }

        return collect($matches)
            ->map(fn (array $match) => [
                'label' => $match[1],
                'body' => self::normalise($match[2]),
            ])
            ->filter(fn (array $match) => $match['body'] !== '')
            ->values()
            ->all();
    }

    private static function text(?string $notes): string
    {
        return trim((string) $notes);
    }

    private static function normalise(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private static function labelAlternation(): string
    {
        return collect(self::LABELS)
            ->map(fn (string $label) => preg_quote($label, '/'))
            ->implode('|');
    }
}
