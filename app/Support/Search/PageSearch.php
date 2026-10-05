<?php

namespace App\Support\Search;

use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Site search over all published pages using MySQL/MariaDB FULLTEXT
 * (boolean mode with prefix matching, title matches weighted 3×). Falls back
 * to LIKE matching on databases without FULLTEXT support (e.g. SQLite in
 * tests) or for very short queries.
 */
class PageSearch
{
    /**
     * InnoDB default stopwords; a required stopword makes a boolean query
     * return nothing, so they are removed from the query.
     */
    private const STOPWORDS = [
        'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how', 'i', 'in', 'is', 'it',
        'la', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what', 'when', 'where', 'who', 'will', 'with', 'und', 'www',
    ];

    public const MAX_QUERY_LENGTH = 100;

    /**
     * @return LengthAwarePaginator<int, Page>
     */
    public function paginate(string $query, ?string $type = null, int $perPage = 12): LengthAwarePaginator
    {
        return $this->builder($query, $type)->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, Page>
     */
    public function suggest(string $query, int $limit = 8): Collection
    {
        return $this->builder($query)->limit($limit)->get();
    }

    /**
     * Normalised search terms (lowercase words, no punctuation).
     *
     * @return list<string>
     */
    public function terms(string $query): array
    {
        $clean = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', Str::limit($query, self::MAX_QUERY_LENGTH, '')));

        return array_values(array_unique(array_filter(preg_split('/\s+/', $clean), fn (string $term): bool => $term !== '')));
    }

    /**
     * Text excerpt around the first matched term, with matches wrapped in
     * <mark>. Output is HTML-escaped.
     */
    public function snippet(Page $page, string $query, int $length = 200): string
    {
        $text = (string) ($page->search_text ?: $page->meta_description);
        $terms = $this->terms($query);
        $position = null;

        foreach ($terms as $term) {
            $found = mb_stripos($text, $term);
            if ($found !== false && ($position === null || $found < $position)) {
                $position = $found;
            }
        }

        $start = max(0, ($position ?? 0) - 60);
        $excerpt = mb_substr($text, $start, $length);
        $excerpt = ($start > 0 ? '… ' : '').trim($excerpt).(mb_strlen($text) > $start + $length ? ' …' : '');

        $escaped = e($excerpt);

        foreach ($terms as $term) {
            if (mb_strlen($term) < 2) {
                continue;
            }
            $escaped = preg_replace('/('.preg_quote(e($term), '/').')/iu', '<mark>$1</mark>', $escaped);
        }

        return $escaped;
    }

    /**
     * @return Builder<Page>
     */
    private function builder(string $query, ?string $type = null): Builder
    {
        $terms = $this->terms($query);
        $base = Page::query()
            ->published()
            ->where('path', '!=', '')
            ->with('parent:id,path,title,nav_label')
            ->when($type, fn (Builder $builder) => $builder->where('type', $type));

        $ftTerms = array_values(array_filter($terms, fn (string $term): bool => mb_strlen($term) >= 3 && ! in_array($term, self::STOPWORDS, true)));

        if ($ftTerms === [] || ! $this->supportsFullText()) {
            return $this->likeQuery($base, $terms);
        }

        $all = implode(' ', array_map(fn (string $term): string => '+'.$term.'*', $ftTerms));
        $any = implode(' ', array_map(fn (string $term): string => $term.'*', $ftTerms));

        $boolean = (clone $base)->whereFullText(['title', 'meta_title', 'meta_description', 'search_text'], $all, ['mode' => 'boolean'])->exists() ? $all : $any;

        return $base
            ->select('pages.*')
            ->selectRaw(
                '(MATCH(title, meta_title) AGAINST (? IN BOOLEAN MODE) * 3 + MATCH(title, meta_title, meta_description, search_text) AGAINST (? IN BOOLEAN MODE)) AS relevance',
                [$boolean, $boolean],
            )
            ->whereFullText(['title', 'meta_title', 'meta_description', 'search_text'], $boolean, ['mode' => 'boolean'])
            ->orderByDesc('relevance')
            ->orderBy('sort_order');
    }

    /**
     * @param  Builder<Page>  $builder
     * @param  list<string>  $terms
     * @return Builder<Page>
     */
    private function likeQuery(Builder $builder, array $terms): Builder
    {
        if ($terms === []) {
            return $builder->whereRaw('1 = 0');
        }

        foreach ($terms as $term) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
            $builder->where(fn (Builder $query) => $query
                ->where('title', 'like', $like)
                ->orWhere('meta_title', 'like', $like)
                ->orWhere('search_text', 'like', $like));
        }

        $first = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $terms[0]).'%';

        return $builder->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', [$first])->orderBy('sort_order');
    }

    private function supportsFullText(): bool
    {
        return in_array(Page::query()->getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
}
