<?php

namespace App\Models;

use Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['from_path', 'to_path', 'status_code', 'hits', 'last_hit_at', 'note'])]
class Redirect extends Model
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory;

    public const CACHE_KEY = 'redirects.map';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect): void {
            $redirect->from_path = self::normalisePath($redirect->from_path);

            if (filled($redirect->to_path) && ! preg_match('#^https?://#i', $redirect->to_path)) {
                $redirect->to_path = self::normalisePath($redirect->to_path);
            }
        });

        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Reduce a path or same-site URL to a root relative path ("/page.html").
     */
    public static function normalisePath(string $path): string
    {
        $path = trim($path);
        $siteHost = parse_url(config('seo.site_url'), PHP_URL_HOST);

        if (preg_match('#^https?://([^/]+)(/.*)?$#i', $path, $match) && $siteHost !== null && ltrim(strtolower($match[1]), 'w.') === ltrim(strtolower($siteHost), 'w.')) {
            $path = $match[2] ?? '/';
        }

        return '/'.ltrim($path, '/');
    }

    /**
     * Cached map of lowercase from_path => [to_path, status_code] used by the
     * redirect middleware. Chains are resolved to their final destination so
     * every redirect is a single hop.
     *
     * @return array<string, array{0: ?string, 1: int}>
     */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $rows = self::query()->get(['from_path', 'to_path', 'status_code'])
                ->mapWithKeys(fn (Redirect $redirect) => [strtolower($redirect->from_path) => [$redirect->to_path, $redirect->status_code]])
                ->all();

            foreach ($rows as $from => [$to, $status]) {
                $seen = [$from => true];

                while ($to !== null && isset($rows[strtolower($to)]) && ! isset($seen[strtolower($to)])) {
                    $seen[strtolower($to)] = true;
                    [$to, $nextStatus] = $rows[strtolower($to)];
                    $status = $nextStatus === 410 ? 410 : $status;
                }

                $rows[$from] = [$to, $status];
            }

            return $rows;
        });
    }
}
