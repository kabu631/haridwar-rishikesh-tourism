<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\Page;
use App\Models\Redirect;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RedirectLegacyUrlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_rule_sends_a_permanent_redirect_to_the_canonical_url(): void
    {
        Page::factory()->create(['path' => 'har-ki-pauri.html']);
        Redirect::factory()->create(['from_path' => '/hari-ki-paudi.html', 'to_path' => '/har-ki-pauri.html']);

        $response = $this->get('/hari-ki-paudi.html');

        $response->assertStatus(301);
        $response->assertRedirect('https://www.haridwarrishikeshtourism.com/har-ki-pauri.html');
    }

    public function test_chained_redirects_are_served_as_a_single_hop(): void
    {
        Redirect::factory()->create(['from_path' => '/a.html', 'to_path' => '/b.html']);
        Redirect::factory()->create(['from_path' => '/b.html', 'to_path' => '/c.html']);

        $this->get('/a.html')->assertRedirect('https://www.haridwarrishikeshtourism.com/c.html');
    }

    public function test_gone_rule_returns_410(): void
    {
        Redirect::factory()->gone()->create(['from_path' => '/retired-offer.html']);

        $this->get('/retired-offer.html')->assertStatus(410);
    }

    public function test_redirect_keeps_the_query_string(): void
    {
        Redirect::factory()->create(['from_path' => '/old.html', 'to_path' => '/new.html']);

        $this->get('/old.html?utm_source=facebook')->assertRedirect('https://www.haridwarrishikeshtourism.com/new.html?utm_source=facebook');
    }

    public function test_trailing_slash_on_a_page_url_redirects_to_the_url_without_it(): void
    {
        // The test client strips trailing slashes, so send the raw request through the kernel.
        $response = $this->app->make(Kernel::class)->handle(Request::create('/har-ki-pauri.html/'));

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://www.haridwarrishikeshtourism.com/har-ki-pauri.html', $response->headers->get('Location'));
    }

    public function test_redirect_hits_are_counted(): void
    {
        $redirect = Redirect::factory()->create(['from_path' => '/counted.html', 'to_path' => '/']);

        $this->get('/counted.html');

        $this->assertSame(1, $redirect->fresh()->hits);
    }
}
