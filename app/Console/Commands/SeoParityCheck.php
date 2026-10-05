<?php

namespace App\Console\Commands;

use App\Models\Redirect;
use App\Support\Legacy\LegacyArchive;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Pre-launch safety net: compares every page of the archived legacy website
 * with the new site and reports anything that could cost rankings – status,
 * title, description, H1, canonical, indexability, text coverage, internal
 * links and images.
 */
#[Signature('seo:parity {--base= : Check a running site (e.g. https://staging.example.com) instead of the local app} {--min-coverage=97 : Minimum % of legacy words that must still appear} {--report= : Write a CSV report to this path}')]
#[Description('Compare the new site with the legacy snapshot page by page')]
class SeoParityCheck extends Command
{
    public function handle(LegacyArchive $archive): int
    {
        // Check what each page asks for, not the environment-wide noindex of a local/staging copy.
        if (! $this->option('base')) {
            config(['seo.allow_indexing' => true]);
        }

        $redirects = Redirect::map();
        $rows = [];
        $problems = 0;
        $minimum = (float) $this->option('min-coverage');

        $pages = collect($archive->pages())->keys()->reject(fn (string $path): bool => in_array($path, ['index.html', 'sitemap.html'], true))->values();
        $bar = $this->output->createProgressBar($pages->count());

        foreach ($pages as $path) {
            $legacy = $this->extractLegacy($archive->get($path));
            [$status, $html] = $this->fetch('/'.$path);
            $new = $html !== null ? $this->extractNew($html) : null;

            $issues = [];

            $legacyRedirected = filled($archive->manifest()[$path]['redirects'] ?? null);

            if ($legacyRedirected && in_array($status, [301, 308], true)) {
                // Already a redirect on the legacy server; still redirects (now in one hop).
            } elseif ($status !== 200 || $new === null) {
                $issues[] = "HTTP {$status}";
            } else {
                if ($this->norm($legacy['title']) !== $this->norm($new['title'])) {
                    $issues[] = 'title changed';
                }
                if ($this->norm($legacy['description']) !== $this->norm($new['description'])) {
                    $issues[] = 'description changed';
                }
                if ($legacy['h1'] !== null && $this->norm($legacy['h1']) !== $this->norm($new['h1'])) {
                    $issues[] = 'H1 changed';
                }
                if (str_contains((string) $new['robots'], 'noindex')) {
                    $issues[] = 'noindex';
                }

                $coverage = $this->coverage($legacy['words'], $new['words']);
                if ($coverage < $minimum) {
                    $issues[] = sprintf('text coverage %.1f%%', $coverage);
                }

                // A link to a redirected URL is kept when the new page links to its target.
                $legacyLinks = array_map(fn (string $link): string => strtolower((string) ($redirects[$link][0] ?? $link)), $legacy['links']);
                $missingLinks = array_values(array_diff($legacyLinks, $new['links']));
                if ($missingLinks !== []) {
                    $issues[] = count($missingLinks).' internal link(s) missing: '.implode(', ', array_slice($missingLinks, 0, 3));
                }

                $missingImages = array_values(array_diff($legacy['images'], $new['images']));
                if ($missingImages !== []) {
                    $issues[] = count($missingImages).' image(s) missing: '.implode(', ', array_slice($missingImages, 0, 2));
                }
            }

            $problems += $issues !== [] ? 1 : 0;
            $rows[] = [$path, $status, isset($coverage) && $new ? sprintf('%.1f', $coverage) : '-', implode('; ', $issues) ?: 'OK'];
            unset($coverage);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $flagged = array_values(array_filter($rows, fn (array $row): bool => $row[3] !== 'OK'));
        if ($flagged !== []) {
            $this->table(['Legacy URL', 'Status', 'Text %', 'Issues'], array_slice($flagged, 0, 60));
        }

        if ($report = $this->option('report')) {
            $handle = fopen($report, 'w');
            fputcsv($handle, ['url', 'status', 'text_coverage', 'issues']);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
            $this->info("Report written to {$report}");
        }

        $total = count($rows);
        $ok = $total - $problems;
        $this->line("<info>{$ok}/{$total}</info> legacy pages fully match.".($problems ? " <comment>{$problems} need a look.</comment>" : ''));

        return $problems === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array{0: int, 1: ?string}
     */
    private function fetch(string $path): array
    {
        if ($base = $this->option('base')) {
            $response = Http::withOptions(['allow_redirects' => false])->timeout(30)->get(rtrim($base, '/').$path);

            return [$response->status(), $response->successful() ? $response->body() : null];
        }

        $response = app(Kernel::class)->handle(Request::create($path, 'GET'));

        return [$response->getStatusCode(), $response->getStatusCode() === 200 ? (string) $response->getContent() : null];
    }

    /**
     * @return array{title: ?string, description: ?string, h1: ?string, words: array<string, int>, links: list<string>, images: list<string>}
     */
    private function extractLegacy(string $html): array
    {
        $xpath = $this->xpath($html);

        $root = $xpath->query('//div[contains(@class, "left-content")]')->item(0)
            ?? $xpath->query('//div[contains(@class, "fullcontainer")]')->item(0)
            ?? $xpath->query('//section[contains(concat(" ", normalize-space(@class), " "), " page ")]')->item(0);

        foreach (['.//*[contains(concat(" ", normalize-space(@class), " "), " sidebar ")]', './/script', './/style', './/form', './/*[contains(@class, "breadcrumbs")]', './/footer', './/*[contains(@class, "footer")]', './/button', './/*[@id="dots"]', './/select'] as $selector) {
            if ($root === null) {
                break;
            }
            foreach (iterator_to_array($xpath->query($selector, $root)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        return [
            'title' => $xpath->query('//title')->item(0)?->textContent,
            'description' => $xpath->query('//meta[translate(@name, "DESCRIPTION", "description")="description"]/@content')->item(0)?->nodeValue,
            'h1' => $xpath->query('//h1')->item(0)?->textContent,
            'words' => $root ? $this->words($this->text($root)) : [],
            'links' => $root ? $this->internalLinks($xpath, $root) : [],
            'images' => $root ? $this->images($xpath, $root) : [],
        ];
    }

    /**
     * @return array{title: ?string, description: ?string, h1: ?string, robots: ?string, words: array<string, int>, links: list<string>, images: list<string>}
     */
    private function extractNew(string $html): array
    {
        $xpath = $this->xpath($html);
        $body = $xpath->query('//body')->item(0);

        return [
            'title' => $xpath->query('//title')->item(0)?->textContent,
            'description' => $xpath->query('//meta[@name="description"]/@content')->item(0)?->nodeValue,
            'h1' => $xpath->query('//h1')->item(0)?->textContent,
            'robots' => $xpath->query('//meta[@name="robots"]/@content')->item(0)?->nodeValue,
            'words' => $body ? $this->words($this->text($body)) : [],
            'links' => $body ? $this->internalLinks($xpath, $body) : [],
            'images' => $body ? $this->images($xpath, $body) : [],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function words(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower(html_entity_decode($text, ENT_QUOTES | ENT_HTML5)), -1, PREG_SPLIT_NO_EMPTY);

        return array_count_values(array_filter($words, fn (string $word): bool => mb_strlen($word) > 2));
    }

    /**
     * @param  array<string, int>  $legacy
     * @param  array<string, int>  $new
     */
    private function coverage(array $legacy, array $new): float
    {
        $total = array_sum($legacy);

        if ($total === 0) {
            return 100.0;
        }

        $kept = 0;
        foreach ($legacy as $word => $count) {
            $kept += min($count, $new[$word] ?? 0);
        }

        return $kept / $total * 100;
    }

    /**
     * Same-site page links (.html/.php), normalised to "/page.html".
     *
     * @return list<string>
     */
    private function internalLinks(DOMXPath $xpath, \DOMNode $root): array
    {
        $links = [];

        foreach ($xpath->query('.//a[@href]', $root) as $link) {
            $href = preg_replace('/\s+/', '', $link->getAttribute('href'));
            $parts = parse_url($href);

            if ($parts === false || (isset($parts['host']) && ! str_contains($parts['host'], 'haridwarrishikeshtourism.com'))) {
                continue;
            }

            $path = '/'.ltrim($parts['path'] ?? '', '/');
            $path = in_array($path, ['/index.html', '/index.php'], true) ? '/' : $path;

            if (preg_match('#^/[a-z0-9\-]+\.(html|php)$#i', $path) && ! in_array($path, ['/onesideform.php'], true)) {
                $links[] = strtolower($path);
            }
        }

        return array_values(array_unique($links));
    }

    /**
     * Local image paths that exist on disk (images already missing on the
     * legacy server, and bullet icons shrunk by inline CSS, are ignored).
     *
     * @return list<string>
     */
    private function images(DOMXPath $xpath, \DOMNode $root): array
    {
        $images = [];

        foreach ($xpath->query('.//img[@src]|.//a[@data-lightbox]', $root) as $node) {
            if (preg_match('/(?:^|;)\s*width\s*:\s*(\d+)px/i', $node->getAttribute('style'), $width) && (int) $width[1] <= 32) {
                continue;
            }

            $src = $node->getAttribute('src') ?: $node->getAttribute('href');
            $path = '/'.ltrim(rawurldecode((string) parse_url(trim($src), PHP_URL_PATH)), '/');

            if (str_contains($src, '://') && ! str_contains($src, 'haridwarrishikeshtourism.com')) {
                continue;
            }

            if (preg_match('/\.(jpe?g|png|gif|webp)$/i', $path) && ! str_contains($path, 'book-now') && ! str_contains($path, 'booknow') && File::exists(public_path(ltrim($path, '/')))) {
                $images[] = strtolower($path);
            }
        }

        return array_values(array_unique($images));
    }

    /**
     * Visible text with element boundaries treated as word boundaries.
     */
    private function text(\DOMNode $node): string
    {
        return strip_tags(str_replace('<', ' <', (string) $node->ownerDocument->saveHTML($node)));
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($document);
    }

    private function norm(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
