<?php

namespace App\Support\Seo;

use App\Enums\PageType;
use App\Models\Author;
use App\Models\Page;
use App\Support\Media\MediaLibrary;
use App\Support\SiteSettings;
use Illuminate\Support\Str;

/**
 * Builds schema.org JSON-LD graphs. Every node uses stable @id references so
 * search engines and AI assistants can connect pages, places, packages and
 * the business entity (India Easy Trip Pvt Ltd).
 */
class SchemaBuilder
{
    public function __construct(private SiteSettings $settings, private MediaLibrary $media) {}

    public function siteUrl(string $path = '/'): string
    {
        return rtrim(config('seo.site_url'), '/').'/'.ltrim($path, '/');
    }

    public function organizationId(): string
    {
        return $this->siteUrl('/').'#organization';
    }

    public function websiteId(): string
    {
        return $this->siteUrl('/').'#website';
    }

    /**
     * @return array<string, mixed>
     */
    public function organization(): array
    {
        $address = (array) $this->settings->get('address');
        $geo = (array) $this->settings->get('geo');
        $social = array_values(array_filter((array) $this->settings->get('social')));

        return array_filter([
            '@type' => ['TravelAgency', 'LocalBusiness'],
            '@id' => $this->organizationId(),
            'name' => $this->settings->get('name'),
            'legalName' => $this->settings->get('legal_name'),
            'alternateName' => ['India Easy Trip', 'HaridwarRishikeshTourism.com'],
            'url' => $this->siteUrl('/'),
            'logo' => [
                '@type' => 'ImageObject',
                '@id' => $this->siteUrl('/').'#logo',
                'url' => $this->siteUrl('/images/india-easy-trip-logo.png'),
                'width' => 2362,
                'height' => 655,
                'caption' => $this->settings->get('legal_name'),
            ],
            'image' => $this->siteUrl(config('seo.default_image')),
            'description' => $this->settings->get('tagline'),
            'slogan' => 'Atithi Devo Bhavah – The Fabulous Experience',
            'foundingDate' => config('site.founded'),
            'telephone' => $this->settings->get('phone'),
            'email' => $this->settings->get('email'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address['street'] ?? null,
                'addressLocality' => $address['locality'] ?? null,
                'addressRegion' => $address['region'] ?? null,
                'postalCode' => $address['postal_code'] ?? null,
                'addressCountry' => $address['country'] ?? 'IN',
            ],
            'geo' => filled($geo['latitude'] ?? null) ? [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $geo['latitude'],
                'longitude' => (float) $geo['longitude'],
            ] : null,
            'hasMap' => filled($geo['latitude'] ?? null) ? 'https://www.google.com/maps?q='.$geo['latitude'].','.$geo['longitude'] : null,
            'openingHours' => $this->settings->get('opening_hours'),
            'areaServed' => [
                ['@type' => 'City', 'name' => 'Haridwar'],
                ['@type' => 'City', 'name' => 'Rishikesh'],
                ['@type' => 'City', 'name' => 'Mussoorie'],
                ['@type' => 'State', 'name' => 'Uttarakhand'],
            ],
            'knowsAbout' => ['Haridwar tourism', 'Rishikesh tourism', 'Char Dham Yatra', 'Kumbh Mela', 'Ganga Aarti', 'Himalayan trekking', 'River rafting in Rishikesh', 'Yoga and meditation in Rishikesh', 'Uttarakhand festivals'],
            'knowsLanguage' => ['en', 'hi'],
            'identifier' => array_map(fn (array $credential): array => [
                '@type' => 'PropertyValue',
                'name' => $credential['name'],
                'value' => $credential['value'],
            ], array_values(array_filter((array) $this->settings->get('credentials'), fn ($credential): bool => filled($credential['value'] ?? null)))),
            'memberOf' => [
                ['@type' => 'Organization', 'name' => 'Indian Association of Tour Operators (IATO)'],
                ['@type' => 'Organization', 'name' => 'Adventure Tour Operators Association of India (ATOAI)'],
            ],
            'contactPoint' => [[
                '@type' => 'ContactPoint',
                'telephone' => $this->settings->get('phone'),
                'contactType' => 'reservations',
                'email' => $this->settings->get('email'),
                'areaServed' => 'IN',
                'availableLanguage' => ['English', 'Hindi'],
            ]],
            'sameAs' => $social,
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    public function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => $this->websiteId(),
            'url' => $this->siteUrl('/'),
            'name' => $this->settings->get('name'),
            'alternateName' => 'HaridwarRishikeshTourism.com',
            'inLanguage' => 'en-IN',
            'publisher' => ['@id' => $this->organizationId()],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $this->siteUrl('/search').'?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  list<array{name: string, url: string}>  $crumbs
     * @return array<string, mixed>
     */
    public function breadcrumbs(string $pageUrl, array $crumbs): array
    {
        return [
            '@type' => 'BreadcrumbList',
            '@id' => $pageUrl.'#breadcrumb',
            'itemListElement' => array_map(fn (array $crumb, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ], $crumbs, array_keys($crumbs)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function person(Author $author): array
    {
        return array_filter([
            '@type' => 'Person',
            '@id' => $this->siteUrl('/our-team.html').'#'.$author->slug,
            'name' => $author->name,
            'jobTitle' => $author->job_title,
            'description' => $author->bio,
            'url' => $this->siteUrl('/our-team.html'),
            'worksFor' => ['@id' => $this->organizationId()],
            'sameAs' => $author->same_as ?: null,
        ]);
    }

    /**
     * Full graph for a content page.
     *
     * @param  list<array{name: string, url: string}>  $crumbs
     * @return list<array<string, mixed>>
     */
    public function forPage(Page $page, array $crumbs, ?string $image): array
    {
        $url = $page->absoluteUrl();
        $graph = [$this->organization(), $this->website()];

        $pageType = match (true) {
            $page->isHome() => 'WebPage',
            filled($page->schema_type) && in_array($page->schema_type, ['AboutPage', 'ContactPage'], true) => $page->schema_type,
            $page->type === PageType::Hub => 'CollectionPage',
            $page->type === PageType::Gallery => 'ImageGallery',
            default => 'WebPage',
        };

        $webPage = array_filter([
            '@type' => $pageType,
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $page->seoTitle(),
            'description' => $page->meta_description,
            'isPartOf' => ['@id' => $this->websiteId()],
            'about' => $page->isHome() ? ['@id' => $this->organizationId()] : null,
            'inLanguage' => 'en-IN',
            'breadcrumb' => $crumbs !== [] ? ['@id' => $url.'#breadcrumb'] : null,
            'primaryImageOfPage' => $image ? ['@id' => $url.'#primaryimage'] : null,
            'datePublished' => $page->published_at?->toIso8601String(),
            'dateModified' => $page->updated_at?->toIso8601String(),
            'lastReviewed' => $page->reviewed_at?->toDateString(),
            'reviewedBy' => $page->reviewer ? ['@id' => $this->siteUrl('/our-team.html').'#'.$page->reviewer->slug] : null,
        ]);

        if ($image) {
            $graph[] = [
                '@type' => 'ImageObject',
                '@id' => $url.'#primaryimage',
                'url' => $image,
                'contentUrl' => $image,
                'caption' => $page->hero_alt ?: $page->title,
            ];
        }

        if ($crumbs !== []) {
            $graph[] = $this->breadcrumbs($url, $crumbs);
        }

        $entity = $this->mainEntity($page, $url, $image);

        if ($entity !== null) {
            $webPage['mainEntity'] = ['@id' => $entity['@id']];
            $graph[] = $entity;
        }

        $graph[] = $webPage;

        if (! $page->isHome() && ! in_array($page->type, [PageType::Hub, PageType::Gallery, PageType::Company], true)) {
            $graph[] = $this->article($page, $url, $image);
        }

        if ($page->author) {
            $graph[] = $this->person($page->author);
        }

        if ($page->reviewer && $page->reviewer->isNot($page->author)) {
            $graph[] = $this->person($page->reviewer);
        }

        if (filled($page->faqs)) {
            $graph[] = $this->faq($page, $url);
        }

        if ($page->type === PageType::Hub && filled($page->cards)) {
            $graph[] = $this->itemList($url, $page->cards);
        }

        return array_values(array_filter($graph));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mainEntity(Page $page, string $url, ?string $image): ?array
    {
        $locality = $this->locality($page);

        return match (true) {
            $page->type === PageType::Package => $this->touristTrip($page, $url, $image),
            $page->schema_type === 'HinduTemple', $page->type === PageType::Attraction => array_filter([
                '@type' => $page->schema_type === 'HinduTemple' ? ['HinduTemple', 'TouristAttraction'] : 'TouristAttraction',
                '@id' => $url.'#place',
                'name' => $this->placeName($page),
                'description' => $page->teaser(300),
                'url' => $url,
                'image' => $image,
                'address' => $locality ? ['@type' => 'PostalAddress', 'addressLocality' => $locality, 'addressRegion' => 'Uttarakhand', 'addressCountry' => 'IN'] : null,
                'containedInPlace' => $locality ? ['@type' => 'City', 'name' => $locality] : null,
                'touristType' => ['Pilgrims', 'Sightseeing'],
            ]),
            $page->type === PageType::Destination => array_filter([
                '@type' => 'TouristDestination',
                '@id' => $url.'#place',
                'name' => $this->placeName($page),
                'description' => $page->teaser(300),
                'url' => $url,
                'image' => $image,
                'containedInPlace' => ['@type' => 'State', 'name' => 'Uttarakhand'],
            ]),
            $page->type === PageType::Hotel => array_filter([
                '@type' => 'Hotel',
                '@id' => $url.'#hotel',
                'name' => $this->placeName($page),
                'description' => $page->teaser(300),
                'url' => $url,
                'image' => $image,
                'address' => $locality ? ['@type' => 'PostalAddress', 'addressLocality' => $locality, 'addressRegion' => 'Uttarakhand', 'addressCountry' => 'IN'] : null,
            ]),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function touristTrip(Page $page, string $url, ?string $image): array
    {
        $itinerary = collect($page->itinerary ?? [])->values();
        $duration = $page->duration();

        return array_filter([
            '@type' => 'TouristTrip',
            '@id' => $url.'#trip',
            'name' => $page->title,
            'description' => $page->teaser(300),
            'url' => $url,
            'image' => $image,
            'touristType' => $this->touristTypes($page),
            'provider' => ['@id' => $this->organizationId()],
            'subjectOf' => ['@id' => $url.'#webpage'],
            'duration' => $duration ? 'P'.$duration['days'].'D' : null,
            'itinerary' => $itinerary->isEmpty() ? null : [
                '@type' => 'ItemList',
                'numberOfItems' => $itinerary->count(),
                'itemListElement' => $itinerary->map(fn (array $day, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'item' => array_filter([
                        '@type' => 'TouristAttraction',
                        'name' => 'Day '.($day['day'] ?? $index + 1).': '.($day['title'] ?? ''),
                        'description' => $day['description'] ?? null,
                    ], filled(...)),
                ])->all(),
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $this->siteUrl('/book-now.php'),
                'availability' => 'https://schema.org/InStock',
                'seller' => ['@id' => $this->organizationId()],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function article(Page $page, string $url, ?string $image): array
    {
        return array_filter([
            '@type' => $page->type === PageType::Blog ? 'BlogPosting' : 'Article',
            '@id' => $url.'#article',
            'headline' => Str::limit($page->title, 110, ''),
            'description' => $page->meta_description,
            'image' => $image,
            'url' => $url,
            'mainEntityOfPage' => ['@id' => $url.'#webpage'],
            'isPartOf' => ['@id' => $url.'#webpage'],
            'author' => $page->author ? ['@id' => $this->siteUrl('/our-team.html').'#'.$page->author->slug] : ['@id' => $this->organizationId()],
            'publisher' => ['@id' => $this->organizationId()],
            'datePublished' => $page->published_at?->toIso8601String(),
            'dateModified' => $page->updated_at?->toIso8601String(),
            'inLanguage' => 'en-IN',
            'articleSection' => $page->section ? Str::headline($page->section) : null,
            'wordCount' => str_word_count(strip_tags((string) $page->body)),
            'citation' => collect($page->sources ?? [])->pluck('url')->filter()->values()->all() ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function faq(Page $page, string $url): array
    {
        return [
            '@type' => 'FAQPage',
            '@id' => $url.'#faq',
            'isPartOf' => ['@id' => $url.'#webpage'],
            'mainEntity' => collect($page->faqs)->map(fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
            ])->values()->all(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     * @return array<string, mixed>
     */
    private function itemList(string $url, array $cards): array
    {
        return [
            '@type' => 'ItemList',
            '@id' => $url.'#itemlist',
            'itemListElement' => collect($cards)
                ->filter(fn (array $card): bool => filled($card['url'] ?? null))
                ->values()
                ->map(fn (array $card, int $index): array => array_filter([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $card['title'] ?? null,
                    'url' => $this->siteUrl($card['url']),
                ], filled(...)))->all(),
        ];
    }

    private function placeName(Page $page): string
    {
        return trim(Str::before(Str::before($page->title, ' - '), ' | '));
    }

    private function locality(Page $page): ?string
    {
        return match ($page->section) {
            'haridwar' => 'Haridwar',
            'rishikesh', 'adventure' => 'Rishikesh',
            'mussoorie' => 'Mussoorie',
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private function touristTypes(Page $page): array
    {
        $haystack = strtolower($page->path.' '.$page->title);

        return array_values(array_filter([
            preg_match('/(pilgrim|chardham|kumbh|temple|yatra|puja|ganga)/', $haystack) ? 'Pilgrims' : null,
            preg_match('/(trek|rafting|camp|adventure|bungee|safari|biking)/', $haystack) ? 'Adventure travellers' : null,
            preg_match('/(yoga|meditation|ayurveda)/', $haystack) ? 'Yoga and wellness travellers' : null,
            preg_match('/(village|tribal|culture|walk|dinner|pottery)/', $haystack) ? 'Cultural travellers' : null,
            'Families',
        ]));
    }
}
