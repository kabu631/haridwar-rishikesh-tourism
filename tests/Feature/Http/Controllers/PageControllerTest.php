<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PageType;
use App\Models\Author;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_a_page_at_its_legacy_html_url_with_seo_tags(): void
    {
        Page::factory()->create([
            'path' => 'har-ki-pauri.html',
            'title' => 'Har Ki Pauri Haridwar',
            'meta_title' => 'Har Ki Pauri - Haridwar Rishikesh Tourism',
            'meta_description' => 'Har Ki Pauri is the most sacred ghat of Haridwar.',
        ]);

        $response = $this->get('/har-ki-pauri.html');

        $response->assertSee('<title>Har Ki Pauri - Haridwar Rishikesh Tourism</title>', false);
        $response->assertSee('<meta name="description" content="Har Ki Pauri is the most sacred ghat of Haridwar.">', false);
        $response->assertSee('<link rel="canonical" href="https://www.haridwarrishikeshtourism.com/har-ki-pauri.html">', false);
        $response->assertSee('<link rel="alternate" hreflang="x-default" href="https://www.haridwarrishikeshtourism.com/har-ki-pauri.html">', false);
        $response->assertSee('index,follow,max-image-preview:large', false);
    }

    public function test_tour_packages_and_blog_posts_show_the_author_update_date_and_reading_time(): void
    {
        $author = Author::factory()->create(['name' => 'Sunil Saini']);
        Page::factory()->package()->create(['path' => 'haridwar-tour-package.html', 'author_id' => $author->id]);
        Page::factory()->create(['path' => 'ganga-dussehra-2026.html', 'type' => PageType::Blog, 'author_id' => $author->id]);

        foreach (['/haridwar-tour-package.html', '/ganga-dussehra-2026.html'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('Sunil Saini')
                ->assertSee('min read')
                ->assertSee('About the author', false)
                ->assertSee('"@type":"Person"', false);
        }
    }

    public function test_regular_pages_do_not_show_an_author_update_date_or_reading_time(): void
    {
        $author = Author::factory()->create(['name' => 'Sunil Saini']);
        Page::factory()->create(['path' => 'our-team.html', 'type' => PageType::Company, 'author_id' => $author->id]);
        Page::factory()->create(['path' => 'kankhal.html', 'type' => PageType::Guide, 'author_id' => $author->id]);

        foreach (['/our-team.html', '/kankhal.html'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('min read')
                ->assertDontSee('About the author', false)
                ->assertDontSee('"@type":"Article"', false);
        }
    }

    public function test_photo_pages_without_a_header_image_show_their_first_photo_on_cards(): void
    {
        $hub = Page::factory()->hub()->create(['path' => 'haridwar-tourism.html']);
        Page::factory()->create(['path' => 'kankhal.html', 'parent_id' => $hub->id]);
        $photos = Page::factory()->create([
            'path' => 'haridwar-photos.html',
            'type' => PageType::Gallery,
            'parent_id' => $hub->id,
            'hero_image' => null,
            'extra' => [],
            'gallery' => [['image' => null, 'alt' => 'Empty'], ['image' => '/haridwar-photos/1.jpg', 'alt' => 'Har Ki Pauri']],
        ]);

        $this->assertSame('/haridwar-photos/1.jpg', $photos->thumbnail());
        $this->get('/kankhal.html')->assertOk()->assertSee('haridwar-photos/1', false);
    }

    public function test_page_with_a_legacy_cross_canonical_keeps_it_and_drops_hreflang(): void
    {
        Page::factory()->canonicalTo('https://www.haridwarrishikeshtourism.com/haridwar-kumbh-mela.html')->create(['path' => 'kumbh-mela-map.html']);

        $response = $this->get('/kumbh-mela-map.html');

        $response->assertSee('<link rel="canonical" href="https://www.haridwarrishikeshtourism.com/haridwar-kumbh-mela.html">', false);
        $response->assertDontSee('hreflang', false);
    }

    public function test_package_page_publishes_touristtrip_structured_data_with_its_itinerary(): void
    {
        Page::factory()->package()->create(['path' => 'haridwar-rishikesh-tour.html']);

        $response = $this->get('/haridwar-rishikesh-tour.html');

        $response->assertSee('"@type":"TouristTrip"', false);
        $response->assertSee('"name":"Day 2: Haridwar Rishikesh sightseeing"', false);
        $response->assertSee('"duration":"P3D"', false);
    }

    public function test_temple_page_is_marked_up_as_a_hindu_temple(): void
    {
        Page::factory()->temple()->create(['path' => 'mansa-devi-temple.html']);

        $this->get('/mansa-devi-temple.html')->assertSee('"@type":["HinduTemple","TouristAttraction"]', false);
    }

    public function test_page_faqs_are_published_as_faqpage_structured_data(): void
    {
        Page::factory()->withFaqs()->create(['path' => 'deoriatal.html']);

        $this->get('/deoriatal.html')->assertSee('"@type":"FAQPage"', false);
    }

    public function test_breadcrumbs_follow_the_parent_hub(): void
    {
        $hub = Page::factory()->hub()->create(['path' => 'haridwar-tourism.html', 'title' => 'Haridwar Tourism']);
        Page::factory()->create(['path' => 'kankhal.html', 'parent_id' => $hub->id]);

        $response = $this->get('/kankhal.html');

        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('"item":"https://www.haridwarrishikeshtourism.com/haridwar-tourism.html"', false);
    }

    public function test_package_byline_shows_the_author_and_last_updated_date(): void
    {
        $author = Author::factory()->create(['name' => 'Sunil Saini']);
        Page::factory()->package()->create(['path' => 'kankhal-tour.html', 'author_id' => $author->id, 'updated_at' => '2020-04-23 05:34:03']);

        $response = $this->get('/kankhal-tour.html');

        $response->assertSee('Sunil Saini');
        $response->assertSee('Updated <time datetime="2020-04-23">23 Apr 2020</time>', false);
    }

    public function test_url_with_different_letter_case_redirects_to_the_indexed_url(): void
    {
        Page::factory()->create(['path' => 'har-ki-pauri.html']);

        $this->get('/Har-Ki-Pauri.html')->assertRedirect('https://www.haridwarrishikeshtourism.com/har-ki-pauri.html');
    }

    public function test_unpublished_page_returns_404(): void
    {
        Page::factory()->unpublished()->create(['path' => 'draft.html']);

        $this->get('/draft.html')->assertNotFound();
    }

    public function test_noindex_page_tells_search_engines_not_to_index_it(): void
    {
        Page::factory()->noindex()->create(['path' => 'private-offer.html']);

        $this->get('/private-offer.html')->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    public function test_listing_items_with_a_leading_photo_are_shown_as_cards(): void
    {
        config(['seo.page_cache_ttl' => 0]);
        Page::factory()->create([
            'path' => 'rishikesh-hotels.html',
            'body' => '<h2>List Of Hotels</h2>'
                .'<p><img src="/hotels/vasundhara.jpg" alt="vasundhara"><strong><a href="/vasundhara-palace.html">Vasundhara Palace</a></strong> : Vasundhara Palace is close to the temples and ashrams of Rishikesh.</p>'
                .'<p><img src="/hotels/divine.jpg" alt="divine"><strong>Divine Resorts</strong> – An economy resort with a view of the turquoise Ganga.</p>'
                .'<p><img src="/hotels/ganga.jpg" alt="ganga"></p><p>An ordinary paragraph after a photo stays as it is.</p>',
        ]);

        $html = (string) $this->get('/rishikesh-hotels.html')->getContent();

        $this->assertSame(1, substr_count($html, 'class="media-list"'));
        $this->assertSame(2, substr_count($html, 'class="media-card"'));
        $this->assertStringContainsString('<p class="media-card-title"><strong><a href="/vasundhara-palace.html">Vasundhara Palace</a></strong></p><p>Vasundhara Palace is close to', $html);
        $this->assertStringContainsString('<a href="/vasundhara-palace.html" class="media-card-more" tabindex="-1" aria-hidden="true">View details</a>', $html);
        $this->assertStringContainsString('<p class="media-card-title"><strong>Divine Resorts</strong></p><p>An economy resort', $html);
        $this->assertStringContainsString('<p>An ordinary paragraph after a photo stays as it is.</p>', $html);
    }

    public function test_book_now_cells_in_package_tables_become_enquire_now_links_with_the_package(): void
    {
        Page::factory()->package()->create([
            'path' => 'chardham-yatra-tour-packages.html',
            'body' => '<table><tr><th>Chardham Packages</th><th>Nights</th><th></th></tr>'
                .'<tr><td>Chardham Yatra From Delhi</td><td>10 Nights</td><td>Book Now</td></tr>'
                .'<tr><td><a href="/valley-of-flowers.html">Chardham with Valley of Flowers</a></td><td>11 Nights</td><td><a href="/book-now.php">Book now</a></td></tr></table>',
        ]);

        $this->get('/chardham-yatra-tour-packages.html')
            ->assertOk()
            ->assertDontSee('<td>Book Now</td>', false)
            ->assertSee('<a href="/contact-us.html?tour=Chardham+Yatra+From+Delhi#contact-form" class="table-cta" rel="nofollow" aria-label="Enquire Now about Chardham Yatra From Delhi">Enquire Now</a>', false)
            ->assertSee('href="/contact-us.html?tour=Chardham+with+Valley+of+Flowers#contact-form"', false);
    }

    public function test_contact_form_is_filled_in_with_the_package_from_an_enquire_now_link(): void
    {
        Page::factory()->create(['path' => 'contact-us.html', 'type' => PageType::Company, 'title' => 'Contact Us']);

        $this->get('/contact-us.html?tour=Chardham+Yatra+From+Delhi')
            ->assertOk()
            ->assertSee('<form id="contact-form"', false)
            ->assertSee('Enquire about this package')
            ->assertSee('name="tour" type="text" maxlength="190" value="Chardham Yatra From Delhi"', false)
            ->assertSee('<input type="hidden" name="type" value="package">', false);

        $this->get('/contact-us.html')
            ->assertOk()
            ->assertSee('Send us a message')
            ->assertDontSee('id="contact-form-tour"', false);
    }

    public function test_non_production_copies_are_never_indexable(): void
    {
        config(['seo.allow_indexing' => false]);
        Page::factory()->create(['path' => 'har-ki-pauri.html']);

        $response = $this->get('/har-ki-pauri.html');

        $response->assertSee('<meta name="robots" content="noindex,nofollow">', false);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
