<?php

namespace App\Support\Seo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notifies IndexNow search engines (Bing, Yandex, Seznam, Naver…) that URLs
 * changed, so updates are re-crawled within minutes instead of days.
 */
class IndexNow
{
    public function enabled(): bool
    {
        return filled(config('seo.indexnow_key')) && config('seo.allow_indexing') && app()->isProduction();
    }

    /**
     * Submit URLs after the response has been sent (never slows the admin).
     *
     * @param  list<string>  $urls
     */
    public function submit(array $urls): void
    {
        if (! $this->enabled() || $urls === []) {
            return;
        }

        $siteUrl = rtrim(config('seo.site_url'), '/');
        $key = config('seo.indexnow_key');

        defer(function () use ($urls, $siteUrl, $key): void {
            try {
                Http::timeout(5)->post('https://api.indexnow.org/indexnow', [
                    'host' => parse_url($siteUrl, PHP_URL_HOST),
                    'key' => $key,
                    'keyLocation' => $siteUrl.'/'.$key.'.txt',
                    'urlList' => array_values(array_unique($urls)),
                ]);
            } catch (Throwable $exception) {
                Log::warning('IndexNow submission failed', ['error' => $exception->getMessage()]);
            }
        });
    }
}
