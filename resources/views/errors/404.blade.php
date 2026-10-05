@extends('layouts.app')

@section('content')
    <section class="container-x py-16 sm:py-24">
        <div class="mx-auto max-w-2xl text-center">
            <p class="font-display text-7xl font-semibold text-saffron-500 sm:text-8xl">404</p>
            <h1 class="mt-4 font-display text-3xl font-semibold text-ink-900 sm:text-4xl">This page has wandered off the trail</h1>
            <p class="mt-4 text-lg text-ink-500">The page you were looking for doesn’t exist or has moved. Search our guides and tour packages instead:</p>
            <x-search-box id="notfound-search" size="lg" :value="$searchTerms ?? ''" class="mt-8 text-left" />
        </div>

        @if (($suggestions ?? collect())->isNotEmpty())
            <div class="mx-auto mt-14 max-w-5xl">
                <h2 class="text-center font-display text-2xl font-semibold text-ink-900">{{ filled($searchTerms ?? '') ? 'Were you looking for…' : 'Popular guides' }}</h2>
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($suggestions as $item)
                        <x-page-card :url="$item->url()" :title="$item->title" :text="$item->teaser(120)" :image="$item->extra['thumbnail'] ?? $item->hero_image" :meta="$item->type?->badge()" />
                    @endforeach
                </div>
            </div>
        @endif

        <p class="mt-12 text-center text-ink-500">Or go back to the <a href="/" class="link-underline font-semibold text-ink-900">homepage</a> · call <a href="{{ $site->phoneHref() }}" class="link-underline font-semibold text-ink-900">{{ $site->get('phone') }}</a></p>
    </section>
@endsection
