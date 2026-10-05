<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\Content\ContentRenderer;
use App\Support\Legacy\PageClassifier;
use App\Support\Seo\SeoBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SitemapController extends Controller
{
    /**
     * XML sitemap with image extensions. Lists only indexable, self-canonical
     * URLs with their real last-modified dates.
     */
    public function xml(): Response
    {
        $pages = $this->indexablePages(['id', 'path', 'title', 'body', 'hero_image', 'hero_alt', 'gallery', 'updated_at']);

        return response()
            ->view('seo.sitemap', ['pages' => $pages, 'siteUrl' => rtrim(config('seo.site_url'), '/')])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Human readable sitemap (kept at the legacy /sitemap.html address). The
     * heading, intro and SEO tags come from the "sitemap.html" page in the
     * admin (Company & legal); the list of links is always generated.
     */
    public function html(SeoBuilder $seo, ContentRenderer $renderer): View
    {
        $page = Page::query()->published()->where('path', 'sitemap.html')->first();

        $sections = Page::query()
            ->published()
            ->whereNotIn('path', ['', 'sitemap.html'])
            ->orderBy('sort_order')
            ->get(['id', 'path', 'title', 'nav_label', 'section', 'parent_id', 'type'])
            ->groupBy('section');

        return view('pages.sitemap', [
            'page' => $page,
            'content' => $page ? $renderer->render($page->body, $page->title)['html'] : '',
            'sections' => collect(PageClassifier::SECTION_LABELS)
                ->map(fn (string $label, string $key): array => ['label' => $label, 'pages' => $sections->get($key, collect())])
                ->filter(fn (array $section): bool => $section['pages']->isNotEmpty()),
            'seo' => $page ? $seo->forPage($page) : $seo->forView('Sitemap – Haridwar Rishikesh Tourism', 'All Haridwar Rishikesh Tourism pages: tour packages, Haridwar and Rishikesh travel guides, temples, treks, festivals and travel information.', 'sitemap.html', true),
        ]);
    }

    /**
     * Plain text URL list (legacy /urllist.txt, referenced by old robots.txt).
     */
    public function urlList(): Response
    {
        $siteUrl = rtrim(config('seo.site_url'), '/');

        return response($this->indexablePages(['path'])->map(fn (Page $page): string => $siteUrl.'/'.$page->path)->implode("\n")."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * @param  list<string>  $columns
     * @return Collection<int, Page>
     */
    private function indexablePages(array $columns): Collection
    {
        return Page::query()->indexable()->orderBy('sort_order')->get($columns);
    }
}
