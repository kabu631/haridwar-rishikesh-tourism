<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * robots.txt: open to search engines and AI answer engines in production,
     * closed on every other environment.
     */
    public function __invoke(): Response
    {
        $siteUrl = rtrim(config('seo.site_url'), '/');

        if (! config('seo.allow_indexing')) {
            $body = "# Non-production copy of {$siteUrl} – not for indexing\nUser-agent: *\nDisallow: /\n";
        } else {
            $lines = ['# '.config('site.name').' – '.$siteUrl, '# Search engines and AI assistants are welcome to crawl and cite this site.', ''];

            foreach (array_merge(['*'], config('seo.ai_crawlers')) as $agent) {
                $lines[] = 'User-agent: '.$agent;
            }

            foreach (config('seo.disallow') as $path) {
                $lines[] = 'Disallow: '.$path;
            }

            $lines[] = 'Allow: /';
            $lines[] = '';
            $lines[] = 'Sitemap: '.$siteUrl.'/sitemap.xml';

            $body = implode("\n", $lines)."\n";
        }

        return response($body)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * IndexNow key verification file (/{key}.txt).
     */
    public function indexNowKey(string $key): Response
    {
        abort_unless(filled(config('seo.indexnow_key')) && hash_equals((string) config('seo.indexnow_key'), $key), 404);

        return response($key)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
