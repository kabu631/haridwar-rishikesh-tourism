<?php

namespace App\Console\Commands;

use App\Support\PageCache;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Retires every cached public page. Runs as part of `php artisan optimize`
 * and `optimize:clear`, so a deploy with template changes never serves old HTML.
 */
#[Signature('page-cache:clear')]
#[Description('Clear the full-page cache of the public website')]
class ClearPageCache extends Command
{
    public function handle(): int
    {
        PageCache::flush();

        $this->components->info('Website page cache cleared.');

        return self::SUCCESS;
    }
}
