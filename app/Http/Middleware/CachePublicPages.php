<?php

namespace App\Http\Middleware;

use App\Support\PageCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Full-page cache for public HTML (fast TTFB). Only cacheable requests are
 * stored: GET/HEAD, no query string other than tracking parameters, and a
 * 200 HTML response. HTML is lightly minified before caching.
 */
class CachePublicPages
{
    private const TRACKING_PARAMETERS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'msclkid', 'ref'];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isCacheable($request)) {
            return $next($request);
        }

        $key = PageCache::key($request->getPathInfo());

        if (is_array($cached = Cache::get($key))) {
            return response($cached['content'], 200, [
                'Content-Type' => $cached['type'],
                'X-Page-Cache' => 'HIT',
            ]);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200 && str_contains((string) $response->headers->get('Content-Type'), 'text/html') && ! $response->headers->has('Set-Cookie')) {
            $content = $this->minify((string) $response->getContent());
            $response->setContent($content);

            Cache::put($key, ['content' => $content, 'type' => $response->headers->get('Content-Type')], config('seo.page_cache_ttl'));
            $response->headers->set('X-Page-Cache', 'MISS');
        }

        return $response;
    }

    private function isCacheable(Request $request): bool
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true) || config('seo.page_cache_ttl') <= 0) {
            return false;
        }

        return array_diff(array_keys($request->query()), self::TRACKING_PARAMETERS) === [];
    }

    /**
     * Remove HTML comments and whitespace between tags, leaving <pre>,
     * <textarea> and <script> contents untouched.
     */
    private function minify(string $html): string
    {
        $preserved = [];

        $html = preg_replace_callback('#<(pre|textarea|script)\b[^>]*>.*?</\1>#is', function (array $match) use (&$preserved): string {
            $preserved[] = $match[0];

            return '%%PRESERVED'.(count($preserved) - 1).'%%';
        }, $html);

        $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);
        $html = preg_replace('/>\s+</', '> <', $html);
        $html = preg_replace('/\n\s+/', "\n", $html);

        return preg_replace_callback('/%%PRESERVED(\d+)%%/', fn (array $match): string => $preserved[(int) $match[1]], trim($html));
    }
}
