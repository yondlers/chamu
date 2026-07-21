@php
    $adsenseAllowed = ($adsenseEnabled ?? null) ?? (
        request()->routeIs(
            'aps.index',
            'aps-calculator.index',
            'bursaries.index',
            'bursaries.show',
            'learn.index',
            'guides.*',
            'public.universities.show',
            'public.qualifications.show',
        ) || (request()->routeIs('content.index') && request()->filled('subject_id'))
    );
@endphp

@if ($adsenseAllowed)
    <aside class="adsense-placement {{ $class ?? 'my-6' }}" aria-label="Advertisement">
        <!-- yondlers_ads_AdSense1_1x1_as -->
        <ins class="adsbygoogle"
            style="display:block"
            data-ad-client="ca-pub-4352231193802470"
            data-ad-slot="2102293393"
            data-ad-format="auto"
            data-full-width-responsive="true"></ins>
        <script>
            (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
    </aside>
@endif
