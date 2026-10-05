<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'location' => $this->faker->city(),
            'body' => $this->faker->sentence(25),
            'is_published' => true,
        ];
    }

    /**
     * A guest review imported from Tripadvisor.
     */
    public function tripadvisor(int $rating = 5): static
    {
        return $this->state(fn (): array => [
            'source' => Testimonial::SOURCE_TRIPADVISOR,
            'external_id' => (string) $this->faker->unique()->numberBetween(100000000, 999999999),
            'title' => $this->faker->sentence(4),
            'rating' => $rating,
            'url' => 'https://www.tripadvisor.in/ShowUserReviews-g616028-d4868270-r'.$this->faker->randomNumber(9).'.html',
            'reviewed_at' => $this->faker->dateTimeBetween('-1 year'),
        ]);
    }
}
