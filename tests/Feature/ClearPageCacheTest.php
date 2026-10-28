<?php

namespace Tests\Feature;

use App\Support\PageCache;
use Illuminate\Support\ServiceProvider;
use Tests\TestCase;

class ClearPageCacheTest extends TestCase
{
    public function test_the_command_retires_every_cached_page(): void
    {
        $key = PageCache::key('/our-team.html');

        $this->artisan('page-cache:clear')->assertSuccessful();

        $this->assertNotSame($key, PageCache::key('/our-team.html'));
    }

    public function test_deploys_clear_the_page_cache_when_optimizing(): void
    {
        $this->assertSame('page-cache:clear', ServiceProvider::$optimizeCommands['page-cache'] ?? null);
        $this->assertSame('page-cache:clear', ServiceProvider::$optimizeClearCommands['page-cache'] ?? null);
    }
}
