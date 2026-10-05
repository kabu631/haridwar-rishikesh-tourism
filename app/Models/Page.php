<?php

namespace App\Models;

use App\Enums\PageType;
use App\Observers\PageObserver;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'path', 'type', 'section', 'parent_id', 'title', 'nav_label', 'meta_title', 'meta_description', 'meta_keywords',
    'excerpt', 'body', 'hero_image', 'hero_alt', 'cards', 'gallery', 'itinerary', 'faqs', 'facts', 'sources', 'extra',
    'robots', 'canonical_url', 'schema_type', 'author_id', 'reviewer_id', 'is_published', 'is_featured', 'sort_order',
    'published_at', 'reviewed_at',
])]
#[ObservedBy(PageObserver::class)]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PageType::class,
            'cards' => 'array',
            'gallery' => 'array',
            'itinerary' => 'array',
            'faqs' => 'array',
            'facts' => 'array',
            'sources' => 'array',
            'extra' => 'array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id');
    }

    /**
     * @return BelongsTo<Author, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * @return BelongsTo<Author, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'reviewer_id');
    }

    /**
     * @param  Builder<Page>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @param  Builder<Page>  $query
     */
    #[Scope]
    protected function indexable(Builder $query): void
    {
        $query->where('is_published', true)
            ->where('robots', 'not like', '%noindex%')
            ->where(fn (Builder $query) => $query->whereNull('canonical_url')->orWhere('canonical_url', ''));
    }

    public function isHome(): bool
    {
        return $this->path === '';
    }

    /**
     * Root relative URL of the page, e.g. "/har-ki-pauri.html".
     */
    public function url(): string
    {
        return '/'.$this->path;
    }

    public function absoluteUrl(): string
    {
        return rtrim(config('seo.site_url'), '/').$this->url();
    }

    /**
     * The canonical URL: an explicit override (kept from the legacy site)
     * or the page's own absolute URL.
     */
    public function canonical(): string
    {
        if (filled($this->canonical_url)) {
            return $this->canonical_url;
        }

        return $this->absoluteUrl();
    }

    public function isSelfCanonical(): bool
    {
        return blank($this->canonical_url) || rtrim($this->canonical_url, '/') === rtrim($this->absoluteUrl(), '/');
    }

    public function seoTitle(): string
    {
        return filled($this->meta_title) ? $this->meta_title : $this->title;
    }

    public function label(): string
    {
        return filled($this->nav_label) ? $this->nav_label : $this->title;
    }

    /**
     * The label without the keyword tail of legacy titles, e.g. "Rishikesh
     * Rafting and Camping - Rishikesh Camping Packages" → "Rishikesh Rafting and Camping".
     */
    public function shortTitle(): string
    {
        return trim(preg_split('/\s+-\s*|\s*-\s+/', $this->label(), 2)[0]);
    }

    public function teaser(int $limit = 160): string
    {
        $text = filled($this->excerpt) ? $this->excerpt : ($this->meta_description ?? '');

        return str($text)->squish()->limit($limit)->toString();
    }

    public function isIndexable(): bool
    {
        return $this->is_published && ! str_contains(strtolower($this->robots), 'noindex');
    }

    /**
     * Duration in nights/days parsed from the title, e.g. "(02 Night 03 Days)".
     *
     * @return array{nights: int, days: int}|null
     */
    /**
     * The trip facts shown on a tour page, for enquiry emails and the admin inbox.
     *
     * @return array<string, string>
     */
    public function tripFacts(): array
    {
        $duration = $this->duration();
        $places = (array) ($this->facts['destinations'] ?? []);

        return array_filter([
            'Duration' => $duration ? "{$duration['nights']} nights / {$duration['days']} days" : null,
            'Starts from' => $this->facts['start_city'] ?? null,
            'Places covered' => $places !== [] ? implode(', ', $places) : null,
        ]);
    }

    public function duration(): ?array
    {
        if (filled($this->facts['duration_days'] ?? null)) {
            return ['nights' => (int) ($this->facts['duration_nights'] ?? max(0, $this->facts['duration_days'] - 1)), 'days' => (int) $this->facts['duration_days']];
        }

        if (preg_match('/(\d{1,2})\s*N(?:ights?)?\s*[\/&,\-\s]*(\d{1,2})\s*D(?:ays?)?/i', $this->title.' '.$this->path, $match)) {
            return ['nights' => (int) $match[1], 'days' => (int) $match[2]];
        }

        $days = count($this->itinerary ?? []);

        return $days > 1 ? ['nights' => $days - 1, 'days' => $days] : null;
    }

    /**
     * Plain text indexed by the site search (FULLTEXT).
     */
    public function buildSearchText(): string
    {
        $parts = [
            $this->title,
            $this->meta_title,
            $this->meta_description,
            $this->excerpt,
            strip_tags(str_replace(['<', '>'], [' <', '> '], (string) $this->body)),
            collect($this->faqs ?? [])->map(fn (array $faq): string => ($faq['question'] ?? '').' '.($faq['answer'] ?? ''))->implode(' '),
            collect($this->cards ?? [])->map(fn (array $card): string => ($card['title'] ?? '').' '.($card['text'] ?? ''))->implode(' '),
        ];

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(implode(' ', array_filter($parts)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /**
     * Estimated reading time in minutes.
     */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 200));
    }
}
