<?php

use App\Models\Author;
use App\Models\Page;
use App\Support\PageCache;
use Illuminate\Database\Migrations\Migration;

/**
 * Team update: a former team member is removed from the editorial team and
 * the /our-team.html page; Avishek Saini, Piyush Saini, Sunny Chauhan and
 * Sunny Dhiman are added (fixing the legacy "Suuny Dhiman" typo).
 *
 * Only touches a database that already holds the site content; a fresh
 * install gets the same team from ContentSeeder (database/data/content.json).
 */
return new class extends Migration
{
    private const TEAM_PAGE = 'our-team.html';

    private const REMOVED_AUTHOR_SLUG = 'vijay-kumar';

    /**
     * The removed member's paragraph on the team page.
     */
    private const REMOVED_ENTRY_PATTERN = '#<p><strong>Mr\. Vijay Kumar \(.*?</p>\n#s';

    private const ADDED_AUTHORS = [
        [
            'name' => 'Avishek Saini',
            'slug' => 'avishek-saini',
            'job_title' => 'Team Member, India Easy Trip Pvt Ltd',
            'bio' => 'Avishek Saini is part of the India Easy Trip Pvt Ltd team in Haridwar, which plans Haridwar, Rishikesh and Uttarakhand tour packages for travellers.',
            'same_as' => [],
        ],
        [
            'name' => 'Piyush Saini',
            'slug' => 'piyush-saini',
            'job_title' => 'Team Member, India Easy Trip Pvt Ltd',
            'bio' => 'Piyush Saini is part of the India Easy Trip Pvt Ltd team in Haridwar, which plans Haridwar, Rishikesh and Uttarakhand tour packages for travellers.',
            'same_as' => [],
        ],
        [
            'name' => 'Sunny Chauhan',
            'slug' => 'sunny-chauhan',
            'job_title' => 'Team Member, India Easy Trip Pvt Ltd',
            'bio' => 'Sunny Chauhan is part of the India Easy Trip Pvt Ltd team in Haridwar, which plans Haridwar, Rishikesh and Uttarakhand tour packages for travellers.',
            'same_as' => [],
        ],
        [
            'name' => 'Sunny Dhiman',
            'slug' => 'sunny-dhiman',
            'job_title' => 'Executive, India Easy Trip Pvt Ltd',
            'bio' => 'Sunny Dhiman is an executive at India Easy Trip Pvt Ltd in Haridwar, which plans Haridwar, Rishikesh and Uttarakhand tour packages for travellers.',
            'same_as' => [],
        ],
    ];

    /**
     * Team page body edits, legacy text => updated text (flipped for down()).
     */
    private const BODY_CHANGES = [
        "<p><strong>Mr. Suuny Dhiman (Executive)</strong><br> +91-735-117-5588<br> B.com + 4 Years Experience of Tour and travel.</p>\n" => "<p><strong>Mr. Sunny Dhiman (Executive)</strong><br> +91-735-117-5588<br> B.com + 4 Years Experience of Tour and travel.</p>\n<p><strong>Avishek Saini (Team Member)</strong></p>\n<p><strong>Piyush Saini (Team Member)</strong></p>\n<p><strong>Sunny Chauhan (Team Member)</strong></p>\n",
    ];

    public function up(): void
    {
        $teamPage = Page::query()->firstWhere('path', self::TEAM_PAGE);

        if ($teamPage === null) {
            return;
        }

        Author::query()->where('slug', self::REMOVED_AUTHOR_SLUG)->delete();

        foreach (self::ADDED_AUTHORS as $author) {
            Author::query()->firstOrCreate(['slug' => $author['slug']], $author);
        }

        $teamPage->body = preg_replace(self::REMOVED_ENTRY_PATTERN, '', (string) $teamPage->body);

        $this->updateBody($teamPage, self::BODY_CHANGES);
    }

    /**
     * Undoes the additions only; the removed member is not restored.
     */
    public function down(): void
    {
        $teamPage = Page::query()->firstWhere('path', self::TEAM_PAGE);

        if ($teamPage === null) {
            return;
        }

        Author::query()->whereIn('slug', array_column(self::ADDED_AUTHORS, 'slug'))->delete();

        $this->updateBody($teamPage, array_flip(self::BODY_CHANGES));
    }

    /**
     * @param  array<string, string>  $changes
     */
    private function updateBody(Page $teamPage, array $changes): void
    {
        $teamPage->body = strtr((string) $teamPage->body, $changes);

        if ($teamPage->isDirty('body')) {
            $teamPage->search_text = $teamPage->buildSearchText();
            $teamPage->saveQuietly();
        }

        PageCache::flush();
    }
};
