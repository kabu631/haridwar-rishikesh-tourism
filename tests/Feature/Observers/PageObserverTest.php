<?php

namespace Tests\Feature\Observers;

use App\Models\Page;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PageObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_a_page_url_creates_a_301_from_the_old_url(): void
    {
        $page = Page::factory()->create(['path' => 'old-name.html']);

        $page->update(['path' => 'new-name.html']);

        $this->assertDatabaseHas('redirects', ['from_path' => '/old-name.html', 'to_path' => '/new-name.html', 'status_code' => 301]);
        $this->get('/old-name.html')->assertRedirect('https://www.haridwarrishikeshtourism.com/new-name.html');
    }

    public function test_deleting_a_page_redirects_its_url_to_the_parent_page(): void
    {
        $parent = Page::factory()->hub()->create(['path' => 'haridwar-tourism.html']);
        $page = Page::factory()->create(['path' => 'old-hotel.html', 'parent_id' => $parent->id]);

        $page->delete();

        $this->get('/old-hotel.html')->assertRedirect('https://www.haridwarrishikeshtourism.com/haridwar-tourism.html');
    }

    public function test_publishing_a_page_at_a_redirected_url_removes_the_redirect(): void
    {
        Redirect::factory()->create(['from_path' => '/comeback.html', 'to_path' => '/']);

        Page::factory()->create(['path' => 'comeback.html']);

        $this->assertDatabaseMissing('redirects', ['from_path' => '/comeback.html']);
    }

    public function test_saving_a_page_in_production_submits_it_to_indexnow(): void
    {
        Http::fake();
        $this->withoutDefer();
        config(['seo.indexnow_key' => 'a1b2c3d4e5f60718293a4b5c6d7e8f90']);
        $this->app->detectEnvironment(fn (): string => 'production');

        Page::factory()->create(['path' => 'kankhal.html']);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.indexnow.org/indexnow'
            && $request['urlList'] === ['https://www.haridwarrishikeshtourism.com/kankhal.html']
            && $request['keyLocation'] === 'https://www.haridwarrishikeshtourism.com/a1b2c3d4e5f60718293a4b5c6d7e8f90.txt');
    }

    public function test_indexnow_is_not_contacted_outside_production(): void
    {
        Http::fake();
        $this->withoutDefer();
        config(['seo.indexnow_key' => 'a1b2c3d4e5f60718293a4b5c6d7e8f90']);

        Page::factory()->create(['path' => 'kankhal.html']);

        Http::assertNothingSent();
    }

    public function test_saving_strips_unsafe_markup_from_the_body(): void
    {
        $page = Page::factory()->create(['body' => '<p onclick="steal()">Ganga Aarti</p><script>alert(1)</script>']);

        $this->assertSame('<p>Ganga Aarti</p>', $page->fresh()->body);
    }

    public function test_lines_bulleted_with_an_icon_image_are_saved_as_a_list(): void
    {
        $page = Page::factory()->create(['body' => '<h2>Presenters</h2><p>Swami Divyanand ji.<br> <img src="/images/Right-Arrow.png" style="width: 15px"> Swami Asanganand ji.<br> <img src="/images/Right-Arrow.png" style="width: 15px"> Swami Chidanand ji.</p>']);

        $this->assertSame(
            "<h2>Presenters</h2>\n<ul><li>Swami Divyanand ji.</li>\n<li>Swami Asanganand ji.</li>\n<li>Swami Chidanand ji.</li>\n</ul>",
            $page->fresh()->body,
        );
    }

    public function test_photos_between_lines_of_text_are_kept(): void
    {
        $body = '<p>Har Ki Pauri<br><img src="/images/har-ki-pauri.jpg"> Evening aarti<br>Ganga ghat</p>';

        $page = Page::factory()->create(['body' => $body]);

        $this->assertStringContainsString('<img src="/images/har-ki-pauri.jpg">', $page->fresh()->body);
        $this->assertStringNotContainsString('<ul>', $page->fresh()->body);
    }
}
