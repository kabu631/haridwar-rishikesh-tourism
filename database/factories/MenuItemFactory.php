<?php

namespace Database\Factories;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu' => 'main',
            'label' => ucfirst($this->faker->unique()->word()),
            'url' => '/'.$this->faker->slug(2).'.html',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
