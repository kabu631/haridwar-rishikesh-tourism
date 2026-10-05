<?php

namespace Tests\Feature\Filament;

use App\Enums\PageType;
use App\Filament\Resources\MenuItems\Pages\ListMenuItems;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Widgets\ContentOverview;
use App\Filament\Widgets\LatestEnquiries;
use App\Filament\Widgets\SeoHealth;
use App\Models\Enquiry;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_sidebar_groups_the_admin_by_category(): void
    {
        Page::factory()->home()->create();

        $this->get('/admin')->assertOk()->assertSeeInOrder([
            'Enquiries',
            'Website pages', 'Homepage', 'Tour packages', 'Destinations &amp; places', 'Travel guides', 'Section pages', 'Company &amp; legal', 'All pages',
            'Site content', 'Header menu', 'Footer links', 'Testimonials', 'Team &amp; authors',
            'SEO', 'Redirects (301)', 'SEO issues', 'XML sitemap',
            'Settings', 'Site settings',
        ], false);
    }

    public function test_dashboard_shows_quick_actions_enquiries_and_content_by_category(): void
    {
        Enquiry::factory()->create(['name' => 'Ramesh Kumar']);
        Page::factory()->package()->create();

        $this->get('/admin')->assertOk()->assertSee('Add tour package');

        Livewire::test(LatestEnquiries::class)->assertSee('Ramesh Kumar');
        Livewire::test(ContentOverview::class)->assertSee('Website content')->assertSeeInOrder(['Tour packages', '1']);
        Livewire::test(SeoHealth::class)->assertSee('SEO health');
    }

    public function test_each_page_category_lists_only_its_own_pages(): void
    {
        $terms = Page::factory()->create(['path' => 'terms-and-conditions.html', 'type' => PageType::Company]);
        $package = Page::factory()->package()->create(['path' => 'haridwar-tour-package.html']);

        Livewire::test(ListPages::class)
            ->set('activeTab', 'company')
            ->assertCanSeeTableRecords([$terms])
            ->assertCanNotSeeTableRecords([$package])
            ->set('activeTab', 'packages')
            ->assertCanSeeTableRecords([$package])
            ->assertCanNotSeeTableRecords([$terms]);
    }

    public function test_adding_a_page_from_a_category_preselects_its_type(): void
    {
        Livewire::withQueryParams(['type' => 'company'])
            ->test(CreatePage::class)
            ->assertSee('Add company page')
            ->assertSchemaStateSet(['type' => 'company', 'section' => 'company']);
    }

    public function test_a_privacy_policy_page_can_be_added_and_is_published_at_its_url(): void
    {
        Livewire::withQueryParams(['type' => 'company'])
            ->test(CreatePage::class)
            ->fillForm([
                'title' => 'Privacy Policy',
                'path' => 'privacy-policy.html',
                'excerpt' => 'How India Easy Trip collects and uses your personal information.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(PageType::Company, Page::query()->where('path', 'privacy-policy.html')->value('type'));
        $this->get('/privacy-policy.html')->assertOk()->assertSee('Privacy Policy');
    }

    public function test_header_menu_and_footer_links_are_listed_separately(): void
    {
        $header = MenuItem::factory()->create(['menu' => 'main', 'label' => 'Trekking']);
        $footer = MenuItem::factory()->create(['menu' => 'footer', 'label' => 'Terms and Conditions']);

        Livewire::test(ListMenuItems::class)
            ->assertCanSeeTableRecords([$header])
            ->assertCanNotSeeTableRecords([$footer])
            ->set('activeTab', 'footer')
            ->assertCanSeeTableRecords([$footer])
            ->assertCanNotSeeTableRecords([$header]);
    }

    public function test_sitemap_heading_and_intro_are_edited_as_a_company_page(): void
    {
        config(['seo.page_cache_ttl' => 0]);
        Page::query()->where('path', 'sitemap.html')->firstOrFail()->update([
            'title' => 'Site map',
            'excerpt' => 'Find every tour and guide in one place.',
        ]);

        $this->get('/sitemap.html')
            ->assertOk()
            ->assertSee('<h1 class="font-display text-3xl font-semibold sm:text-5xl">Site map</h1>', false)
            ->assertSee('Find every tour and guide in one place.');
    }
}
