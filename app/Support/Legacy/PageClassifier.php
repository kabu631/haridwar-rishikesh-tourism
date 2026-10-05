<?php

namespace App\Support\Legacy;

use App\Enums\PageType;

/**
 * Assigns a type, section and parent hub to each legacy page. The section
 * hierarchy mirrors the legacy main menu (which Google already understands
 * from internal links) and is used for breadcrumbs and related-page links.
 */
class PageClassifier
{
    /**
     * Section key => hub page path.
     */
    public const SECTION_HUBS = [
        'haridwar' => 'haridwar-tourism.html',
        'rishikesh' => 'rishikesh-tourism.html',
        'destinations' => 'uttarakhand-destinations.html',
        'mussoorie' => 'mussoorie-tourism.html',
        'adventure' => 'adventure-tourism.html',
        'packages' => 'tour-packages.html',
        'festivals' => 'uttarakhand-festival.html',
        'travel' => 'how-to-reach.html',
        'trekking' => 'trekking.html',
        'special' => 'special-tour.html',
        'gallery' => 'gallery.html',
        'company' => 'about-us.html',
    ];

    public const SECTION_LABELS = [
        'haridwar' => 'Haridwar',
        'rishikesh' => 'Rishikesh',
        'destinations' => 'Uttarakhand',
        'mussoorie' => 'Mussoorie',
        'adventure' => 'Adventure',
        'packages' => 'Tour Packages',
        'festivals' => 'Festivals',
        'travel' => 'Travel Info',
        'trekking' => 'Trekking',
        'special' => 'Special Tours',
        'gallery' => 'Gallery',
        'blog' => 'Blog',
        'company' => 'About Us',
    ];

    /**
     * Ordered slug rules for pages that are not linked from the main menu:
     * pattern => [section, parent path].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const RULES = [
        '/^(our-|about-|contact-|terms-|book-now)/' => ['company', 'about-us.html'],
        '/^hotel-.*haridwar/' => ['haridwar', 'haridwar-hotels.html'],
        '/(hotel|resort|lodge|palace).*rishikesh/' => ['rishikesh', 'rishikesh-hotels.html'],
        '/kumbh/' => ['haridwar', 'haridwar-kumbh-mela.html'],
        '/(mussoorie|kempty|lal-tibba|happy-valley|gun-hill|christ-church|jwalaji|camel-back|mall-road|bhatta-falls|hathipoan|clouds-end)/' => ['mussoorie', 'mussoorie-tourism.html'],
        '/tehri/' => ['destinations', 'tehri-lake-adventures.html'],
        '/(teacher-training|meaning-of-yoga|history-of-yoga|benefits-of-yoga)/' => ['rishikesh', 'yoga-in-rishikesh.html'],
        '/(trek|trekking)/' => ['trekking', 'trekking.html'],
        '/haridwar-festivals|(festival|mela|-fair-|holi|jatra|sankranti|dance|dussehra|harela|phool-dei|bagwal|kandali|bissu|uttarayani|kanwar|nanda-devi|rajat-yatra)/' => ['festivals', 'uttarakhand-festival.html'],
        '/(tour-package|tour-packages|-package-|packages?\.html|-package\.html|yatra-package|chardham|auli|chopta|kanatal)/' => ['packages', 'tour-packages.html'],
        '/(walk-tour|rickshaw|village-tour|motor-biking|tribal|pottery|lecture|dinner|vasishtha)/' => ['special', 'special-tour.html'],
        '/ashram.*rishikesh/' => ['rishikesh', 'rishikesh-ashrams.html'],
        '/(temple|mandir|mahadev|devi|manzil).*rishikesh|rishikesh.*temple/' => ['rishikesh', 'rishikesh-temples.html'],
        '/(temple|mandir|mahadev|devi)/' => ['haridwar', 'haridwar-temples.html'],
        '/(rajaji|safari|rafting|camping|bungee|waterfall|neergarh|balloon|ballon|paragliding|ganga-camp)/' => ['adventure', 'adventure-tourism.html'],
        '/(rishikesh|ramjhula|lakshman|triveni|janki-setu|rishikund|geeta-bhawan|beatles|swarg|parmarth|neelkanth|kunjapuri)/' => ['rishikesh', 'rishikesh-attractions-and-sightseeing.html'],
        '/(haridwar|har-ki-pauri|kankhal|pitra|pind-daan|puja|pooja|rudraksha|ganga-|patanjali|street-food)/' => ['haridwar', 'haridwar-attractions.html'],
    ];

    private const DESTINATIONS = [
        'haridwar.html', 'rishikesh.html', 'mussoorie.html', 'haridwar-city.html', 'rishikesh-city.html', 'valley-of-flowers.html',
        'deoriatal.html', 'kunjapuri.html', 'nelong-valley-uttarakhand.html', 'tehri-lake-adventures.html', 'mussoorie-tourism.html',
    ];

    /**
     * @param  array<string, array{section: string, parent: ?string}>  $menuMembership  path => membership from the legacy main menu
     */
    public function __construct(private array $menuMembership = []) {}

