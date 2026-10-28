<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Widgets\LatestEnquiries;
use App\Models\Author;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_add_and_edit_pages_but_cannot_delete_them(): void
    {
        $page = Page::factory()->create();
        $this->actingAs(User::factory()->staff()->create());

        $this->get('/hrtsiteadmin/pages')->assertOk();
        $this->get('/hrtsiteadmin/pages/create')->assertOk();

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['title' => 'Chandi Devi Temple Haridwar'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertActionHidden('delete');

        $this->assertSame('Chandi Devi Temple Haridwar', $page->fresh()->title);
    }

    public function test_staff_cannot_open_anything_besides_pages(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        foreach (['/hrtsiteadmin/enquiries', '/hrtsiteadmin/redirects', '/hrtsiteadmin/menu-items', '/hrtsiteadmin/testimonials', '/hrtsiteadmin/authors', '/hrtsiteadmin/settings', '/hrtsiteadmin/users'] as $screen) {
            $this->get($screen)->assertForbidden();
        }

        $this->assertFalse(LatestEnquiries::canView());
    }

    public function test_editors_delete_pages_and_manage_site_content_but_not_enquiries_settings_or_users(): void
    {
        $page = Page::factory()->create();
        $this->actingAs(User::factory()->editor()->create());

        foreach (['/hrtsiteadmin/pages', '/hrtsiteadmin/redirects', '/hrtsiteadmin/menu-items', '/hrtsiteadmin/testimonials', '/hrtsiteadmin/authors'] as $screen) {
            $this->get($screen)->assertOk();
        }

        foreach (['/hrtsiteadmin/enquiries', '/hrtsiteadmin/settings', '/hrtsiteadmin/users'] as $screen) {
            $this->get($screen)->assertForbidden();
        }

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->callAction('delete');

        $this->assertModelMissing($page);
    }

    public function test_admin_adds_a_staff_user_who_can_sign_in(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Sunita Rawat',
                'email' => 'sunita@example.com',
                'password' => 'Ganga-Aarti-2026',
                'role' => UserRole::Staff->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'sunita@example.com')->firstOrFail();
        $this->assertSame(UserRole::Staff, $user->role);
        $this->assertTrue(Hash::check('Ganga-Aarti-2026', $user->password));

        $this->actingAs($user)->get('/hrtsiteadmin/pages')->assertOk();
    }

    public function test_leaving_the_password_blank_keeps_the_current_one(): void
    {
        $editor = User::factory()->editor()->create(['password' => 'Old-Password-123']);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditUser::class, ['record' => $editor->getRouteKey()])
            ->fillForm(['role' => UserRole::Staff->value, 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $editor->refresh();
        $this->assertSame(UserRole::Staff, $editor->role);
        $this->assertTrue(Hash::check('Old-Password-123', $editor->password));
    }

    public function test_admin_links_a_staff_user_to_their_author_profile(): void
    {
        $author = Author::factory()->create(['name' => 'Sunita Rawat']);
        $staff = User::factory()->staff()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditUser::class, ['record' => $staff->getRouteKey()])
            ->fillForm(['author_id' => $author->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($staff->fresh()->author->is($author));
        $this->assertTrue($author->fresh()->user->is($staff));
    }

    public function test_an_author_profile_can_belong_to_only_one_user(): void
    {
        $author = Author::factory()->create();
        User::factory()->editor()->create(['author_id' => $author->id]);
        $staff = User::factory()->staff()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditUser::class, ['record' => $staff->getRouteKey()])
            ->fillForm(['author_id' => $author->id])
            ->call('save')
            ->assertHasFormErrors(['author_id']);

        $this->assertNull($staff->fresh()->author_id);
    }

    public function test_pages_added_by_a_user_are_credited_to_their_author_profile(): void
    {
        Author::factory()->create();
        $author = Author::factory()->create();
        $this->actingAs(User::factory()->staff()->create(['author_id' => $author->id]));

        Livewire::withQueryParams(['type' => 'company'])
            ->test(CreatePage::class)
            ->assertSchemaStateSet(['author_id' => $author->id])
            ->fillForm([
                'title' => 'Cancellation Policy',
                'path' => 'cancellation-policy.html',
                'excerpt' => 'How to cancel or change a Haridwar Rishikesh tour booking.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame($author->id, Page::query()->where('path', 'cancellation-policy.html')->value('author_id'));
    }

    public function test_admins_cannot_delete_their_own_account_or_change_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->assertActionHidden('delete')
            ->assertFormFieldDisabled('role');
    }
}
