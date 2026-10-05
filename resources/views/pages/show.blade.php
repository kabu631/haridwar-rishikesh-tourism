@extends('layouts.app')

@php
    use App\Enums\PageType;
    use Illuminate\Support\Str;

    $isPackage = $page->type === PageType::Package;
    $duration = $isPackage ? $page->duration() : null;
    $facts = $page->facts ?? [];
    $heroImage = $page->hero_image;
    $updated = $page->updated_at;
    $shareUrl = $page->canonical();
@endphp

@section('content')
    {{-- Page hero --}}
    <section class="relative isolate overflow-hidden bg-brand-950 text-white">
        @if ($heroImage)
            <x-picture :src="$heroImage" :alt="$page->hero_alt ? Str::headline($page->hero_alt) : $page->title" sizes="100vw" eager priority class="absolute inset-0 -z-20 size-full object-cover" />
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink-900/90 via-ink-900/55 to-ink-900/25" aria-hidden="true"></div>
        @endif

        <div class="container-x pt-10 pb-12 sm:pt-16 sm:pb-16 lg:pt-24">
            <x-breadcrumbs :items="$seo->breadcrumbs" class="text-white/90" />
            <div class="mt-5 max-w-4xl">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide uppercase ring-1 ring-white/25">
                    {{ $page->type?->badge() }}
                    @if ($duration)
                        · {{ $duration['nights'] }} Nights / {{ $duration['days'] }} Days
                    @endif
                </span>
                @php($h1Link = $page->extra['h1_link'] ?? null)
                <h1 class="mt-4 font-display text-3xl leading-[1.12] font-semibold tracking-tight text-balance sm:text-4xl lg:text-5xl">
                    @if ($h1Link && str_contains($page->title, $h1Link['text']))
                        {!! str_replace(e($h1Link['text']), '<a href="'.e($h1Link['url']).'" class="underline decoration-saffron-400/70 decoration-2 underline-offset-[6px] hover:decoration-white">'.e($h1Link['text']).'</a>', e($page->title)) !!}
                    @else
                        {{ $page->title }}
                    @endif
                </h1>
                <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/80">
                    @if ($page->author)
                        <span class="flex items-center gap-1.5"><x-glyph name="user" class="size-4" /> By <a href="/our-team.html" class="font-medium text-white hover:underline">{{ $page->author->name }}</a></span>
                    @endif
                    @if ($updated)
                        <span class="flex items-center gap-1.5"><x-glyph name="calendar" class="size-4" /> Updated <time datetime="{{ $updated->toDateString() }}">{{ $updated->format('j M Y') }}</time></span>
                    @endif
                    <span class="flex items-center gap-1.5"><x-glyph name="clock" class="size-4" /> {{ $page->readingMinutes() }} min read</span>
                </div>
                @if ($isPackage)
                    <div class="mt-7 flex flex-wrap gap-3">
                        <a href="#enquire" class="btn-primary">Enquire about this tour</a>
                        <a href="{{ $site->whatsappUrl('Hello! I am interested in: '.$page->title) }}" target="_blank" rel="noopener" class="btn-light"><x-glyph name="whatsapp" class="size-4" /> WhatsApp</a>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="container-x grid gap-10 py-10 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-14 lg:py-14">
        <article class="min-w-0">
            {{-- Photo slider (the legacy page slideshow) --}}
            @if ($slides->isNotEmpty())
                <div class="relative mb-10 overflow-hidden rounded-3xl bg-brand-950 shadow-card ring-1 ring-ink-900/5" data-slider data-autoplay="5000" aria-roledescription="carousel" aria-label="{{ Str::before($page->label(), ' - ') }} photos">
                    <div class="relative aspect-[16/10] sm:aspect-[2/1]">
                        @foreach ($slides as $slide)
                            <figure data-slide @class(['absolute inset-0', 'is-active' => $loop->first]) role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $slides->count() }}">
                                <x-picture :src="$slide['image']" :alt="Str::headline($slide['alt'] ?? $page->title)" sizes="(min-width: 1024px) 760px, 100vw" :eager="$loop->first" class="slide-media size-full object-cover" />
                                @if (filled($slide['alt'] ?? $slide['title'] ?? null))
                                    <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink-900/80 to-transparent px-5 pt-12 pb-4 text-sm font-medium text-white sm:px-6">{{ $slide['title'] ?? Str::headline($slide['alt']) }}</figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                    @if ($slides->count() > 1)
                        <button type="button" data-slider-prev class="absolute top-1/2 left-3 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-ink-900 shadow-lg transition hover:bg-white" aria-label="Previous photo"><x-glyph name="chevron-left" /></button>
                        <button type="button" data-slider-next class="absolute top-1/2 right-3 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-ink-900 shadow-lg transition hover:bg-white" aria-label="Next photo"><x-glyph name="chevron-right" /></button>
                        <div data-slider-dots class="absolute right-5 bottom-4 flex items-center gap-2"></div>
                        <div class="absolute inset-x-0 bottom-0 h-1 bg-white/10" aria-hidden="true"><div data-slider-progress class="h-full origin-left bg-saffron-400"></div></div>
                        <span data-slider-status class="sr-only" aria-live="polite"></span>
                    @endif
                </div>
            @endif

            {{-- Trip facts for packages --}}
            @if ($isPackage && ($duration || ! empty($facts['start_city']) || ! empty($facts['destinations'])))
                <dl class="mb-10 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @if ($duration)
                        <div class="rounded-2xl bg-white p-4 ring-1 ring-ink-900/5"><dt class="flex items-center gap-1.5 text-xs font-medium text-ink-500"><x-glyph name="clock" class="size-4 text-saffron-600" /> Duration</dt><dd class="mt-1 font-semibold text-ink-900">{{ $duration['nights'] }}N / {{ $duration['days'] }}D</dd></div>
                    @endif
                    @if (! empty($facts['start_city']))
                        <div class="rounded-2xl bg-white p-4 ring-1 ring-ink-900/5"><dt class="flex items-center gap-1.5 text-xs font-medium text-ink-500"><x-glyph name="map-pin" class="size-4 text-saffron-600" /> Starts from</dt><dd class="mt-1 font-semibold text-ink-900">{{ $facts['start_city'] }}</dd></div>
                    @endif
                    @if (! empty($facts['destinations']))
                        <div class="col-span-2 rounded-2xl bg-white p-4 ring-1 ring-ink-900/5"><dt class="flex items-center gap-1.5 text-xs font-medium text-ink-500"><x-glyph name="route" class="size-4 text-saffron-600" /> Places covered</dt><dd class="mt-1 font-semibold text-ink-900">{{ implode(' · ', $facts['destinations']) }}</dd></div>
                    @endif
                </dl>
            @endif

            {{-- Table of contents for long guides --}}
            @if ($toc)
                <details class="group mb-10 rounded-2xl bg-white ring-1 ring-ink-900/5 open:shadow-card" @if (count($toc) <= 8) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 font-semibold text-ink-900">
                        <span class="flex items-center gap-2"><x-glyph name="list" class="size-5 text-saffron-500" /> On this page</span>
                        <x-glyph name="chevron-down" class="size-5 transition group-open:rotate-180" />
                    </summary>
                    <ol class="grid gap-1 border-t border-ink-900/5 px-5 py-4 text-[15px] sm:grid-cols-2">
                        @foreach ($toc as $entry)
                            <li @class(['pl-4' => $entry['level'] === 3])><a href="#{{ $entry['id'] }}" class="block rounded-lg py-1 text-ink-700 hover:text-brand-700">{{ Str::limit($entry['text'], 70) }}</a></li>
                        @endforeach
                    </ol>
                </details>
            @endif

            @if ($content !== '')
                <div class="prose-content">{!! $content !!}</div>
            @endif

            {{-- Blog listing (/blog.html): posts added in Admin → Blog, newest first --}}
            @if ($posts !== null)
                @if ($posts->isEmpty())
                    <p class="mt-10 rounded-2xl bg-white p-8 text-center text-ink-500 ring-1 ring-ink-900/5">New articles are on their way. Check back soon.</p>
                @else
                    <div class="mt-10 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($posts as $post)
                            <x-page-card
                                :url="$post->url()"
                                :title="$post->title"
                                :text="$post->excerpt ?: $post->meta_description"
                                :image="$post->hero_image"
                                :alt="$post->hero_alt"
                                :meta="collect([$post->published_at?->format('j M Y'), $post->author?->name])->filter()->implode(' · ')"
                                heading-level="2" />
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $posts->links() }}</div>
                @endif
            @endif

            {{-- Hub page cards (legacy "story" blocks) --}}
            @if (filled($page->cards))
                <div class="mt-12 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($page->cards as $card)
                        @continue(blank($card['url'] ?? null) && blank($card['title'] ?? null))
                        <x-page-card
                            :url="$card['url'] ?? '#'"
                            :title="$card['title'] ?? 'Read more'"
                            :text="$card['text'] ?? null"
                            :image="$card['image'] ?? null"
                            :alt="isset($card['alt']) ? Str::headline($card['alt']) : null"
                            :badge="$card['badge'] ?? null"
                            heading-level="2" />
                    @endforeach
                </div>
            @endif

            {{-- Photo / video gallery --}}
            @if ($gallery->isNotEmpty())
                @inject('media', 'App\Support\Media\MediaLibrary')
                <section class="mt-12" aria-label="Photos">
                    @if ($page->type !== PageType::Gallery)
                        <h2 class="mb-5 font-display text-2xl font-semibold text-ink-900">Photo gallery</h2>
                    @endif
                    <ul class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3">
                        @foreach ($gallery as $item)
                            @if (! empty($item['youtube']))
                                <li class="col-span-2 md:col-span-1">
                                    <button type="button" class="yt-facade" data-youtube="{{ $item['youtube'] }}" style="background-image:url('https://i.ytimg.com/vi/{{ $item['youtube'] }}/hqdefault.jpg')" aria-label="Play video: {{ $item['title'] ?? 'Guest review' }}">
                                        @if (! empty($item['image']) && $media->exists($item['image']))
                                            <x-picture :src="$item['image']" :alt="Str::headline($item['alt'] ?? ($item['title'] ?? 'Guest video review'))" sizes="(min-width: 768px) 33vw, 100vw" class="absolute inset-0 size-full object-cover" />
                                        @endif
                                    </button>
                                </li>
                            @elseif (! empty($item['url']))
                                <li class="col-span-2 md:col-span-1">
                                    <x-page-card :url="$item['url']" :title="$item['title'] ?? Str::headline($item['alt'] ?? '')" :text="$item['caption'] ?? null" :image="$item['image']" :alt="Str::headline($item['alt'] ?? '')" heading-level="2">View photos</x-page-card>
                                </li>
                            @elseif (! empty($item['image']) && $media->exists($item['image']))
                                <li>
                                    <figure>
                                        <a href="{{ $item['full'] ?? $item['image'] }}" data-lightbox="page" data-caption="{{ $item['title'] ?? Str::headline($item['alt'] ?? '') }}" class="group block overflow-hidden rounded-2xl bg-sand-200 ring-1 ring-ink-900/5">
                                            <x-picture :src="$item['image']" :alt="Str::headline($item['alt'] ?? ($item['title'] ?? $page->title))" sizes="(min-width: 768px) 33vw, 50vw" class="aspect-[4/3] size-full object-cover transition duration-500 group-hover:scale-105" />
                                        </a>
                                        @if (! empty($item['caption']))
                                            <figcaption class="mt-2 text-sm text-ink-500">{{ $item['caption'] }}</figcaption>
                                        @endif
                                    </figure>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Company pages extras --}}
            @if ($page->path === 'contact-us.html')
                @include('pages.partials.contact-details')
            @endif
            @if ($page->path === 'our-team.html')
                @include('pages.partials.team')
            @endif

            {{-- Author & editorial info (E-E-A-T) --}}
            @if ($page->author && $page->type !== PageType::Company)
                <aside class="mt-14 flex flex-col gap-5 rounded-3xl bg-white p-6 ring-1 ring-ink-900/5 sm:flex-row sm:p-7" aria-label="About the author">
                    <span class="grid size-16 shrink-0 place-items-center rounded-2xl bg-brand-600 font-display text-2xl font-semibold text-white">{{ Str::of($page->author->name)->explode(' ')->map(fn ($part) => mb_substr($part, 0, 1))->implode('') }}</span>
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-saffron-700 uppercase">Written by</p>
                        <p class="mt-1 font-display text-lg font-semibold text-ink-900"><a href="/our-team.html" class="hover:text-brand-700">{{ $page->author->name }}</a> <span class="font-sans text-sm font-normal text-ink-500">· {{ $page->author->job_title }}</span></p>
                        <p class="mt-2 text-[15px] leading-relaxed text-ink-500">{{ $page->author->bio }}</p>
                        <p class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm text-ink-500">
                            @if ($page->published_at)
                                <span>Published <time datetime="{{ $page->published_at->toDateString() }}">{{ $page->published_at->format('j M Y') }}</time></span>
                            @endif
                            @if ($updated)
                                <span>Last updated <time datetime="{{ $updated->toDateString() }}">{{ $updated->format('j M Y') }}</time></span>
                            @endif
                            @if ($page->reviewed_at)
                                <span class="flex items-center gap-1 text-leaf-700"><x-glyph name="check" class="size-4" /> Reviewed {{ $page->reviewer ? 'by '.$page->reviewer->name : '' }} on <time datetime="{{ $page->reviewed_at->toDateString() }}">{{ $page->reviewed_at->format('j M Y') }}</time></span>
                            @endif
                        </p>
                    </div>
                </aside>
            @endif

            {{-- Sources --}}
            @if (filled($page->sources))
                <section class="mt-8 rounded-2xl bg-sand-100 p-6" aria-labelledby="sources-heading">
                    <h2 id="sources-heading" class="font-semibold text-ink-900">Sources</h2>
                    <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm text-ink-700">
                        @foreach ($page->sources as $source)
                            <li>@if (! empty($source['url']))<a href="{{ $source['url'] }}" target="_blank" rel="noopener" class="link-underline">{{ $source['title'] ?? $source['url'] }}</a>@else{{ $source['title'] ?? '' }}@endif</li>
                        @endforeach
                    </ol>
                </section>
            @endif

            {{-- Share --}}
            <div class="mt-8 flex flex-wrap items-center gap-2 border-t border-ink-900/5 pt-6 text-sm">
                <span class="mr-1 font-medium text-ink-500">Share:</span>
                <a href="https://wa.me/?text={{ rawurlencode($page->title.' '.$shareUrl) }}" target="_blank" rel="noopener" class="chip hover:bg-sand-200"><x-glyph name="whatsapp" class="size-4" /> WhatsApp</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($shareUrl) }}" target="_blank" rel="noopener" class="chip hover:bg-sand-200"><x-glyph name="facebook" class="size-4" /> Facebook</a>
                <a href="https://twitter.com/intent/tweet?url={{ rawurlencode($shareUrl) }}&text={{ rawurlencode($page->title) }}" target="_blank" rel="noopener" class="chip hover:bg-sand-200"><x-glyph name="twitter" class="size-4" /> X</a>
                <button type="button" data-copy-link data-url="{{ $shareUrl }}" class="chip hover:bg-sand-200"><x-glyph name="link" class="size-4" /> <span data-label>Copy link</span></button>
                <button type="button" data-share-native data-url="{{ $shareUrl }}" hidden class="chip hover:bg-sand-200"><x-glyph name="share" class="size-4" /> More</button>
            </div>
        </article>

        {{-- Sidebar --}}
        <aside class="space-y-6 lg:sticky lg:top-36 lg:self-start" aria-label="Sidebar">
            <div id="enquire" class="scroll-mt-36">
                <x-enquiry-form
                    id="page-enquiry"
                    :type="$isPackage ? 'package' : 'quick'"
                    :tour="$page->type === PageType::Company ? null : $page->title"
                    :page-id="$page->id"
                    compact
                    :title="$isPackage ? 'Enquire about this tour' : 'Plan your trip with a local expert'" />
            </div>

            @if ($isPackage && filled($page->itinerary))
                <div class="rounded-3xl bg-white p-6 ring-1 ring-ink-900/5">
                    <p class="flex items-center gap-2 font-display text-lg font-semibold text-ink-900"><x-glyph name="route" class="size-5 text-saffron-500" /> Trip at a glance</p>
                    <ol class="mt-4 space-y-3">
                        @foreach ($page->itinerary as $day)
                            <li class="flex gap-3 text-sm">
                                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-saffron-50 text-xs font-bold text-saffron-700 ring-1 ring-saffron-200">{{ $day['day'] ?? $loop->iteration }}</span>
                                <span class="pt-1 text-ink-700">{{ Str::limit($day['title'] ?? '', 70) }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            @if ($siblings->count() > 1)
                <nav class="rounded-3xl bg-white p-6 ring-1 ring-ink-900/5" aria-label="In this section">
                    <p class="font-display text-lg font-semibold text-ink-900">{{ $page->type === PageType::Hub ? $page->label() : ($page->parent?->label() ?? 'In this section') }}</p>
                    <ul class="mt-3 max-h-[420px] space-y-0.5 overflow-y-auto pr-1 text-[15px]">
                        @foreach ($siblings as $sibling)
                            <li>
                                <a href="{{ $sibling->url() }}" @class([
                                    'flex items-center gap-2 rounded-lg px-2 py-2 transition hover:bg-sand-100 hover:text-brand-700',
                                    'bg-sand-100 font-semibold text-brand-700' => $sibling->is($page),
                                    'text-ink-700' => ! $sibling->is($page),
                                ]) @if ($sibling->is($page)) aria-current="page" @endif>
                                    <x-glyph name="chevron-right" class="size-3.5 text-saffron-500" /> {{ $sibling->label() }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            <div class="rounded-3xl bg-brand-900 p-6 text-white">
                <p class="font-display text-lg font-semibold">Need help planning?</p>
                <p class="mt-1 text-sm text-white/75">Talk to our Haridwar office – {{ $site->get('legal_name') }}.</p>
                <div class="mt-4 grid gap-2">
                    <a href="{{ $site->phoneHref() }}" class="btn bg-white text-brand-800"><x-glyph name="phone" class="size-4" /> {{ $site->get('phone') }}</a>
                    <a href="{{ $site->whatsappUrl('Hello! I am reading: '.$page->title) }}" target="_blank" rel="noopener" class="btn-light"><x-glyph name="whatsapp" class="size-4" /> WhatsApp</a>
                </div>
            </div>
        </aside>
    </div>

    {{-- Related pages --}}
    @if ($related->isNotEmpty())
        <section class="bg-sand-100 py-14 sm:py-16" aria-labelledby="related-heading">
            <div class="container-x">
                <h2 id="related-heading" class="section-title text-2xl sm:text-3xl">More from {{ $page->parent?->label() ?? $site->get('name') }}</h2>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        @php($itemDuration = $item->type === PageType::Package ? $item->duration() : null)
                        <x-page-card
                            :url="$item->url()"
                            :title="$item->title"
                            :text="$item->teaser(150)"
                            :image="$item->extra['thumbnail'] ?? $item->hero_image"
                            :alt="isset($item->extra['thumbnail_alt']) ? Str::headline($item->extra['thumbnail_alt']) : $item->title"
                            :badge="$itemDuration ? $itemDuration['nights'].'N / '.$itemDuration['days'].'D' : null"
                            :meta="$item->type?->badge()" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <div class="mt-16">
        @include('partials.cta-band')
    </div>
@endsection