    /**
     * @param  array<string, mixed>  $parsed
     * @return array{type: PageType, section: string, parent: ?string, schema_type: ?string}
     */
    public function classify(array $parsed): array
    {
        $path = $parsed['path'];

        [$section, $parent] = $this->placement($path);

        return [
            'type' => $type = $this->type($path, $section, $parsed),
            'section' => $section,
            'parent' => $parent === $path ? null : $parent,
            'schema_type' => $this->schemaType($path, $type),
        ];
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function placement(string $path): array
    {
        if (($hubSection = array_search($path, self::SECTION_HUBS, true)) !== false) {
            return [$hubSection, null];
        }

        if (isset($this->menuMembership[$path])) {
            return [$this->menuMembership[$path]['section'], $this->menuMembership[$path]['parent']];
        }

        foreach (self::RULES as $pattern => [$section, $parent]) {
            if (preg_match($pattern, $path)) {
                return [$section, $parent];
            }
        }

        return ['destinations', 'uttarakhand-destinations.html'];
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function type(string $path, string $section, array $parsed): PageType
    {
        $hubPaths = array_merge(array_values(self::SECTION_HUBS), ['haridwar-festivals.html', 'kumbh-mela-tour-packages.html', 'mussoorie-attraction.html', 'tehri-attraction.html']);

        return match (true) {
            $section === 'company' => PageType::Company,
            count($parsed['gallery'] ?? []) >= 3 => PageType::Gallery,
            in_array($path, $hubPaths, true) || count($parsed['cards'] ?? []) >= 3 => PageType::Hub,
            (bool) preg_match('/^(hotel-|hotels-)|(hotel|resort|lodge|palace)-[a-z-]*(haridwar|rishikesh)\.html$|resorts-rishikesh|hideaway/', $path) => PageType::Hotel,
            $this->isPackage($path, $parsed) => PageType::Package,
            $section === 'festivals' || (bool) preg_match('/(festival|mela)/', $path) => PageType::Festival,
            in_array($path, self::DESTINATIONS, true) => PageType::Destination,
            ! str_contains($path, 'temples') && (bool) preg_match('/(temple|mandir|mahadev|ghat|pauri|jhula|falls|waterfall|lake|kund|cave|national-park|setu|bhawan|ashram-rishikesh|tibba|hill|church|road|monastery|happy-valley|kankhal|yogpeeth|devi|manzil)/', $path) => PageType::Attraction,
            default => PageType::Guide,
        };
    }

    /**
     * A bookable trip: a tour-style URL plus either a day-wise itinerary or
     * an explicit package URL.
     *
     * @param  array<string, mixed>  $parsed
     */
    private function isPackage(string $path, array $parsed): bool
    {
        if ((bool) preg_match('/(tour-package|-package-|package\.html|packages\.html|bathing-tour|trek-tour)/', $path)) {
            return true;
        }

        $tourLike = (bool) preg_match('/(tour|trek|trekking|camp|safari|yatra|hiking|village|rafting)/', $path);

        return $tourLike && (count($parsed['itinerary'] ?? []) >= 2 || (bool) preg_match('/-tours?\.html$/', $path));
    }

    private function schemaType(string $path, PageType $type): ?string
    {
        if ($type === PageType::Attraction && ! str_contains($path, 'temples') && preg_match('/(temple|mandir|mahadev|devi|manzil)/', $path)) {
            return 'HinduTemple';
        }

        return match ($path) {
            'about-us.html', 'our-team.html' => 'AboutPage',
            'contact-us.html' => 'ContactPage',
            default => null,
        };
    }
}
