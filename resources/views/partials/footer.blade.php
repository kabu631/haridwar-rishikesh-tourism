@php
    $social = array_filter([
        'facebook' => $site->get('social.facebook'),
        'youtube' => $site->get('social.youtube'),
        'twitter' => $site->get('social.twitter'),
        'tripadvisor' => $site->get('social.tripadvisor'),
        'wordpress' => $site->get('social.blog'),
    ]);
@endphp
<footer class="mt-24 bg-brand-950 text-white/75">
    <div class="container-x grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <a href="/" class="inline-block rounded-2xl bg-white px-4 py-3">
                <picture>
                    <source type="image/webp" srcset="/images/india-easy-trip-logo-h104.webp">
                    <img src="/images/india-easy-trip-logo-h104.png" width="173" height="48" alt="India Easy Trip – Haridwar Rishikesh Tourism" loading="lazy" class="h-11 w-auto">
                </picture>
            </a>
            <p class="mt-5 max-w-sm text-[15px] leading-relaxed">{{ $site->get('footer_about') }}</p>
            <ul class="mt-5 space-y-1.5 text-[13px]">
                @foreach ((array) $site->get('credentials') as $credential)
                    <li class="flex gap-2"><x-glyph name="check" class="mt-0.5 size-4 shrink-0 text-saffron-400" /> <span>{{ $credential['name'] ?? '' }}: <span class="text-white">{{ $credential['value'] ?? '' }}</span></span></li>
                @endforeach
            </ul>
            <ul class="mt-6 flex gap-2.5">
                @foreach ($social as $network => $url)
                    <li>
                        <a href="{{ $url }}" target="_blank" rel="noopener me" class="grid size-11 place-items-center rounded-full bg-white/10 text-white transition hover:bg-saffron-500" aria-label="{{ $site->get('name') }} on {{ ucfirst($network === 'wordpress' ? 'our blog' : $network) }}">
                            <x-glyph :name="$network" class="size-5" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="lg:col-span-2">
            <p class="font-display text-lg font-semibold text-white">Explore</p>
            <ul class="mt-4 space-y-2.5 text-[15px]">
                @foreach ($navigation->take(10) as $item)
                    <li><a href="{{ $item->url }}" class="transition hover:text-saffron-300">{{ $item->label }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="lg:col-span-3">
            <p class="font-display text-lg font-semibold text-white">Contact us</p>
            <address class="mt-4 space-y-4 text-[15px] not-italic">
                <p class="flex gap-3"><x-glyph name="map-pin" class="mt-0.5 size-5 shrink-0 text-saffron-400" /> <span>{{ $site->addressLine() }}</span></p>
                <p class="flex gap-3"><x-glyph name="phone" class="mt-0.5 size-5 shrink-0 text-saffron-400" />
                    <span class="flex flex-col"><a href="{{ $site->phoneHref() }}" class="inline-block py-1.5 font-semibold text-white hover:text-saffron-300">{{ $site->get('phone') }}</a>
                        <a href="{{ $site->phoneHref($site->get('landline')) }}" class="inline-block py-1.5 hover:text-white">{{ $site->get('landline') }}</a></span>
                </p>
                <p class="flex gap-3"><x-glyph name="mail" class="mt-0.5 size-5 shrink-0 text-saffron-400" /> <a href="mailto:{{ $site->get('email') }}" class="break-all hover:text-white">{{ $site->get('email') }}</a></p>
                <p class="flex gap-3"><x-glyph name="whatsapp" class="mt-0.5 size-5 shrink-0 text-saffron-400" /> <a href="{{ $site->whatsappUrl() }}" target="_blank" rel="noopener" class="hover:text-white">Chat on WhatsApp</a></p>
            </address>
        </div>

        <div class="lg:col-span-3">
            <p class="font-display text-lg font-semibold text-white">Our websites</p>
            <ul class="mt-4 space-y-2.5 text-[15px]">
                @foreach ((array) $site->get('network') as $label => $url)
                    <li><a href="{{ $url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 transition hover:text-saffron-300">www.{{ $label }} <x-glyph name="arrow-up-right" class="size-3.5 opacity-60" /></a></li>
                @endforeach
            </ul>
            <a href="{{ $site->get('social.tripadvisor_haridwar') }}" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/20">
                <x-glyph name="tripadvisor" class="size-5" /> Review us on Tripadvisor
            </a>
        </div>
    </div>

    @if ($footerMenu->isNotEmpty())
        <nav aria-label="Footer" class="border-t border-white/10">
            <ul class="container-x flex flex-wrap justify-center gap-x-6 gap-y-2 py-6 text-sm">
                @foreach ($footerMenu as $link)
                    <li><a href="{{ $link->url }}" @if ($link->title) title="{{ $link->title }}" @endif @if ($link->isExternal()) target="_blank" rel="noopener" @endif class="transition hover:text-white">{{ $link->label }}</a></li>
                @endforeach
                <li><a href="/sitemap.html" class="transition hover:text-white">Sitemap</a></li>
            </ul>
        </nav>
    @endif

    <div class="border-t border-white/10">
        <div class="container-x flex flex-col items-center justify-between gap-2 py-5 text-center text-[13px] text-white/60 sm:flex-row sm:text-left">
            <p>© 1995–{{ now()->year }} {{ $site->get('legal_name') }}. {{ $site->get('name') }}. All rights reserved.</p>
            <p class="flex gap-4"><a href="/terms-and-conditions.html" class="hover:text-white">Terms</a><a href="/feed.xml" class="hover:text-white">RSS</a><a href="/sitemap.xml" class="hover:text-white">XML sitemap</a></p>
        </div>
    </div>
</footer>
