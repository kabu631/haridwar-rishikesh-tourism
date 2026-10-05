<?php

namespace App\Console\Commands;

use App\Enums\PageType;
use App\Models\Media;
use App\Support\Content\HtmlSanitizer;
use App\Support\Legacy\LegacyArchive;
use App\Support\Legacy\LegacyPageParser;
use App\Support\Legacy\PageClassifier;
use App\Support\Media\ImageProcessor;
use DOMElement;
use DOMXPath;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Converts the archived legacy site into the content snapshot
 * (database/data/content.json) loaded by ContentSeeder, and copies every
 * referenced image to the same public path it had on the old site.
 */
#[Signature('legacy:import {--skip-images : Do not download or optimise images} {--seed : Load the snapshot into the database afterwards}')]
#[Description('Build the content snapshot and media from the archived legacy site')]
class ImportLegacySite extends Command
{
    /**
     * Legacy URLs that are not pages of their own.
     *
     * @var list<string>
     */
    private const SKIP = ['', 'index.html', 'sitemap.html'];

    public function handle(LegacyArchive $archive, LegacyPageParser $parser, HtmlSanitizer $sanitizer, ImageProcessor $images): int
    {
        $manifest = $archive->manifest();
        $home = $archive->get('');

        $menus = $this->menus($home);
        $classifier = new PageClassifier($this->menuMembership($menus['main']));

        $pages = [];
        $redirects = $this->knownRedirects();

        foreach ($archive->pages() as $path => $entry) {
            if (in_array($path, self::SKIP, true)) {
                continue;
            }

            if (filled($entry['redirects'] ?? null)) {
                $final = Str::afterLast($entry['redirects'], ', ');
                $redirects[] = ['from_path' => '/'.$path, 'to_path' => '/'.ltrim((string) parse_url($final, PHP_URL_PATH), '/'), 'status_code' => 301, 'note' => 'Legacy server redirect (flattened to a single hop)'];

                continue;
            }

            if ($path !== strtolower($path)) {
                continue;
            }

            $parsed = $parser->parse($path, $archive->get($path));
            $classification = $classifier->classify($parsed);

            $pages[$path] = array_merge($parsed, $classification, [
                'lastmod' => $entry['lastmod'] ?? null,
                'in_sitemap' => (bool) ($entry['in_sitemap'] ?? false),
            ]);
        }

        $this->info('Parsed '.count($pages).' pages.');

        $pages = $this->describeAnchors($pages, $sanitizer);
        $pages = $this->skipRedirectHops($pages, $redirects, $sanitizer);
        $pages = $this->addTeasers($pages);

        $snapshot = [
            'generated_at' => now()->toIso8601String(),
            'authors' => $this->authors(),
            'home' => $this->homePage($home, $parser),
            'pages' => array_values(array_map(fn (array $page): array => $this->record($page), $pages)),
            'menus' => $menus,
            'redirects' => $redirects,
            'testimonials' => $this->testimonials($home),
        ];

        File::ensureDirectoryExists(database_path('data'));
        File::put(database_path('data/content.json'), json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->info('Wrote database/data/content.json');

        if (! $this->option('skip-images')) {
            $this->importImages($snapshot, $images);
        }

        if ($this->option('seed')) {
            $this->call('db:seed', ['--class' => 'ContentSeeder', '--force' => true]);
        }

        return self::SUCCESS;
    }

    /**
     * Main menu tree and footer links exactly as on the legacy site.
     *
     * @return array{main: list<array<string, mixed>>, footer: list<array<string, mixed>>}
     */
    private function menus(string $html): array
    {
        $xpath = $this->xpath($html);
        $main = [];

        foreach ($xpath->query('//ul[@id="menu-navigation"]/li') as $item) {
            $link = $xpath->query('./a', $item)->item(0);
            if (! $link instanceof DOMElement) {
                continue;
            }

            $top = $this->menuLink($link);
            $top['children'] = [];

            foreach ($xpath->query('./ul/li/a', $item) as $child) {
                $childLink = $this->menuLink($child);
                if ($childLink['url'] === $top['url'] || strcasecmp($childLink['label'], 'Read More') === 0) {
                    continue;
                }

                if (collect($top['children'])->contains('url', $childLink['url'])) {
                    continue;
                }

                $top['children'][] = $childLink;
            }

            $main[] = $top;
        }

        $footer = [];
        foreach ($xpath->query('//div[contains(@class, "footer-bottom")]//a') as $link) {
            $footer[] = $this->menuLink($link);
        }

        return ['main' => $main, 'footer' => $footer];
    }

    /**
     * @return array{label: string, url: string, title: ?string}
     */
    private function menuLink(DOMElement $link): array
    {
        $parser = app(LegacyPageParser::class);
        $title = trim(preg_replace('/\s+/', ' ', $link->getAttribute('title')));

        return [
            'label' => trim(preg_replace('/\s+/', ' ', $link->textContent)),
            'url' => $parser->localUrl($link->getAttribute('href')),
            'title' => $title === '' ? null : $title,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $main
     * @return array<string, array{section: string, parent: ?string}>
     */
    private function menuMembership(array $main): array
    {
        $membership = [];

        foreach ($main as $top) {
            $hubPath = ltrim($top['url'], '/');
            $section = array_search($hubPath, PageClassifier::SECTION_HUBS, true);

            if ($section === false) {
                continue;
            }

            foreach ($top['children'] as $child) {
                $path = ltrim($child['url'], '/');
                $membership[$path] ??= ['section' => $section, 'parent' => $hubPath];
            }
        }

        // A page listed under "Gallery" also lives in its city section.
        unset($membership['haridwar-photos.html'], $membership['rishikesh-photos.html']);
        $membership['haridwar-photos.html'] = ['section' => 'haridwar', 'parent' => 'haridwar-tourism.html'];
        $membership['rishikesh-photos.html'] = ['section' => 'rishikesh', 'parent' => 'rishikesh-tourism.html'];

        return $membership;
    }

    /**
     * Links whose visible text is a raw URL slug get the target page's title
     * as anchor text (descriptive internal anchors).
     *
     * @param  array<string, array<string, mixed>>  $pages
     * @return array<string, array<string, mixed>>
     */
    private function describeAnchors(array $pages, HtmlSanitizer $sanitizer): array
    {
        $titles = array_map(fn (array $page): ?string => $page['h1'], $pages);

        foreach ($pages as $path => $page) {
            if (! str_contains($page['body'], '<a ')) {
                continue;
            }

            $document = $sanitizer->load($page['body']);
            $changed = false;

            foreach ((new DOMXPath($document))->query('//a[@href]') as $link) {
                $text = trim($link->textContent);

                if (! preg_match('/^[a-z0-9]+(-[a-z0-9]+){2,}(\.html)?$/i', $text) || $link->getElementsByTagName('img')->length > 0) {
                    continue;
                }

                $target = ltrim((string) parse_url($link->getAttribute('href'), PHP_URL_PATH), '/');
                $label = $titles[$target] ?? Str::headline(Str::beforeLast($text, '.html'));

                while ($link->firstChild !== null) {
                    $link->removeChild($link->firstChild);
                }

                $link->appendChild($document->createTextNode($label));
                $changed = true;
            }

            if ($changed) {
                $pages[$path]['body'] = $sanitizer->innerHtml($document->getElementById('__root'));
            }
        }

        return $pages;
    }

    /**
     * Internal links that point at a redirected URL go straight to the final
     * page (no redirect hop for visitors or crawlers).
     *
     * @param  array<string, array<string, mixed>>  $pages
     * @param  list<array<string, mixed>>  $redirects
     * @return array<string, array<string, mixed>>
     */
    private function skipRedirectHops(array $pages, array $redirects, HtmlSanitizer $sanitizer): array
    {
        $map = collect($redirects)
            ->filter(fn (array $redirect): bool => filled($redirect['to_path']) && (int) $redirect['status_code'] === 301)
            ->mapWithKeys(fn (array $redirect): array => [strtolower($redirect['from_path']) => $redirect['to_path']])
            ->all();

        $resolve = fn (?string $url): ?string => $url !== null && isset($map[strtolower((string) parse_url($url, PHP_URL_PATH))]) ? $map[strtolower((string) parse_url($url, PHP_URL_PATH))] : $url;

        foreach ($pages as $path => $page) {
            $pages[$path]['cards'] = array_map(fn (array $card): array => array_merge($card, ['url' => $resolve($card['url'] ?? null)]), $page['cards']);

            if (! str_contains($page['body'], '<a ')) {
                continue;
            }

            $document = $sanitizer->load($page['body']);
            $changed = false;

            foreach ((new DOMXPath($document))->query('//a[@href]') as $link) {
                $href = $link->getAttribute('href');
                $target = $resolve($href);

                if ($target !== $href) {
                    $link->setAttribute('href', $target);
                    $changed = true;
                }
            }

            if ($changed) {
                $pages[$path]['body'] = $sanitizer->innerHtml($document->getElementById('__root'));
            }
        }

        return $pages;
    }

    /**
     * Teaser text and thumbnail per page: the hand-written teaser from its hub
     * card when there is one, otherwise the first substantial paragraph.
     *
     * @param  array<string, array<string, mixed>>  $pages
     * @return array<string, array<string, mixed>>
     */
    private function addTeasers(array $pages): array
    {
        $fromCards = [];

        foreach ($pages as $page) {
            foreach ($page['cards'] as $card) {
                $target = ltrim((string) $card['url'], '/');
                if ($target !== '' && ! isset($fromCards[$target])) {
                    $fromCards[$target] = $card;
                }
            }
        }

        foreach ($pages as $path => $page) {
            $card = $fromCards[$path] ?? null;

            $firstParagraph = null;
            if (preg_match_all('#<p>(.*?)</p>#s', $page['body'], $matches)) {
                foreach ($matches[1] as $paragraph) {
                    $text = trim(html_entity_decode(strip_tags($paragraph), ENT_QUOTES | ENT_HTML5));
                    if (mb_strlen($text) >= 80) {
                        $firstParagraph = $text;
                        break;
                    }
                }
            }

            $firstImage = preg_match('#<img[^>]+src="([^"]+)"#', $page['body'], $image) ? $image[1] : null;

            $pages[$path]['excerpt'] = $card['text'] ?? ($firstParagraph ? Str::limit($firstParagraph, 280) : $page['meta_description']);
            $pages[$path]['thumbnail'] = $card['image'] ?? $page['hero']['src'] ?? $firstImage;
            $pages[$path]['thumbnail_alt'] = $card['alt'] ?? $page['hero']['alt'] ?? null;
            $pages[$path]['badge'] = $card['badge'] ?? null;
        }

        return $pages;
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    private function record(array $page): array
    {
        /** @var PageType $type */
        $type = $page['type'];

        return [
            'path' => $page['path'],
            'type' => $type->value,
            'section' => $page['section'],
            'parent' => $page['parent'],
            'title' => $page['h1'] ?? Str::headline(Str::beforeLast($page['path'], '.')),
            'meta_title' => $page['meta_title'],
            'meta_description' => $page['meta_description'],
            'meta_keywords' => $page['meta_keywords'],
            'robots' => $page['robots'] ?: 'index,follow',
            'canonical_url' => $this->legacyCanonical($page),
            'schema_type' => $page['schema_type'],
            'excerpt' => $page['excerpt'],
            'body' => $page['body'],
            'hero_image' => $page['hero']['src'] ?? null,
            'hero_alt' => $page['hero']['alt'] ?? null,
            'cards' => $page['cards'] ?: null,
            'gallery' => array_merge($page['slides'] ?? [], $page['gallery']) ?: null,
            'itinerary' => $page['itinerary'] ?: null,
            'faqs' => $page['faqs'] ?: null,
            'facts' => $type === PageType::Package ? $this->packageFacts($page) : null,
            'extra' => array_filter([
                'thumbnail' => $page['thumbnail'],
                'thumbnail_alt' => $page['thumbnail_alt'],
                'badge' => $page['badge'],
                'h1_link' => $page['h1_link'] ?? null,
                'legacy_in_sitemap' => $page['in_sitemap'],
            ], fn ($value) => $value !== null),
            'lastmod' => $page['lastmod'],
        ];
    }

    /**
     * Keep a legacy canonical only when it points somewhere else; self
     * references are generated automatically.
     *
     * @param  array<string, mixed>  $page
     */
    private function legacyCanonical(array $page): ?string
    {
        $canonical = $page['canonical'] ?? null;

        if (blank($canonical) || ltrim((string) parse_url($canonical, PHP_URL_PATH), '/') === $page['path']) {
            return null;
        }

        return $canonical;
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    private function packageFacts(array $page): array
    {
        $haystack = strtolower($page['path'].' '.$page['h1']);

        $start = match (true) {
            (bool) preg_match('/(ex|from)[- ]delhi/', $haystack) => 'Delhi',
            (bool) preg_match('/(ex|from)[- ]haridwar/', $haystack) => 'Haridwar',
            (bool) preg_match('/(ex|from)[- ]dehradun/', $haystack) => 'Dehradun',
            (bool) preg_match('/(ex|from)[- ]srinagar/', $haystack) => 'Srinagar',
            default => null,
        };

        $places = collect(['Haridwar', 'Rishikesh', 'Mussoorie', 'Auli', 'Chopta', 'Nainital', 'Corbett', 'Kanatal', 'Kausani', 'Agra', 'Mathura', 'Vrindavan', 'Varanasi', 'Ayodhya', 'Shimla', 'Manali', 'Dharamshala', 'Delhi', 'Jaipur', 'Tehri', 'Kedarnath', 'Badrinath', 'Gangotri', 'Yamunotri'])
            ->filter(fn (string $place): bool => str_contains($haystack, strtolower($place)) || ($place === 'Vrindavan' && str_contains($haystack, 'virandvan')))
            ->values()
            ->all();

        $days = count($page['itinerary'] ?? []);
        $nights = null;

        if (preg_match('/(\d{1,2})\s*n(?:ights?)?[\s\/&,-]*(\d{1,2})\s*d(?:ays?)?/i', $haystack, $match)) {
            [$nights, $days] = [(int) $match[1], (int) $match[2]];
        }

        return array_filter([
            'duration_days' => $days > 0 ? $days : null,
            'duration_nights' => $nights ?? ($days > 1 ? $days - 1 : null),
            'start_city' => $start,
            'destinations' => $places ?: null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function homePage(string $html, LegacyPageParser $parser): array
    {
        $xpath = $this->xpath($html);
        $meta = fn (string $name): ?string => trim((string) $xpath->query('//meta[@name="'.$name.'"]/@content')->item(0)?->nodeValue) ?: null;

        return [
            'path' => '',
            'type' => PageType::Home->value,
            'section' => null,
            'parent' => null,
            'title' => 'Haridwar Rishikesh Tour Packages & Travel Guide',
            'meta_title' => trim($xpath->query('//title')->item(0)?->textContent ?? ''),
            'meta_description' => $meta('description'),
            'meta_keywords' => $meta('keywords'),
            'robots' => 'index,follow',
            'extra' => ['home' => require database_path('data/home.php')],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function testimonials(string $html): array
    {
        $xpath = $this->xpath($html);
        $testimonials = [];

        foreach ($xpath->query('//div[contains(@class, "footer_carousel")]//li') as $index => $item) {
            $review = $xpath->query('.//div[contains(@class, "review")]', $item)->item(0);
            $name = $xpath->query('.//h6', $item)->item(0);
            $location = $xpath->query('.//div[contains(@class, "from")]//span', $item)->item(0);

            if ($review === null || $name === null) {
                continue;
            }

            $testimonials[] = [
                'name' => Str::of($name->textContent)->squish()->title()->toString(),
                'location' => $location ? Str::of($location->textContent)->squish()->toString() : null,
                'body' => Str::of($review->textContent)->squish()->toString(),
                'source' => 'Guest feedback',
                'sort_order' => $index,
            ];
        }

        return $testimonials;
    }

    /**
     * Authors for editorial bylines (E-E-A-T), from the legacy "Our Team" page.
     *
     * @return list<array<string, mixed>>
     */
    private function authors(): array
    {
        return [
            [
                'name' => 'Sunil Saini',
                'slug' => 'sunil-saini',
                'job_title' => 'CEO, India Easy Trip Pvt Ltd',
                'bio' => 'Sunil Saini leads India Easy Trip Pvt Ltd, the Haridwar based, Uttarakhand Tourism approved travel agency behind Haridwar Rishikesh Tourism. The company has organised pilgrimages, sightseeing, trekking and adventure tours across Haridwar, Rishikesh and Uttarakhand since March 1995.',
                'same_as' => ['https://twitter.com/8888Saini'],
            ],
            [
                'name' => 'Avishek Saini',
                'slug' => 'avishek-saini',
                'job_title' => 'Team Member, India Easy Trip Pvt Ltd',
                'bio' => 'Avishek Saini is part of the India Easy Trip Pvt Ltd team in Haridwar, which plans Haridwar, Rishikesh and Uttarakhand tour packages for travellers.',
                'same_as' => [],
            ],
            [
                'name' => 'Piyush Saini',
                'slug' => 'piyush-saini',
                'job_title' => 'Team Member, India Easy Trip Pvt Ltd',
                'bio' => 'Piyush Saini is part of the India Easy Trip Pvt Ltd team in Haridwar, which plans Haridwar, Rishikesh and Uttarakhand tour packages for travellers.',
                'same_as' => [],
            ],
            [
                'name' => 'Sunny Chauhan',
                'slug' => 'sunny-chauhan',
                'job_title' => 'Team Member, India Easy Trip Pvt Ltd',
                'bio' => 'Sunny Chauhan is part of the India Easy Trip Pvt Ltd team in Haridwar, which plans Haridwar, Rishikesh and Uttarakhand tour packages for travellers.',
                'same_as' => [],
            ],
            [
                'name' => 'Sunny Dhiman',
                'slug' => 'sunny-dhiman',
                'job_title' => 'Executive, India Easy Trip Pvt Ltd',
                'bio' => 'Sunny Dhiman is an executive at India Easy Trip Pvt Ltd in Haridwar, which plans Haridwar, Rishikesh and Uttarakhand tour packages for travellers.',
                'same_as' => [],
            ],
        ];
    }

    /**
     * Redirects known from the legacy crawl (broken internal links, retired
     * helper URLs). Each points straight at its final destination.
     *
     * @return list<array<string, mixed>>
     */
    private function knownRedirects(): array
    {
        return [
            ['from_path' => '/index.html', 'to_path' => '/', 'status_code' => 301, 'note' => 'Legacy homepage alias'],
            ['from_path' => '/index.php', 'to_path' => '/', 'status_code' => 301, 'note' => 'Legacy homepage alias'],
            ['from_path' => '/place-to-see-in-mussoorie.html', 'to_path' => '/places-to-see-in-mussoorie.html', 'status_code' => 301, 'note' => 'Broken legacy link (sitemap.html)'],
            ['from_path' => '/onesideform.php', 'to_path' => '/book-now.php', 'status_code' => 301, 'note' => 'Legacy quick form handler'],
            ['from_path' => '/ror.xml', 'to_path' => '/sitemap.xml', 'status_code' => 301, 'note' => 'Obsolete ROR sitemap'],
        ];
    }

    /**
     * Download every referenced image to its original public path and build
     * optimised WebP/AVIF variants.
     *
     * @param  array<string, mixed>  $snapshot
     */
    private function importImages(array $snapshot, ImageProcessor $images): void
    {
        $heroes = [];
        $all = [];

        foreach ($snapshot['pages'] as $page) {
            if ($page['hero_image']) {
                $heroes[$page['hero_image']] = true;
            }

            preg_match_all('#<img[^>]+src="([^"]+)"#', (string) $page['body'], $matches);
            preg_match_all('#<a[^>]+href="(/[^"]+\.(?:pdf|docx?|xlsx?|pptx?))"#i', (string) $page['body'], $documents);
            foreach (array_merge(
                $matches[1],
                array_map(fn (string $href): string => rawurldecode($href), $documents[1]),
                [$page['hero_image'], $page['extra']['thumbnail'] ?? null],
                array_column($page['cards'] ?? [], 'image'),
                array_column($page['gallery'] ?? [], 'image'),
                array_column($page['gallery'] ?? [], 'full'),
            ) as $src) {
                if (is_string($src) && str_starts_with($src, '/') && ! str_starts_with($src, '//')) {
                    $all[html_entity_decode($src)] = true;
                }
            }
        }

        array_walk_recursive($snapshot['home']['extra']['home'], function ($value, $key) use (&$all, &$heroes): void {
            if (in_array($key, ['image', 'badge'], true) && is_string($value) && str_starts_with($value, '/')) {
                $all[$value] = true;
                $heroes[$value] = true;
            }
        });

        $paths = array_keys($all);
        $missing = array_values(array_filter($paths, fn (string $path): bool => ! File::exists(public_path(ltrim(rawurldecode($path), '/')))));
        $this->info(count($paths).' images referenced, '.count($missing).' to download.');

        $origin = rtrim(config('legacy.origin'), '/');
        $failed = [];

        foreach (array_chunk($missing, 6) as $chunk) {
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn (string $path) => $pool->as($path)
                    ->withUserAgent(config('legacy.user_agent'))
                    ->withOptions(['verify' => config('legacy.ca_bundle')])
                    ->timeout(60)
                    ->get($origin.str_replace(' ', '%20', $path)),
                $chunk,
            ));

            foreach ($chunk as $path) {
                $response = $responses[$path] ?? null;

                $type = (string) ($response instanceof Response ? $response->header('Content-Type') : '');

                if ($response instanceof Response && $response->successful() && (str_starts_with($type, 'image/') || str_starts_with($type, 'application/'))) {
                    $target = public_path(ltrim(rawurldecode($path), '/'));
                    File::ensureDirectoryExists(dirname($target));
                    File::put($target, $response->body());
                } else {
                    $failed[] = $path;
                }
            }
        }

        if ($failed !== []) {
            $this->warn(count($failed).' images could not be downloaded (missing on the legacy server too):');
            foreach ($failed as $path) {
                $this->line('  '.$path);
            }
        }

        $this->info('Optimising images…');
        $bar = $this->output->createProgressBar(count($paths));

        foreach ($paths as $path) {
            $images->process(rawurldecode($path), isset($heroes[$path]));
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        File::put(database_path('data/media.json'), Media::query()->orderBy('path')->get(['path', 'width', 'height', 'bytes', 'variants'])->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Wrote database/data/media.json');
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        return new DOMXPath($document);
    }
}
