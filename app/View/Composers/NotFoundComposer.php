<?php

namespace App\View\Composers;

use App\Models\Page;
use App\Support\Search\PageSearch;
use App\Support\Seo\SeoBuilder;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Suggests pages matching the words of a URL that was not found, e.g.
 * "/har-ki-paudi.html" suggests "Har Ki Pauri".
 */
class NotFoundComposer
{
    public function __construct(private PageSearch $search, private SeoBuilder $seo) {}

    public function compose(View $view): void
    {
        $words = Str::of(request()->path())->afterLast('/')->beforeLast('.')->replace(['-', '_', '+'], ' ')->squish()->toString();

        try {
            $suggestions = $words === '' ? collect() : $this->search->suggest($words, 6);

            if ($suggestions->isEmpty()) {
                $suggestions = Page::query()->published()->whereIn('path', ['haridwar-tourism.html', 'rishikesh-tourism.html', 'tour-packages.html', 'trekking.html', 'adventure-tourism.html', 'uttarakhand-destinations.html'])->get();
            }
        } catch (Throwable) {
            $suggestions = collect();
        }

        $view->with([
            'suggestions' => $suggestions,
            'searchTerms' => $words,
            'seo' => $this->seo->forView('Page not found – '.config('site.name'), 'The page you were looking for could not be found.'),
        ]);
    }
}
