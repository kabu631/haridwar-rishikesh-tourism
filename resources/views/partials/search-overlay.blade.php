@php
    $popularSearches = ['Haridwar', 'Rishikesh', 'Char Dham Yatra', 'Rishikesh Rafting', 'Ganga Aarti', 'Har Ki Pauri', 'Valley of Flowers', 'Kedarnath', 'Mussoorie'];
@endphp
{{-- Full-screen search on phones and tablets, opened by the header search icon (which still links to /search without JavaScript). --}}
<div id="search-overlay" data-search-overlay hidden class="group fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Search">
    <div class="absolute inset-0 flex -translate-y-3 flex-col bg-sand-50 opacity-0 transition duration-200 ease-out group-[.is-open]:translate-y-0 group-[.is-open]:opacity-100 motion-reduce:transition-none">
        <div class="flex items-center justify-between border-b border-ink-900/5 bg-white px-5 py-3">
            <span class="font-display text-lg font-semibold text-ink-900">Search</span>
            <button type="button" data-search-close class="grid size-11 place-items-center rounded-full hover:bg-sand-100" aria-label="Close search">
                <x-glyph name="x" class="size-6" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto overscroll-contain px-5 py-5">
            <x-search-box id="overlay-search" :phrases="[]" inline-results />

            {{-- Hidden while the instant suggestions are showing. --}}
            <section class="mt-6 group-has-[[data-search-results]:not([hidden])]:hidden" aria-labelledby="overlay-popular-heading">
                <h2 id="overlay-popular-heading" class="eyebrow">Popular searches</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($popularSearches as $term)
                        <li><button type="button" data-search-term="{{ $term }}" class="chip px-4 py-2 text-sm hover:bg-saffron-50 hover:text-brand-700">{{ $term }}</button></li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</div>
