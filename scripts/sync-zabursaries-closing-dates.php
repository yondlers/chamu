<?php

/**
 * Fetch zabursaries closing dates for bursaries missing closing_date.
 * Usage: php scripts/sync-zabursaries-closing-dates.php [--dry-run] [--limit=N]
 */

use App\Models\Bursary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dryRun = in_array('--dry-run', $argv, true);
$limit = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = (int) substr($arg, 8);
    }
}

$monthMap = [
    'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
    'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
    'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
];

$buildDate = function (int $day, int $month, int $year): ?Carbon {
    if (! checkdate($month, $day, $year)) {
        return null;
    }

    return Carbon::create($year, $month, $day)->startOfDay();
};

$parseClosingSnippet = function (string $snippet) use ($monthMap, $buildDate): ?array {
    $snippet = html_entity_decode($snippet, ENT_QUOTES | ENT_HTML5);
    $snippet = preg_replace('/\s+/', ' ', trim(strip_tags($snippet))) ?? '';
    $snippet = rtrim($snippet, '.');

    // Explicit dated closing: 31 August 2023
    if (preg_match('/\b(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})\b/', $snippet, $m)) {
        $month = $monthMap[strtolower($m[2])] ?? null;
        if ($month === null) {
            return null;
        }
        $date = $buildDate((int) $m[1], $month, (int) $m[3]);
        if ($date === null) {
            return null;
        }

        return [
            'date' => $date->toDateString(),
            'label' => $date->lt(now()->startOfDay())
                ? 'Closed — '.$date->format('j F Y')
                : $date->format('j F Y'),
            'raw' => $m[0],
        ];
    }

    // Annual closing: 30 September annually
    if (preg_match('/\b(\d{1,2})\s+([A-Za-z]+)\s+annually\b/i', $snippet, $m)) {
        $month = $monthMap[strtolower($m[2])] ?? null;
        if ($month === null) {
            return null;
        }
        $day = (int) $m[1];
        $year = (int) now()->year;
        $date = $buildDate($day, $month, $year);
        if ($date === null) {
            return null;
        }
        if ($date->lt(now()->startOfDay())) {
            $date = $buildDate($day, $month, $year + 1);
        }
        if ($date === null) {
            return null;
        }

        return [
            'date' => $date->toDateString(),
            'label' => $day.' '.Carbon::create(null, $month, 1)->format('F').' annually',
            'raw' => $m[0],
        ];
    }

    // Open / no fixed date / unconfirmed
    if (preg_match('/\b(none|no closing date|not confirmed|to be confirmed|applications accepted anytime|throughout the year|open throughout|open all year|apply asap)\b/i', $snippet)) {
        $label = 'Closing date not confirmed — see source';
        if (preg_match('/\bnone\b/i', $snippet) || preg_match('/throughout the year|accepted anytime|open throughout|open all year/i', $snippet)) {
            $label = 'No fixed closing date — see source';
        } elseif (preg_match('/apply asap/i', $snippet)) {
            $label = 'Not confirmed — apply ASAP';
        }

        return [
            'date' => null,
            'label' => $label,
            'raw' => $snippet,
            'skip_date' => true,
        ];
    }

    return null;
};

$extractClosingDate = function (string $html) use ($parseClosingSnippet): ?array {
    // Prefer the page's CLOSING DATE section in entry content.
    if (preg_match(
        '/<h3[^>]*>\s*CLOSING DATE[\s\S]{0,80}?<\/h3>\s*(<p[\s\S]{0,500}?<\/p>)/i',
        $html,
        $m
    )) {
        $parsed = $parseClosingSnippet($m[1]);
        if ($parsed !== null) {
            return $parsed;
        }
    }

    $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $html)), ENT_QUOTES | ENT_HTML5);
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;

    if (preg_match('/CLOSING DATE.{0,220}/i', $text, $m)) {
        $parsed = $parseClosingSnippet($m[0]);
        if ($parsed !== null) {
            return $parsed;
        }
    }

    return null;
};

$query = Bursary::query()
    ->whereNull('closing_date')
    ->where('is_active', true)
    ->where('source_url', 'like', 'https://www.zabursaries.co.za/%')
    ->orderBy('id');

if ($limit !== null && $limit > 0) {
    $query->limit($limit);
}

$bursaries = $query->get(['id', 'title', 'source_url', 'closing_date_label']);
$total = $bursaries->count();
echo "Scanning {$total} zabursaries bursaries with no closing_date...\n";

$results = [
    'updated' => [],
    'label_only' => [],
    'unparsed' => [],
    'failed' => [],
];

$mh = curl_multi_init();
$batchSize = 15;
$chunks = $bursaries->chunk($batchSize);

