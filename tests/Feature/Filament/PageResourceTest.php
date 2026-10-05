<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Author;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PageResourceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_visitors_must_sign_in_to_reach_the_admin_panel(): void
    {
        $this->get('/admin/pages')->assertRedirect('/admin/login');
    }

    public function test_users_who_are_not_admins_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get('/admin')->assertForbidden();
    }

    public function test_admin_sees_the_pages_list(): void
    {
        $page = Page::factory()->create(['title' => 'Har Ki Pauri Haridwar']);
        $this->actingAs($this->admin());

        Livewire::test(ListPages::class)->assertCanSeeTableRecords([$page]);
    }

    public function test_admin_edits_the_title_tag_and_meta_description(): void
    {
        $page = Page::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm([
                'meta_title' => 'Har Ki Pauri Ganga Aarti Timings & Guide',
                'meta_description' => 'Ganga Aarti timings at Har Ki Pauri, Haridwar, with tips for the best viewing spots.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();
        $this->assertSame('Har Ki Pauri Ganga Aarti Timings & Guide', $page->meta_title);
        $this->assertSame('Ganga Aarti timings at Har Ki Pauri, Haridwar, with tips for the best viewing spots.', $page->meta_description);
    }

    public function test_changing_the_url_in_the_admin_creates_a_301_redirect(): void
    {
        $page = Page::factory()->create(['path' => 'old-url.html']);
        $this->actingAs($this->admin());

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['path' => 'new-url.html'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('redirects', ['from_path' => '/old-url.html', 'to_path' => '/new-url.html', 'status_code' => 301]);
    }

    public function test_url_must_be_lowercase_words_ending_in_html(): void
    {
        $page = Page::factory()->create(['path' => 'kankhal.html']);
        $this->actingAs($this->admin());

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['path' => 'Kankhal Temple'])
            ->call('save')
            ->assertHasFormErrors(['path' => 'regex']);

        $this->assertSame('kankhal.html', $page->fresh()->path);
    }

    public function test_saving_keeps_stored_data_that_the_form_does_not_show(): void
    {
        $page = Page::factory()->create(['extra' => ['thumbnail' => '/haridwar-tourism/kankhal.jpg', 'legacy_in_sitemap' => true]]);
        $this->actingAs($this->admin());

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['title' => 'Kankhal Haridwar'])
            ->call('save')
            ->assertHasNoFormErrors();

        $extra = $page->fresh()->extra;
        $this->assertSame('/haridwar-tourism/kankhal.jpg', $extra['thumbnail']);
        $this->assertTrue($extra['legacy_in_sitemap']);
    }

    public function test_mark_as_reviewed_records_the_reviewer_and_date(): void
    {
        $this->travelTo('2026-10-04 10:00:00');
        $page = Page::factory()->create();
        $reviewer = Author::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->callAction('markReviewed', ['reviewer_id' => $reviewer->id]);

        $page->refresh();
        $this->assertSame($reviewer->id, $page->reviewer_id);
        $this->assertSame('2026-10-04', $page->reviewed_at->toDateString());
    }
}
