<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Chamu Course Matcher Report</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #171717; font-size: 11px; line-height: 1.45; }
        h1, h2, h3, p { margin: 0; }
        a { color: #01225E; text-decoration: none; }
        .header { border-bottom: 2px solid #01225E; padding-bottom: 14px; margin-bottom: 18px; }
        .brand { display: table; width: 100%; }
        .brand-left, .brand-right { display: table-cell; vertical-align: middle; }
        .brand-right { text-align: right; color: #737373; font-size: 10px; }
        .logo { width: 38px; height: 38px; vertical-align: middle; border-radius: 8px; }
        .brand-name { display: inline-block; margin-left: 8px; font-size: 18px; font-weight: bold; vertical-align: middle; }
        .title { margin-top: 14px; font-size: 24px; color: #01225E; }
        .muted { color: #666; }
        .grid { display: table; width: 100%; border-spacing: 8px; margin: 0 -8px; }
        .cell { display: table-cell; width: 25%; border: 1px solid #e5e5e5; border-radius: 8px; padding: 10px; }
        .label { color: #666; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .value { margin-top: 4px; font-size: 18px; font-weight: bold; }
        .section { margin-top: 18px; }
        .section h2 { font-size: 15px; color: #01225E; margin-bottom: 8px; }
        .review { border: 1px solid #bfdbfe; background: #eff6ff; border-radius: 8px; padding: 12px; color: #01225E; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f5f5f5; color: #525252; font-size: 9px; text-transform: uppercase; padding: 7px; border-bottom: 1px solid #ddd; }
        td { padding: 8px 7px; border-bottom: 1px solid #eee; vertical-align: top; }
        .uni-logo { width: 26px; height: 26px; object-fit: contain; vertical-align: middle; margin-right: 6px; }
        .uni-fallback { display: inline-block; width: 26px; height: 26px; border-radius: 6px; background: #01225E; color: white; text-align: center; line-height: 26px; font-size: 9px; font-weight: bold; margin-right: 6px; }
        .pill { display: inline-block; border-radius: 999px; background: #f5f5f5; padding: 3px 7px; margin: 2px 3px 2px 0; font-size: 9px; font-weight: bold; color: #404040; }
        .bar-table td { border-bottom: 0; padding: 5px 7px; }
        .bar-label { width: 110px; font-weight: bold; color: #404040; }
        .bar-track { height: 14px; border-radius: 999px; background: #e5e7eb; overflow: hidden; }
        .bar-fill { height: 14px; border-radius: 999px; background: #01225E; }
        .bar-value { width: 46px; text-align: right; font-weight: bold; }
        .page-break { page-break-before: always; }
        .footer { margin-top: 18px; color: #737373; font-size: 9px; border-top: 1px solid #eee; padding-top: 8px; }
    </style>
</head>
<body>
    @php
        $results = collect($courseMatch['results'] ?? []);
        $progress = collect($courseMatch['progress'] ?? []);
        $matches = collect($courseMatch['matches'] ?? []);
        $maxAps = max(42, (int) ($progress->max('aps_total') ?? 0));
    @endphp

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
        <p class="muted">Prepared for {{ $user->name ?: $user->email }} using {{ $courseMatch['term']->label ?? 'latest saved marks' }}.</p>
    </div>

    <div class="grid">
        <div class="cell">
            <p class="label">Qualified matches</p>
            <p class="value">{{ number_format((int) $courseMatch['qualified_count']) }}</p>
        </div>
        <div class="cell">
            <p class="label">APS</p>
            <p class="value">{{ number_format((int) $courseMatch['aps_total']) }}</p>
        </div>
        <div class="cell">
            <p class="label">Average</p>
            <p class="value">{{ $courseMatch['average_mark'] === null ? 'N/A' : number_format((float) $courseMatch['average_mark'], 1).'%' }}</p>
        </div>
        <div class="cell">
            <p class="label">Compared terms</p>
            <p class="value">{{ number_format($progress->count()) }}</p>
        </div>
    </div>

    <div class="section">
        <h2>AI Review</h2>
        <div class="review">{{ $courseReview }}</div>
    </div>

    @if ($progress->isNotEmpty())
        <div class="section">
            <h2>Grade Graph</h2>
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
        <h2>Saved Marks</h2>
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
        <h2>Qualified Courses</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 24%;">University</th>
                    <th style="width: 30%;">Qualification</th>
                    <th style="width: 18%;">Score</th>
                    <th style="width: 28%;">Requirements Met</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($matches as $match)
                    <tr>
                        <td>
                            @if ($match['university_logo_path'])
                                <img class="uni-logo" src="file://{{ $match['university_logo_path'] }}" alt="">
                            @else
                                <span class="uni-fallback">{{ $match['university_initials'] }}</span>
                            @endif
                            {{ $match['university_abbreviation'] ?: $match['university_name'] }}
                        </td>
                        <td>
                            <a href="{{ $match['url'] }}">{{ $match['qualification_name'] }}</a>
                            @if ($match['faculty_name'])
                                <br><span class="muted">{{ $match['faculty_name'] }}</span>
                            @endif
                        </td>
                        <td>{{ $match['score_label'] }} {{ $match['actual_score'] }} / {{ $match['required_score'] }}</td>
                        <td>
                            @foreach ($match['requirements'] as $requirement)
                                <span class="pill">{{ $requirement }}</span>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No qualified courses were found for these saved marks.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        This report is a guidance tool based on Chamu's stored admission rules. Always confirm official requirements with the university before applying.
    </div>
</body>
</html>
