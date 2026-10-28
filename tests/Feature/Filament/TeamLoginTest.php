<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Authors\Pages\CreateAuthor;
use App\Filament\Resources\Authors\Pages\EditAuthor;
use App\Models\Author;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TeamLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gives_a_team_member_a_login(): void
    {
        $author = Author::factory()->create(['name' => 'Piyush Saini']);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditAuthor::class, ['record' => $author->getRouteKey()])
            ->fillForm([
                'has_login' => true,
                'user' => ['email' => 'piyush@example.com', 'password' => 'Kankhal-Ghat-26', 'role' => UserRole::Editor->value],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $login = $author->fresh()->user;
        $this->assertSame('Piyush Saini', $login->name);
        $this->assertSame('piyush@example.com', $login->email);
        $this->assertSame(UserRole::Editor, $login->role);
        $this->assertTrue(Hash::check('Kankhal-Ghat-26', $login->password));

        $this->actingAs($login)->get('/hrtsiteadmin/pages/create')->assertOk();
    }

    public function test_admin_adds_a_new_team_member_with_a_login(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateAuthor::class)
            ->fillForm([
                'name' => 'Sunny Dhiman',
                'slug' => 'sunny-dhiman',
                'has_login' => true,
                'user' => ['email' => 'sunny@example.com', 'password' => 'Rishikesh-Raft-26', 'role' => UserRole::Staff->value],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $login = Author::query()->where('slug', 'sunny-dhiman')->firstOrFail()->user;
        $this->assertSame('sunny@example.com', $login->email);
        $this->assertSame(UserRole::Staff, $login->role);
    }

    public function test_a_new_login_needs_a_password_and_an_unused_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $author = Author::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditAuthor::class, ['record' => $author->getRouteKey()])
            ->fillForm(['has_login' => true, 'user' => ['email' => 'taken@example.com', 'password' => '', 'role' => UserRole::Staff->value]])
            ->call('save')
            ->assertHasFormErrors(['user.email' => 'unique', 'user.password' => 'required']);

        $this->assertNull($author->fresh()->user);
    }

    public function test_changing_the_role_keeps_the_password_when_left_blank(): void
    {
        $author = Author::factory()->create();
        $login = User::factory()->staff()->create(['author_id' => $author->id, 'password' => 'Old-Password-123']);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditAuthor::class, ['record' => $author->getRouteKey()])
            ->assertSchemaStateSet(['has_login' => true, 'user.email' => $login->email])
            ->fillForm(['user' => ['email' => $login->email, 'password' => '', 'role' => UserRole::Editor->value]])
            ->call('save')
            ->assertHasNoFormErrors();

        $login->refresh();
        $this->assertSame(UserRole::Editor, $login->role);
        $this->assertTrue(Hash::check('Old-Password-123', $login->password));
    }

    public function test_switching_the_login_off_removes_it(): void
    {
        $author = Author::factory()->create();
        $login = User::factory()->staff()->create(['author_id' => $author->id]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditAuthor::class, ['record' => $author->getRouteKey()])
            ->fillForm(['has_login' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertModelMissing($login);
        $this->assertModelExists($author);
    }

    public function test_deleting_a_team_member_removes_their_login(): void
    {
        $author = Author::factory()->create();
        $login = User::factory()->staff()->create(['author_id' => $author->id]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditAuthor::class, ['record' => $author->getRouteKey()])->callAction('delete');

        $this->assertModelMissing($author);
        $this->assertModelMissing($login);
    }

    public function test_editors_edit_team_profiles_but_cannot_see_or_remove_logins(): void
    {
        $author = Author::factory()->create();
        $login = User::factory()->staff()->create(['author_id' => $author->id]);
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(EditAuthor::class, ['record' => $author->getRouteKey()])
            ->assertDontSee('Admin login')
            ->assertActionHidden('delete')
            ->fillForm(['job_title' => 'Senior Tour Manager'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Senior Tour Manager', $author->fresh()->job_title);
        $this->assertModelExists($login);
    }

    public function test_admins_cannot_remove_their_own_login_from_the_team_page(): void
    {
        $author = Author::factory()->create();
        $admin = User::factory()->admin()->create(['author_id' => $author->id]);
        $this->actingAs($admin);

        Livewire::test(EditAuthor::class, ['record' => $author->getRouteKey()])
            ->assertFormFieldDisabled('has_login')
            ->assertFormFieldDisabled('user.role')
            ->assertActionHidden('delete');
    }
}
