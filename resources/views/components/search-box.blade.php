@props([
    'id' => 'search',
    'size' => 'md',
    'value' => '',
    'placeholder' => 'Search tours, temples, treks…',
    'autofocus' => false,
    'inlineResults' => false,
    'phrases' => [
        'Haridwar…',
        'Rishikesh…',
        'Haridwar Rishikesh Tour…',
        'Rishikesh Rafting & Camping…',
        'Valley of Flowers Trek…',
        'Chopta Chandrashila Trek…',
        'Haridwar with Mussoorie Tour…',
        'Hemkund Sahib Yatra…',
    ],
])
@php
    $large = $size === 'lg';
@endphp
<form action="{{ route('search', [], false) }}" method="get" role="search" data-search data-suggest="{{ route('search.suggest', [], false) }}" {{ $attributes->merge(['class' => 'relative']) }}>
    <label for="{{ $id }}" class="sr-only">Search Haridwar Rishikesh Tourism</label>
    <div @class([
        'flex items-center gap-2 rounded-full bg-white ring-1 transition focus-within:ring-2 focus-within:ring-saffron-500',
        'p-1.5 pl-5 shadow-xl ring-white/30' => $large,
        'py-1 pr-1 pl-4 ring-ink-900/10 shadow-sm' => ! $large,
    ])>
        <x-glyph name="search" @class(['text-ink-400', 'size-5' => ! $large, 'size-6' => $large]) />
        <input
            id="{{ $id }}"
            type="search"
            name="q"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            @if ($phrases) data-typed-placeholder="{{ json_encode(array_values($phrases), JSON_UNESCAPED_UNICODE) }}" @endif
            autocomplete="off"
            enterkeyhint="search"
            maxlength="100"
            role="combobox"
            aria-autocomplete="list"
            aria-expanded="false"
            aria-controls="{{ $id }}-results"
            @if ($autofocus) autofocus @endif
            @class([
                'min-w-0 flex-1 border-0 bg-transparent text-ink-900 placeholder:text-ink-400 focus:ring-0 focus:outline-none [&::-webkit-search-cancel-button]:hidden',
                'h-12 text-base sm:text-lg' => $large,
                'h-10 text-[15px]' => ! $large,
            ])
        >
        <button type="submit" @class([
            'btn-primary shrink-0',
            'min-h-12 px-6 sm:px-8' => $large,
            'min-h-10 px-4 text-xs' => ! $large,
        ])>
            <span @class(['sr-only' => ! $large, 'sm:not-sr-only' => $large])>Search</span>
            @if (! $large)
                <x-glyph name="arrow-right" class="size-4" />
            @endif
        </button>
    </div>
    <ul id="{{ $id }}-results" role="listbox" aria-label="Search suggestions" hidden data-search-results
        @class([
            'search-results rounded-2xl bg-white py-2 text-left ring-1',
            'absolute inset-x-0 top-full z-50 mt-2 max-h-[70vh] overflow-y-auto shadow-2xl ring-ink-900/10' => ! $inlineResults,
            'mt-3 ring-ink-900/5' => $inlineResults,
        ])></ul>
</form>
