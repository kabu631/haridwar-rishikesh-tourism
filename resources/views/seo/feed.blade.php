@php
    echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
@endphp
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title>{{ config('site.name') }}</title>
        <link>{{ $siteUrl }}/</link>
        <description>{{ config('site.tagline') }}</description>
        <language>en-in</language>
        <atom:link href="{{ $siteUrl }}/feed.xml" rel="self" type="application/rss+xml" />
        @if ($pages->isNotEmpty())
            <lastBuildDate>{{ $pages->first()->updated_at?->toRssString() }}</lastBuildDate>
        @endif
        <image>
            <url>{{ $siteUrl }}/icon-192.png</url>
            <title>{{ config('site.name') }}</title>
            <link>{{ $siteUrl }}/</link>
        </image>
@foreach ($pages as $page)
        <item>
            <title>{{ $page->title }}</title>
            <link>{{ $siteUrl }}/{{ $page->path }}</link>
            <guid isPermaLink="true">{{ $siteUrl }}/{{ $page->path }}</guid>
            <pubDate>{{ ($page->updated_at ?? $page->published_at)?->toRssString() }}</pubDate>
            @if ($page->author)
                <dc:creator>{{ $page->author->name }}</dc:creator>
            @endif
            @if ($page->section)
                <category>{{ \Illuminate\Support\Str::headline($page->section) }}</category>
            @endif
            <description>{{ $page->meta_description ?: $page->excerpt }}</description>
        </item>
@endforeach
    </channel>
</rss>
