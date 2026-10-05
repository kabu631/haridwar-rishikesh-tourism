{{-- Search, social and AI-engine metadata. $seo is an App\Support\Seo\SeoData. --}}
<title>{{ $seo->title }}</title>
@if ($seo->description)
    <meta name="description" content="{{ $seo->description }}">
@endif
@if ($seo->keywords)
    <meta name="keywords" content="{{ $seo->keywords }}">
@endif
<meta name="robots" content="{{ $seo->robots }}">
@if ($seo->canonical)
    <link rel="canonical" href="{{ $seo->canonical }}">
    @if ($seo->hreflang)
        @foreach (config('seo.hreflang') as $lang)
            <link rel="alternate" hreflang="{{ $lang }}" href="{{ $seo->canonical }}">
        @endforeach
    @endif
@endif

{{-- Local / geo targeting --}}
<meta name="geo.region" content="{{ config('site.address.region_code') }}">
<meta name="geo.placename" content="{{ $seo->placename ?? config('site.address.locality') }}">
<meta name="geo.position" content="{{ $site->get('geo.latitude') }};{{ $site->get('geo.longitude') }}">
<meta name="ICBM" content="{{ $site->get('geo.latitude') }}, {{ $site->get('geo.longitude') }}">

{{-- Open Graph --}}
<meta property="og:site_name" content="{{ $site->get('name') }}">
<meta property="og:locale" content="{{ config('seo.locale') }}">
<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:title" content="{{ $seo->title }}">
@if ($seo->description)
    <meta property="og:description" content="{{ $seo->description }}">
@endif
@if ($seo->canonical)
    <meta property="og:url" content="{{ $seo->canonical }}">
@endif
@if ($seo->image)
    <meta property="og:image" content="{{ $seo->image }}">
    <meta property="og:image:alt" content="{{ $seo->imageAlt ?? $seo->title }}">
    @if ($seo->imageWidth && $seo->imageHeight)
        <meta property="og:image:width" content="{{ $seo->imageWidth }}">
        <meta property="og:image:height" content="{{ $seo->imageHeight }}">
    @endif
@endif
@if ($seo->ogType === 'article')
    @if ($seo->publishedTime)
        <meta property="article:published_time" content="{{ $seo->publishedTime }}">
    @endif
    @if ($seo->modifiedTime)
        <meta property="article:modified_time" content="{{ $seo->modifiedTime }}">
    @endif
    <meta property="article:publisher" content="{{ $site->get('social.facebook') }}">
@endif

{{-- Twitter / X --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="{{ config('seo.twitter_handle') }}">
<meta name="twitter:title" content="{{ $seo->title }}">
@if ($seo->description)
    <meta name="twitter:description" content="{{ $seo->description }}">
@endif
@if ($seo->image)
    <meta name="twitter:image" content="{{ $seo->image }}">
@endif

{{-- Search engine verification --}}
@if ($site->get('google_verification'))
    <meta name="google-site-verification" content="{{ $site->get('google_verification') }}">
@endif
@if ($site->get('bing_verification'))
    <meta name="msvalidate.01" content="{{ $site->get('bing_verification') }}">
@endif
@if (config('seo.verification.yandex'))
    <meta name="yandex-verification" content="{{ config('seo.verification.yandex') }}">
@endif

@if ($seo->schema)
    <script type="application/ld+json">{!! $seo->jsonLd() !!}</script>
@endif
