<?php

namespace Database\Factories;

use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enquiry>
 */
class EnquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'quick',
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => '+91 98765 43210',
            'tour' => 'Haridwar Rishikesh Tour',
            'status' => 'new',
        ];
    }

    public function spam(): static
    {
        return $this->state(fn (): array => ['status' => 'spam']);
    }
}
