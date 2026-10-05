<?php

namespace Tests\Feature\View\Composers;

use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_is_linked_from_the_utility_bar_instead_of_the_main_navigation(): void
    {
        config(['seo.page_cache_ttl' => 0]);
        Page::factory()->home()->create();
        MenuItem::factory()->create(['label' => 'Trekking', 'url' => '/trekking.html', 'sort_order' => 1]);
        MenuItem::factory()->create(['label' => 'Gallery', 'url' => '/gallery.html', 'sort_order' => 2]);

        $html = (string) $this->get('/')->getContent();
        $mainNavigation = str($html)->after('<nav aria-label="Main"')->before('</nav>')->toString();

        $this->assertStringContainsString('>Trekking', $mainNavigation);
        $this->assertStringNotContainsString('/gallery.html', $mainNavigation);
        $this->assertStringContainsString('href="/gallery.html"', $html);
        $this->assertStringContainsString('href="'.config('site.social.blog').'"', $html);
    }

    public function test_every_page_offers_the_trip_assistant_and_a_back_to_top_button(): void
    {
        config(['seo.page_cache_ttl' => 0]);
        Page::factory()->home()->create();

        $response = $this->get('/');

        $response->assertSee('data-back-to-top', false);
        $response->assertSee('data-endpoint="/chatbot/packages"', false);

        foreach (config('chatbot.topics') as $topic) {
            $response->assertSee('data-topic="'.$topic['key'].'"', false);
        }
    }
}
