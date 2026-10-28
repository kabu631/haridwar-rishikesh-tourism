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
        $this->actingAs(User::factory()->admin()->create());

        $screens = [
            '/hrtsiteadmin',
            '/hrtsiteadmin/pages',
            '/hrtsiteadmin/pages?tab=packages',
            '/hrtsiteadmin/pages?tab=places',
            '/hrtsiteadmin/pages?tab=guides',
            '/hrtsiteadmin/pages?tab=sections',
            '/hrtsiteadmin/pages?tab=company',
            '/hrtsiteadmin/pages/create',
            '/hrtsiteadmin/pages/create?type=company',
            '/hrtsiteadmin/pages/'.Page::query()->where('path', '')->value('id').'/edit',
            '/hrtsiteadmin/pages/'.Page::query()->where('type', 'package')->value('id').'/edit',
            '/hrtsiteadmin/pages/'.Page::query()->where('type', 'hub')->value('id').'/edit',
            '/hrtsiteadmin/pages/'.Page::query()->where('path', 'contact-us.html')->value('id').'/edit',
            '/hrtsiteadmin/enquiries',
            '/hrtsiteadmin/enquiries/'.$enquiry->id,
            '/hrtsiteadmin/redirects',
            '/hrtsiteadmin/redirects/'.Redirect::query()->value('id').'/edit',
            '/hrtsiteadmin/menu-items',
            '/hrtsiteadmin/menu-items?tab=footer',
            '/hrtsiteadmin/menu-items/create?menu=footer',
            '/hrtsiteadmin/authors',
            '/hrtsiteadmin/testimonials',
            '/hrtsiteadmin/settings',
            '/hrtsiteadmin/users',
            '/hrtsiteadmin/users/create',
        ];

        foreach ($screens as $screen) {
            $this->get($screen)->assertOk();
        }
    }
}
