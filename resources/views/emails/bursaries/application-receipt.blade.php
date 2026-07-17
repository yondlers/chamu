<!doctype html>
<html>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <p>Hi {{ $application->applicant_name }},</p>

    <p>
        Chamu sent your application for {{ $application->bursary->title }}
        to {{ $application->provider_email }}.
    </p>

    <h2 style="font-size: 16px;">Documents included</h2>
    <ul>
        @foreach ($application->documents as $document)
            <li>{{ $document->requirement?->label ?? Str::of($document->document_key)->replace('_', ' ')->title() }}: {{ $document->original_name }}</li>
        @endforeach
    </ul>

    <p>
        Your email address was added as the reply-to address, so the bursary provider
        can contact you directly.
    </p>

    <p>Keep this receipt for your records.</p>
</body>
</html>
