<?php

namespace App\Models;

use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'location', 'title', 'body', 'rating', 'source', 'external_id', 'url', 'reviewed_at', 'is_published', 'sort_order'])]
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    public const SOURCE_TRIPADVISOR = 'Tripadvisor';

    /**
     * Lowest Tripadvisor rating (out of 5) imported and shown as a testimonial.
     */
    public const MIN_TRIPADVISOR_RATING = 4;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_published' => 'boolean',
            'reviewed_at' => 'date',
        ];
    }

    /**
     * @param  Builder<Testimonial>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('sort_order');
    }

    /**
     * Tripadvisor reviews rated 4 or 5, highest rated and most recent first.
     *
     * @param  Builder<Testimonial>  $query
     */
    #[Scope]
    protected function topRatedOnTripadvisor(Builder $query): void
    {
        $query->where('source', self::SOURCE_TRIPADVISOR)
            ->where('rating', '>=', self::MIN_TRIPADVISOR_RATING)
            ->orderByDesc('rating')
            ->orderByDesc('reviewed_at');
    }
}
