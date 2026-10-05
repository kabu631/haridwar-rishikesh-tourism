@extends('layouts.app')

@php
    use Illuminate\Support\Str;

    $hero = $home['hero'] ?? [];
    $slides = array_values(array_filter($hero['slides'] ?? [], fn ($slide) => filled($slide['image'] ?? null)));
    if ($slides === [] && filled($hero['image'] ?? null)) {
        $slides = [['image' => $hero['image'], 'image_alt' => $hero['image_alt'] ?? '', 'eyebrow' => $hero['eyebrow'] ?? '', 'title' => $hero['text'] ?? '', 'text' => '']];
    }
@endphp

@section('content')
    {{-- Hero slider (the four slides of the legacy homepage) --}}
    <section class="relative isolate overflow-hidden bg-brand-950 text-white" data-slider data-autoplay="6500" aria-roledescription="carousel" aria-label="Haridwar Rishikesh highlights">
        <div class="absolute inset-x-0 top-0 -z-10 h-[32rem] sm:h-[38rem] lg:inset-0 lg:h-auto">
            @foreach ($slides as $slide)
                <div data-slide @class(['absolute inset-0', 'is-active' => $loop->first]) role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ count($slides) }}">
                    <x-picture :src="$slide['image']" :alt="$slide['image_alt'] ?? ($slide['title'] ?? '')" sizes="100vw" :eager="$loop->first" :priority="$loop->first" class="slide-media size-full object-cover" />
                </div>
            @endforeach
            <div class="absolute inset-0 bg-gradient-to-b from-brand-950/45 via-brand-950/70 to-brand-950 lg:bg-gradient-to-r lg:from-brand-950/95 lg:via-brand-950/70 lg:to-brand-950/10" aria-hidden="true"></div>
            <div class="absolute inset-x-0 bottom-0 hidden h-48 bg-gradient-to-t from-brand-950/80 to-transparent lg:block" aria-hidden="true"></div>
        </div>

        <div class="container-x grid grid-cols-1 items-center gap-8 pt-8 pb-12 sm:gap-10 sm:pt-16 sm:pb-16 lg:min-h-[640px] lg:grid-cols-[1.25fr_0.9fr] lg:pb-20">
            <div class="min-w-0 max-w-2xl lg:self-start lg:pt-6">
                <h1 class="text-xs font-semibold tracking-[0.14em] text-saffron-300 uppercase sm:text-sm">{{ $page->title }}</h1>

                <div class="mt-5 grid">
                    @foreach ($slides as $slide)
                        <div data-slide-sync="{{ $loop->index }}" @class(['col-start-1 row-start-1', 'is-active' => $loop->first]) @unless ($loop->first) aria-hidden="true" @endunless>
                            <div data-slide-caption>
                                @if (filled($slide['eyebrow'] ?? null))
                                    <p class="text-xs font-medium text-white/75 sm:text-sm">{{ $slide['eyebrow'] }}</p>
                                @endif
                                <p @class(['mt-3 font-display leading-[1.08] font-semibold tracking-tight text-balance', 'text-[2.1rem] sm:text-5xl lg:text-[3.4rem]' => mb_strlen($slide['title'] ?? '') <= 45, 'text-[1.7rem] sm:text-4xl lg:text-[2.6rem]' => mb_strlen($slide['title'] ?? '') > 45])>{{ $slide['title'] ?? '' }}</p>
                                @if (filled($slide['text'] ?? null) || filled($slide['button_url'] ?? null))
                                    <div>
                                        @if (filled($slide['text'] ?? null))
                                            <p class="mt-3 line-clamp-2 max-w-xl text-base leading-relaxed text-white/85 sm:mt-4 sm:line-clamp-none sm:text-lg">{{ $slide['text'] }}</p>
                                        @endif
                                        @if (filled($slide['button_url'] ?? null))
                                            <a href="{{ $slide['button_url'] }}" class="btn-primary mt-6">{{ $slide['button_label'] ?? 'Explore' }} <x-glyph name="arrow-right" class="size-4" /></a>
                                        @endif
                                    </div>
                                @endif
                                @if ($loop->first)
                                <dl class="mt-8 hidden max-w-2xl gap-3 sm:grid sm:grid-cols-3">
                                    @foreach (array_slice((array) $site->get('trust'), 0, 3) as $badge)
                                        <div class="flex items-center gap-3 rounded-2xl bg-white/10 px-3.5 py-3 ring-1 ring-white/15 backdrop-blur-sm">
                                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-saffron-400/20 text-saffron-300"><x-glyph :name="$badge['icon'] ?? 'shield'" class="size-[18px]" /></span>
                                            <div class="min-w-0">
                                                <dt class="text-sm leading-tight font-semibold text-white">{{ $badge['title'] ?? '' }}</dt>
                                                <dd class="mt-0.5 line-clamp-2 text-xs leading-snug text-white/65">{{ $badge['text'] ?? '' }}</dd>
                                            </div>
                                        </div>
                                    @endforeach
                                </dl>

                                <p class="mt-5 hidden flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/80 sm:flex">
                                    <a href="{{ $site->phoneHref() }}" class="inline-flex items-center gap-2 font-semibold text-white hover:text-saffron-300"><x-glyph name="phone" class="size-4" /> {{ $site->get('phone') }}</a>
                                    <a href="{{ $site->whatsappUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 font-semibold text-white hover:text-saffron-300"><x-glyph name="whatsapp" class="size-4" /> WhatsApp us</a>
                                    <span>Talk directly to our Haridwar office</span>
                                </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <ul class="scrollbar-none -mx-4 mt-6 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0" aria-label="We arrange">
                    @foreach ($hero['highlights'] ?? [] as $highlight)
                        <li class="shrink-0 rounded-full bg-white/10 px-3 py-1.5 text-[13px] font-medium text-white/90 ring-1 ring-white/20">{{ $highlight }}</li>
                    @endforeach
                </ul>

                @if (count($slides) > 1)
                    <div class="mt-6 flex items-center gap-4 sm:mt-8">
                        <button type="button" data-slider-prev class="grid size-11 place-items-center rounded-full bg-white/10 ring-1 ring-white/25 transition hover:bg-white hover:text-ink-900" aria-label="Previous slide"><x-glyph name="chevron-left" /></button>
                        <div data-slider-dots class="flex items-center gap-2"></div>
                        <button type="button" data-slider-next class="grid size-11 place-items-center rounded-full bg-white/10 ring-1 ring-white/25 transition hover:bg-white hover:text-ink-900" aria-label="Next slide"><x-glyph name="chevron-right" /></button>
                        <span data-slider-status class="sr-only" aria-live="polite"></span>
                    </div>
                @endif
            </div>

            <x-enquiry-form id="hero-enquiry" dark title="Book Tour" subtitle="Get Best Deals From Local Travel Agent" class="lg:justify-self-end lg:max-w-md" />
        </div>

        {{-- Fixed departure offer (badge on the slider, as on the legacy site) --}}
        @if (filled($home['offer']['url'] ?? null) && filled($home['offer']['image'] ?? null))
            <a href="{{ $home['offer']['url'] }}" target="_blank" rel="noopener" class="absolute right-4 bottom-6 z-10 hidden size-28 transition hover:scale-105 xl:block 2xl:right-10" title="{{ $home['offer']['alt'] ?? 'Special offer' }}">
                <x-picture :src="$home['offer']['image']" :alt="$home['offer']['alt'] ?? 'Special offer'" sizes="112px" class="size-full drop-shadow-xl" />
            </a>
        @endif

        <div class="absolute inset-x-0 bottom-0 h-1 overflow-hidden bg-white/10" aria-hidden="true">
            <div data-slider-progress class="h-full origin-left bg-saffron-400"></div>
        </div>
    </section>

    {{-- Trust strip --}}
    <section aria-label="Why travel with us" class="border-b border-ink-900/5 bg-white">
        <div class="container-x grid grid-cols-2 gap-x-6 gap-y-5 py-6 lg:grid-cols-4">
            @foreach ((array) $site->get('trust') as $trust)
                <div class="flex items-center gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-saffron-50 text-saffron-700"><x-glyph :name="$trust['icon'] ?? 'check'" class="size-5" /></span>
                    <p class="leading-tight"><span class="block text-sm font-semibold text-ink-900">{{ $trust['title'] ?? '' }}</span><span class="text-xs text-ink-500">{{ $trust['text'] ?? '' }}</span></p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Promotions (sister-site offers from the legacy homepage) --}}
    @if (! empty($home['promos']) || filled($home['offer']['url'] ?? null))
        <section class="container-x pt-10" aria-label="Special offers">
            <div class="grid gap-4">
                @foreach ($home['promos'] ?? [] as $promo)
                    @continue(blank($promo['url'] ?? null) || blank($promo['image'] ?? null))
                    <a href="{{ $promo['url'] }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-full shadow-card ring-1 ring-ink-900/5 transition hover:-translate-y-0.5 hover:shadow-lift">
                        <img src="{{ $promo['image'] }}" alt="{{ $promo['alt'] ?? '' }}" width="{{ $promo['width'] ?? 1200 }}" height="{{ $promo['height'] ?? 150 }}" loading="lazy" decoding="async" class="h-auto w-full">
                    </a>
                @endforeach
                @if (filled($home['offer']['url'] ?? null))
                    <a href="{{ $home['offer']['url'] }}" target="_blank" rel="noopener" class="flex items-center gap-4 rounded-2xl bg-white p-4 ring-1 ring-ink-900/10 transition hover:shadow-card xl:hidden">
                        <x-picture :src="$home['offer']['image'] ?? null" :alt="$home['offer']['alt'] ?? ''" sizes="64px" class="size-16 shrink-0" />
                        <span class="font-semibold text-ink-900">{{ Str::ucfirst($home['offer']['alt'] ?? 'Special offer') }} <x-glyph name="arrow-up-right" class="inline size-4 text-brand-700" /></span>
                    </a>
                @endif
            </div>
        </section>
    @endif

    {{-- Latest updates + other packages --}}
    <section class="container-x py-16 sm:py-20">
        <div class="grid gap-12 lg:grid-cols-2">
            @foreach (['latest', 'other'] as $block)
                @if (! empty($home[$block]))
                    <div data-reveal>
                        <h2 class="section-title text-2xl sm:text-3xl">{{ $home[$block]['heading'] }}</h2>
                        <ul class="mt-6 grid gap-4 sm:grid-cols-2">
                            @foreach ($home[$block]['items'] as $item)
                                @php($external = str_starts_with($item['url'] ?? '', 'http'))
                                <li class="group relative rounded-2xl bg-white p-5 shadow-card ring-1 ring-ink-900/5 transition hover:-translate-y-0.5 hover:shadow-lift">
                                    <span class="grid size-11 place-items-center rounded-xl bg-saffron-50 text-saffron-700"><x-glyph :name="$item['icon'] ?? 'sparkles'" class="size-5" /></span>
                                    <h3 class="mt-4 font-semibold text-ink-900 group-hover:text-brand-700">
                                        <a href="{{ $item['url'] ?? '#' }}" class="after:absolute after:inset-0" @if ($external) target="_blank" rel="noopener" @endif>{{ $item['title'] }}</a>
                                    </h3>
                                    <p class="mt-1.5 text-sm leading-relaxed text-ink-500">{{ $item['text'] ?? '' }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </div>
    </section>

    {{-- Popular packages carousel --}}
    @if ($packages->isNotEmpty())
        <section class="bg-sand-100 py-16 sm:py-20" data-carousel>
            <div class="container-x">
                <div class="flex flex-wrap items-end justify-between gap-4" data-reveal>
                    <div>
                        <p class="eyebrow">Ready-made itineraries</p>
                        <h2 class="section-title mt-2">Popular Haridwar Rishikesh tour packages</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="/tour-packages.html" class="btn-ghost hidden sm:inline-flex">All tour packages</a>
                        <button type="button" data-carousel-prev class="grid size-12 place-items-center rounded-full bg-white ring-1 ring-ink-900/10 transition hover:bg-brand-600 hover:text-white disabled:opacity-40" aria-label="Previous packages"><x-glyph name="chevron-left" /></button>
                        <button type="button" data-carousel-next class="grid size-12 place-items-center rounded-full bg-white ring-1 ring-ink-900/10 transition hover:bg-brand-600 hover:text-white disabled:opacity-40" aria-label="Next packages"><x-glyph name="chevron-right" /></button>
                    </div>
                </div>
                <ul class="carousel-track mt-8" data-carousel-track>
                    @foreach ($packages as $package)
                        @php($duration = $package->duration())
                        <li class="w-[82%] shrink-0 sm:w-[46%] lg:w-[31.5%] xl:w-[23.5%]">
                            <x-page-card
                                :url="$package->url()"
                                :title="$package->title"
                                :text="$package->teaser(140)"
                                :image="$package->extra['thumbnail'] ?? $package->hero_image"
                                :alt="$package->extra['thumbnail_alt'] ?? $package->hero_alt"
                                :badge="$duration ? $duration['nights'].'N / '.$duration['days'].'D' : null"
                                :meta="isset($package->facts['start_city']) ? 'From '.$package->facts['start_city'] : 'Tour package'">View itinerary</x-page-card>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- Haridwar & Rishikesh tourism --}}
    @foreach (['haridwar', 'rishikesh'] as $city)
        @if (! empty($home[$city]))
            <section class="container-x py-16 sm:py-20">
                <div class="flex flex-wrap items-end justify-between gap-4" data-reveal>
                    <div>
                        <p class="eyebrow">Travel guide</p>
                        <h2 class="section-title mt-2">{{ $home[$city]['heading'] }}</h2>
                    </div>
                    <a href="{{ $home[$city]['url'] }}" class="inline-flex items-center gap-2 font-semibold text-brand-700 transition-all hover:gap-3">Complete {{ $home[$city]['heading'] }} guide <x-glyph name="arrow-right" class="size-4" /></a>
                </div>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($home[$city]['items'] as $item)
                        <article class="group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-ink-900/5 transition duration-300 hover:-translate-y-1 hover:shadow-lift" data-reveal style="--reveal-delay: {{ $loop->index * 60 }}ms">
                            <div class="relative aspect-[16/10] overflow-hidden">
                                <x-picture :src="$item['image'] ?? null" :alt="Str::headline($item['alt'] ?? $item['title'] ?? '')" sizes="(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw" class="size-full object-cover transition duration-500 group-hover:scale-105" />
                                <div class="absolute inset-0 bg-gradient-to-t from-brand-950/80 via-brand-950/10 to-transparent"></div>
                                <div class="absolute inset-x-0 bottom-0 p-5 text-white">
                                    <h3 class="font-display text-xl font-semibold">{{ $item['label'] ?? $item['title'] }}</h3>
                                    <p class="text-sm text-white/80">{{ $item['subtitle'] ?? '' }}</p>
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <h4 class="font-semibold text-ink-900">{{ $item['title'] ?? '' }}</h4>
                                <p class="mt-2 text-[15px] leading-relaxed text-ink-500">{{ $item['text'] ?? '' }}</p>
                                <a href="{{ $item['url'] ?? '#' }}" class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-brand-700 after:absolute after:inset-0">Read More <span class="sr-only">about {{ $item['title'] ?? '' }}</span><x-glyph name="arrow-right" class="size-4" /></a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($city === 'haridwar')
            {{-- Reviews --}}
            <section class="bg-brand-900 py-16 text-white sm:py-20" data-carousel data-carousel-autoplay>
                <div class="container-x grid gap-10 lg:grid-cols-[320px_1fr] lg:items-center">
                    <div data-reveal>
                        <p class="eyebrow text-saffron-300">{{ $home['reviews']['subheading'] ?? '' }}</p>
                        <h2 class="mt-2 font-display text-3xl font-semibold sm:text-4xl">{{ $home['reviews']['heading'] ?? 'What Our Clients Say' }}</h2>
                        <div class="mt-6 flex items-center gap-4">
                            <x-picture :src="$home['reviews']['badge'] ?? null" :alt="Str::headline($home['reviews']['badge_alt'] ?? '')" sizes="96px" class="h-24 w-auto rounded-xl bg-white p-2" />
                            <a href="{{ $home['reviews']['url'] ?? '#' }}" target="_blank" rel="noopener" class="btn-light">{{ $home['reviews']['cta'] ?? 'Write a Review' }} <x-glyph name="arrow-up-right" class="size-4" /></a>
                        </div>
                        <div class="mt-6 flex gap-2">
                            <button type="button" data-carousel-prev class="grid size-11 place-items-center rounded-full bg-white/10 ring-1 ring-white/20 transition hover:bg-white hover:text-ink-900 disabled:opacity-40" aria-label="Previous review"><x-glyph name="chevron-left" /></button>
                            <button type="button" data-carousel-next class="grid size-11 place-items-center rounded-full bg-white/10 ring-1 ring-white/20 transition hover:bg-white hover:text-ink-900 disabled:opacity-40" aria-label="Next review"><x-glyph name="chevron-right" /></button>
                        </div>
                    </div>
                    <ul class="carousel-track" data-carousel-track>
                        @foreach ($testimonials as $testimonial)
                            <li class="w-[88%] shrink-0 sm:w-[60%] xl:w-[48%]">
                                <figure class="flex h-full flex-col rounded-3xl bg-white/[0.06] p-7 ring-1 ring-white/10">
                                    <x-glyph name="quote" class="size-9 text-saffron-400" />
                                    <blockquote class="mt-4 flex-1 text-[15px] leading-relaxed text-white/90">“{{ $testimonial->body }}”</blockquote>
                                    <figcaption class="mt-6 flex items-center gap-3">
                                        <span class="grid size-11 place-items-center rounded-full bg-white/10 font-display text-lg font-semibold">{{ mb_substr(trim(str_ireplace(['Mr. ', 'Mrs. ', 'Mr ', 'Mrs '], '', $testimonial->name)), 0, 1) }}</span>
                                        <span><span class="block font-semibold text-white">{{ $testimonial->name }}</span><span class="text-sm text-white/65">{{ $testimonial->location }}</span></span>
                                    </figcaption>
                                </figure>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif
    @endforeach

    {{-- Tour packages (legacy homepage section) --}}
    @if (! empty($home['packages']))
        <section class="bg-sand-100 py-16 sm:py-20">
            <div class="container-x">
                <div class="max-w-2xl" data-reveal>
                    <p class="eyebrow">Tour packages</p>
                    <h2 class="section-title mt-2">{{ $home['packages']['heading'] }}</h2>
                </div>
                <div class="mt-10 grid gap-6 lg:grid-cols-2">
                    @foreach ($home['packages']['items'] as $item)
                        <article class="group relative grid overflow-hidden rounded-3xl bg-white shadow-card ring-1 ring-ink-900/5 transition hover:shadow-lift sm:grid-cols-[200px_1fr]" data-reveal>
                            <div class="relative aspect-[16/10] overflow-hidden sm:aspect-auto">
                                <x-picture :src="$item['image'] ?? null" :alt="Str::headline($item['alt'] ?? $item['title'] ?? '')" sizes="(min-width: 640px) 200px, 100vw" class="size-full object-cover transition duration-500 group-hover:scale-105" />
                            </div>
                            <div class="p-6">
                                <h3 class="font-display text-xl font-semibold text-ink-900 group-hover:text-brand-700"><a href="{{ $item['url'] ?? '#' }}" title="{{ $item['title'] ?? '' }}" class="after:absolute after:inset-0">{{ $item['title'] ?? '' }}</a></h3>
                                <p class="mt-2 text-[15px] leading-relaxed text-ink-500">{{ $item['text'] ?? '' }}</p>
                                <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700">See package <x-glyph name="arrow-right" class="size-4" /></span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Guide --}}
    @if (! empty($home['guide']))
        <section class="container-x py-16 sm:py-20">
            <div class="max-w-2xl" data-reveal>
                <p class="eyebrow">Plan your journey</p>
                <h2 class="section-title mt-2">{{ $home['guide']['heading'] }}</h2>
            </div>
            <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($home['guide']['items'] as $item)
                    <li class="group relative rounded-2xl bg-white p-6 shadow-card ring-1 ring-ink-900/5 transition hover:-translate-y-0.5 hover:shadow-lift" data-reveal style="--reveal-delay: {{ $loop->index * 50 }}ms">
                        <span class="grid size-12 place-items-center rounded-2xl bg-saffron-50 text-saffron-700"><x-glyph :name="$item['icon'] ?? 'compass'" class="size-6" /></span>
                        <h3 class="mt-4 font-display text-xl font-semibold text-ink-900 group-hover:text-brand-700"><a href="{{ $item['url'] ?? '#' }}" class="after:absolute after:inset-0">{{ $item['title'] ?? '' }}</a></h3>
                        <p class="mt-2 text-[15px] leading-relaxed text-ink-500 [&_a]:relative [&_a]:z-10 [&_a]:font-semibold [&_a]:text-brand-700 [&_a]:underline">{!! $item['html'] ?? e($item['text'] ?? '') !!}</p>
                        <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700">Explore <x-glyph name="arrow-right" class="size-4" /></span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Char Dham note --}}
    @if (! empty($home['note']))
        <section class="container-x" data-reveal>
            <p class="flex flex-col gap-3 rounded-3xl bg-white px-6 py-5 text-[15px] leading-relaxed text-ink-700 ring-1 ring-ink-900/10 sm:flex-row sm:items-center [&_a]:font-semibold [&_a]:text-brand-700 [&_a]:underline [&_a]:decoration-brand-200 [&_a]:underline-offset-2">
                <x-glyph name="temple" class="size-8 shrink-0 text-brand-600" />
                <span>{!! $home['note'] !!}</span>
            </p>
        </section>
    @endif

    {{-- About India Easy Trip --}}
    @if (! empty($home['about']))
        <section class="container-x py-16 sm:py-20">
            <div class="grid gap-10 lg:grid-cols-[1fr_380px] lg:items-start">
                <div data-reveal>
                    <p class="eyebrow">About us</p>
                    <h2 class="section-title mt-2">{{ $home['about']['heading'] }}</h2>
                    <div class="prose-content mt-6 text-base">
                        @foreach ($home['about']['paragraphs'] ?? [] as $paragraph)
                            <p>{!! $paragraph !!}</p>
                        @endforeach
                    </div>
                </div>
                <aside class="rounded-3xl bg-brand-900 p-7 text-white" data-reveal>
                    <p class="font-display text-xl font-semibold">Registered &amp; approved</p>
                    <dl class="mt-5 space-y-4 text-sm">
                        @foreach ((array) $site->get('credentials') as $credential)
                            <div class="border-b border-white/10 pb-3 last:border-0 last:pb-0">
                                <dt class="text-white/65">{{ $credential['name'] ?? '' }}</dt>
                                <dd class="mt-0.5 font-semibold tracking-wide text-saffron-200">{{ $credential['value'] ?? '' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <a href="/about-us.html" class="btn-light mt-6 w-full">More about India Easy Trip</a>
                </aside>
            </div>
        </section>
    @endif

    @include('partials.cta-band')
@endsection
