<?php

namespace App\Console\Commands;

use App\Support\Legacy\LegacyArchive;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Snapshots every page of the legacy website (sitemap URLs plus any internal
 * link discovered while crawling) so the import and the SEO parity check can
 * run repeatedly without hitting the live server.
 */
#[Signature('legacy:fetch {--fresh : Re-download pages that are already archived} {--concurrency=4 : Parallel requests}')]
#[Description('Download a snapshot of the legacy haridwarrishikeshtourism.com site')]
class FetchLegacySite extends Command
{
    public function handle(LegacyArchive $archive): int
    {
        $origin = rtrim(config('legacy.origin'), '/');
        $manifest = $archive->manifest();

        $sitemap = $this->fetchSitemap($origin);
        $this->info('Sitemap URLs: '.count($sitemap));

        $queue = [];
        foreach ($sitemap as $path => $lastmod) {
            $manifest[$path] = array_merge($manifest[$path] ?? [], ['in_sitemap' => true, 'lastmod' => $lastmod]);
            $queue[$path] = true;
        }

        foreach (config('legacy.extra_paths') as $path) {
            $queue[$path] = true;
        }

        $seen = [];

        while ($queue !== []) {
            $batch = array_slice(array_keys($queue), 0, (int) $this->option('concurrency'));

            foreach ($batch as $path) {
                unset($queue[$path]);
                $seen[$path] = true;
            }

            $toDownload = array_values(array_filter(
                $batch,
                fn (string $path): bool => $this->option('fresh') || ! $archive->has($path),
            ));

            $responses = $toDownload === [] ? [] : Http::pool(fn (Pool $pool) => array_map(
                fn (string $path) => $pool->as($path)
                    ->withUserAgent(config('legacy.user_agent'))
                    ->timeout(30)
                    ->withOptions(['allow_redirects' => ['track_redirects' => true], 'verify' => config('legacy.ca_bundle')])
                    ->get($origin.'/'.$path),
                $toDownload,
            ));

            foreach ($batch as $path) {
                $response = $responses[$path] ?? null;

                if ($response instanceof Response) {
                    $manifest[$path] = array_merge($manifest[$path] ?? [], [
                        'status' => $response->status(),
                        'redirects' => $response->header('X-Guzzle-Redirect-History') ?: null,
                        'fetched_at' => now()->toIso8601String(),
                    ]);

                    if ($response->successful()) {
                        $archive->put($path, $response->body());
                    }

                    $this->line(sprintf('  %s %s', $response->status(), $path));
                } elseif ($response instanceof Throwable) {
                    $manifest[$path] = array_merge($manifest[$path] ?? [], ['status' => 0, 'error' => $response->getMessage()]);
                    $this->warn("  ERR {$path}: {$response->getMessage()}");
                }

                if (! $archive->has($path) || ! str_ends_with($path, '.html')) {
                    continue;
                }

                foreach ($archive->internalLinks($archive->get($path)) as $linked) {
                    if (! isset($seen[$linked]) && ! isset($queue[$linked])) {
                        $queue[$linked] = true;
                        $manifest[$linked] = array_merge($manifest[$linked] ?? [], ['in_sitemap' => $manifest[$linked]['in_sitemap'] ?? false, 'found_on' => $path]);
                    }
                }
            }

            $archive->saveManifest($manifest);
        }

        $ok = collect($manifest)->where('status', 200)->count();
        $this->info("Done. {$ok} of ".count($manifest).' legacy URLs archived with HTTP 200.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string|null> path => lastmod
     */
    private function fetchSitemap(string $origin): array
    {
        $xml = Http::withUserAgent(config('legacy.user_agent'))->withOptions(['verify' => config('legacy.ca_bundle')])->timeout(30)->get($origin.'/sitemap.xml')->throw()->body();

        preg_match_all('#<url>\s*<loc>([^<]+)</loc>(?:\s*<lastmod>([^<]+)</lastmod>)?#', $xml, $matches, PREG_SET_ORDER);

        $paths = [];
        foreach ($matches as $match) {
            $path = ltrim((string) parse_url(trim($match[1]), PHP_URL_PATH), '/');
            $paths[$path] = $match[2] ?? null;
        }

        return $paths;
    }
}
