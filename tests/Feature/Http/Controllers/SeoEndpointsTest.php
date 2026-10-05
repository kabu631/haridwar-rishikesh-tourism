<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_indexable_pages_with_lastmod_and_images(): void
    {
        Page::factory()->create(['path' => 'har-ki-pauri.html', 'hero_image' => '/header/har-ki-pauri.jpg', 'updated_at' => '2020-02-01 07:30:33']);

        $response = $this->get('/sitemap.xml');

        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<loc>https://www.haridwarrishikeshtourism.com/har-ki-pauri.html</loc>', false);
        $response->assertSee('<lastmod>2020-02-01T07:30:33+00:00</lastmod>', false);
        $response->assertSee('<image:loc>https://www.haridwarrishikeshtourism.com/header/har-ki-pauri.jpg</image:loc>', false);
    }

    public function test_sitemap_excludes_noindex_unpublished_and_canonicalised_pages(): void
    {
        Page::factory()->noindex()->create(['path' => 'hidden.html']);
        Page::factory()->unpublished()->create(['path' => 'draft.html']);
        Page::factory()->canonicalTo('https://www.haridwarrishikeshtourism.com/haridwar-kumbh-mela.html')->create(['path' => 'kumbh-mela-map.html']);

        $response = $this->get('/sitemap.xml');

        $response->assertDontSee('hidden.html');
        $response->assertDontSee('draft.html');
        $response->assertDontSee('kumbh-mela-map.html');
    }

    public function test_robots_allows_search_and_ai_crawlers_and_points_to_the_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertSee('User-agent: *');
        $response->assertSee('User-agent: GPTBot');
        $response->assertSee('Disallow: /backup/');
        $response->assertSee('Disallow: /admin');
        $response->assertSee('Sitemap: https://www.haridwarrishikeshtourism.com/sitemap.xml');
    }

    public function test_robots_blocks_everything_on_non_production_copies(): void
    {
        config(['seo.allow_indexing' => false]);

        $this->get('/robots.txt')->assertSee("User-agent: *\nDisallow: /", false);
    }

    public function test_rss_feed_lists_recent_pages(): void
    {
        Page::factory()->create(['path' => 'kankhal.html', 'title' => 'Kankhal Haridwar']);

        $response = $this->get('/feed.xml');

        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $response->assertSee('<title>Kankhal Haridwar</title>', false);
        $response->assertSee('<link>https://www.haridwarrishikeshtourism.com/kankhal.html</link>', false);
    }

    public function test_llms_txt_summarises_the_business_and_links_pages_by_section(): void
    {
        Page::factory()->create(['path' => 'kankhal.html', 'title' => 'Kankhal Haridwar', 'section' => 'haridwar']);

        $response = $this->get('/llms.txt');

        $response->assertSee('# Haridwar Rishikesh Tourism');
        $response->assertSee('TA-01/HDR/53/2016-17');
        $response->assertSee('## Haridwar');
        $response->assertSee('- [Kankhal Haridwar](https://www.haridwarrishikeshtourism.com/kankhal.html)');
    }

    public function test_indexnow_key_file_is_served_only_for_the_configured_key(): void
    {
        config(['seo.indexnow_key' => 'a1b2c3d4e5f60718293a4b5c6d7e8f90']);

        $this->get('/a1b2c3d4e5f60718293a4b5c6d7e8f90.txt')->assertContent('a1b2c3d4e5f60718293a4b5c6d7e8f90');
        $this->get('/somethingelse123.txt')->assertNotFound();
    }

    public function test_html_sitemap_links_every_published_page(): void
    {
        Page::factory()->create(['path' => 'kankhal.html', 'title' => 'Kankhal Haridwar', 'section' => 'haridwar']);

        $this->get('/sitemap.html')->assertSee('href="/kankhal.html"', false);
    }
}
