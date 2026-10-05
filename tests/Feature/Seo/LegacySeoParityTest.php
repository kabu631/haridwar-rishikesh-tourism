<?php

namespace Tests\Feature\Seo;

use Database\Seeders\ContentSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Guards the rankings of the migrated website: every legacy URL must still
 * answer 200 at the same address with the same title tag, meta description,
 * H1 and canonical, and must be indexable.
 */
#[Group('parity')]
class LegacySeoParityTest extends TestCase
{
    use RefreshDatabase;

    private const SITE = 'https://www.haridwarrishikeshtourism.com';

    public function test_every_legacy_page_keeps_its_url_title_description_heading_and_canonical(): void
    {
        // ~290 full pages: keep them out of the in-memory test cache.
        config(['seo.page_cache_ttl' => 0]);
        $this->seed(ContentSeeder::class);
        $snapshot = json_decode(File::get(database_path('data/content.json')), true);
        $failures = [];

        foreach (array_merge([$snapshot['home']], $snapshot['pages']) as $legacy) {
            $path = '/'.$legacy['path'];
            $response = $this->get($path);

            if ($response->status() !== 200) {
                $failures[] = "{$path}: HTTP {$response->status()}";

                continue;
            }

            $head = $this->parseHead((string) $response->getContent());
            $expectedCanonical = $legacy['canonical_url'] ?? self::SITE.$path;

            $checks = [
                'title' => [$legacy['meta_title'], $head['title']],
                'description' => [$legacy['meta_description'], $head['description']],
                'h1' => [$legacy['title'], $head['h1']],
                'canonical' => [$expectedCanonical, $head['canonical']],
            ];

            foreach ($checks as $name => [$expected, $actual]) {
                if ($this->normalise($expected) !== $this->normalise($actual)) {
                    $failures[] = "{$path}: {$name} changed – expected \"{$expected}\", got \"{$actual}\"";
                }
            }

            if (str_contains((string) $head['robots'], 'noindex')) {
                $failures[] = "{$path}: robots is \"{$head['robots']}\"";
            }

            if ($head['h1_count'] !== 1) {
                $failures[] = "{$path}: {$head['h1_count']} H1 headings";
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_sitemap_lists_every_self_canonical_legacy_page(): void
    {
        $this->seed(ContentSeeder::class);
        $snapshot = json_decode(File::get(database_path('data/content.json')), true);

        $sitemap = (string) $this->get('/sitemap.xml')->getContent();

        $missing = collect($snapshot['pages'])
            ->filter(fn (array $page): bool => blank($page['canonical_url'] ?? null) && ($page['extra']['legacy_in_sitemap'] ?? false))
            ->reject(fn (array $page): bool => str_contains($sitemap, '<loc>'.self::SITE.'/'.$page['path'].'</loc>'))
            ->pluck('path')
            ->all();

        $this->assertSame([], $missing);
    }

    /**
     * @return array{title: ?string, description: ?string, h1: ?string, h1_count: int, canonical: ?string, robots: ?string}
     */
    private function parseHead(string $html): array
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        $xpath = new DOMXPath($document);

        return [
            'title' => $xpath->query('//title')->item(0)?->textContent,
            'description' => $xpath->query('//meta[@name="description"]/@content')->item(0)?->nodeValue,
            'h1' => $xpath->query('//h1')->item(0)?->textContent,
            'h1_count' => $xpath->query('//h1')->length,
            'canonical' => $xpath->query('//link[@rel="canonical"]/@href')->item(0)?->nodeValue,
            'robots' => $xpath->query('//meta[@name="robots"]/@content')->item(0)?->nodeValue,
        ];
    }

    private function normalise(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
