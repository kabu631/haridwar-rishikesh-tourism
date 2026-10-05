<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The /sitemap.html page gets its heading, intro and SEO tags from this
 * page record, so they can be edited in the admin (Company & legal).
 */
return new class extends Migration
{
    private const PATH = 'sitemap.html';

    public function up(): void
    {
        if (DB::table('pages')->where('path', self::PATH)->exists()) {
            return;
        }

        $intro = 'Every guide, tour package and travel page on Haridwar Rishikesh Tourism.';

        DB::table('pages')->insert([
            'path' => self::PATH,
            'type' => 'company',
            'section' => 'company',
            'title' => 'Sitemap',
            'nav_label' => 'Sitemap',
            'meta_title' => 'Sitemap – Haridwar Rishikesh Tourism',
            'meta_description' => 'All Haridwar Rishikesh Tourism pages: tour packages, Haridwar and Rishikesh travel guides, temples, treks, festivals and travel information.',
            'excerpt' => $intro,
            'search_text' => 'Sitemap '.$intro,
            'robots' => 'index,follow',
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 999,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('pages')->where('path', self::PATH)->delete();
    }
};
