<?php

namespace Tests\Feature\View\Composers;

use App\Models\Page;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * Suggestions use FULLTEXT search, which only sees committed rows.
 */
class NotFoundComposerTest extends TestCase
{
    use DatabaseTruncation;

    public function test_unknown_url_returns_404_suggesting_pages_that_match_its_words(): void
    {
        Page::factory()->create(['path' => 'har-ki-pauri.html', 'title' => 'Har Ki Pauri Haridwar']);

        $response = $this->get('/har-ki-pauri-ghat.html');

        $response->assertNotFound();
        $response->assertSee('Har Ki Pauri Haridwar');
        $response->assertSee('<meta name="robots" content="noindex,follow">', false);
    }
}
