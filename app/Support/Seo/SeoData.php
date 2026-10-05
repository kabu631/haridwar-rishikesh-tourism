<?php

namespace App\Support\Seo;

/**
 * Everything printed in <head> for one response.
 */
class SeoData
{
    /**
     * @param  list<array{name: string, url: string}>  $breadcrumbs
     * @param  list<array<string, mixed>>  $schema  JSON-LD @graph nodes
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $canonical = null,
        public string $robots = 'index,follow',
        public ?string $image = null,
        public ?string $imageAlt = null,
        public ?int $imageWidth = null,
        public ?int $imageHeight = null,
        public string $ogType = 'website',
        public ?string $publishedTime = null,
        public ?string $modifiedTime = null,
        public ?string $keywords = null,
        public ?string $placename = null,
        public array $breadcrumbs = [],
        public array $schema = [],
        public ?string $preloadImage = null,
        public ?string $preloadImageSrcset = null,
        public bool $hreflang = true,
    ) {}

    public function isIndexable(): bool
    {
        return ! str_contains(strtolower($this->robots), 'noindex');
    }

    /**
     * The JSON-LD document for the <script type="application/ld+json"> tag.
     * Built here rather than in Blade, where "@context" is a directive.
     */
    public function jsonLd(): string
    {
        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $this->schema],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR,
        );
    }
}
