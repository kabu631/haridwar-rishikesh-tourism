<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Widgets\Widget;

/**
 * At-a-glance on-page SEO checks across all published pages.
 */
class SeoHealth extends Widget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.seo-health';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $pages = Page::query()->published()->get(['id', 'title', 'meta_title', 'meta_description', 'robots', 'body', 'type', 'path', 'hero_alt', 'hero_image']);

        $checks = [
            ['label' => 'Title tag outside 30–65 characters', 'count' => $pages->filter(fn (Page $page): bool => ($length = mb_strlen($page->seoTitle())) < 30 || $length > 65)->count(), 'filter' => 'seo_issues'],
            ['label' => 'Missing or too short/long meta description', 'count' => $pages->filter(fn (Page $page): bool => ($length = mb_strlen((string) $page->meta_description)) < 70 || $length > 170)->count(), 'filter' => 'seo_issues'],
            ['label' => 'Header image without alt text', 'count' => $pages->filter(fn (Page $page): bool => filled($page->hero_image) && blank($page->hero_alt))->count(), 'filter' => null],
            ['label' => 'Pages set to noindex', 'count' => $pages->filter(fn (Page $page): bool => str_contains(strtolower($page->robots), 'noindex'))->count(), 'filter' => null],
            ['label' => 'Duplicate title tags', 'count' => $pages->groupBy(fn (Page $page): string => mb_strtolower($page->seoTitle()))->filter(fn ($group) => $group->count() > 1)->flatten()->count(), 'filter' => null],
            ['label' => 'Not reviewed in 12 months', 'count' => Page::query()->published()->where(fn ($query) => $query->whereNull('reviewed_at')->orWhere('reviewed_at', '<', now()->subYear()))->count(), 'filter' => 'stale'],
        ];

        return [
            'checks' => $checks,
            'total' => $pages->count(),
            'pagesUrl' => PageResource::getUrl('index'),
        ];
    }
}
