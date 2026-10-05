<?php

namespace App\Support\Seo;

use App\Models\Page;
use App\Support\Media\MediaLibrary;

/**
 * Creates the SeoData for pages and system views.
 */
class SeoBuilder
{
    public function __construct(private SchemaBuilder $schema, private MediaLibrary $media) {}

    public function forPage(Page $page): SeoData
    {
        $crumbs = $this->breadcrumbs($page);
        $imagePath = $page->hero_image ?: ($page->extra['thumbnail'] ?? null) ?: config('seo.default_image');

        if ($page->isHome()) {
            $imagePath = $page->extra['home']['hero']['image'] ?? $imagePath;
        }

        $image = $this->absolute($imagePath);
        $media = $this->media->find($imagePath);

        return new SeoData(
            title: $page->seoTitle(),
            description: $page->meta_description,
            canonical: $page->canonical(),
            robots: $this->robots($page),
            image: $image,
            imageAlt: $page->hero_alt ?: $page->title,
            imageWidth: $media['w'] ?? null,
            imageHeight: $media['h'] ?? null,
            ogType: $page->isHome() || $page->type?->value === 'hub' ? 'website' : 'article',
            publishedTime: $page->published_at?->toIso8601String(),
            modifiedTime: $page->updated_at?->toIso8601String(),
            keywords: config('seo.render_meta_keywords') ? $page->meta_keywords : null,
            placename: $this->placename($page),
            breadcrumbs: $crumbs,
            schema: $this->schema->forPage($page, $crumbs, $image),
            hreflang: $page->isSelfCanonical(),
        );
    }

    /**
     * SEO data for application views (search, booking, errors).
     */
    public function forView(string $title, ?string $description = null, ?string $path = null, bool $index = false): SeoData
    {
        return new SeoData(
            title: $title,
            description: $description,
            canonical: $path !== null ? rtrim(config('seo.site_url'), '/').'/'.ltrim($path, '/') : null,
            robots: $index && config('seo.allow_indexing') ? config('seo.default_robots') : 'noindex,follow',
            image: $this->absolute(config('seo.default_image')),
            schema: [$this->schema->organization(), $this->schema->website()],
            hreflang: false,
        );
    }

    /**
     * Home › Section hub › … › Page
     *
     * @return list<array{name: string, url: string}>
     */
    public function breadcrumbs(Page $page): array
    {
        if ($page->isHome()) {
            return [];
        }

        $trail = [];
        $node = $page;
        $guard = 0;

        while ($node !== null && $guard++ < 6) {
            array_unshift($trail, ['name' => $node->label(), 'url' => $node->absoluteUrl()]);
            $node = $node->parent;
        }

        array_unshift($trail, ['name' => 'Home', 'url' => rtrim(config('seo.site_url'), '/').'/']);

        return $trail;
    }

    private function robots(Page $page): string
    {
        if (! config('seo.allow_indexing') || ! $page->is_published) {
            return 'noindex,nofollow';
        }

        $robots = strtolower(str_replace(' ', '', $page->robots ?: 'index,follow'));

        if (str_contains($robots, 'noindex') || str_contains($robots, 'nofollow')) {
            return $robots;
        }

        return config('seo.default_robots');
    }

    private function placename(Page $page): string
    {
        return match ($page->section) {
            'rishikesh', 'adventure' => 'Rishikesh',
            'mussoorie' => 'Mussoorie',
            'destinations', 'trekking', 'festivals' => 'Uttarakhand',
            default => 'Haridwar',
        };
    }

    private function absolute(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return rtrim(config('seo.site_url'), '/').implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
