<?php

namespace Tests\Feature\Filament;

use App\Models\Enquiry;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Smoke test: every admin screen renders with the real migrated content.
 */
#[Group('parity')]
class AdminScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_screens_render_with_the_migrated_content(): void
    {
        $this->seed(ContentSeeder::class);
        $enquiry = Enquiry::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $screens = [
            '/admin',
            '/admin/pages',
            '/admin/pages?tab=packages',
            '/admin/pages?tab=places',
            '/admin/pages?tab=guides',
            '/admin/pages?tab=sections',
            '/admin/pages?tab=company',
            '/admin/pages/create',
            '/admin/pages/create?type=company',
            '/admin/pages/'.Page::query()->where('path', '')->value('id').'/edit',
            '/admin/pages/'.Page::query()->where('type', 'package')->value('id').'/edit',
            '/admin/pages/'.Page::query()->where('type', 'hub')->value('id').'/edit',
            '/admin/pages/'.Page::query()->where('path', 'contact-us.html')->value('id').'/edit',
            '/admin/enquiries',
            '/admin/enquiries/'.$enquiry->id,
            '/admin/redirects',
            '/admin/redirects/'.Redirect::query()->value('id').'/edit',
            '/admin/menu-items',
            '/admin/menu-items?tab=footer',
            '/admin/menu-items/create?menu=footer',
            '/admin/authors',
            '/admin/testimonials',
            '/admin/settings',
        ];

        foreach ($screens as $screen) {
            $this->get($screen)->assertOk();
        }
    }
}
