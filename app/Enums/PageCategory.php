<?php

namespace App\Enums;

/**
 * Admin groupings of page types: the "Website pages" sidebar entries and the
 * tabs above the pages list. The homepage has its own sidebar entry.
 */
enum PageCategory: string
{
    case Packages = 'packages';
    case Places = 'places';
    case Guides = 'guides';
    case Blog = 'blog';
    case Sections = 'sections';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Packages => 'Tour packages',
            self::Places => 'Destinations & places',
            self::Guides => 'Travel guides',
            self::Blog => 'Blog',
            self::Sections => 'Section pages',
            self::Company => 'Company & legal',
        };
    }

    /**
     * Used in "Add …" buttons.
     */
    public function singularLabel(): string
    {
        return match ($this) {
            self::Packages => 'tour package',
            self::Places => 'place',
            self::Guides => 'travel guide',
            self::Blog => 'blog post',
            self::Sections => 'section page',
            self::Company => 'company page',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Packages => 'Itineraries with duration, price and day-by-day plan.',
            self::Places => 'Destinations, temples, ghats and other places to visit.',
            self::Guides => 'Travel guides, festivals and hotel pages.',
            self::Blog => 'News, travel tips and stories, listed newest first on the Blog page (/blog.html).',
            self::Sections => 'Menu landing pages that list other pages, and photo galleries.',
            self::Company => 'About us, contact, our team, terms, privacy policy and sitemap.',
        };
    }

    /**
     * @return list<PageType>
     */
    public function types(): array
    {
        return match ($this) {
            self::Packages => [PageType::Package],
            self::Places => [PageType::Destination, PageType::Attraction],
            self::Guides => [PageType::Guide, PageType::Festival, PageType::Hotel],
            self::Blog => [PageType::Blog],
            self::Sections => [PageType::Hub, PageType::Gallery],
            self::Company => [PageType::Company],
        };
    }

    /**
     * Type preselected when a page is added from this category.
     */
    public function defaultType(): PageType
    {
        return match ($this) {
            self::Places => PageType::Attraction,
            default => $this->types()[0],
        };
    }

    public static function forType(?PageType $type): ?self
    {
        foreach (self::cases() as $category) {
            if (in_array($type, $category->types(), true)) {
                return $category;
            }
        }

        return null;
    }
}
