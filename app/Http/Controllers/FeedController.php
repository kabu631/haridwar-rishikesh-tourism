<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    /**
     * RSS 2.0 feed of the most recently updated guides and packages.
     */
    public function __invoke(): Response
    {
        $pages = Page::query()
            ->indexable()
            ->where('path', '!=', '')
            ->with('author:id,name')
            ->latest('updated_at')
            ->limit(40)
            ->get(['id', 'path', 'title', 'meta_description', 'excerpt', 'section', 'hero_image', 'author_id', 'updated_at', 'published_at']);

        return response()
            ->view('seo.feed', ['pages' => $pages, 'siteUrl' => rtrim(config('seo.site_url'), '/')])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
