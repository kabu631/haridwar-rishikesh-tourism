<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Cards on legacy hub pages sat under a content heading (e.g. the four rows
 * of the Tour Packages page). The heading is now stored on each card as
 * "group" so the page shows every row under its own heading again. Copies the
 * headings from the legacy snapshot into existing pages; cards edited since
 * the import (different URL/title) are left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $snapshot = json_decode(File::get(database_path('data/content.json')), true);

        foreach (array_merge([$snapshot['home']], $snapshot['pages']) as $record) {
            $groups = collect($record['cards'] ?? [])
                ->filter(fn (array $card): bool => filled($card['group'] ?? null))
                ->mapWithKeys(fn (array $card): array => [($card['url'] ?? '').'|'.($card['title'] ?? '') => $card['group']]);

            if ($groups->isEmpty()) {
                continue;
            }

            $row = DB::table('pages')->where('path', $record['path'])->first(['id', 'cards']);
            $cards = $row ? json_decode((string) $row->cards, true) : null;

            if (! is_array($cards)) {
                continue;
            }

            foreach ($cards as $index => $card) {
                $key = ($card['url'] ?? '').'|'.($card['title'] ?? '');

                if (empty($card['group']) && $groups->has($key)) {
                    $cards[$index]['group'] = $groups[$key];
                }
            }

            DB::table('pages')->where('id', $row->id)->update(['cards' => json_encode($cards, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        //
    }
};
