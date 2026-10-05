<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The /blog.html page lists blog posts newest first. Its heading, intro and
 * SEO tags come from this page record, so they can be edited in the admin.
 */
return new class extends Migration
{
    private const PATH = 'blog.html';

    public function up(): void
    {
        if (DB::table('pages')->where('path', self::PATH)->exists()) {
            return;
        }

        $intro = 'News, travel tips and stories about Haridwar, Rishikesh and Uttarakhand from our local Haridwar team.';

        DB::table('pages')->insert([
            'path' => self::PATH,
            'type' => 'hub',
            'section' => 'blog',
            'title' => 'Blog',
            'nav_label' => 'Blog',
            'meta_title' => 'Haridwar Rishikesh Travel Blog – Tips, News & Stories',
            'meta_description' => 'Travel tips, festival dates, yatra updates and stories about Haridwar, Rishikesh and Uttarakhand from the local India Easy Trip team.',
            'excerpt' => $intro,
            'search_text' => 'Blog '.$intro,
            'robots' => 'index,follow',
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 900,
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
