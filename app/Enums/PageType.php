<?php

namespace App\Enums;

/**
 * Content type of a page; drives the template variant and its schema.org type.
 */
enum PageType: string
{
    case Home = 'home';
    case Hub = 'hub';
    case Package = 'package';
    case Destination = 'destination';
    case Attraction = 'attraction';
    case Guide = 'guide';
    case Hotel = 'hotel';
    case Festival = 'festival';
    case Gallery = 'gallery';
    case Company = 'company';
    case Blog = 'blog';

    public function label(): string
    {
        return match ($this) {
            self::Blog => 'Blog post',
            self::Home => 'Homepage',
            self::Hub => 'Hub / category',
            self::Package => 'Tour package',
            self::Destination => 'Destination',
            self::Attraction => 'Attraction / place',
            self::Guide => 'Travel guide',
            self::Hotel => 'Hotel',
            self::Festival => 'Festival / event',
            self::Gallery => 'Gallery',
            self::Company => 'Company page',
        };
    }

    /**
     * Short label shown on cards and search results.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Package => 'Tour package',
            self::Destination => 'Destination',
            self::Attraction => 'Place to visit',
            self::Hotel => 'Hotel',
            self::Festival => 'Festival',
            self::Gallery => 'Photos',
            self::Company => 'About us',
            self::Blog => 'Blog',
            default => 'Travel guide',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
