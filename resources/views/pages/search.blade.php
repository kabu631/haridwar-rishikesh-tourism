@extends('layouts.app')

@php
    use App\Enums\PageType;

    $filters = [
        null => 'All',
        PageType::Package->value => 'Tour packages',
        PageType::Attraction->value => 'Places & temples',
        PageType::Guide->value => 'Travel guides',
        PageType::Destination->value => 'Destinations',
        PageType::Festival->value => 'Festivals',
        PageType::Hotel->value => 'Hotels',
        PageType::Blog->value => 'Blog',
    ];
@endphp

@section('content')
    <section class="bg-gradient-to-b from-brand-950 to-brand-900 text-white">
        <div class="container-x py-10 sm:py-12">
            <p class="text-xs font-semibold tracking-[0.18em] text-saffron-300 uppercase">Site search</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-balance sm:text-4xl">
                @if ($query !== '')
                    Search results for “{{ $query }}”
                @else
                    Search Haridwar Rishikesh Tourism
                @endif
            </h1>
        </div>
    </section>

    {{-- On phones the search card comes first and the packages last; from "lg" both sit in a sticky right sidebar. --}}
    <div class="container-x grid gap-8 py-10 sm:py-14 lg:grid-cols-[minmax(0,1fr)_21rem] lg:items-start lg:gap-10">
        <div class="min-w-0 lg:col-start-1 lg:row-start-1">
            @if ($query !== '')
                <nav class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 scrollbar-none sm:mx-0 sm:flex-wrap sm:px-0" aria-label="Filter results">
                    @foreach ($filters as $value => $label)
                        <a href="{{ route('search', array_filter(['q' => $query, 'type' => $value]), false) }}"
                           @class([
                               'shrink-0 rounded-full px-4 py-2 text-sm font-medium ring-1 transition',
                               'bg-brand-600 text-white ring-brand-600' => $type === ($value ?: null),
                               'bg-white text-ink-700 ring-ink-900/10 hover:text-brand-700 hover:ring-brand-300' => $type !== ($value ?: null),
                           ])
                           @if ($type === ($value ?: null)) aria-current="true" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
            @endif

            @if ($results && $results->total() > 0)
                <p class="mt-6 text-sm text-ink-500" role="status">{{ number_format($results->total()) }} {{ \Illuminate\Support\Str::plural('page', $results->total()) }} found</p>
                <ol class="mt-4 space-y-4">
                    @foreach ($results as $result)
                        <li>
                            <article class="group relative flex gap-4 rounded-2xl bg-white p-4 ring-1 ring-ink-900/5 transition hover:shadow-card sm:gap-6 sm:p-5">
                                <div class="hidden w-40 shrink-0 overflow-hidden rounded-xl bg-sand-200 sm:block">
                                    <x-picture :src="$result->extra['thumbnail'] ?? $result->hero_image" :alt="$result->title" sizes="160px" class="aspect-[4/3] size-full object-cover" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2 text-xs">
                                        <span class="rounded-full bg-saffron-50 px-2.5 py-0.5 font-semibold text-saffron-700 ring-1 ring-saffron-200">{{ $result->type?->badge() }}</span>
                                        @if ($result->parent)
                                            <span class="text-ink-500">{{ $result->parent->label() }}</span>
                                        @endif
                                    </p>
                                    <h2 class="mt-2 font-display text-lg font-semibold text-ink-900 group-hover:text-brand-700">
                                        <a href="{{ $result->url() }}" class="after:absolute after:inset-0">{{ $result->title }}</a>
                                    </h2>
                                    <p class="mt-1.5 line-clamp-2 text-[15px] leading-relaxed text-ink-500">{!! $snippets[$result->id] ?? e($result->teaser()) !!}</p>
                                    <p class="mt-2 truncate text-xs text-ink-500">{{ parse_url(config('seo.site_url'), PHP_URL_HOST) }}{{ $result->url() }}</p>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ol>
                <div class="mt-10">{{ $results->links() }}</div>
            @else
                @if ($query !== '')
                    <div class="mt-6 rounded-3xl bg-white p-8 text-center ring-1 ring-ink-900/5">
                        <p class="font-display text-2xl font-semibold text-ink-900">No pages matched “{{ $query }}”</p>
                        <p class="mt-2 text-ink-500">Try a shorter phrase, check the spelling, or call us – we know every ghat and trail in Haridwar and Rishikesh.</p>
                        <a href="{{ $site->phoneHref() }}" class="btn-primary mt-6"><x-glyph name="phone" class="size-4" /> {{ $site->get('phone') }}</a>
                    </div>
                @endif
                <section @class(['mt-10' => $query !== '']) aria-labelledby="popular-heading">
                    <h2 id="popular-heading" class="section-title text-2xl">Popular searches</h2>
                    <ul class="mt-5 flex flex-wrap gap-3">
                        @foreach ($popular as $item)
                            <li><a href="{{ $item->url() }}" class="chip px-4 py-2 text-sm hover:bg-saffron-50 hover:text-brand-700">{{ $item->label() }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <aside class="contents lg:col-start-2 lg:row-start-1 lg:block lg:space-y-6 lg:[@media(min-height:56rem)]:sticky lg:[@media(min-height:56rem)]:top-28 xl:[@media(min-height:56rem)]:top-36" aria-label="Search and popular packages">
            {{-- No overflow-hidden here: the instant-search suggestions drop down over the card below. --}}
            <section class="-order-1 rounded-2xl bg-white p-5 shadow-card ring-1 ring-ink-900/5 lg:order-none" aria-labelledby="sidebar-search-heading">
                <h2 id="sidebar-search-heading" class="flex items-center gap-2 font-display text-lg font-semibold text-ink-900">
                    <x-glyph name="search" class="size-5 text-brand-600" /> {{ $query !== '' ? 'Search again' : 'What are you looking for?' }}
                </h2>
                <x-search-box id="page-search" :value="$query" :autofocus="$query === ''" class="mt-4" />
            </section>

            @if ($highlights->isNotEmpty())
                <section class="card order-1 lg:order-none" aria-labelledby="highlights-heading">
                    <div class="border-b border-ink-900/5 px-5 pt-5 pb-4">
                        <p class="eyebrow">Most booked</p>
                        <h2 id="highlights-heading" class="mt-1 font-display text-lg font-semibold text-ink-900">Popular tour packages</h2>
                    </div>
                    <ul class="divide-y divide-ink-900/5">
                        @foreach ($highlights as $package)
                            @php($duration = $package->duration())
                            <li>
                                <a href="{{ $package->url() }}" class="group flex items-center gap-3.5 px-5 py-3 transition hover:bg-sand-50">
                                    <span class="size-14 shrink-0 overflow-hidden rounded-xl bg-sand-200">
                                        <x-picture :src="$package->extra['thumbnail'] ?? $package->hero_image" :alt="$package->extra['thumbnail_alt'] ?? $package->hero_alt ?? ''" sizes="56px" class="size-full object-cover transition duration-300 group-hover:scale-105" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="line-clamp-2 text-sm leading-snug font-semibold text-ink-900 group-hover:text-brand-700">{{ $package->shortTitle() }}</span>
                                        <span class="mt-1 flex items-center gap-1 text-xs text-ink-500">
                                            <x-glyph name="clock" class="size-3.5 text-saffron-600" />
                                            {{ $duration ? $duration['nights'].'N / '.$duration['days'].'D' : 'Flexible dates' }}
                                        </span>
                                    </span>
                                    <x-glyph name="chevron-right" class="size-4 text-ink-400 transition group-hover:translate-x-0.5 group-hover:text-brand-600" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <a href="/tour-packages.html" class="flex items-center justify-between border-t border-ink-900/5 bg-sand-50 px-5 py-3.5 text-sm font-semibold text-brand-700 transition hover:bg-sand-100">
                        All tour packages <x-glyph name="arrow-right" class="size-4" />
                    </a>
                </section>
            @endif
        </aside>
    </div>
@endsection
