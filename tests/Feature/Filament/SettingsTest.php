<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Settings;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_phone_number_replaces_the_default_on_the_website(): void
    {
        Page::factory()->create(['path' => 'kankhal.html']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(Settings::class)
            ->fillForm(['phone' => '+91-9000000000'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/kankhal.html')->assertSee('+91-9000000000');
    }
}
