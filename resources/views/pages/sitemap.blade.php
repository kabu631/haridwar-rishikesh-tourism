@extends('layouts.app')

@section('content')
    <section class="bg-gradient-to-br from-brand-950 to-brand-800 text-white">
        <div class="container-x py-12 sm:py-16">
            <h1 class="font-display text-3xl font-semibold sm:text-5xl">{{ $page?->title ?? 'Sitemap' }}</h1>
            <p class="mt-3 max-w-2xl text-lg text-white/80">{{ filled($page?->excerpt) ? $page->excerpt : 'Every guide, tour package and travel page on '.$site->get('name').'.' }}</p>
        </div>
    </section>
    @if ($content !== '')
        <div class="container-x pt-12">
            <div class="prose-content max-w-3xl">{!! $content !!}</div>
        </div>
    @endif
    <div class="container-x columns-1 gap-8 py-12 sm:columns-2 lg:columns-3">
        @foreach ($sections as $section)
            <section class="mb-8 break-inside-avoid rounded-3xl bg-white p-6 ring-1 ring-ink-900/5">
                <h2 class="font-display text-xl font-semibold text-ink-900">{{ $section['label'] }}</h2>
                <ul class="mt-3 space-y-1.5 text-[15px]">
                    @foreach ($section['pages'] as $item)
                        <li><a href="{{ $item->url() }}" class="text-ink-700 hover:text-brand-700 hover:underline">{{ $item->title }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
@endsection
