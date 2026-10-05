<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Support\Media\MediaLibrary;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Builds a static, read-only preview of the website for GitHub Pages (or any
 * static host under a sub-path). Every page is rendered by the app itself,
 * links are rewritten for the sub-path, and every page is marked noindex with
 * its canonical tag still pointing at the live domain, so the preview can
 * never compete with the real site in search results. Search, the trip
 * assistant and the enquiry forms are emulated by resources/static-demo.
 */
#[Signature('demo:export {--path=static-demo : Output directory, relative to the project root} {--base=/haridwar-rishikesh-tourism : URL path the preview is served under} {--live=https://www.haridwarrishikeshtourism.com : Live domain kept in canonical tags}')]
#[Description('Export a static, noindex preview of the website (e.g. for GitHub Pages)')]
class ExportStaticDemo extends Command
{
    private string $base;

    private string $out;

    /**
     * @var array<string, true> public paths of files the exported pages use
     */
    private array $assets = [];

    /**
     * @var array<string, true> page paths written so far
     */
    private array $written = [];

    public function handle(Kernel $kernel, MediaLibrary $media): int
    {
        $this->base = rtrim((string) $this->option('base'), '/');
        $this->out = base_path(trim((string) $this->option('path'), '/\\'));

        // Render as the live site would (canonical, schema), but never indexable and never from/into the page cache.
        config([
            'seo.site_url' => rtrim((string) $this->option('live'), '/'),
            'seo.allow_indexing' => false,
            'seo.force_canonical_host' => false,
            'seo.page_cache_ttl' => 0,
        ]);

        $this->prepareOutput();

        $paths = Page::query()->published()->where('path', '!=', '')->orderBy('path')->pluck('path')
            ->map(fn (string $path): string => '/'.$path)
            ->prepend('/')
            ->push('/search', '/sitemap.html')
            ->unique()
            ->values();

        $this->components->info("Rendering {$paths->count()} pages");
        $bar = $this->output->createProgressBar($paths->count());
        $failed = [];

        foreach ($paths as $path) {
            [$status, $html] = $this->render($kernel, $path);
            $status === 200 ? $this->writePage($path, $html) : $failed[] = "{$path} (HTTP {$status})";
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->writeNotFoundPage($kernel);
        $this->writeRedirectStubs($kernel);
        $this->writeChatbotData($kernel);
        $this->writeSearchIndex($media);
        $this->copyAssets();
        $this->copyDemoScripts();
        File::put($this->out.'/.nojekyll', '');

        foreach ($failed as $problem) {
            $this->components->warn("Skipped {$problem}");
        }

        $this->components->info(sprintf('Exported %d pages and %d files to %s', count($this->written), count($this->assets), $this->out));

        return self::SUCCESS;
    }

    /**
     * Empty the output directory but keep its .git folder (the gh-pages checkout).
     */
    private function prepareOutput(): void
    {
        File::ensureDirectoryExists($this->out);

        foreach (File::directories($this->out) as $directory) {
            if (basename($directory) !== '.git') {
                File::deleteDirectory($directory);
            }
        }

        foreach (File::files($this->out, true) as $file) {
            File::delete($file->getPathname());
        }
    }

    /**
     * @return array{0: int, 1: string, 2: ?string}
     */
    private function render(Kernel $kernel, string $path): array
    {
        $request = Request::create($path, 'GET');
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return [$response->getStatusCode(), (string) $response->getContent(), $response->headers->get('Location')];
    }

    private function writePage(string $path, string $html): void
    {
        $file = $this->fileFor($path);
        $this->collectAssets($html);
        File::ensureDirectoryExists(dirname($this->out.'/'.$file));
        File::put($this->out.'/'.$file, $this->rewriteHtml($html));
        $this->written[$file] = true;
    }

    /**
     * Static file name for a site path: "/" → index.html, "/search" → search.html,
     * "/book-now.php" → book-now.html (static hosts download .php files).
     */
    private function fileFor(string $path): string
    {
        $path = ltrim($path, '/');

        return match (true) {
            $path === '' => 'index.html',
            str_ends_with($path, '.php') => Str::beforeLast($path, '.php').'.html',
            ! str_contains($path, '.') => $path.'.html',
            default => $path,
        };
    }

    /**
     * Map a root-relative URL of the app to its address in the preview.
     */
    private function previewUrl(string $url): string
    {
        $path = strtok($url, '?#') ?: '/';
        $suffix = substr($url, strlen($path));

        $path = match (true) {
            $path === '/search' => '/search.html',
            $path === '/chatbot/packages' => '/chatbot/packages.json',
            str_ends_with($path, '.php') => Str::beforeLast($path, '.php').'.html',
            default => $path,
        };

        return $this->base.$path.$suffix;
    }

    private function rewriteHtml(string $html): string
    {
        $origins = '(?:https?:\/\/(?:localhost|127\.0\.0\.1(?::\d+)?)|'.preg_quote(rtrim((string) $this->option('live'), '/'), '/').')';

        // Links back into the site that the app printed as absolute URLs (anchors and forms only:
        // canonical, Open Graph and JSON-LD keep pointing at the live domain).
        // Feeds and XML/TXT files are not part of the preview: those links keep pointing at the live site.
        $html = preg_replace_callback('/(<(?:a|form)\b[^>]*?\b(?:href|action)=")'.$origins.'(\/[^"]*)"/i', fn (array $m): string => preg_match('/\.(xml|txt)$/', $m[2]) ? $m[0] : $m[1].'%%ROOT%%'.$m[2].'"', $html);
        $html = preg_replace('/\bhref="(\/(?!\/)[^"]*\.(?:xml|txt))"/', 'href="'.rtrim((string) $this->option('live'), '/').'$1"', $html);

        // Root-relative attribute values.
        $html = preg_replace_callback('/\b(href|src|action|poster|data-[a-z0-9-]+)="(\/(?!\/)[^"]*)"/i', fn (array $m): string => $m[1].'="'.$this->previewUrl($m[2]).'"', $html);
        $html = str_replace('%%ROOT%%', '', preg_replace_callback('/%%ROOT%%(\/[^"]*)"/', fn (array $m): string => $this->previewUrl($m[1]).'"', $html));

        // Responsive image candidates and inline CSS.
        $html = preg_replace_callback('/\b(srcset|imagesrcset)="([^"]*)"/i', fn (array $m): string => $m[1].'="'.preg_replace('/(^|,\s*)\/(?!\/)/', '$1'.$this->base.'/', $m[2]).'"', $html);
        $html = preg_replace('/url\(([\'"]?)\/(?!\/)/', 'url($1'.$this->base.'/', $html);

        // Never indexable, whatever the page itself says.
        $html = preg_replace('/<meta name="robots" content="[^"]*">/', '<meta name="robots" content="noindex,nofollow">', $html, 1);

        $head = '<script>window.__DEMO_BASE__='.json_encode($this->base).';</script>'
            .'<script src="'.$this->base.'/demo/demo.js"></script>'
            .'<link rel="stylesheet" href="'.$this->base.'/demo/demo.css">';

        return preg_replace('/<\/head>/', $head.'</head>', $html, 1);
    }

    private function collectAssets(string $content): void
    {
        preg_match_all('/\b(?:href|src|poster|data-[a-z0-9-]+)="(\/(?!\/)[^"#?]+)/i', $content, $attributes);
        preg_match_all('/\b(?:srcset|imagesrcset)="([^"]+)"/i', $content, $sets);
        preg_match_all('/url\([\'"]?(\/(?!\/)[^\'")?#]+)/', $content, $urls);

        $candidates = array_merge($attributes[1], $urls[1]);

        foreach ($sets[1] as $set) {
            foreach (explode(',', $set) as $candidate) {
                $candidates[] = strtok(trim($candidate), ' ');
            }
        }

        foreach ($candidates as $candidate) {
            $this->addAsset((string) $candidate);
        }
    }

    private function addAsset(?string $path): void
    {
        if ($path === null || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return;
        }

        $path = rawurldecode(strtok($path, '?#') ?: '');

        if (preg_match('/\.(html|php)$/i', $path) || ! is_file(public_path(ltrim($path, '/')))) {
            return;
        }

        $this->assets[$path] = true;
    }

    private function writeNotFoundPage(Kernel $kernel): void
    {
        [, $html] = $this->render($kernel, '/'.Str::random(12).'-preview-404.html');
        $this->collectAssets($html);
        File::put($this->out.'/404.html', $this->rewriteHtml($html));
    }

    /**
     * Old URLs that 301 on the live site get a meta-refresh page, so links to them still work.
     */
    private function writeRedirectStubs(Kernel $kernel): void
    {
        $linked = [];

        foreach (File::allFiles($this->out) as $file) {
            if ($file->getExtension() === 'html') {
                preg_match_all('/href="'.preg_quote($this->base, '/').'\/([^"#?]+\.html)/', File::get($file->getPathname()), $matches);
                array_push($linked, ...$matches[1]);
            }
        }

        foreach (array_unique($linked) as $path) {
            if (isset($this->written[$path]) || is_file($this->out.'/'.$path)) {
                continue;
            }

            [$status, , $location] = $this->render($kernel, '/'.$path);

            if (in_array($status, [301, 302], true) && $location) {
                $target = $this->previewUrl((string) (parse_url($location, PHP_URL_PATH) ?: '/'));
                File::put($this->out.'/'.$path, '<!DOCTYPE html><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><meta http-equiv="refresh" content="0;url='.e($target).'"><link rel="canonical" href="'.e($location).'"><a href="'.e($target).'">Continue</a>');
            }
        }
    }

    private function writeChatbotData(Kernel $kernel): void
    {
        [$status, $json] = $this->render($kernel, '/chatbot/packages');

        if ($status !== 200) {
            return;
        }

        $data = $this->rewriteJsonUrls(json_decode($json, true));
        File::ensureDirectoryExists($this->out.'/chatbot');
        File::put($this->out.'/chatbot/packages.json', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function rewriteJsonUrls(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->rewriteJsonUrls($value);
            } elseif (is_string($value) && str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
                $this->addAsset($value);
                $data[$key] = $this->previewUrl($value);
            }
        }

        return $data;
    }

    /**
     * Data for the in-browser search that stands in for /search and /search/suggest.
     */
    private function writeSearchIndex(MediaLibrary $media): void
    {
        $pages = Page::query()->published()->where('path', '!=', '')->with('parent')->get()
            ->map(function (Page $page) use ($media): array {
                $thumb = $page->extra['thumbnail'] ?? $page->hero_image;
                $image = $thumb ? ($media->bestVariant($thumb, 160) ?? $thumb) : null;
                $this->addAsset($image);

                return [
                    'title' => $page->title,
                    'url' => $this->previewUrl($page->url()),
                    'type' => $page->type?->badge(),
                    'section' => $page->parent?->label(),
                    'image' => $image ? $this->previewUrl($image) : null,
                    'teaser' => $page->teaser(),
                    'text' => Str::limit(preg_replace('/\s+/', ' ', (string) $page->search_text), 1200, ''),
                ];
            })
            ->values();

        File::ensureDirectoryExists($this->out.'/demo');
        File::put($this->out.'/demo/search-index.json', json_encode($pages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function copyAssets(): void
    {
        foreach (File::allFiles(public_path('build')) as $file) {
            $this->assets['/build/'.str_replace('\\', '/', $file->getRelativePathname())] = true;
        }

        $manifest = json_decode(File::get(public_path('site.webmanifest')), true);
        foreach ($manifest['icons'] ?? [] as $icon) {
            $this->addAsset($icon['src'] ?? null);
        }

        foreach (array_keys($this->assets) as $path) {
            $target = $this->out.$path;
            File::ensureDirectoryExists(dirname($target));

            if (str_ends_with($path, '.css')) {
                File::put($target, preg_replace('/url\(([\'"]?)\/(?!\/)/', 'url($1'.$this->base.'/', File::get(public_path(ltrim($path, '/')))));
            } else {
                File::copy(public_path(ltrim($path, '/')), $target);
            }
        }

        $manifest['start_url'] = $this->base.'/';
        $manifest['icons'] = array_map(fn (array $icon): array => ['src' => $this->previewUrl($icon['src'])] + $icon, $manifest['icons'] ?? []);
        File::put($this->out.'/site.webmanifest', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function copyDemoScripts(): void
    {
        File::copyDirectory(resource_path('static-demo'), $this->out.'/demo');
    }
}
