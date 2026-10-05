<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\Legacy\PageClassifier;
use App\Support\PageCache;
use App\Support\SiteSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * llms.txt (https://llmstxt.org): a concise, structured map of the site for
 * AI assistants and answer engines (Generative Engine Optimisation), plus
 * llms-full.txt with the full plain-text content.
 */
class LlmsTxtController extends Controller
{
    public function index(SiteSettings $settings): Response
    {
        $body = Cache::remember('llms.index.'.PageCache::version(), 86400, function () use ($settings): string {
            $siteUrl = rtrim(config('seo.site_url'), '/');
            $lines = [
                '# '.$settings->get('name'),
                '',
                '> '.$settings->get('name').' ('.$siteUrl.') is the travel guide and booking website of '.$settings->get('legal_name').', a Haridwar based travel agency operating since March 1995. It publishes tour packages, pilgrimage and sightseeing guides for Haridwar, Rishikesh, Mussoorie and Uttarakhand.',
                '',
                'Key facts:',
                '- Company: '.$settings->get('legal_name').', '.$settings->addressLine(),
                '- Phone / WhatsApp: '.$settings->get('phone').' | Email: '.$settings->get('email'),
            ];

            foreach ((array) $settings->get('credentials') as $credential) {
                $lines[] = '- '.$credential['name'].': '.$credential['value'];
            }

            $lines[] = '- Bookings: '.$siteUrl.'/book-now.php';
            $lines[] = '';

            $pages = Page::query()->indexable()->where('path', '!=', '')->orderBy('sort_order')
                ->get(['path', 'title', 'meta_description', 'excerpt', 'section', 'type'])
                ->groupBy('section');

            foreach (PageClassifier::SECTION_LABELS as $key => $label) {
                if (! $pages->has($key)) {
                    continue;
                }

                $lines[] = '## '.$label;
                foreach ($pages[$key] as $page) {
                    $description = str($page->meta_description ?: $page->excerpt)->squish()->limit(180)->toString();
                    $lines[] = '- ['.$page->title.']('.$siteUrl.'/'.$page->path.')'.($description !== '' ? ': '.$description : '');
                }
                $lines[] = '';
            }

            $lines[] = '## Optional';
            $lines[] = '- [Full text of all guides]('.$siteUrl.'/llms-full.txt)';
            $lines[] = '- [XML sitemap]('.$siteUrl.'/sitemap.xml)';

            return implode("\n", $lines)."\n";
        });

        return $this->plain($body);
    }

    public function full(): Response
    {
        $body = Cache::remember('llms.full.'.PageCache::version(), 86400, function (): string {
            $siteUrl = rtrim(config('seo.site_url'), '/');
            $out = [];

            Page::query()->indexable()->where('path', '!=', '')->orderBy('sort_order')
                ->with('author:id,name')
                ->each(function (Page $page) use (&$out, $siteUrl): void {
                    $text = trim(preg_replace("/\n{3,}/", "\n\n", html_entity_decode(strip_tags(preg_replace(['#</(p|h2|h3|h4|li|tr)>#i', '#<br\s*/?>#i'], ["\n", "\n"], (string) $page->body)), ENT_QUOTES | ENT_HTML5)));
                    $out[] = '# '.$page->title."\nURL: ".$siteUrl.'/'.$page->path."\nUpdated: ".$page->updated_at?->toDateString().($page->author ? "\nAuthor: ".$page->author->name : '')."\n\n".$text."\n";
                });

            return implode("\n---\n\n", $out);
        });

        return $this->plain($body);
    }

    private function plain(string $body): Response
    {
        return response($body)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('X-Robots-Tag', 'noindex');
    }
}
