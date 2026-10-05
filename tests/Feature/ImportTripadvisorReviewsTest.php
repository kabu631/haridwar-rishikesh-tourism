<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportTripadvisorReviewsTest extends TestCase
{
    use RefreshDatabase;

    private const HARIDWAR_REVIEWS = 'api.content.tripadvisor.com/api/v1/location/4868270/reviews*';

    private const RISHIKESH_REVIEWS = 'api.content.tripadvisor.com/api/v1/location/5982072/reviews*';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.tripadvisor.key' => 'test-key',
            'services.tripadvisor.location_ids' => [4868270, 5982072],
        ]);
    }

    public function test_imports_four_and_five_star_reviews_as_tripadvisor_testimonials(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::HARIDWAR_REVIEWS => Http::response(['data' => [
                $this->review(['id' => 1001, 'rating' => 5, 'title' => 'Wonderful  Haridwar trip', 'text' => "Sunil ji planned\n\nevery detail."]),
                $this->review(['id' => 1002, 'rating' => 4, 'user' => ['username' => 'Meera K', 'user_location' => null]]),
            ]]),
            self::RISHIKESH_REVIEWS => Http::response(['data' => []]),
        ]);

        $this->artisan('tripadvisor:import-reviews')->assertSuccessful();

        $fiveStar = Testimonial::query()->where('external_id', '1001')->firstOrFail();
        $this->assertSame(Testimonial::SOURCE_TRIPADVISOR, $fiveStar->source);
        $this->assertSame(5, $fiveStar->rating);
        $this->assertSame('Raghu A', $fiveStar->name);
        $this->assertSame('Mumbai, India', $fiveStar->location);
        $this->assertSame('Wonderful Haridwar trip', $fiveStar->title);
        $this->assertSame('Sunil ji planned every detail.', $fiveStar->body);
        $this->assertSame('https://www.tripadvisor.in/ShowUserReviews-g616028-d4868270-r1001.html', $fiveStar->url);
        $this->assertSame('2026-09-14', $fiveStar->reviewed_at->toDateString());
        $this->assertTrue($fiveStar->refresh()->is_published);

        $fourStar = Testimonial::query()->where('external_id', '1002')->firstOrFail();
        $this->assertSame(4, $fourStar->rating);
        $this->assertSame('Meera K', $fourStar->name);
        $this->assertNull($fourStar->location);

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://api.content.tripadvisor.com/api/v1/location/4868270/reviews')
            && $request['key'] === 'test-key'
            && $request['language'] === 'en');
    }

    public function test_skips_reviews_below_four_stars_and_machine_translated_reviews(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::HARIDWAR_REVIEWS => Http::response(['data' => [
                $this->review(['id' => 2001, 'rating' => 3]),
                $this->review(['id' => 2002, 'rating' => 1]),
                $this->review(['id' => 2003, 'rating' => 5, 'is_machine_translated' => true]),
            ]]),
            self::RISHIKESH_REVIEWS => Http::response(['data' => []]),
        ]);

        $this->artisan('tripadvisor:import-reviews')->assertSuccessful();

        $this->assertDatabaseCount('testimonials', 0);
    }

    public function test_reimport_refreshes_review_text_but_keeps_admin_publishing_choices(): void
    {
        $testimonial = Testimonial::factory()->tripadvisor()->create([
            'external_id' => '3001',
            'body' => 'Old text.',
            'is_published' => false,
            'sort_order' => 7,
        ]);

        Http::preventStrayRequests();
        Http::fake([
            self::HARIDWAR_REVIEWS => Http::response(['data' => [$this->review(['id' => 3001, 'text' => 'Edited text.'])]]),
            self::RISHIKESH_REVIEWS => Http::response(['data' => []]),
        ]);

        $this->artisan('tripadvisor:import-reviews')->assertSuccessful();

        $testimonial->refresh();
        $this->assertDatabaseCount('testimonials', 1);
        $this->assertSame('Edited text.', $testimonial->body);
        $this->assertFalse($testimonial->is_published);
        $this->assertSame(7, $testimonial->sort_order);
    }

    public function test_fails_without_calling_tripadvisor_when_no_api_key_is_configured(): void
    {
        config(['services.tripadvisor.key' => null]);
        Http::preventStrayRequests();
        Http::fake();

        $this->artisan('tripadvisor:import-reviews')
            ->expectsOutputToContain('TRIPADVISOR_API_KEY')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_still_imports_other_listings_but_fails_when_a_listing_request_is_rejected(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::HARIDWAR_REVIEWS => Http::response(['error' => ['message' => 'Unauthorized']], 401),
            self::RISHIKESH_REVIEWS => Http::response(['data' => [$this->review(['id' => 4001])]]),
        ]);

        $this->artisan('tripadvisor:import-reviews')
            ->expectsOutputToContain('Location 4868270')
            ->assertFailed();

        $this->assertDatabaseHas('testimonials', ['source' => Testimonial::SOURCE_TRIPADVISOR, 'external_id' => '4001']);
    }

    /**
     * A review object as returned by the Tripadvisor Content API.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function review(array $overrides = []): array
    {
        $id = $overrides['id'] ?? 1001;

        return array_merge([
            'id' => $id,
            'lang' => 'en',
            'location_id' => 4868270,
            'published_date' => '2026-09-14T08:15:00Z',
            'rating' => 5,
            'url' => "https://www.tripadvisor.in/ShowUserReviews-g616028-d4868270-r{$id}.html",
            'text' => 'Excellent driver and a well planned Haridwar Rishikesh tour.',
            'title' => 'Excellent tour',
            'is_machine_translated' => false,
            'user' => ['username' => 'Raghu A', 'user_location' => ['id' => '304554', 'name' => 'Mumbai, India']],
        ], $overrides);
    }
}
