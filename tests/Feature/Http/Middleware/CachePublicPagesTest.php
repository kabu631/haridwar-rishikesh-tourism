<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\Author;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class CachePublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_request_for_a_page_is_served_from_the_page_cache(): void
    {
        Page::factory()->create(['path' => 'kankhal.html']);

        $this->get('/kankhal.html')->assertHeader('X-Page-Cache', 'MISS');

        $this->get('/kankhal.html')->assertHeader('X-Page-Cache', 'HIT');
    }

    public function test_cached_html_uses_host_independent_asset_and_form_urls(): void
    {
        Page::factory()->create(['path' => 'kankhal.html']);
        $this->get('http://localhost/kankhal.html');

        $response = $this->get('http://127.0.0.1:8000/kankhal.html');

        $response->assertHeader('X-Page-Cache', 'HIT');
        $response->assertSee('<link rel="stylesheet" href="/build/assets/', false);
        $response->assertSee('action="/enquiry"', false);
        $response->assertSee('data-suggest="/search/suggest"', false);
        $response->assertDontSee('src="http://localhost', false);
    }

    public function test_editing_a_page_refreshes_the_cached_html(): void
    {
        $page = Page::factory()->create(['path' => 'kankhal.html', 'title' => 'Kankhal Old Title']);
        $this->get('/kankhal.html');

        $page->update(['title' => 'Kankhal New Title']);

        $this->get('/kankhal.html')->assertSee('Kankhal New Title');
    }

    public function test_editing_a_team_member_refreshes_the_cached_team_page(): void
    {
        Page::factory()->create(['path' => 'our-team.html']);
        $author = Author::factory()->create(['job_title' => 'Team Member']);
        $this->get('/our-team.html');

        $author->update(['job_title' => 'Tour Manager']);

        $this->get('/our-team.html')->assertHeader('X-Page-Cache', 'MISS')->assertSee('Tour Manager');
    }

    public function test_a_new_asset_build_retires_pages_cached_with_old_asset_links(): void
    {
        Page::factory()->create(['path' => 'kankhal.html']);
        $manifestHash = 'first-build';
        Vite::partialMock()->shouldReceive('manifestHash')->andReturnUsing(function () use (&$manifestHash): string {
            return $manifestHash;
        });

        $this->get('/kankhal.html');
        $this->get('/kankhal.html')->assertHeader('X-Page-Cache', 'HIT');

        $manifestHash = 'second-build';

        $this->get('/kankhal.html')->assertHeader('X-Page-Cache', 'MISS');
    }

    public function test_requests_with_query_parameters_are_not_cached(): void
    {
        Page::factory()->create(['path' => 'kankhal.html']);

        $this->get('/kankhal.html?preview=1')->assertHeaderMissing('X-Page-Cache');
    }

    public function test_public_pages_do_not_start_a_session(): void
    {
        Page::factory()->create(['path' => 'kankhal.html']);

        $this->get('/kankhal.html')->assertCookieMissing(config('session.cookie'));
    }
}
