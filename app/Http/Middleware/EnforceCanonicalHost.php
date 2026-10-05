<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends http:// and bare-domain requests to the canonical
 * https://www. origin in ONE 301 hop (no http → https → www chain).
 */
class EnforceCanonicalHost
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('seo.force_canonical_host') || ! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $canonical = parse_url(config('seo.site_url'));
        $scheme = $canonical['scheme'] ?? 'https';
        $host = $canonical['host'] ?? $request->getHost();

        if ($request->getScheme() === $scheme && strcasecmp($request->getHost(), $host) === 0) {
            return $next($request);
        }

        return redirect()->to($scheme.'://'.$host.$request->getRequestUri(), 301);
    }
}
