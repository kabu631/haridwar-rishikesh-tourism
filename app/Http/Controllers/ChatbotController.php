<?php

namespace App\Http\Controllers;

use App\Enums\PageType;
use App\Models\Page;
use App\Support\Media\MediaLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    /**
     * Tour packages suggested by the trip assistant, grouped by chat option
     * (see config/chatbot.php). Fetched once, when a visitor picks an option.
     */
    public function packages(MediaLibrary $media): JsonResponse
    {
        $packages = Page::query()
            ->published()
            ->where('type', PageType::Package)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'path', 'title', 'nav_label', 'section', 'facts', 'itinerary', 'hero_image', 'extra', 'is_featured', 'sort_order']);

        $topics = collect(config('chatbot.topics'))->mapWithKeys(fn (array $topic): array => [
            $topic['key'] => $packages
                ->filter(fn (Page $page): bool => $this->belongsToTopic($page, $topic))
                ->sortBy(fn (Page $page): int => $this->keywordRank($page, $topic))
                ->take((int) config('chatbot.limit', 12))
                ->map(fn (Page $page): array => $this->suggestion($page, $media))
                ->values(),
        ]);

        return response()->json(['topics' => $topics])
            ->header('Cache-Control', 'public, max-age=600')
            ->header('X-Robots-Tag', 'noindex');
    }

    /**
     * @param  array{sections?: list<string>, keywords?: list<string>, exclude?: list<string>}  $topic
     */
    private function belongsToTopic(Page $page, array $topic): bool
    {
        if (Str::contains($page->path, $topic['exclude'] ?? [])) {
            return false;
        }

        return in_array($page->section, $topic['sections'] ?? [], true) || Str::contains($page->path, $topic['keywords'] ?? []);
    }

    /**
     * Packages matching an earlier keyword are listed first; section-only
     * matches keep their admin sort order after them.
     *
     * @param  array{keywords?: list<string>}  $topic
     */
    private function keywordRank(Page $page, array $topic): int
    {
        foreach ($topic['keywords'] ?? [] as $index => $keyword) {
            if (str_contains($page->path, $keyword)) {
                return $index;
            }
        }

        return PHP_INT_MAX;
    }

    /**
     * @return array{title: string, url: string, days: int|null, nights: int|null, image: string|null}
     */
    private function suggestion(Page $page, MediaLibrary $media): array
    {
        $duration = $page->duration();
        $thumbnail = $page->extra['thumbnail'] ?? $page->hero_image;

        return [
            'title' => $page->shortTitle(),
            'url' => $page->url(),
            'days' => $duration['days'] ?? null,
            'nights' => $duration['nights'] ?? null,
            'image' => $thumbnail ? ($media->bestVariant($thumbnail, 160) ?? $thumbnail) : null,
        ];
    }
}
