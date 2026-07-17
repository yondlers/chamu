<!doctype html>
<html>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <p>Hi {{ $application->applicant_name }},</p>

    @if ($application->delivery_type === 'postal')
        <p>
            Chamu prepared your postal application record for {{ $application->bursary->title }}.
        </p>

        @if ($application->provider_postal_address)
            <p><strong>Provider postal instructions:</strong><br>{{ $application->provider_postal_address }}</p>
        @endif

        @if ($application->applicant_postal_address)
            <p><strong>Your postal or return address:</strong><br>{{ $application->applicant_postal_address }}</p>
        @endif
    @else
        <p>
            Chamu sent your application for {{ $application->bursary->title }}
            to {{ $application->provider_email }}.
        </p>
    @endif

    <h2 style="font-size: 16px;">Documents included</h2>
    <ul>
        @foreach ($application->documents as $document)
            <li>{{ $document->requirement?->label ?? Str::of($document->document_key)->replace('_', ' ')->title() }}: {{ $document->original_name }}</li>
        @endforeach
    </ul>

    @if ($application->delivery_type !== 'postal')
        <p>
            Your email address was added as the reply-to address, so the bursary provider
            can contact you directly.
        </p>
    @endif

    <p>Keep this receipt for your records.</p>
</body>
</html>
