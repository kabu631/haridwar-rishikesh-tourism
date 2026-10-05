<?php

namespace App\Console\Commands;

use App\Models\Testimonial;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Imports the best (4 and 5 star) guest reviews from the Tripadvisor listings
 * as homepage testimonials. The Content API only returns the five most recent
 * reviews per listing, so the scheduled weekly run builds the collection up
 * over time. Re-imports refresh a review's text but never change whether it
 * is shown or its position, so choices made in the admin stick.
 */
#[Signature('tripadvisor:import-reviews')]
#[Description('Import 4 and 5 star Tripadvisor reviews as homepage testimonials')]
class ImportTripadvisorReviews extends Command
{
    private const API_URL = 'https://api.content.tripadvisor.com/api/v1';

    public function handle(): int
    {
        $key = config('services.tripadvisor.key');

        if (blank($key)) {
            $this->error('Set TRIPADVISOR_API_KEY in .env first (get a key at https://www.tripadvisor.com/developers).');

            return self::FAILURE;
        }

        $succeeded = true;

        foreach (config('services.tripadvisor.location_ids') as $locationId) {
            try {
                $reviews = $this->fetchReviews($locationId, $key);
            } catch (ConnectionException|RequestException $exception) {
                $this->error("Location {$locationId}: {$exception->getMessage()}");
                $succeeded = false;

                continue;
            }

            $counts = ['new' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];

            foreach ($reviews as $review) {
                $counts[$this->import($review)]++;
            }

            $this->info(sprintf(
                'Location %d: %d new, %d updated, %d unchanged, %d skipped (below %d stars or machine translated).',
                $locationId, $counts['new'], $counts['updated'], $counts['unchanged'], $counts['skipped'], Testimonial::MIN_TRIPADVISOR_RATING,
            ));
        }

        return $succeeded ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws ConnectionException|RequestException
     */
    private function fetchReviews(int $locationId, string $key): array
    {
        return Http::baseUrl(self::API_URL)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->retry([1000, 3000], 0, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException && ($exception->response->serverError() || $exception->response->tooManyRequests())))
            ->get("location/{$locationId}/reviews", ['key' => $key, 'language' => 'en'])
            ->json('data', []);
    }

    /**
     * @param  array<string, mixed>  $review
     * @return 'new'|'updated'|'unchanged'|'skipped'
     */
    private function import(array $review): string
    {
        if ((int) ($review['rating'] ?? 0) < Testimonial::MIN_TRIPADVISOR_RATING
            || ($review['is_machine_translated'] ?? false)
            || blank($review['id'] ?? null)
            || blank($review['text'] ?? null)) {
            return 'skipped';
        }

        $testimonial = Testimonial::query()->firstOrNew([
            'source' => Testimonial::SOURCE_TRIPADVISOR,
            'external_id' => (string) $review['id'],
        ]);

        $testimonial->fill([
            'name' => Str::squish($review['user']['username'] ?? '') ?: 'Tripadvisor traveller',
            'location' => Str::squish($review['user']['user_location']['name'] ?? '') ?: null,
            'title' => Str::squish($review['title'] ?? '') ?: null,
            'body' => Str::squish($review['text']),
            'rating' => (int) $review['rating'],
            'url' => $review['url'] ?? null,
            'reviewed_at' => filled($review['published_date'] ?? null) ? Carbon::parse($review['published_date'])->toDateString() : null,
        ]);

        if (! $testimonial->isDirty()) {
            return 'unchanged';
        }

        $status = $testimonial->exists ? 'updated' : 'new';
        $testimonial->save();

        return $status;
    }
}
