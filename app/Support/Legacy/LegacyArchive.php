<?php

namespace App\Support\Legacy;

use Illuminate\Support\Facades\File;

/**
 * Local, file based snapshot of the legacy website.
 */
class LegacyArchive
{
    private string $root;

    public function __construct()
    {
        $this->root = config('legacy.archive_path');
    }

    public function has(string $path): bool
    {
        return File::exists($this->pathFor($path));
    }

    public function get(string $path): string
    {
        return File::get($this->pathFor($path));
    }

    public function put(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($this->pathFor($path)));
        File::put($this->pathFor($path), $contents);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function manifest(): array
    {
        $file = $this->root.'/manifest.json';

        return File::exists($file) ? json_decode(File::get($file), true) : [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $manifest
     */
    public function saveManifest(array $manifest): void
    {
        ksort($manifest);
        File::ensureDirectoryExists($this->root);
        File::put($this->root.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Legacy pages that were archived successfully, keyed by path.
     *
     * @return array<string, array<string, mixed>>
     */
    public function pages(): array
    {
        return collect($this->manifest())
            ->filter(fn (array $entry, string $path): bool => ($entry['status'] ?? 0) === 200
                && (str_ends_with($path, '.html') || $path === '' || $path === 'book-now.php')
                && $this->has($path))
            ->all();
    }

    /**
     * Same-site page links (.html / .php) found in a legacy document.
     *
     * @return list<string>
     */
    public function internalLinks(string $html): array
    {
        preg_match_all('/href\s*=\s*["\']([^"\'#]+)["\']/i', $html, $matches);

        $host = parse_url(config('legacy.origin'), PHP_URL_HOST);
        $links = [];

        foreach ($matches[1] as $href) {
            $href = trim(html_entity_decode($href));
            $parts = parse_url($href);

            if ($parts === false) {
                continue;
            }

            if (isset($parts['host']) && ltrim(strtolower($parts['host']), 'w.') !== ltrim($host, 'w.')) {
                continue;
            }

            if (isset($parts['scheme']) && ! in_array($parts['scheme'], ['http', 'https'], true)) {
                continue;
            }

            $path = ltrim($parts['path'] ?? '', '/');

            if (preg_match('/^[A-Za-z0-9_\-.]+\.(html|php)$/', $path)) {
                $links[] = $path;
            }
        }

        return array_values(array_unique($links));
    }

    private function pathFor(string $path): string
    {
        return $this->root.'/raw/'.($path === '' ? 'index-root.html' : $path);
    }
}
