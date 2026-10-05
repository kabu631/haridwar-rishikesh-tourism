{{-- Utility bar --}}
<div class="hidden bg-brand-900 text-sm text-white/90 md:block">
    <div class="container-x flex h-10 items-center justify-between gap-6">
        <p class="flex min-w-0 items-center gap-2.5">
            <x-glyph name="shield" class="size-[18px] text-saffron-300" />
            <span class="truncate">{{ $site->get('topbar_text') }}</span>
        </p>
        <div class="flex shrink-0 items-center gap-5">
            <a href="{{ $site->get('social.tripadvisor') }}" target="_blank" rel="noopener" class="flex items-center gap-1.5 transition hover:text-saffron-200"><x-glyph name="tripadvisor" class="size-5" /> Reviews</a>
            <a href="/blog.html" class="transition hover:text-saffron-200">Blog</a>
            <a href="/gallery.html" class="transition hover:text-saffron-200">Gallery</a>
            <a href="/contact-us.html" class="transition hover:text-saffron-200">Contact Us</a>
        </div>
    </div>
</div>

<header data-header class="sticky top-0 z-40 border-b border-ink-900/5 bg-white/95 backdrop-blur-md transition-shadow duration-300 supports-[backdrop-filter]:bg-white/85">
    <div class="container-x flex h-[68px] items-center gap-4 lg:h-[76px] lg:gap-8">
        <a href="/" class="shrink-0" aria-label="{{ $site->get('name') }} – home">
            <picture>
                <source type="image/webp" srcset="/images/india-easy-trip-logo-h104.webp">
                <img src="/images/india-easy-trip-logo-h104.png" width="173" height="48" alt="{{ $site->get('name') }} by India Easy Trip" class="h-10 w-auto lg:h-12" fetchpriority="high">
            </picture>
        </a>

        <x-search-box id="header-search" class="hidden max-w-xl flex-1 lg:block" />

        <div class="ml-auto flex items-center gap-1.5 sm:gap-3">
            <a href="{{ $site->phoneHref() }}" class="hidden items-center gap-3 rounded-full py-1 pr-2 transition hover:text-brand-700 xl:flex">
                <span class="grid size-10 place-items-center rounded-full bg-saffron-50 text-saffron-700 ring-1 ring-saffron-200"><x-glyph name="phone" class="size-5" /></span>
                <span class="leading-tight">
                    <span class="block text-[11px] font-medium tracking-wide text-ink-500 uppercase">Call our Haridwar office</span>
                    <span class="block text-[15px] font-semibold text-ink-900">{{ $site->get('phone') }}</span>
                </span>
            </a>
            <a href="/book-now.php" class="btn-primary hidden sm:inline-flex">Book Now</a>
            <a href="{{ route('search', [], false) }}" data-search-open aria-haspopup="dialog" aria-expanded="false" aria-controls="search-overlay" class="grid size-12 place-items-center rounded-full text-ink-900 transition hover:bg-sand-100 lg:hidden" aria-label="Search">
                <x-glyph name="search" class="size-6" />
            </a>
            <button type="button" data-drawer-open aria-expanded="false" aria-controls="site-drawer" class="grid size-12 place-items-center rounded-full text-ink-900 transition hover:bg-sand-100 xl:hidden" aria-label="Open menu">
                <x-glyph name="menu" class="size-6" />
            </button>
        </div>
    </div>

    {{-- Main navigation (desktop). Every legacy menu link is kept for internal linking. --}}
    <nav aria-label="Main" class="hidden border-t border-ink-900/5 xl:block">
        <ul class="container-x flex h-12 items-center justify-between gap-0.5 text-[13.5px] font-medium whitespace-nowrap text-ink-700">
            @foreach ($navigation as $item)
                @php($wide = $item->children->count() > 9)
                <li class="nav-item group/nav relative" data-nav-item data-menu-id="{{ $item->id }}">
                    @if ($item->children->isNotEmpty())
                        <a href="{{ $item->url }}" data-nav-trigger aria-haspopup="true" aria-expanded="false" @if ($item->title) title="{{ $item->title }}" @endif
                            class="flex h-12 items-center gap-1 rounded-lg px-2.5 transition hover:text-brand-700 @if (request()->is(ltrim($item->url, '/'))) text-brand-700 @endif">
                            {{ $item->label }} <x-glyph name="chevron-down" class="size-4 opacity-70 transition duration-200 group-hover/nav:rotate-180 group-[.is-open]/nav:rotate-180" />
                        </a>
                        <div class="nav-panel {{ $loop->index < 2 ? '!left-0 !translate-x-0' : ($loop->remaining < 2 ? '!right-0 !left-auto !translate-x-0' : '') }}" data-nav-panel>
                            <div @class(['rounded-2xl bg-white p-3 shadow-2xl ring-1 ring-ink-900/10', 'w-[680px]' => $wide, 'w-72' => ! $wide])>
                                <ul @class(['grid gap-0.5', 'grid-cols-2' => $wide])>
                                    @foreach ($item->children as $child)
                                        <li>
                                            <a href="{{ $child->url }}" @if ($child->title) title="{{ $child->title }}" @endif @if ($child->isExternal()) target="_blank" rel="noopener" @endif
                                                class="mega-link">{{ $child->label }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <a href="{{ $item->url }}" class="mt-2 flex items-center justify-between rounded-xl bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-800 transition hover:bg-brand-100">
                                    Explore all {{ $item->label }} <x-glyph name="arrow-right" class="size-4" />
                                </a>
                            </div>
                        </div>
                    @else
                        <a href="{{ $item->url }}" @if ($item->title) title="{{ $item->title }}" @endif class="flex h-12 items-center rounded-lg px-2.5 transition hover:text-brand-700">{{ $item->label }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
</header>
