@php
    $contactUniversity = $university ?? null;
    $hasContactDetails = $contactUniversity?->hasContactDetails() ?? false;
    $hasMap = $contactUniversity?->hasMapCoordinates() ?? false;
    $mapId = 'university-contact-map-'.($contactUniversity?->id ?? 'unknown');
    $phoneHref = filled($contactUniversity?->contact_phone)
        ? 'tel:'.preg_replace('/[^\d+]/', '', (string) $contactUniversity->contact_phone)
        : null;
    $addressLines = collect([
        $contactUniversity?->physical_address,
    ])
        ->filter(fn ($value) => filled($value))
        ->flatMap(fn ($value) => preg_split("/\r\n|\n|\r/", (string) $value) ?: [])
        ->map(fn ($line) => trim((string) $line))
        ->filter()
        ->values();
@endphp

@if ($hasContactDetails)
    @once
        @push('styles')
            <link
                rel="stylesheet"
                href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
                integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
                crossorigin=""
            />
            <style>
                .university-contact-map {
                    min-height: 280px;
                    z-index: 0;
                }
                .university-contact-map .leaflet-control-attribution {
                    font-size: 10px;
                }
            </style>
        @endpush

        @push('scripts')
            <script
                src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
                crossorigin=""
            ></script>
            <script>
                document.querySelectorAll('[data-university-map]').forEach((el) => {
                    if (typeof L === 'undefined' || el.dataset.mapReady === '1') {
                        return;
                    }

                    const lat = Number(el.dataset.lat);
                    const lng = Number(el.dataset.lng);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                        return;
                    }

                    const map = L.map(el, {
                        scrollWheelZoom: false,
                        attributionControl: true,
                    }).setView([lat, lng], 13);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    }).addTo(map);

                    L.marker([lat, lng]).addTo(map);
                    el.dataset.mapReady = '1';

                    requestAnimationFrame(() => map.invalidateSize());
                });
            </script>
        @endpush
    @endonce

    <section class="{{ $sectionClass ?? 'mx-auto max-w-7xl px-4 pb-6 sm:px-5 lg:px-8' }}" aria-labelledby="contact-university-heading">
        <div class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
            <div class="border-b border-neutral-200 px-6 py-5">
                <h2 id="contact-university-heading" class="text-2xl font-bold text-neutral-950">Contact university</h2>
                <p class="mt-2 text-sm font-semibold text-neutral-500">Campus location and published contact details for {{ $contactUniversity->name }}.</p>
            </div>

            <div class="grid lg:grid-cols-2">
                @if ($hasMap)
                    <div
                        id="{{ $mapId }}"
                        class="university-contact-map h-full min-h-[280px] w-full bg-neutral-100 lg:min-h-[420px]"
                        data-university-map
                        data-lat="{{ $contactUniversity->latitude }}"
                        data-lng="{{ $contactUniversity->longitude }}"
                        role="img"
                        aria-label="Map showing {{ $contactUniversity->name }}"
                    ></div>
                @else
                    <div class="flex min-h-[220px] items-center justify-center bg-neutral-50 px-6 py-10 text-center lg:min-h-[420px]">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wide text-neutral-500">Map</p>
                            <p class="mt-2 text-sm font-semibold text-neutral-600">Campus coordinates are not available yet.</p>
                        </div>
                    </div>
                @endif

                <div class="space-y-8 px-6 py-6">
                    @if ($contactUniversity->contact_email || $contactUniversity->contact_phone)
                        <div>
                            <div class="mb-3 h-1 w-10 rounded-full bg-neutral-950"></div>
                            <h3 class="text-xl font-bold text-neutral-950">Contact details</h3>
                            <p class="mt-1 text-xs font-bold uppercase tracking-wide text-neutral-500">University contact</p>
                            <div class="mt-4 grid gap-3">
                                @if ($contactUniversity->contact_email)
                                    <a
                                        href="mailto:{{ $contactUniversity->contact_email }}"
                                        class="inline-flex items-center gap-3 rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm font-bold text-neutral-950 hover:bg-neutral-50"
                                    >
                                        <i data-lucide="mail" style="width:16px;height:16px;"></i>
                                        <span class="break-all">{{ $contactUniversity->contact_email }}</span>
                                    </a>
                                @endif
                                @if ($contactUniversity->contact_phone && $phoneHref)
                                    <a
                                        href="{{ $phoneHref }}"
                                        class="inline-flex items-center gap-3 rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm font-bold text-neutral-950 hover:bg-neutral-50"
                                    >
                                        <i data-lucide="phone" style="width:16px;height:16px;"></i>
                                        <span>{{ $contactUniversity->contact_phone }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($addressLines->isNotEmpty())
                        <div>
                            <div class="mb-3 h-1 w-10 rounded-full bg-neutral-950"></div>
                            <h3 class="text-xl font-bold text-neutral-950">{{ $contactUniversity->name }}</h3>
                            <address class="mt-3 space-y-1 text-sm font-semibold not-italic leading-6 text-neutral-700">
                                @foreach ($addressLines as $line)
                                    <span class="block">{{ $line }}</span>
                                @endforeach
                            </address>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        @if ($contactUniversity->website)
                            <a
                                href="{{ $contactUniversity->website }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center justify-center gap-2 rounded-full bg-[#01225E] px-5 py-3 text-sm font-bold text-white hover:bg-[#001A48]"
                            >
                                Visit website <i data-lucide="external-link" style="width:15px;height:15px;"></i>
                            </a>
                        @endif
                        @if (! empty($qualification?->slug) && ! empty($contactUniversity->slug))
                            @php
                                $contactEntityLabel = $contactUniversity->isTvetCollege() ? 'College page' : 'University page';
                            @endphp
                            <a
                                href="{{ route('public.universities.show', $contactUniversity) }}"
                                class="inline-flex items-center justify-center gap-2 rounded-full border border-neutral-300 bg-white px-5 py-3 text-sm font-bold text-neutral-950 hover:bg-neutral-50"
                            >
                                {{ $contactEntityLabel }} <i data-lucide="building-2" style="width:15px;height:15px;"></i>
                            </a>
                        @endif
                    </div>

                    @if ($contactUniversity->contact_source_url)
                        @php
                            $contactSourceHost = parse_url((string) $contactUniversity->contact_source_url, PHP_URL_HOST) ?: 'official directory';
                            $contactSourceLabel = str_contains(strtolower((string) $contactSourceHost), 'dhet.gov.za')
                                ? 'DHET TVET college directory'
                                : (str_contains(strtolower((string) $contactSourceHost), 'education.gov.za')
                                    ? 'education.gov.za universities list'
                                    : $contactSourceHost);
                        @endphp
                        <p class="text-xs font-semibold leading-5 text-neutral-500">
                            Contact directory sourced from
                            <a href="{{ $contactUniversity->contact_source_url }}" target="_blank" rel="noopener noreferrer" class="underline hover:text-neutral-800">
                                {{ $contactSourceLabel }}
                            </a>
                            · Map tiles © OpenStreetMap
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