foreach ($chunks as $chunkIndex => $chunk) {
    $handles = [];
    foreach ($chunk as $bursary) {
        $ch = curl_init($bursary->source_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ChamuClosingDateSync/1.1)',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-ZA,en;q=0.9',
            ],
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[(int) $ch] = ['ch' => $ch, 'bursary' => $bursary];
    }

    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 1.0);
        }
    } while ($running && $status === CURLM_OK);

    foreach ($handles as $item) {
        $ch = $item['ch'];
        $bursary = $item['bursary'];
        $html = curl_multi_getcontent($ch);
        $errno = curl_errno($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);

        if ($errno !== 0 || $statusCode >= 400 || ! is_string($html) || $html === '') {
            $results['failed'][] = [
                'id' => $bursary->id,
                'title' => $bursary->title,
                'source_url' => $bursary->source_url,
                'error' => $errno ? (curl_strerror($errno) ?: "errno {$errno}") : "HTTP {$statusCode}",
            ];
            echo "FAIL #{$bursary->id} {$bursary->title}\n";
            continue;
        }

        $parsed = $extractClosingDate($html);
        if ($parsed === null) {
            $results['unparsed'][] = [
                'id' => $bursary->id,
                'title' => $bursary->title,
                'source_url' => $bursary->source_url,
            ];
            echo "MISS #{$bursary->id} {$bursary->title}\n";
            continue;
        }

        if (! empty($parsed['skip_date']) || $parsed['date'] === null) {
            if (! $dryRun) {
                DB::table('bursaries')->where('id', $bursary->id)->update([
                    'closing_date_label' => $parsed['label'],
                    'updated_at' => now(),
                ]);
            }
            $results['label_only'][] = [
                'id' => $bursary->id,
                'title' => $bursary->title,
                'source_url' => $bursary->source_url,
                'closing_date_label' => $parsed['label'],
                'raw' => $parsed['raw'],
            ];
            echo "LABL #{$bursary->id} {$parsed['label']} — {$bursary->title}\n";
            continue;
        }

        if (! $dryRun) {
            DB::table('bursaries')->where('id', $bursary->id)->update([
                'closing_date' => $parsed['date'],
                'closing_date_label' => $parsed['label'],
                'updated_at' => now(),
            ]);
        }

        $results['updated'][] = [
            'id' => $bursary->id,
            'title' => $bursary->title,
            'source_url' => $bursary->source_url,
            'closing_date' => $parsed['date'],
            'closing_date_label' => $parsed['label'],
            'raw' => $parsed['raw'],
        ];
        echo "OK   #{$bursary->id} {$parsed['label']} — {$bursary->title}\n";
    }

    echo sprintf(
        "Batch %d/%d (updated=%d label=%d miss=%d fail=%d)\n",
        $chunkIndex + 1,
        $chunks->count(),
        count($results['updated']),
        count($results['label_only']),
        count($results['unparsed']),
        count($results['failed']),
    );
    usleep(250000);
}

curl_multi_close($mh);

$outDir = storage_path('app/bursary-closing-dates');
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$timestamp = now()->format('Ymd_His');
$reportPath = "{$outDir}/sync_{$timestamp}.json";
$overridePath = database_path('seeders/data/zabursaries-closing-dates.json');

file_put_contents($reportPath, json_encode([
    'generated_at' => now()->toIso8601String(),
    'dry_run' => $dryRun,
    'counts' => [
        'scanned' => $total,
        'updated' => count($results['updated']),
        'label_only' => count($results['label_only']),
        'unparsed' => count($results['unparsed']),
        'failed' => count($results['failed']),
    ],
    'results' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$overrides = [];
if (is_file($overridePath)) {
    $existing = json_decode((string) file_get_contents($overridePath), true);
    if (is_array($existing)) {
        $overrides = $existing;
    }
}

foreach ($results['updated'] as $row) {
    $overrides[$row['source_url']] = [
        'closing_date' => $row['closing_date'],
        'closing_date_label' => $row['closing_date_label'],
    ];
}

foreach ($results['label_only'] as $row) {
    $overrides[$row['source_url']] = [
        'closing_date' => null,
        'closing_date_label' => $row['closing_date_label'],
    ];
}

ksort($overrides);
if (! is_dir(dirname($overridePath))) {
    mkdir(dirname($overridePath), 0777, true);
}
file_put_contents($overridePath, json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

echo "\nDone.\n";
echo "Updated: ".count($results['updated'])."\n";
echo "Label only: ".count($results['label_only'])."\n";
echo "Unparsed: ".count($results['unparsed'])."\n";
echo "Failed: ".count($results['failed'])."\n";
echo "Report: {$reportPath}\n";
echo "Overrides: {$overridePath}\n";
