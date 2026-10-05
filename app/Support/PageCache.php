<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Vite;

/**
 * Full-page HTML cache for public pages. Keys are versioned so any content
 * change (page, menu, setting, testimonial) invalidates every cached page in
 * one step, regardless of the cache driver. Keys also carry the asset build
 * fingerprint, so `npm run build` retires pages that link to old CSS/JS files.
 */
class PageCache
{
    private const VERSION_KEY = 'page-cache.version';

    public static function key(string $path): string
    {
        return 'page-cache.'.self::version().'.'.self::buildFingerprint().'.'.sha1(strtolower($path));
    }

    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn (): int => 1);
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    /**
     * Short hash of the Vite manifest ("hot" while the dev server runs).
     */
    private static function buildFingerprint(): string
    {
        return substr(Vite::manifestHash() ?? 'hot', 0, 12);
    }
}
