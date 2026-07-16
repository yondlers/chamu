<meta name="description" content="{{ $description }}">
@if ($robots)
    <meta name="robots" content="{{ $robots }}">
@endif
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="website">
@if ($image)
    <meta property="og:image" content="{{ $image }}">
@endif

<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
@if ($image)
    <meta name="twitter:image" content="{{ $image }}">
@endif

@if ($jsonLd)
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
