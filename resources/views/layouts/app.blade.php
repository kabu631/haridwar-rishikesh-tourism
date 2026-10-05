<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.seo')
    @stack('preload')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#6e1c20">
    <link rel="alternate" type="application/rss+xml" title="{{ $site->get('name') }} – latest guides" href="{{ rtrim(config('seo.site_url'), '/') }}/feed.xml">
    @php($analyticsIds = array_filter([$site->get('ga4_id'), $site->get('google_ads_id')]))
    @if ($analyticsIds && app()->isProduction())
        <meta name="analytics-ids" content="{{ implode(',', $analyticsIds) }}">
        @if (config('services.google_ads.conversion_label'))
            <meta name="ads-conversion" content="{{ $site->get('google_ads_id') }}/{{ config('services.google_ads.conversion_label') }}">
        @endif
    @endif
</head>
<body class="min-h-screen pb-16 lg:pb-0">
    <a href="#main" class="sr-only z-[60] rounded-full bg-brand-700 px-5 py-3 font-semibold text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>

    @include('partials.header')

    <main id="main" tabindex="-1" class="focus:outline-none">
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.mobile-bar')
    @include('partials.floating-actions')
    @include('partials.drawer')
    @include('partials.search-overlay')
</body>
</html>
