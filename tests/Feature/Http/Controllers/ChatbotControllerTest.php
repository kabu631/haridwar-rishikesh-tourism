<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_option_suggests_only_published_tour_packages_that_match_it(): void
    {
        Page::factory()->package()->create(['path' => 'haridwar-tour-package.html']);
        Page::factory()->package()->create(['path' => 'kedarkantha-trek.html', 'section' => 'trekking', 'title' => 'Kedarkantha Trek']);
        Page::factory()->package()->unpublished()->create(['path' => 'rishikesh-tour-package.html']);
        Page::factory()->create(['path' => 'haridwar-tour-package-guide.html']);

        $response = $this->getJson('/chatbot/packages');

        $response->assertOk();
        $response->assertJsonCount(1, 'topics.haridwar-rishikesh');
        $response->assertJsonPath('topics.haridwar-rishikesh.0.url', '/haridwar-tour-package.html');
        $response->assertJsonPath('topics.trekking.0.title', 'Kedarkantha Trek');
        $response->assertJsonCount(0, 'topics.chardham');
    }

    public function test_suggestions_use_the_first_part_of_long_seo_titles_and_include_the_duration(): void
    {
        Page::factory()->package()->create([
            'path' => 'rishikesh-rafting-and-camping.html',
            'section' => 'adventure',
            'title' => 'Rishikesh Rafting and Camping - Rishikesh Camping and Rafting Tour Packages',
            'facts' => ['duration_nights' => 2, 'duration_days' => 3],
        ]);

        $response = $this->getJson('/chatbot/packages');

        $response->assertJsonPath('topics.adventure.0.title', 'Rishikesh Rafting and Camping');
        $response->assertJsonPath('topics.adventure.0.days', 3);
        $response->assertJsonPath('topics.adventure.0.nights', 2);
    }

    public function test_excluded_url_fragments_keep_combination_tours_out_of_hill_stations(): void
    {
        Page::factory()->package()->create(['path' => 'auli-tour-package-delhi.html']);
        Page::factory()->package()->create(['path' => 'haridwar-rishikesh-with-auli-tour.html']);

        $response = $this->getJson('/chatbot/packages');

        $response->assertJsonCount(1, 'topics.hills');
        $response->assertJsonPath('topics.hills.0.url', '/auli-tour-package-delhi.html');
        $response->assertJsonPath('topics.combined.0.url', '/haridwar-rishikesh-with-auli-tour.html');
    }

    public function test_suggestions_are_browser_cacheable_and_kept_out_of_search_engines(): void
    {
        $response = $this->getJson('/chatbot/packages');

        $response->assertHeader('X-Robots-Tag', 'noindex');
        $this->assertStringContainsString('max-age=600', (string) $response->headers->get('Cache-Control'));
    }
}
