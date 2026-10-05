{{-- Page cards (legacy "story" blocks), shown under their heading or at the end of the page --}}
<div class="{{ $class ?? 'mt-12' }} grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ($cards as $card)
        @continue(blank($card['url'] ?? null) && blank($card['title'] ?? null))
        <x-page-card
            :url="$card['url'] ?? '#'"
            :title="$card['title'] ?? 'Read more'"
            :text="$card['text'] ?? null"
            :image="$card['image'] ?? null"
            :alt="isset($card['alt']) ? \Illuminate\Support\Str::headline($card['alt']) : null"
            :badge="$card['badge'] ?? null"
            :heading-level="$headingLevel ?? 2" />
    @endforeach
</div>
