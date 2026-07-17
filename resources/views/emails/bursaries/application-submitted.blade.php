<!doctype html>
<html>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <p>Good day,</p>

    <p>
        Chamu is applying on behalf of {{ $application->applicant_name }}
        for the {{ $application->bursary->title }}.
    </p>

    <p>Please find attached the required documents for this bursary application.</p>

    <h2 style="font-size: 16px;">Applicant information</h2>
    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
        <tr><td><strong>Name</strong></td><td>{{ $application->applicant_name }}</td></tr>
        <tr><td><strong>Email</strong></td><td>{{ $application->applicant_email }}</td></tr>
        @if ($application->applicant_phone)
            <tr><td><strong>Phone</strong></td><td>{{ $application->applicant_phone }}</td></tr>
        @endif
        @if ($application->study_level)
            <tr><td><strong>Study level</strong></td><td>{{ $application->study_level }}</td></tr>
        @endif
        @if ($application->institution)
            <tr><td><strong>Institution</strong></td><td>{{ $application->institution }}</td></tr>
        @endif
        @if ($application->qualification)
            <tr><td><strong>Qualification</strong></td><td>{{ $application->qualification }}</td></tr>
        @endif
        @if ($application->current_year)
            <tr><td><strong>Current year</strong></td><td>{{ $application->current_year }}</td></tr>
        @endif
        @if ($application->household_income)
            <tr><td><strong>Household income context</strong></td><td>{{ $application->household_income }}</td></tr>
        @endif
        <tr><td><strong>SASSA recipient</strong></td><td>{{ $application->sassa_recipient ? 'Yes' : 'No' }}</td></tr>
    </table>

    @if ($application->funding_need)
        <h2 style="font-size: 16px;">Funding need</h2>
        <p>{{ $application->funding_need }}</p>
    @endif

    @if (! empty($application->special_circumstances))
        <h2 style="font-size: 16px;">Special cases</h2>
        <ul>
            @foreach ($application->special_circumstances as $circumstance)
                <li>{{ Str::of($circumstance)->replace('_', ' ')->title() }}</li>
            @endforeach
        </ul>
    @endif

    <h2 style="font-size: 16px;">Attached documents</h2>
    <ul>
        @foreach ($application->documents as $document)
            <li>{{ $document->requirement?->label ?? Str::of($document->document_key)->replace('_', ' ')->title() }}: {{ $document->original_name }}</li>
        @endforeach
    </ul>

    <p>
        Please reply directly to {{ $application->applicant_name }} at
        {{ $application->applicant_email }} if more information is needed.
    </p>

    <p>Kind regards,<br>Chamu Applications</p>
</body>
</html>
