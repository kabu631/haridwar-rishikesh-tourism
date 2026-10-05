<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PageType;
use App\Mail\EnquiryReceived;
use App\Models\Enquiry;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EnquiryControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validEnquiry(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Asha Verma',
            'email' => 'asha@example.com',
            'phone' => '+91 98765 43210',
            'tour' => 'Haridwar Rishikesh Tour',
            'travel_date' => now()->addMonth()->toDateString(),
        ], $overrides);
    }

    public function test_quick_enquiry_is_stored_and_the_office_is_emailed(): void
    {
        Mail::fake();

        $response = $this->post('/enquiry', $this->validEnquiry());

        $response->assertRedirect(route('enquiry.thanks'));
        $this->assertDatabaseHas('enquiries', ['email' => 'asha@example.com', 'status' => 'new', 'type' => 'quick']);
        Mail::assertSent(EnquiryReceived::class, fn (EnquiryReceived $mail): bool => $mail->hasTo('mail@haridwarrishikeshtourism.com'));
    }

    public function test_notification_email_shows_the_traveller_details_and_replies_to_them(): void
    {
        $enquiry = Enquiry::factory()->create(['name' => 'Asha Verma', 'email' => 'asha@example.com', 'tour' => 'Char Dham Yatra', 'message' => 'Two senior citizens travelling.']);

        $mail = new EnquiryReceived($enquiry);

        $mail->assertHasReplyTo('asha@example.com');
        $mail->assertHasSubject('New enquiry: Char Dham Yatra – Asha Verma');
        $mail->assertSeeInHtml('Two senior citizens travelling.');
    }

    public function test_enquiry_from_a_cached_page_does_not_need_a_csrf_token(): void
    {
        Mail::fake();
        $page = Page::factory()->package()->create();

        $response = $this->postJson('/enquiry', $this->validEnquiry(['type' => 'package', 'page_id' => $page->id]));

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertDatabaseHas('enquiries', ['type' => 'package', 'page_id' => $page->id]);
    }

    public function test_honeypot_submissions_look_successful_but_are_discarded(): void
    {
        Mail::fake();

        $this->post('/enquiry', $this->validEnquiry(['website' => 'http://spam.example']))->assertRedirect(route('enquiry.thanks'));

        $this->assertDatabaseCount('enquiries', 0);
        Mail::assertNothingSent();
    }

    public function test_messages_full_of_links_are_stored_as_spam_without_email(): void
    {
        Mail::fake();

        $this->post('/enquiry', $this->validEnquiry(['message' => 'http://a.example http://b.example http://c.example']));

        $this->assertDatabaseHas('enquiries', ['status' => 'spam']);
        Mail::assertNothingSent();
    }

    public function test_invalid_ajax_enquiry_returns_422_with_field_errors(): void
    {
        $response = $this->postJson('/enquiry', $this->validEnquiry(['email' => 'not-an-email', 'phone' => 'call me']));

        $response->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone']);
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_invalid_form_post_shows_errors_on_the_booking_page(): void
    {
        $response = $this->post('/enquiry', $this->validEnquiry(['name' => '']));

        $response->assertRedirect(route('booking.create', ['tour' => 'Haridwar Rishikesh Tour']));
        $response->assertSessionHasErrors('name');
    }

    public function test_booking_form_is_served_at_the_legacy_book_now_php_url(): void
    {
        $this->get('/book-now.php?tour=Char+Dham')->assertSee('value="Char Dham"', false);
    }

    public function test_booking_form_submission_is_stored_as_a_booking(): void
    {
        Mail::fake();

        $this->post('/book-now.php', $this->validEnquiry(['adults' => 2, 'children' => 1]))->assertRedirect(route('enquiry.thanks'));

        $this->assertSame('booking', Enquiry::query()->sole()->type);
    }

    public function test_enquiries_are_rate_limited(): void
    {
        Mail::fake();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/enquiry', $this->validEnquiry());
        }

        $this->postJson('/enquiry', $this->validEnquiry())->assertTooManyRequests();
    }

    public function test_an_enquiry_sent_from_a_tour_page_records_that_tour_whatever_the_form_says(): void
    {
        Mail::fake();
        $page = Page::factory()->package()->create(['path' => 'haridwar-rishikesh-with-golden-triangle-tour.html', 'title' => 'Haridwar Rishikesh with Golden Triangle Tour']);

        $this->postJson('/enquiry', $this->validEnquiry(['type' => 'package', 'page_id' => $page->id, 'tour' => 'Something else']))->assertOk();

        $this->assertDatabaseHas('enquiries', ['page_id' => $page->id, 'tour' => 'Haridwar Rishikesh with Golden Triangle Tour']);
    }

    public function test_notification_email_includes_the_tour_package_details(): void
    {
        $page = Page::factory()->package()->create(['path' => 'haridwar-rishikesh-tour.html']);
        $enquiry = Enquiry::factory()->create(['page_id' => $page->id, 'tour' => $page->title]);

        $mail = new EnquiryReceived($enquiry);

        $mail->assertSeeInHtml($page->absoluteUrl());
        $mail->assertSeeInHtml('2 nights / 3 days');
        $mail->assertSeeInHtml('Haridwar, Rishikesh');
        $mail->assertSeeInHtml('Delhi');
    }

    public function test_book_this_tour_now_links_carry_the_tour_and_the_booking_form_locks_it(): void
    {
        Page::factory()->create(['path' => 'book-now.php', 'title' => 'Online Booking Form']);
        $tour = Page::factory()->package()->create([
            'path' => 'haridwar-rishikesh-with-golden-triangle-tour.html',
            'title' => 'Haridwar Rishikesh with Golden Triangle Tour',
            'body' => '<p>Day by day plan.</p><p><a href="/book-now.php">Book this tour now</a></p>',
        ]);

        $this->get('/haridwar-rishikesh-with-golden-triangle-tour.html')
            ->assertSee('href="/book-now.php?tour=haridwar-rishikesh-with-golden-triangle-tour.html"', false);

        $this->get('/book-now.php?tour=haridwar-rishikesh-with-golden-triangle-tour.html')
            ->assertOk()
            ->assertSee('<input type="hidden" name="page_id" value="'.$tour->id.'">', false)
            ->assertSee('value="Haridwar Rishikesh with Golden Triangle Tour" readonly', false);
    }

    public function test_only_tour_package_pages_can_be_booked_as_a_fixed_tour(): void
    {
        Page::factory()->create(['path' => 'book-now.php', 'title' => 'Online Booking Form']);
        Page::factory()->create(['path' => 'contact-us.html', 'type' => PageType::Company, 'title' => 'Contact Us', 'body' => '<p>Write to us.</p><h2>Enquiry form</h2>']);

        $this->get('/contact-us.html')->assertDontSee('book-now.php?tour=', false);
        $this->get('/book-now.php?tour=contact-us.html')->assertOk()->assertDontSee('readonly', false)->assertDontSee('value="Contact Us"', false)->assertDontSee('value="contact-us.html"', false);
    }

    public function test_a_general_booking_keeps_the_tour_the_visitor_typed(): void
    {
        Mail::fake();
        $bookingPage = Page::factory()->create(['path' => 'book-now.php', 'title' => 'Online Booking Form']);

        $this->post('/book-now.php', $this->validEnquiry(['type' => 'booking', 'page_id' => $bookingPage->id, 'tour' => 'Char Dham Yatra']));

        $this->assertDatabaseHas('enquiries', ['type' => 'booking', 'tour' => 'Char Dham Yatra']);
        $this->get('/book-now.php')->assertSee('name="page_id" value="'.$bookingPage->id.'"', false);
    }
}
