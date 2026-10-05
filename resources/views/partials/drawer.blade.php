<div id="site-drawer" data-drawer hidden class="group fixed inset-0 z-50 xl:hidden" role="dialog" aria-modal="true" aria-label="Menu">
    <div data-drawer-close class="absolute inset-0 bg-ink-900/50 opacity-0 backdrop-blur-sm transition-opacity duration-300 group-[.is-open]:opacity-100"></div>
    <div data-drawer-panel class="absolute inset-y-0 right-0 flex w-[90vw] max-w-sm translate-x-full flex-col bg-sand-50 shadow-2xl transition-transform duration-300 ease-out group-[.is-open]:translate-x-0">
        <div class="flex items-center justify-between border-b border-ink-900/5 bg-white px-5 py-3">
            <span class="font-display text-lg font-semibold text-ink-900">Menu</span>
            <button type="button" data-drawer-close class="grid size-11 place-items-center rounded-full hover:bg-sand-100" aria-label="Close menu">
                <x-glyph name="x" class="size-6" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-5">
            <x-search-box id="drawer-search" />

            <ul class="mt-5 divide-y divide-ink-900/5 rounded-2xl bg-white ring-1 ring-ink-900/5">
                <li><a href="/" class="flex min-h-12 items-center gap-3 px-4 font-medium text-ink-900"><x-glyph name="home" class="size-5 text-saffron-500" /> Home</a></li>
                @foreach ($navigation as $item)
                    <li>
                        @if ($item->children->isNotEmpty())
                            <div class="flex items-center">
                                <a href="{{ $item->url }}" class="flex min-h-12 flex-1 items-center px-4 font-medium text-ink-900">{{ $item->label }}</a>
                                <button type="button" data-accordion-trigger aria-expanded="false" aria-controls="drawer-group-{{ $item->id }}" class="grid size-12 place-items-center text-ink-500 aria-expanded:rotate-180" aria-label="Show {{ $item->label }} pages">
                                    <x-glyph name="chevron-down" class="size-5 transition" />
                                </button>
                            </div>
                            {{-- Filled from the header menu on first open (keeps each link in the HTML once). --}}
                            <ul id="drawer-group-{{ $item->id }}" data-menu-source="{{ $item->id }}" hidden class="space-y-0.5 bg-sand-50 px-2 py-2"></ul>
                        @else
                            <a href="{{ $item->url }}" class="flex min-h-12 items-center px-4 font-medium text-ink-900">{{ $item->label }}</a>
                        @endif
                    </li>
                @endforeach
                <li><a href="/gallery.html" class="flex min-h-12 items-center px-4 font-medium text-ink-900">Gallery</a></li>
                <li><a href="/blog.html" class="flex min-h-12 items-center px-4 font-medium text-ink-900">Blog</a></li>
                <li><a href="/about-us.html" class="flex min-h-12 items-center px-4 font-medium text-ink-900">About Us</a></li>
                <li><a href="/contact-us.html" class="flex min-h-12 items-center px-4 font-medium text-ink-900">Contact Us</a></li>
            </ul>
        </div>

        <div class="grid grid-cols-2 gap-3 border-t border-ink-900/5 bg-white p-4">
            <a href="{{ $site->phoneHref() }}" class="btn-ghost"><x-glyph name="phone" class="size-4" /> Call</a>
            <a href="/book-now.php" class="btn-primary">Book Now</a>
        </div>
    </div>
</div>
