<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Chamu Course Matcher Report</title>
    <style>
        @page { margin: 30px 34px 44px; }
        body { font-family: DejaVu Sans, sans-serif; color: #171717; font-size: 10.5px; line-height: 1.38; }
        h1, h2, h3, p { margin: 0; }
        a { color: #01225E; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f4f6f8; color: #525252; font-size: 8.5px; text-transform: uppercase; padding: 6px; border-bottom: 1px solid #d9d9d9; }
        td { padding: 6px; border-bottom: 1px solid #ececec; vertical-align: top; }
        .header { border-bottom: 2px solid #01225E; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { display: table; width: 100%; }
        .brand-left, .brand-right { display: table-cell; vertical-align: middle; }
        .brand-right { text-align: right; color: #737373; font-size: 9px; }
        .logo { width: 34px; height: 34px; vertical-align: middle; }
        .brand-name { display: inline-block; margin-left: 8px; font-size: 17px; font-weight: bold; vertical-align: middle; }
        .title { margin-top: 12px; font-size: 24px; color: #01225E; }
        .muted { color: #666; }
        .small { font-size: 9px; }
        .summary { display: table; width: 100%; border-spacing: 7px; margin: 0 -7px; }
        .summary-cell { display: table-cell; width: 25%; border: 1px solid #e2e2e2; padding: 9px; }
        .label { color: #666; font-size: 8.5px; font-weight: bold; text-transform: uppercase; }
        .value { margin-top: 3px; font-size: 18px; font-weight: bold; }
        .section { margin-top: 16px; }
        .section-title { font-size: 15px; color: #01225E; margin-bottom: 7px; }
        .review { border: 1px solid #bfdbfe; background: #eff6ff; padding: 10px; color: #01225E; }
        .bar-table td { border-bottom: 0; padding: 4px 6px; }
        .bar-label { width: 115px; font-weight: bold; color: #404040; }
        .bar-track { height: 12px; background: #e5e7eb; }
        .bar-fill { height: 12px; background: #01225E; }
        .bar-value { width: 42px; text-align: right; font-weight: bold; }
        .page-break { page-break-before: always; }
        .toc td:first-child { font-weight: bold; color: #01225E; width: 32%; }
        .institution-list th, .institution-list td { font-size: 8.5px; padding: 4px 5px; }
        .institution-list td:nth-child(1), .institution-list td:nth-child(3), .institution-list td:nth-child(5) { width: 25%; }
        .institution-list td:nth-child(2), .institution-list td:nth-child(4), .institution-list td:nth-child(6) { width: 8%; text-align: right; }
        .university-section { margin-top: 16px; page-break-inside: auto; }
        .university-head { border-top: 2px solid #01225E; padding-top: 9px; margin-bottom: 6px; page-break-after: avoid; }
        .uni-head-table td { border-bottom: 0; padding: 0; vertical-align: middle; }
        .uni-logo-cell { width: 42px; }
        .uni-logo { width: 34px; height: 34px; object-fit: contain; }
        .uni-fallback { width: 34px; height: 34px; background: #01225E; color: #fff; text-align: center; line-height: 34px; font-size: 10px; font-weight: bold; }
        .uni-name { font-size: 14px; font-weight: bold; color: #01225E; }
        .course-name { font-weight: bold; }
        .requirements { color: #404040; }
        .footer { position: fixed; bottom: -24px; left: 0; right: 0; color: #737373; font-size: 8px; border-top: 1px solid #e5e5e5; padding-top: 6px; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    @php
        $results = collect($courseMatch['results'] ?? []);
        $progress = collect($courseMatch['progress'] ?? []);
        $matches = collect($courseMatch['matches'] ?? []);
        $maxAps = max(42, (int) ($progress->max('aps_total') ?? 0));
        $studentName = $user->name ?: $user->email;
        $termLabel = $courseMatch['term']->label ?? $courseMatch['term']->name ?? 'latest saved marks';
        $universities = $matches
            ->groupBy(fn (array $match): string => (string) ($match['university_name'] ?? 'University'))
            ->map(function ($items, string $name): array {
                $first = $items->first();

                return [
                    'name' => $name,
                    'abbreviation' => $first['university_abbreviation'] ?? null,
                    'logo_path' => $first['university_logo_path'] ?? null,
                    'initials' => $first['university_initials'] ?? 'U',
                    'count' => $items->count(),
                    'matches' => $items
                        ->sortBy(fn (array $match): string => strtolower((string) ($match['qualification_name'] ?? '')))
                        ->values(),
                ];
            })
            ->sortBy(fn (array $university): string => strtolower($university['name']))
            ->values();
        $institutionColumnSize = max(1, (int) ceil($universities->count() / 3));
        $leftInstitutions = $universities->take($institutionColumnSize)->values();
        $middleInstitutions = $universities->slice($institutionColumnSize, $institutionColumnSize)->values();
        $rightInstitutions = $universities->slice($institutionColumnSize * 2)->values();
        $institutionRowCount = max($leftInstitutions->count(), $middleInstitutions->count(), $rightInstitutions->count());
        $institutionIndexes = $institutionRowCount > 0 ? range(0, $institutionRowCount - 1) : [];
    @endphp

    <div class="footer">
        Chamu Course Matcher Report - guidance only. Confirm final requirements with the institution. Page <span class="page-number"></span>
    </div>

    <div class="header">
        <div class="brand">
            <div class="brand-left">
                @if (is_file($brandLogoPath))
                    <img class="logo" src="file://{{ $brandLogoPath }}" alt="Chamu">
                @endif
                <span class="brand-name">Chamu</span>
            </div>
            <div class="brand-right">
                Generated {{ $generatedAt->format('d M Y H:i') }}<br>
                {{ config('app.url') }}
            </div>
        </div>
        <h1 class="title">Course Matcher Report</h1>
        <p class="muted">Prepared for {{ $studentName }} using {{ $termLabel }}.</p>
    </div>

    <div class="summary">
        <div class="summary-cell">
            <p class="label">Qualified matches</p>
            <p class="value">{{ number_format((int) $courseMatch['qualified_count']) }}</p>
        </div>
        <div class="summary-cell">
            <p class="label">Institutions</p>
            <p class="value">{{ number_format($universities->count()) }}</p>
        </div>
        <div class="summary-cell">
            <p class="label">APS</p>
            <p class="value">{{ number_format((int) $courseMatch['aps_total']) }}</p>
        </div>
        <div class="summary-cell">
            <p class="label">Average</p>
            <p class="value">{{ $courseMatch['average_mark'] === null ? 'N/A' : number_format((float) $courseMatch['average_mark'], 1).'%' }}</p>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">AI Review</h2>
        <div class="review">{{ $courseReview }}</div>
    </div>

    <div class="section page-break">
        <h2 class="section-title">Contents & Context</h2>
        <p class="muted">This report uses the marks Chamu selected as the most academically recent saved term, not simply the marks uploaded most recently. Course links open Chamu's qualification pages where available.</p>

        <div class="section">
            <table class="toc">
                <tbody>
                    <tr>
                        <td>Report snapshot</td>
                        <td>Student, term used, APS, average, and total matching courses.</td>
                    </tr>
                    <tr>
                        <td>AI review</td>
                        <td>A short learner review based on saved marks, trend, and matching course count.</td>
                    </tr>
                    <tr>
                        <td>Grade graph</td>
                        <td>Saved APS progress by grade and term, excluding Life Orientation.</td>
                    </tr>
                    <tr>
                        <td>Saved marks</td>
                        <td>The subject marks used for this Course Matcher report.</td>
                    </tr>
                    <tr>
                        <td>Qualified courses</td>
                        <td>Only courses where the saved marks meet Chamu's stored score and subject checks.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3 class="section-title">University Sections</h3>
            <table class="institution-list">
                <thead>
                    <tr>
                        <th>Institution</th>
                        <th>Courses</th>
                        <th>Institution</th>
                        <th>Courses</th>
                        <th>Institution</th>
                        <th>Courses</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($institutionIndexes as $index)
                        @php
                            $leftInstitution = $leftInstitutions->get($index);
                            $middleInstitution = $middleInstitutions->get($index);
                            $rightInstitution = $rightInstitutions->get($index);
                        @endphp
                        <tr>
                            <td>{{ $leftInstitution ? ($leftInstitution['abbreviation'] ?: $leftInstitution['name']) : '' }}</td>
                            <td>{{ $leftInstitution ? number_format($leftInstitution['count']) : '' }}</td>
                            <td>{{ $middleInstitution ? ($middleInstitution['abbreviation'] ?: $middleInstitution['name']) : '' }}</td>
                            <td>{{ $middleInstitution ? number_format($middleInstitution['count']) : '' }}</td>
                            <td>{{ $rightInstitution ? ($rightInstitution['abbreviation'] ?: $rightInstitution['name']) : '' }}</td>
                            <td>{{ $rightInstitution ? number_format($rightInstitution['count']) : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No matching institutions were found for these saved marks.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($progress->isNotEmpty())
        <div class="section page-break">
            <h2 class="section-title">Grade Graph</h2>
            <table class="bar-table">
                <tbody>
                @foreach ($progress as $point)
                    @php $width = max(3, min(100, ((int) $point->aps_total / $maxAps) * 100)); @endphp
                    <tr>
                        <td class="bar-label">{{ $point->label }}</td>
                        <td>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: {{ $width }}%;"></div>
                            </div>
                        </td>
                        <td class="bar-value">{{ (int) $point->aps_total }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="section">
        <h2 class="section-title">Saved Marks</h2>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Mark</th>
                    <th>APS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results as $result)
                    <tr>
                        <td>{{ $result->name }}</td>
                        <td>{{ $result->mark }}%</td>
                        <td>{{ $result->aps_score }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section page-break">
        <h2 class="section-title">Qualified Courses By University</h2>
        @forelse ($universities as $university)
            <div class="university-section">
                <div class="university-head">
                    <table class="uni-head-table">
                        <tr>
                            <td class="uni-logo-cell">
                                @if (! empty($university['logo_path']) && is_file($university['logo_path']))
                                    <img class="uni-logo" src="file://{{ $university['logo_path'] }}" alt="">
                                @else
                                    <div class="uni-fallback">{{ $university['initials'] }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="uni-name">{{ $university['name'] }}</div>
                                <p class="muted small">{{ number_format($university['count']) }} qualified {{ $university['count'] === 1 ? 'course' : 'courses' }}</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 39%;">Qualification</th>
                            <th style="width: 18%;">Score</th>
                            <th style="width: 43%;">Requirements Met</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($university['matches'] as $match)
                            @php
                                $requirements = collect($match['requirements'] ?? [])->take(3)->implode('; ');
                                $actualScore = (string) ($match['actual_score'] ?? 'N/A');
                                $requiredScore = (string) ($match['required_score'] ?? 'N/A');
                                $scoreText = $actualScore === 'N/A' && $requiredScore === 'N/A'
                                    ? (string) $match['score_label']
                                    : $match['score_label'].' '.$actualScore.' / '.$requiredScore;
                            @endphp
                            <tr>
                                <td>
                                    <div class="course-name"><a href="{{ $match['url'] }}">{{ $match['qualification_name'] }}</a></div>
                                    @if ($match['faculty_name'])
                                        <div class="muted small">{{ $match['faculty_name'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $scoreText }}</td>
                                <td class="requirements">{{ \Illuminate\Support\Str::limit($requirements ?: 'Listed checks met', 230) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p class="muted">No qualified courses were found for these saved marks.</p>
        @endforelse
    </div>
</body>
</html>
