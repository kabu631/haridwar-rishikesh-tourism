@php
    $encode = fn (string $path): string => implode('/', array_map('rawurlencode', explode('/', $path)));
    echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
@endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($pages as $page)
    @php
        preg_match_all('#<img[^>]+src="(/[^"]+)"#', (string) $page->body, $matches);
        $images = collect([$page->hero_image])
            ->merge($matches[1])
            ->merge(collect($page->gallery ?? [])->pluck('image'))
            ->filter(fn ($src) => is_string($src) && str_starts_with($src, '/'))
            ->map(fn ($src) => html_entity_decode($src))
            ->unique()
            ->take(20);
    @endphp
    <url>
        <loc>{{ $siteUrl }}/{{ $page->path }}</loc>
        <lastmod>{{ $page->updated_at?->toAtomString() }}</lastmod>
@foreach ($images as $image)
        <image:image><image:loc>{{ $siteUrl }}{{ $encode($image) }}</image:loc></image:image>
@endforeach
    </url>
@endforeach
</urlset>
