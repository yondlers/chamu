<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Chamu Bursary Report</title>
    <style>
        @page { margin: 24px 26px; }
        body { font-family: DejaVu Sans, sans-serif; color: #171717; font-size: 10px; line-height: 1.38; }
        h1, h2, p { margin: 0; }
        a { color: #01225E; text-decoration: none; }
        .header { border-bottom: 2px solid #01225E; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { display: table; width: 100%; }
        .brand-left, .brand-right { display: table-cell; vertical-align: middle; }
        .brand-right { text-align: right; color: #737373; font-size: 9px; }
        .logo { width: 34px; height: 34px; vertical-align: middle; border-radius: 8px; }
        .brand-name { display: inline-block; margin-left: 8px; font-size: 17px; font-weight: bold; vertical-align: middle; }
        .title { margin-top: 12px; font-size: 22px; color: #01225E; }
        .muted { color: #666; }
        .summary { display: table; width: 100%; border-spacing: 8px; margin: 0 -8px 12px; }
        .summary-card { display: table-cell; width: 33%; border: 1px solid #e5e5e5; border-radius: 8px; padding: 10px; }
        .label { color: #666; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .value { margin-top: 4px; font-size: 17px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f5f5f5; color: #525252; font-size: 8px; text-transform: uppercase; padding: 7px; border-bottom: 1px solid #ddd; }
        td { padding: 7px; border-bottom: 1px solid #eee; vertical-align: top; }
        .footer { margin-top: 14px; color: #737373; font-size: 8px; border-top: 1px solid #eee; padding-top: 8px; }
    </style>
</head>
<body>
    @php
        $bursaries = collect($bursaries);
        $fieldCount = $bursaries->pluck('field')->filter()->unique()->count();
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
        <h1 class="title">Open Bursary Report</h1>
        <p class="muted">Prepared for {{ $user->name ?: $user->email }} from bursaries currently open in Chamu.</p>
    </div>

    <div class="summary">
        <div class="summary-card">
            <p class="label">Open bursaries</p>
            <p class="value">{{ number_format($bursaries->count()) }}</p>
        </div>
        <div class="summary-card">
            <p class="label">Fields</p>
            <p class="value">{{ number_format($fieldCount) }}</p>
        </div>
        <div class="summary-card">
            <p class="label">Applicant</p>
            <p class="value" style="font-size: 13px;">{{ $user->first_name ?: $user->name ?: 'Student' }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 24%;">Bursary</th>
                <th style="width: 18%;">Company</th>
                <th style="width: 28%;">Field</th>
                <th style="width: 14%;">Coverage</th>
                <th style="width: 10%;">Closes</th>
                <th style="width: 6%;">Link</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bursaries as $bursary)
                <tr>
                    <td>{{ $bursary['name'] }}</td>
                    <td>{{ $bursary['company'] }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($bursary['field'], 170) }}</td>
                    <td>{{ $bursary['coverage'] ?: 'Not listed' }}</td>
                    <td>{{ $bursary['closing'] }}</td>
                    <td><a href="{{ $bursary['url'] }}">Open</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No open bursaries were found in Chamu.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        This report lists bursaries currently marked as open in Chamu. Always confirm eligibility, documents, and closing dates before applying.
    </div>
</body>
</html>
