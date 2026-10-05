<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Serves 301/302/410 responses from the redirects table (managed in the
 * admin panel). Chains are pre-resolved so every redirect is a single hop.
 * Also removes trailing slashes from page URLs ("/page.html/" → "/page.html").
 */
class RedirectLegacyUrls
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = '/'.ltrim($request->getPathInfo(), '/');

        if ($path !== '/' && str_ends_with($path, '/') && preg_match('#\.(html|php)/+$#i', $path)) {
            return $this->redirect($request, rtrim($path, '/'), 301);
        }

        if (str_starts_with($path, '/admin') || str_starts_with($path, '/livewire') || str_starts_with($path, '/build/') || str_starts_with($path, '/_optimized/')) {
            return $next($request);
        }

        try {
            $match = Redirect::map()[strtolower(rawurldecode($path))] ?? null;
        } catch (Throwable) {
            $match = null;
        }

        if ($match === null) {
            return $next($request);
        }

        [$to, $status] = $match;

        $this->recordHit($path);

        if ($status === 410 || $to === null) {
            abort(410);
        }

        return $this->redirect($request, $to, $status);
    }

    private function redirect(Request $request, string $to, int $status): Response
    {
        $query = $request->getQueryString();

        if (! preg_match('#^https?://#i', $to)) {
            $to = rtrim(config('seo.site_url'), '/').'/'.ltrim($to, '/');
        }

        return redirect()->to($to.($query ? (str_contains($to, '?') ? '&' : '?').$query : ''), in_array($status, [301, 302, 307, 308], true) ? $status : 301, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function recordHit(string $path): void
    {
        defer(function () use ($path): void {
            DB::table('redirects')->where('from_path', $path)->update([
                'hits' => DB::raw('hits + 1'),
                'last_hit_at' => now(),
            ]);
        });
    }
}
