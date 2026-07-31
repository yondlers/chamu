<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SourceMeta
{
    /**
     * @return array{
     *     label: string,
     *     tone: string,
     *     summary: string,
     *     source_url: string|null,
     *     source_host: string|null,
     *     last_reviewed: string,
     *     last_reviewed_machine: string|null
     * }
     */
    public static function make(?string $sourceUrl, mixed $updatedAt = null, ?string $officialUrl = null): array
    {
        $sourceUrl = filled($sourceUrl) ? trim((string) $sourceUrl) : null;
        $officialUrl = filled($officialUrl) ? trim((string) $officialUrl) : null;
        $sourceHost = self::host($sourceUrl);
        $officialHost = self::host($officialUrl);
        $reviewedAt = self::date($updatedAt);

        if ($sourceUrl === null) {
            return [
                'label' => 'Needs confirmation',
                'tone' => 'amber',
                'summary' => 'No source link is captured yet. Confirm the latest requirements or application steps on the official provider website before acting.',
                'source_url' => null,
                'source_host' => null,
                'last_reviewed' => $reviewedAt?->format('j F Y') ?? 'Review date not captured',
                'last_reviewed_machine' => $reviewedAt?->toDateString(),
            ];
        }

        if ($officialHost !== null && self::hostsMatch($sourceHost, $officialHost)) {
            return [
                'label' => 'High confidence',
                'tone' => 'emerald',
                'summary' => 'The captured source appears to be from the institution or provider website.',
                'source_url' => $sourceUrl,
                'source_host' => $sourceHost,
                'last_reviewed' => $reviewedAt?->format('j F Y') ?? 'Review date not captured',
                'last_reviewed_machine' => $reviewedAt?->toDateString(),
            ];
        }

        if (self::isInstitutionalHost($sourceHost)) {
            return [
                'label' => 'High confidence',
                'tone' => 'emerald',
                'summary' => 'The captured source is on a recognised institution, funder, or public-sector domain.',
                'source_url' => $sourceUrl,
                'source_host' => $sourceHost,
                'last_reviewed' => $reviewedAt?->format('j F Y') ?? 'Review date not captured',
                'last_reviewed_machine' => $reviewedAt?->toDateString(),
            ];
        }

        return [
            'label' => 'Medium confidence',
            'tone' => 'sky',
            'summary' => 'The captured source is useful, but learners should still confirm final dates, documents, and rules with the official provider.',
            'source_url' => $sourceUrl,
            'source_host' => $sourceHost,
            'last_reviewed' => $reviewedAt?->format('j F Y') ?? 'Review date not captured',
            'last_reviewed_machine' => $reviewedAt?->toDateString(),
        ];
    }

    private static function host(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return Str::of($host)
            ->lower()
            ->replaceStart('www.', '')
            ->toString();
    }

    private static function hostsMatch(?string $sourceHost, ?string $officialHost): bool
    {
        if ($sourceHost === null || $officialHost === null) {
            return false;
        }

        return $sourceHost === $officialHost
            || Str::endsWith($sourceHost, '.'.$officialHost)
            || Str::endsWith($officialHost, '.'.$sourceHost);
    }

    private static function isInstitutionalHost(?string $host): bool
    {
        if ($host === null) {
            return false;
        }

        return Str::endsWith($host, [
            '.ac.za',
            '.edu',
            '.edu.za',
            '.gov.za',
            '.org.za',
        ]) || Str::contains($host, [
            'studytrust.org.za',
            'nsfas.org.za',
            'internationalscholarships.dhet.gov.za',
        ]);
    }

    private static function date(mixed $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value;
        }

        return Carbon::parse($value);
    }
}
