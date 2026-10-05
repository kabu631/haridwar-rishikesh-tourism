<?php

namespace Database\Factories;

use App\Enums\PageType;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title($this->faker->unique()->words(3, true));

        return [
            'path' => Str::slug($title).'.html',
            'type' => PageType::Guide,
            'section' => 'haridwar',
            'title' => $title,
            'meta_title' => $title.' – Haridwar Rishikesh Tourism',
            'meta_description' => $this->faker->sentence(20),
            'excerpt' => $this->faker->sentence(18),
            'body' => '<p>'.$this->faker->paragraph(8).'</p><h2>Things to know</h2><p>'.$this->faker->paragraph(6).'</p>',
            'robots' => 'index,follow',
            'is_published' => true,
            'published_at' => now()->subYear(),
        ];
    }

    public function home(): static
    {
        return $this->state(fn (): array => [
            'path' => '',
            'type' => PageType::Home,
            'section' => null,
            'title' => 'Haridwar Rishikesh Tour Packages & Travel Guide',
            'meta_title' => 'Book Haridwar Rishikesh Packages - Har Ki Pauri Snan Tour',
            'extra' => ['home' => ['hero' => ['eyebrow' => 'Haridwar Tours', 'text' => 'Local travel agent since 1995.', 'highlights' => ['Hotels']]]],
        ]);
    }

    public function package(): static
    {
        return $this->state(fn (): array => [
            'type' => PageType::Package,
            'section' => 'packages',
            'title' => 'Haridwar Rishikesh Tour Packages From Delhi (02 Night 03 Days)',
            'facts' => ['duration_nights' => 2, 'duration_days' => 3, 'start_city' => 'Delhi', 'destinations' => ['Haridwar', 'Rishikesh']],
            'itinerary' => [
                ['day' => 1, 'title' => 'Delhi to Haridwar', 'description' => 'Drive to Haridwar and attend Ganga Aarti.'],
                ['day' => 2, 'title' => 'Haridwar Rishikesh sightseeing', 'description' => 'Visit Lakshman Jhula and Triveni Ghat.'],
                ['day' => 3, 'title' => 'Haridwar to Delhi', 'description' => 'Return to Delhi.'],
            ],
        ]);
    }

    public function hub(): static
    {
        return $this->state(fn (): array => [
            'type' => PageType::Hub,
            'cards' => [
                ['title' => 'Har Ki Pauri', 'url' => '/har-ki-pauri.html', 'image' => null, 'alt' => null, 'text' => 'The sacred ghat of Haridwar.', 'badge' => null],
            ],
        ]);
    }

    public function temple(): static
    {
        return $this->state(fn (): array => [
            'type' => PageType::Attraction,
            'schema_type' => 'HinduTemple',
            'title' => 'Mansa Devi Temple Haridwar',
        ]);
    }

    public function withFaqs(): static
    {
        return $this->state(fn (): array => [
            'faqs' => [
                ['question' => 'What is the best time to visit?', 'answer' => 'October to March offers pleasant weather for sightseeing.'],
            ],
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function noindex(): static
    {
        return $this->state(fn (): array => ['robots' => 'noindex,follow']);
    }

    public function canonicalTo(string $url): static
    {
        return $this->state(fn (): array => ['canonical_url' => $url]);
    }
}
