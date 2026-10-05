<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Page;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * InnoDB FULLTEXT indexes only see committed rows, so these tests commit
 * their data (truncation) instead of running inside a rolled-back transaction.
 */
class SearchControllerTest extends TestCase
{
    use DatabaseTruncation;

    public function test_search_finds_pages_by_words_in_their_content_and_ranks_title_matches_first(): void
    {
        Page::factory()->create(['path' => 'rafting.html', 'title' => 'Rafting in Rishikesh', 'body' => '<p>White water adventure on the Ganges.</p>']);
        Page::factory()->create(['path' => 'camping.html', 'title' => 'Camping in Shivpuri', 'body' => '<p>Beach camps with rafting included.</p>']);
        Page::factory()->create(['path' => 'temples.html', 'title' => 'Haridwar Temples', 'body' => '<p>Mansa Devi and Chandi Devi.</p>']);

        $response = $this->get('/search?q=rafting');

        $response->assertSeeInOrder(['Rafting in Rishikesh', 'Camping in Shivpuri']);
        $response->assertDontSee('Haridwar Temples');
        $response->assertSee('noindex,follow', false);
    }

    public function test_search_matches_word_prefixes_while_typing(): void
    {
        Page::factory()->create(['path' => 'har-ki-pauri.html', 'title' => 'Har Ki Pauri Haridwar']);

        $this->get('/search?q=harid')->assertSee('Har Ki Pauri Haridwar');
    }

    public function test_search_filters_by_page_type(): void
    {
        Page::factory()->package()->create(['path' => 'tour.html', 'title' => 'Haridwar Rishikesh Tour Package']);
        Page::factory()->create(['path' => 'guide.html', 'title' => 'Haridwar Guide']);

        $response = $this->get('/search?q=haridwar&type=package');

        $response->assertSee('Haridwar Rishikesh Tour Package');
        $response->assertDontSee('Haridwar Guide</a>', false);
    }

    public function test_search_never_returns_unpublished_pages(): void
    {
        Page::factory()->unpublished()->create(['path' => 'secret.html', 'title' => 'Secret Rafting Offer']);

        $this->get('/search?q=rafting')->assertDontSee('Secret Rafting Offer');
    }

    public function test_search_escapes_the_query_in_the_results_page(): void
    {
        $this->get('/search?q=<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_results_sidebar_offers_a_search_box_and_featured_packages_before_best_sellers(): void
    {
        Page::factory()->package()->create(['path' => 'haridwar-tour-package.html', 'title' => 'Haridwar Tour Package']);
        Page::factory()->package()->create(['path' => 'auli-ski-tour.html', 'title' => 'Auli Ski Tour - Auli Skiing Packages', 'is_featured' => true]);
        Page::factory()->package()->create(['path' => 'kedarkantha-trek.html', 'title' => 'Kedarkantha Trek']);

        $response = $this->get('/search?q=temples');

        $response->assertSee('id="page-search"', false);
        $response->assertSee('value="temples"', false);
        $response->assertSeeInOrder(['Popular tour packages', 'Auli Ski Tour', 'Haridwar Tour Package']);
        $response->assertDontSee('Auli Skiing Packages');
        $response->assertDontSee('Kedarkantha Trek');
    }

    public function test_suggestions_return_matching_pages_as_json(): void
    {
        Page::factory()->create(['path' => 'rafting.html', 'title' => 'Rafting in Rishikesh']);

        $response = $this->getJson('/search/suggest?q=raft');

        $response->assertJsonPath('results.0.title', 'Rafting in Rishikesh');
        $response->assertJsonPath('results.0.url', '/rafting.html');
    }

    public function test_suggestions_need_at_least_two_characters(): void
    {
        $this->getJson('/search/suggest?q=r')->assertExactJson(['query' => 'r', 'results' => []]);
    }
}
