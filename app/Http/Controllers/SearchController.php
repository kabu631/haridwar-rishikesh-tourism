<?php

namespace App\Http\Controllers;

use App\Enums\PageType;
use App\Models\Page;
use App\Support\Media\MediaLibrary;
use App\Support\Search\PageSearch;
use App\Support\Seo\SeoBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Best-selling packages that fill the sidebar after any packages marked
     * "Feature on homepage" in the admin.
     */
    private const BEST_SELLERS = [
        'haridwar-tour-package.html', 'rishikesh-tour-package.html', 'haridwar-rishikesh-tour.html',
        'chardham-yatra-tour-packages.html', 'rishikesh-rafting-and-camping.html', 'haridwar-rishikesh-with-mussoorie-tour.html',
    ];

    private const SIDEBAR_PACKAGES = 5;

    public function __construct(private PageSearch $search) {}

    /**
     * Search results page (noindex, follow).
     */
    public function index(Request $request, SeoBuilder $seo): View
    {
        $query = trim((string) $request->string('q'));
        $type = in_array($request->query('type'), array_column(PageType::cases(), 'value'), true) ? $request->query('type') : null;

        $results = $query === '' ? null : $this->search->paginate($query, $type);

        return view('pages.search', [
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'snippets' => $results ? $results->getCollection()->mapWithKeys(fn (Page $page) => [$page->id => $this->search->snippet($page, $query)]) : collect(),
            'popular' => Page::query()->published()->whereIn('path', [
                'haridwar-tour-package.html', 'rishikesh-tour-package.html', 'har-ki-pauri.html', 'ganga-aarti-haridwar.html',
                'rafting-in-rishikesh.html', 'chardham-yatra-tour-packages.html', 'haridwar-kumbh-mela.html', 'mussoorie.html',
            ])->get(['id', 'path', 'title', 'nav_label']),
            'highlights' => $this->highlightedPackages(),
            'seo' => $seo->forView($query === '' ? 'Search Haridwar Rishikesh Tourism' : 'Search results for "'.$query.'"', 'Search tour packages, temples, ghats, treks and travel guides for Haridwar, Rishikesh and Uttarakhand.', 'search'),
        ]);
    }

    /**
     * Packages shown in the search sidebar: featured ones first, then the
     * best-sellers in their listed order.
     *
     * @return Collection<int, Page>
     */
    private function highlightedPackages(): Collection
    {
        return Page::query()
            ->published()
            ->where('type', PageType::Package)
            ->where(fn (Builder $query) => $query->where('is_featured', true)->orWhereIn('path', self::BEST_SELLERS))
            ->orderBy('sort_order')
            ->get(['id', 'path', 'title', 'nav_label', 'type', 'hero_image', 'hero_alt', 'extra', 'facts', 'itinerary', 'is_featured'])
            ->sortBy(fn (Page $page): int => $page->is_featured ? 0 : 1 + (int) array_search($page->path, self::BEST_SELLERS, true))
            ->take(self::SIDEBAR_PACKAGES)
            ->values();
    }

    /**
     * Instant search suggestions for the header search box.
     */
    public function suggest(Request $request, MediaLibrary $media): JsonResponse
    {
        $query = trim((string) $request->string('q'));

        if (mb_strlen($query) < 2) {
            return response()->json(['query' => $query, 'results' => []]);
        }

        $results = $this->search->suggest($query)->map(fn (Page $page): array => [
            'title' => $page->title,
            'url' => $page->url(),
            'type' => $page->type?->badge(),
            'section' => $page->parent?->label(),
            'image' => ($thumb = $page->extra['thumbnail'] ?? $page->hero_image) ? ($media->bestVariant($thumb, 160) ?? $thumb) : null,
        ]);

        return response()->json(['query' => $query, 'results' => $results])
            ->header('Cache-Control', 'public, max-age=300')
            ->header('X-Robots-Tag', 'noindex');
    }
}
