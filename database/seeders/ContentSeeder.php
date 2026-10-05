<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Testimonial;
use App\Support\PageCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Loads the migrated website content (database/data/content.json and
 * media.json, produced by `php artisan legacy:import`) into the database.
 * Safe to re-run: records are matched on their natural keys.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $snapshot = json_decode(File::get(database_path('data/content.json')), true);

        DB::transaction(function () use ($snapshot): void {
            $authors = $this->authors($snapshot['authors']);
            $pages = $this->pages(array_merge([$snapshot['home']], $snapshot['pages']), $authors['sunil-saini'] ?? null);
            $this->linkParents(array_merge([$snapshot['home']], $snapshot['pages']), $pages);
            $this->menus($snapshot['menus']);
            $this->redirects($snapshot['redirects']);
            $this->testimonials($snapshot['testimonials']);
        });

        $this->media();

        PageCache::flush();
    }

    /**
     * @param  list<array<string, mixed>>  $authors
     * @return array<string, int>
     */
    private function authors(array $authors): array
    {
        $ids = [];

        foreach ($authors as $author) {
            $ids[$author['slug']] = Author::query()->updateOrCreate(['slug' => $author['slug']], $author)->id;
        }

        return $ids;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, int> path => id
     */
    private function pages(array $records, ?int $authorId): array
    {
        $ids = [];

        foreach ($records as $index => $record) {
            $lastmod = filled($record['lastmod'] ?? null) ? Carbon::parse($record['lastmod']) : now();

            $page = Page::query()->firstOrNew(['path' => $record['path']]);

            $page->fill([
                'type' => $record['type'],
                'section' => $record['section'],
                'title' => $record['title'],
                'meta_title' => $record['meta_title'] ?? null,
                'meta_description' => $record['meta_description'] ?? null,
                'meta_keywords' => $record['meta_keywords'] ?? null,
                'robots' => $record['robots'] ?? 'index,follow',
                'canonical_url' => $record['canonical_url'] ?? null,
                'schema_type' => $record['schema_type'] ?? null,
                'excerpt' => $record['excerpt'] ?? null,
                'body' => $record['body'] ?? null,
                'hero_image' => $record['hero_image'] ?? null,
                'hero_alt' => $record['hero_alt'] ?? null,
                'cards' => $record['cards'] ?? null,
                'gallery' => $record['gallery'] ?? null,
                'itinerary' => $record['itinerary'] ?? null,
                'faqs' => $record['faqs'] ?? null,
                'facts' => $record['facts'] ?? null,
                'extra' => $record['extra'] ?? null,
                'author_id' => $authorId,
                'is_published' => true,
                'sort_order' => $index,
            ]);

            if (! $page->exists) {
                $page->published_at = $lastmod;
                $page->created_at = $lastmod;
            }

            if ($page->isDirty() || ! $page->exists) {
                $page->updated_at = $lastmod;
            }

            $page->search_text = $page->buildSearchText();
            $page->saveQuietly();

            $ids[$record['path']] = $page->id;
        }

        return $ids;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array<string, int>  $ids
     */
    private function linkParents(array $records, array $ids): void
    {
        $order = $this->displayOrder($records);

        foreach ($records as $record) {
            $parentId = filled($record['parent'] ?? null) ? ($ids[$record['parent']] ?? null) : null;

            // Query builder update: keeps the legacy updated_at (last modified) date.
            Page::query()->whereKey($ids[$record['path']])->toBase()->update([
                'parent_id' => $parentId,
                'sort_order' => $order[$record['path']] ?? 1000,
            ]);
        }
    }

    /**
     * Pages are listed in the order the legacy site used: main menu order
     * first, then the order of the cards on hub pages, then alphabetical.
     *
     * @param  list<array<string, mixed>>  $records
     * @return array<string, int>
     */
    private function displayOrder(array $records): array
    {
        $snapshot = json_decode(File::get(database_path('data/content.json')), true);
        $order = [];

        foreach ($snapshot['menus']['main'] as $topPosition => $item) {
            $order[ltrim($item['url'], '/')] ??= $topPosition;

            foreach ($item['children'] ?? [] as $position => $child) {
                $order[ltrim($child['url'], '/')] ??= $position;
            }
        }

        foreach ($records as $record) {
            foreach ($record['cards'] ?? [] as $position => $card) {
                $order[ltrim((string) $card['url'], '/')] ??= 100 + $position;
            }
        }

        $paths = array_column($records, 'path');
        sort($paths);

        foreach ($paths as $position => $path) {
            $order[$path] ??= 1000 + $position;
        }

        return $order;
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $menus
     */
    private function menus(array $menus): void
    {
        MenuItem::query()->delete();

        foreach ($menus as $menu => $items) {
            foreach ($items as $position => $item) {
                $parent = MenuItem::query()->create([
                    'menu' => $menu,
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'title' => $item['title'] ?? null,
                    'open_in_new_tab' => (bool) preg_match('#^https?://#', $item['url']),
                    'sort_order' => $position,
                ]);

                foreach ($item['children'] ?? [] as $childPosition => $child) {
                    MenuItem::query()->create([
                        'menu' => $menu,
                        'parent_id' => $parent->id,
                        'label' => $child['label'],
                        'url' => $child['url'],
                        'title' => $child['title'] ?? null,
                        'sort_order' => $childPosition,
                    ]);
                }
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $redirects
     */
    private function redirects(array $redirects): void
    {
        foreach ($redirects as $redirect) {
            Redirect::query()->updateOrCreate(
                ['from_path' => Redirect::normalisePath($redirect['from_path'])],
                ['to_path' => $redirect['to_path'], 'status_code' => $redirect['status_code'], 'note' => $redirect['note'] ?? null],
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $testimonials
     */
    private function testimonials(array $testimonials): void
    {
        foreach ($testimonials as $testimonial) {
            Testimonial::query()->updateOrCreate(['name' => $testimonial['name']], $testimonial);
        }
    }

    private function media(): void
    {
        $file = database_path('data/media.json');

        if (! File::exists($file)) {
            return;
        }

        foreach (array_chunk(json_decode(File::get($file), true), 200) as $chunk) {
            Media::query()->upsert(
                array_map(fn (array $row): array => array_merge($row, [
                    'variants' => json_encode($row['variants'] ?? []),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]), $chunk),
                ['path'],
                ['width', 'height', 'bytes', 'variants', 'updated_at'],
            );
        }
    }
}
