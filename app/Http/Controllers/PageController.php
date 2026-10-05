<?php

namespace App\Http\Controllers;

use App\Enums\PageType;
use App\Models\Author;
use App\Models\Page;
use App\Support\Content\ContentRenderer;
use App\Support\Media\MediaLibrary;
use App\Support\Seo\SeoBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PageController extends Controller
{
    private const BLOG_PATH = 'blog.html';

    public function __construct(private SeoBuilder $seo, private ContentRenderer $renderer, private MediaLibrary $media) {}

    /**
     * Any legacy "*.html" URL, served at exactly the same address.
     */
    public function show(string $path): View|RedirectResponse
    {
        $page = Page::query()
            ->published()
            ->where('path', $path)
            ->with(['parent.parent.parent', 'author', 'reviewer'])
            ->first();

        abort_if($page === null, 404);

        // Same page requested with different letter case: send to the indexed URL.
        if ($page->path !== $path) {
            return redirect()->to($page->absoluteUrl(), 301);
        }

        // Cards that sat under a heading on the legacy page are shown under that heading again.
        $cardGroups = collect($page->cards ?? [])
            ->filter(fn (array $card): bool => filled($card['group'] ?? null))
            ->groupBy(fn (array $card): string => $this->headingKey($card['group']));
        $placedGroups = [];

        $content = $this->renderer->render($page->body, $page->title, function (string $heading, bool $empty) use ($page, $cardGroups, &$placedGroups): ?string {
            $key = $this->headingKey($heading);
            $html = '';

            if ($cardGroups->has($key) && ! isset($placedGroups[$key])) {
                $placedGroups[$key] = true;
                $html = view('pages.partials.card-grid', ['cards' => $cardGroups[$key], 'class' => 'my-8', 'headingLevel' => 3])->render();
            } elseif ($empty) {
                $html = $this->blockForEmptyHeading($page, $key);
            }

            // Blocks sit between two runs of article typography, not inside it.
            return $html === '' ? null : '</div>'.$html.'<div class="prose-content mt-10">';
        });

        // "Book this tour now" opens the booking form with this tour filled in and locked.
        $content['html'] = str_replace('href="/book-now.php"', 'href="'.$this->bookingUrl($page).'"', $content['html']);

        $cards = collect($page->cards ?? [])
            ->reject(fn (array $card): bool => isset($placedGroups[$this->headingKey($card['group'] ?? '')]))
            ->values();

        [$slides, $gallery] = collect($page->gallery ?? [])
            ->filter(fn (array $item): bool => filled($item['image'] ?? null) || filled($item['youtube'] ?? null))
            ->partition(fn (array $item): bool => ! empty($item['slide']) && $this->media->exists($item['image'] ?? null));

        return view('pages.show', [
            'page' => $page,
            'seo' => $this->seo->forPage($page),
            'content' => $content['html'],
            'toc' => count($content['toc']) >= 4 ? $content['toc'] : [],
            'cards' => $cards,
            'slides' => $slides->values(),
            'gallery' => $gallery->values(),
            'siblings' => $this->siblings($page),
            'related' => $page->path === self::BLOG_PATH ? collect() : $this->related($page),
            'authors' => $page->path === 'our-team.html' ? Author::query()->orderBy('id')->get() : collect(),
            'posts' => $page->path === self::BLOG_PATH ? $this->blogPosts() : null,
        ]);
    }

    /**
     * Legacy headings whose content was a widget the import could not keep (a
     * dead "Book Now" image, an enquiry form) get working booking buttons, so
     * no heading is left with nothing under it.
     */
    private function blockForEmptyHeading(Page $page, string $heading): string
    {
        if ($page->path !== 'book-now.php' && preg_match('/^(book your|book now)\b|\bbook now$|^(enquiry|booking) form$|^send your query|^for more information contact/', $heading)) {
            return view('pages.partials.section-cta', ['page' => $page])->render();
        }

        return '';
    }

    private function bookingUrl(Page $page): string
    {
        return $page->type === PageType::Package
            ? '/book-now.php?'.http_build_query(['tour' => $page->path])
            : '/book-now.php';
    }

    private function headingKey(string $text): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text)))));
    }

    /**
     * Published blog posts, newest first (the /blog.html listing).
     *
     * @return LengthAwarePaginator<int, Page>
     */
    private function blogPosts(): LengthAwarePaginator
    {
        return Page::query()
            ->published()
            ->where('type', PageType::Blog)
            ->with('author')
            ->latest('published_at')
            ->paginate(12)
            ->withPath('/'.self::BLOG_PATH);
    }

    /**
     * Pages in the same section (sidebar navigation).
     *
     * @return Collection<int, Page>
     */
    private function siblings(Page $page): Collection
    {
        $parentId = $page->type === PageType::Hub ? $page->id : $page->parent_id;

        if ($parentId === null) {
            return collect();
        }

        return Page::query()
            ->published()
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->get(['id', 'path', 'title', 'nav_label', 'type']);
    }

    /**
     * Related reading with images: siblings first, then pages in the same section.
     *
     * @return Collection<int, Page>
     */
    private function related(Page $page): Collection
    {
        if ($page->type === PageType::Company) {
            return collect();
        }

        $columns = ['id', 'path', 'title', 'nav_label', 'type', 'section', 'excerpt', 'meta_description', 'hero_image', 'hero_alt', 'extra', 'facts', 'itinerary'];
        $linkedFromCards = collect($page->cards ?? [])->pluck('url')->map(fn (?string $url): string => ltrim((string) $url, '/'))->all();

        $related = Page::query()
            ->published()
            ->whereKeyNot($page->id)
            ->where('parent_id', $page->parent_id ?? $page->id)
            ->whereNotIn('path', $linkedFromCards)
            ->orderBy('sort_order')
            ->limit(6)
            ->get($columns);

        if ($related->count() < 3 && $page->section !== null) {
            $related = $related->concat(
                Page::query()->published()
                    ->whereKeyNot($page->id)
                    ->where('section', $page->section)
                    ->whereNotIn('id', $related->pluck('id'))
                    ->whereNotIn('path', $linkedFromCards)
                    ->inRandomOrder(crc32($page->path))
                    ->limit(6 - $related->count())
                    ->get($columns)
            );
        }

        return $related;
    }
}
