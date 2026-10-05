<?php

namespace Database\Factories;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redirect>
 */
class RedirectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_path' => '/'.$this->faker->unique()->slug(3).'.html',
            'to_path' => '/',
            'status_code' => 301,
        ];
    }

    public function gone(): static
    {
        return $this->state(fn (): array => ['to_path' => null, 'status_code' => 410]);
    }
}
