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

    public function test_homepage_shows_published_testimonials_only(): void
    {
        Page::factory()->home()->create();
        Testimonial::factory()->create(['name' => 'Mr. Raghu Acharya']);
        Testimonial::factory()->create(['name' => 'Hidden Guest', 'is_published' => false]);

        $response = $this->get('/');

        $response->assertSee('Mr. Raghu Acharya');
        $response->assertDontSee('Hidden Guest');
    }
}
