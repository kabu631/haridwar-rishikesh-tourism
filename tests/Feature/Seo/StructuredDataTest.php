<?php

namespace Tests\Feature\Seo;

use App\Models\Page;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * The JSON-LD on every page must be something Google can read: valid JSON,
 * typed nodes, absolute URLs, no empty values, sequential breadcrumbs and
 * complete FAQ answers. One broken block silently costs rich results.
 */
#[Group('parity')]
class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    private const URL_PROPERTIES = ['url', 'item', 'logo', 'contentUrl', 'sameAs', 'target', 'urlTemplate'];

    public function test_every_page_publishes_valid_structured_data(): void
    {
        config(['seo.page_cache_ttl' => 0]);
        $this->seed(ContentSeeder::class);

        $problems = [];
        $paths = Page::query()->published()->pluck('path')->map(fn (string $path): string => '/'.$path)->push('/')->unique();

        foreach ($paths as $path) {
            foreach ($this->problemsOn($path) as $problem) {
                $problems[] = "{$path}: {$problem}";
            }
        }

        $this->assertSame([], $problems, implode("\n", array_slice($problems, 0, 40)));
    }

    /**
     * @return list<string>
     */
    private function problemsOn(string $path): array
    {
        $html = (string) $this->get($path)->getContent();

        if (preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $blocks) !== 1) {
            return ['expected exactly one JSON-LD block, found '.count($blocks[1])];
        }

        try {
            $data = json_decode($blocks[1][0], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return ['invalid JSON: '.$exception->getMessage()];
        }

        if (($data['@context'] ?? null) !== 'https://schema.org' || empty($data['@graph'])) {
            return ['missing @context or @graph'];
        }

        $problems = [];
        $ids = [];

        foreach ($data['@graph'] as $index => $node) {
            $label = ($node['@type'] ?? "node {$index}");
            $label = is_array($label) ? implode('/', $label) : $label;

            if (empty($node['@type'])) {
                $problems[] = "{$label} has no @type";
            }

            if (isset($node['@id'])) {
                if (in_array($node['@id'], $ids, true)) {
                    $problems[] = "duplicate @id {$node['@id']}";
                }
                $ids[] = $node['@id'];
            }

            array_push($problems, ...$this->emptyOrRelativeValues($node, $label));

            if ($label === 'BreadcrumbList') {
                $positions = array_column($node['itemListElement'] ?? [], 'position');
                if ($positions !== range(1, max(1, count($positions)))) {
                    $problems[] = 'breadcrumb positions are not 1..n: '.json_encode($positions);
                }
            }

            if ($label === 'FAQPage') {
                foreach ($node['mainEntity'] ?? [] as $question) {
                    if (blank($question['name'] ?? null) || blank($question['acceptedAnswer']['text'] ?? null)) {
                        $problems[] = 'FAQ question without a name or answer';
                    }
                }
            }
        }

        return $problems;
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return list<string>
     */
    private function emptyOrRelativeValues(array $value, string $trail): array
    {
        $problems = [];

        foreach ($value as $key => $child) {
            $here = "{$trail}.{$key}";

            if ($child === null || $child === '' || $child === []) {
                $problems[] = "{$here} is empty";
            } elseif (is_array($child)) {
                array_push($problems, ...$this->emptyOrRelativeValues($child, $here));
            } elseif (is_string($child) && in_array($key, self::URL_PROPERTIES, true) && ! str_starts_with($child, 'http')) {
                $problems[] = "{$here} is not an absolute URL ({$child})";
            }
        }

        return $problems;
    }
}
