@props([
    'url',
    'title',
    'text' => null,
    'image' => null,
    'alt' => null,
    'badge' => null,
    'meta' => null,
    'external' => false,
    'headingLevel' => 3,
])
<article {{ $attributes->merge(['class' => 'group relative flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-ink-900/5 transition duration-300 hover:-translate-y-1 hover:shadow-lift']) }}>
    <div class="relative aspect-[16/10] overflow-hidden bg-sand-200">
        <x-picture :src="$image" :alt="$alt ?: $title" sizes="(min-width: 1280px) 380px, (min-width: 640px) 50vw, 100vw" class="size-full object-cover transition duration-500 group-hover:scale-105" />
        @if ($badge)
            <span class="absolute top-3 left-3 inline-flex items-center gap-1 rounded-full bg-white/95 px-3 py-1 text-xs font-semibold text-brand-800 shadow-sm backdrop-blur">
                <x-glyph name="clock" class="size-3.5" /> {{ $badge }}
            </span>
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-2 p-5">
        @if ($meta)
            <p class="text-xs font-semibold tracking-wide text-brand-700 uppercase">{{ $meta }}</p>
        @endif
        <h{{ $headingLevel }} class="font-display text-lg leading-snug font-semibold text-ink-900">
            <a href="{{ $url }}" class="after:absolute after:inset-0 focus-visible:outline-none" @if ($external) target="_blank" rel="noopener" @endif>{{ $title }}</a>
        </h{{ $headingLevel }}>
        @if ($text)
            <p class="line-clamp-3 text-[15px] leading-relaxed text-ink-500">{{ $text }}</p>
        @endif
        <span class="mt-auto inline-flex items-center gap-1.5 pt-2 text-sm font-semibold text-brand-700 transition group-hover:gap-2.5">
            {{ $slot->isNotEmpty() ? $slot : 'Read more' }} <x-glyph name="arrow-right" class="size-4" />
        </span>
    </div>
</article>
