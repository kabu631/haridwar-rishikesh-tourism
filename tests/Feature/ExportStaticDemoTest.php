<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ExportStaticDemoTest extends TestCase
{
    use RefreshDatabase;

    private const OUTPUT = 'storage/framework/testing/static-demo';

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path(self::OUTPUT));

        parent::tearDown();
    }

    public function test_preview_pages_are_noindex_keep_the_live_canonical_and_link_under_the_sub_path(): void
    {
        Page::factory()->home()->create();
        Page::factory()->create(['path' => 'har-ki-pauri.html', 'title' => 'Har Ki Pauri Haridwar']);

        $this->artisan('demo:export', ['--path' => self::OUTPUT, '--base' => '/preview'])->assertSuccessful();

        $html = File::get(base_path(self::OUTPUT.'/har-ki-pauri.html'));

        $this->assertStringContainsString('<meta name="robots" content="noindex,nofollow">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://www.haridwarrishikeshtourism.com/har-ki-pauri.html">', $html);
        $this->assertStringContainsString('href="/preview/build/assets/', $html);
        $this->assertDoesNotMatchRegularExpression('/\b(?:href|src|action)="\/(?!preview\/|\/)/', $html);

        $this->assertFileExists(base_path(self::OUTPUT.'/index.html'));
        $this->assertFileExists(base_path(self::OUTPUT.'/404.html'));
        $this->assertFileExists(base_path(self::OUTPUT.'/.nojekyll'));
        $this->assertFileExists(base_path(self::OUTPUT.'/demo/demo.js'));
        $this->assertStringContainsString('Har Ki Pauri Haridwar', File::get(base_path(self::OUTPUT.'/demo/search-index.json')));
    }
}
