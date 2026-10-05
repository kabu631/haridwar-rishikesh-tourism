<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Legacy headings that lost their content in the import (rows of cards, a
 * Book Now image, sentences written as headings) must never render empty.
 */
class PageSectionBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_cards_render_under_the_heading_they_sat_under_on_the_legacy_page(): void
    {
        Page::factory()->create([
            'path' => 'tour-packages.html',
            'body' => '<p>Intro text.</p><h2>Haridwar Rishikesh Tour Packages</h2><h2>Nainital Corbett Holiday Packages</h2><h2>Holiday Packages</h2>',
            'cards' => [
                ['title' => 'Haridwar Tour Package', 'url' => '/haridwar-tour-package.html', 'group' => 'Haridwar Rishikesh Tour Packages'],
                ['title' => 'Corbett Tour', 'url' => '/corbett-tour.html', 'group' => 'Nainital Corbett Holiday Packages'],
                ['title' => 'Card Without Heading', 'url' => '/other.html'],
            ],
        ]);

        $this->get('/tour-packages.html')->assertOk()->assertSeeInOrder([
            'Haridwar Rishikesh Tour Packages', 'Haridwar Tour Package',
            'Nainital Corbett Holiday Packages', 'Corbett Tour',
            'Holiday Packages', 'Card Without Heading',
        ]);
    }

    public function test_a_book_your_heading_left_empty_by_the_import_gets_booking_buttons(): void
    {
        Page::factory()->create(['path' => 'bharat-mandir-rishikesh.html', 'body' => '<p>About the temple.</p><h3>Book Your Bharat Mandir Rishikesh</h3>']);

        $this->get('/bharat-mandir-rishikesh.html')->assertSeeInOrder(['Book Your Bharat Mandir Rishikesh', 'Send an enquiry', 'href="/book-now.php"'], false);
    }

    public function test_sentences_written_as_empty_headings_are_styled_as_notes(): void
    {
        Page::factory()->create([
            'path' => 'kartik-swami-temple-trek.html',
            'body' => '<h3>Should we book in advance?</h3><h5>Yes you should book earlier because many companies give a full refund if you cancel 24 hours before.</h5><h3>Is there a hotel near the temple?</h3><p>Stay in Rudraprayag.</p>',
        ]);

        $this->get('/kartik-swami-temple-trek.html')->assertSee('class="heading-note"', false);
    }
}
