<?php

namespace App\Observers;

use App\Models\Media;
use App\Models\Page;
use App\Models\Redirect;
use App\Support\Content\HtmlSanitizer;
use App\Support\Media\ImageProcessor;
use App\Support\PageCache;
use App\Support\Seo\IndexNow;

/**
 * Keeps search data in sync and protects rankings: a page whose URL changes
 * or that is deleted automatically gets a 301 redirect, so no indexed URL or
 * backlink ever ends in a 404.
 */
class PageObserver
{
    public function __construct(private HtmlSanitizer $sanitizer, private ImageProcessor $images, private IndexNow $indexNow) {}

    public function saving(Page $page): void
    {
        $page->path = ltrim(trim((string) $page->path), '/');

        if ($page->isDirty('body') && filled($page->body)) {
            $page->body = $this->sanitizer->sanitize($page->body);
        }

        $page->search_text = $page->buildSearchText();
    }

    public function updated(Page $page): void
    {
        if ($page->wasChanged('path') && filled($page->getOriginal('path'))) {
            Redirect::query()->updateOrCreate(
                ['from_path' => '/'.$page->getOriginal('path')],
                ['to_path' => $page->url(), 'status_code' => 301, 'note' => 'Automatic: page URL changed'],
            );
        }
    }

    public function saved(Page $page): void
    {
        // A live page always wins over an old redirect for the same URL.
        Redirect::query()->where('from_path', $page->url())->get()->each->delete();

        if ($page->wasChanged(['hero_image', 'body']) || $page->wasRecentlyCreated) {
            $this->optimiseImages($page);
        }

        if ($page->isIndexable() && $page->isSelfCanonical()) {
            $this->indexNow->submit([$page->absoluteUrl()]);
        }

        PageCache::flush();
    }

    /**
     * Create WebP/AVIF variants for newly added local images.
     */
    private function optimiseImages(Page $page): void
    {
        preg_match_all('#<img[^>]+src="(/[^"]+)"#', (string) $page->body, $matches);

        $paths = collect([$page->hero_image])
            ->merge($matches[1])
            ->filter(fn (?string $path): bool => is_string($path) && str_starts_with($path, '/') && ! str_starts_with($path, '//'))
            ->map(fn (string $path): string => rawurldecode(html_entity_decode($path)))
            ->unique();

        $known = Media::query()->whereIn('path', $paths)->pluck('path')->all();

        foreach ($paths->diff($known) as $path) {
            $this->images->process($path, $path === $page->hero_image);
        }
    }

    public function deleted(Page $page): void
    {
        if (! $page->isHome()) {
            Redirect::query()->updateOrCreate(
                ['from_path' => $page->url()],
                ['to_path' => $page->parent?->url() ?? '/', 'status_code' => 301, 'note' => 'Automatic: page deleted'],
            );
        }

        PageCache::flush();
    }
}
