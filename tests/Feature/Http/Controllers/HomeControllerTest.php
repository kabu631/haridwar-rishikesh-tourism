<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Page;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_keeps_its_legacy_title_and_has_one_h1(): void
    {
        Page::factory()->home()->create();

        $response = $this->get('/');

        $response->assertSee('<title>Book Haridwar Rishikesh Packages - Har Ki Pauri Snan Tour</title>', false);
        $this->assertSame(1, substr_count((string) $response->getContent(), '<h1'));
    }

    public function test_homepage_publishes_organisation_and_sitelinks_search_structured_data(): void
    {
        Page::factory()->home()->create();

        $response = $this->get('/');

        $response->assertSee('"@type":["TravelAgency","LocalBusiness"]', false);
        $response->assertSee('"urlTemplate":"https://www.haridwarrishikeshtourism.com/search?q={search_term_string}"', false);
        $response->assertSee('"value":"TA-01/HDR/53/2016-17"', false);
    }

    public function test_homepage_shows_only_published_four_and_five_star_tripadvisor_reviews(): void
    {
        Page::factory()->home()->create();
        Testimonial::factory()->tripadvisor(5)->create(['name' => 'Raghu A', 'title' => 'Wonderful Ganga Aarti']);
        Testimonial::factory()->tripadvisor(4)->create(['name' => 'Meera K']);
        Testimonial::factory()->tripadvisor(3)->create(['name' => 'Three Star Guest']);
        Testimonial::factory()->tripadvisor(5)->create(['name' => 'Hidden Guest', 'is_published' => false]);
        Testimonial::factory()->create(['name' => 'Mr. Legacy Guest', 'source' => 'Guest feedback', 'rating' => 5]);

        $response = $this->get('/');

        $response->assertSee('Raghu A');
        $response->assertSee('Wonderful Ganga Aarti');
        $response->assertSee('Rated 5 out of 5 on Tripadvisor');
        $response->assertSee('Meera K');
        $response->assertDontSee('Three Star Guest');
        $response->assertDontSee('Hidden Guest');
        $response->assertDontSee('Mr. Legacy Guest');
    }

    public function test_homepage_hides_the_reviews_section_until_tripadvisor_reviews_are_imported(): void
    {
        Page::factory()->home()->create();
        Testimonial::factory()->create(['name' => 'Mr. Legacy Guest', 'source' => 'Guest feedback']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('aria-label="Previous review"', false);
    }
}
