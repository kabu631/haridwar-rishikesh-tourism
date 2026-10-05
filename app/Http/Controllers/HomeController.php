<?php

namespace App\Http\Controllers;

use App\Enums\PageType;
use App\Models\Page;
use App\Models\Testimonial;
use App\Support\Seo\SeoBuilder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(SeoBuilder $seo): View
    {
        $page = Page::query()->where('path', '')->firstOrFail();

        $packages = Page::query()
            ->published()
            ->where('type', PageType::Package)
            ->where('section', 'packages')
            ->where(fn ($query) => $query->where('is_featured', true)->orWhere('path', 'like', 'haridwar-rishikesh-tour-packages-ex-%'))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(8)
            ->get(['id', 'path', 'title', 'nav_label', 'type', 'section', 'excerpt', 'meta_description', 'hero_image', 'hero_alt', 'extra', 'facts', 'itinerary']);

        return view('pages.home', [
            'page' => $page,
            'home' => $page->extra['home'] ?? [],
            'packages' => $packages,
            'testimonials' => Testimonial::query()->published()->topRatedOnTripadvisor()->limit(8)->get(),
            'seo' => $seo->forPage($page),
        ]);
    }
}
