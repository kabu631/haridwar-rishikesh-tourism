<?php

namespace Tests\Feature\Seo;

use App\Models\Page;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Every internal link and image on every page must work: no visitor (or
 * crawler) following a link on the site may land on an error.
 */
#[Group('parity')]
class LinkIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_internal_link_on_every_page_opens_a_working_page(): void
    {
        config(['seo.page_cache_ttl' => 0]);
        $this->seed(ContentSeeder::class);

        $links = [];
        $images = [];

        foreach (Page::query()->published()->pluck('path') as $path) {
            $html = (string) $this->get('/'.$path)->getContent();

            preg_match_all('#<a[^>]+href="(/[^"]*)"#', $html, $hrefs);
            foreach ($hrefs[1] as $href) {
                $href = strtok(html_entity_decode($href, ENT_QUOTES | ENT_HTML5), '#?');
                $links[$href][] = '/'.$path;
            }

            preg_match_all('#<(?:img|source)[^>]+(?:src|srcset)="(/[^"\s,]+)#', $html, $sources);
            foreach ($sources[1] as $src) {
                $images[rawurldecode($src)] = '/'.$path;
            }
        }

        $broken = [];

        foreach ($links as $href => $foundOn) {
            if (preg_match('#\.(jpe?g|png|gif|webp|avif|pdf|docx?)$#i', $href)) {
                if (! is_file(public_path(ltrim(rawurldecode($href), '/')))) {
                    $broken[] = "file {$href} missing (linked from {$foundOn[0]})";
                }

                continue;
            }

            $status = $this->get($href)->status();

            if (! in_array($status, [200, 301], true)) {
                $broken[] = "{$href} → HTTP {$status} (linked from ".implode(', ', array_slice(array_unique($foundOn), 0, 3)).')';
            }
        }

        foreach ($images as $src => $foundOn) {
            if (! is_file(public_path(ltrim($src, '/')))) {
                $broken[] = "image {$src} missing (on {$foundOn})";
            }
        }

        $this->assertSame([], $broken, implode("\n", $broken));
    }
}
