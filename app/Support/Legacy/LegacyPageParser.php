<?php

namespace App\Support\Legacy;

use App\Support\Content\HtmlSanitizer;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Extracts the content and SEO signals of one legacy page.
 *
 * The legacy site uses two templates: article pages (content inside
 * `.left-content .post`) and hub pages (`section.page` with a `.page-title`
 * heading followed by "story" cards or galleries). Everything outside the
 * main content (menus, sidebars, footers, forms) is discarded because the new
 * layout renders those itself.
 */
class LegacyPageParser
{
    private const CHROME_SELECTORS = [
        '//header', '//nav', '//footer', '//*[@id="top-bar"]', '//select',
        '//*[contains(concat(" ", normalize-space(@class), " "), " sidebar ")]',
        '//*[contains(@class, "footer-")]', '//*[contains(@class, "footer_carousel")]',
        '//*[contains(@class, "flickr-images")]', '//*[contains(@class, "newsletter")]',
        '//*[contains(@class, "social-bar")]', '//*[contains(@class, "breadcrumbs")]',
        '//*[contains(@class, "g-recaptcha")]', '//*[@id="dots"]', '//button[@onclick]',
        '//script', '//style', '//noscript', '//form', '//*[@id="layerslider-container-fw"]',
    ];

    public function __construct(private HtmlSanitizer $sanitizer) {}

    /**
     * @return array<string, mixed>
     */
    public function parse(string $path, string $html): array
    {
        $document = $this->load($html);
        $xpath = new DOMXPath($document);

        $meta = $this->meta($xpath);
        $h1Node = $xpath->query('//h1')->item(0);
        $h1 = $h1Node ? $this->text($h1Node) : null;
        $h1Link = $h1Node ? $xpath->query('.//a[@href]', $h1Node)->item(0) : null;
        $hero = $this->hero($xpath);
        $slides = $this->extractSlides($xpath);

        foreach (self::CHROME_SELECTORS as $selector) {
            foreach (iterator_to_array($xpath->query($selector)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        $root = $this->contentRoot($xpath);

        $cards = $root ? $this->extractCards($xpath, $root) : [];
        $slides = array_merge($slides, $root ? $this->extractStrips($xpath, $root) : []);
        $gallery = $root ? $this->extractGallery($xpath, $root) : [];

        if ($root !== null) {
            $this->removeTitle($xpath, $root);
            $this->convertCollapsibles($xpath, $root);
            $this->convertBookingImages($xpath, $root);
            $this->splitBoldHeadings($xpath, $root);
            $this->normaliseUrls($xpath, $root);
        }

        $body = $root ? $this->sanitizer->sanitize($this->sanitizer->innerHtml($root)) : '';

        return [
            'path' => $path,
            'meta_title' => $meta['title'],
            'meta_description' => $meta['description'],
            'meta_keywords' => $meta['keywords'],
            'robots' => $meta['robots'],
            'canonical' => $meta['canonical'],
            'h1' => $h1,
            'h1_link' => $h1Link instanceof DOMElement && $this->text($h1Link) !== '' ? ['text' => $this->text($h1Link), 'url' => $this->localUrl($h1Link->getAttribute('href'))] : null,
            'hero' => $hero,
            'body' => $body,
            'cards' => $cards,
            'gallery' => $gallery,
            'slides' => $slides,
            'faqs' => $this->extractFaqs($body),
            'itinerary' => $this->extractItinerary($body),
        ];
    }

    private function load(string $html): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        return $document;
    }

    /**
     * @return array{title: ?string, description: ?string, keywords: ?string, robots: ?string, canonical: ?string}
     */
    private function meta(DOMXPath $xpath): array
    {
        $content = fn (string $name): ?string => $this->clean(
            $xpath->query('//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="'.$name.'"]/@content')->item(0)?->nodeValue
        );

        return [
            'title' => $this->clean($xpath->query('//title')->item(0)?->textContent),
            'description' => $content('description'),
            'keywords' => $content('keywords'),
            'robots' => $content('robots'),
            'canonical' => $this->clean($xpath->query('//link[@rel="canonical"]/@href')->item(0)?->nodeValue),
        ];
    }

    /**
     * @return array{src: string, alt: string}|null
     */
    private function hero(DOMXPath $xpath): ?array
    {
        $img = $xpath->query('//*[contains(@class, "top-image")]//img')->item(0);

        if (! $img instanceof DOMElement || trim($img->getAttribute('src')) === '') {
            return null;
        }

        return ['src' => $this->localPath($img->getAttribute('src')), 'alt' => $this->clean($img->getAttribute('alt')) ?? ''];
    }

    private function contentRoot(DOMXPath $xpath): ?DOMElement
    {
        foreach ([
            // The whole content column: some pages continue in a second ".post" block.
            '//div[contains(@class, "left-content")]',
            '//div[contains(@class, "fullcontainer")]',
            '//section[contains(concat(" ", normalize-space(@class), " "), " page ")][.//h1]',
            '//section[contains(concat(" ", normalize-space(@class), " "), " page ")]',
        ] as $selector) {
            $node = $xpath->query($selector)->item(0);
            if ($node instanceof DOMElement) {
                foreach (iterator_to_array($xpath->query('.//*[contains(@class, "top-image")]', $node)) as $top) {
                    $top->parentNode->removeChild($top);
                }

                return $node;
            }
        }

        return null;
    }

    /**
     * Hub pages list child pages as "story" cards (image, title, teaser).
     *
     * @return list<array<string, ?string>>
     */
    private function extractCards(DOMXPath $xpath, DOMElement $root): array
    {
        $cards = [];

        foreach (iterator_to_array($xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " story ")]', $root)) as $story) {
            $href = null;
            foreach ($xpath->query('.//a[@href]', $story) as $link) {
                $candidate = HtmlSanitizer::cleanHref($link->getAttribute('href'));
                if (preg_match('/\.html$/i', $candidate)) {
                    $href = $this->localPath($candidate);
                    break;
                }
            }

            $img = $xpath->query('.//img', $story)->item(0);
            $title = $xpath->query('.//h5|.//h4|.//h3', $story)->item(0);
            $badge = $xpath->query('.//*[contains(@class, "badge-custom")]', $story)->item(0);
            $teaser = $xpath->query('./p', $story)->item(0);

            $cards[] = [
                'title' => $title ? $this->text($title) : null,
                'url' => $href,
                'image' => $img instanceof DOMElement ? $this->localPath($img->getAttribute('src')) : null,
                'alt' => $img instanceof DOMElement ? $this->clean($img->getAttribute('alt')) : null,
                'text' => $teaser ? $this->text($teaser) : null,
                'badge' => $badge ? $this->text($badge) : null,
            ];

            $this->removeColumn($story);
        }

        return array_values(array_filter($cards, fn (array $card): bool => $card['title'] !== null || $card['url'] !== null));
    }

    /**
     * Images of the in-content slideshow (LayerSlider) shown at the top of
     * many legacy pages. They become the page's photo gallery so the images
     * and their alt text stay on the page.
     *
     * @return list<array<string, ?string>>
     */
    private function extractSlides(DOMXPath $xpath): array
    {
        $slides = [];

        foreach ($xpath->query('//div[contains(@class, "left-content")]//div[@id="layerslider"]//img|//div[contains(@class, "fullcontainer")]//div[@id="layerslider"]//img') as $img) {
            $src = trim($img->getAttribute('src'));

            if ($src === '' || str_contains($src, 'slider1-img')) {
                continue;
            }

            $path = $this->localPath($src);
            $slides[$path] = [
                'image' => $path,
                'alt' => $this->clean($img->getAttribute('alt')),
                'full' => $path,
                'youtube' => null,
                'url' => null,
                'title' => null,
                'caption' => null,
                'slide' => true,
            ];
        }

        return array_values($slides);
    }

    /**
     * Image strips (".pics") inside an article: page photos, not a gallery page.
     *
     * @return list<array<string, ?string>>
     */
    private function extractStrips(DOMXPath $xpath, DOMElement $root): array
    {
        $items = [];

        foreach (iterator_to_array($xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " pics ")]', $root)) as $strip) {
            foreach ($xpath->query('.//img', $strip) as $img) {
                $path = $this->localPath($img->getAttribute('src'));
                $items[] = ['image' => $path, 'alt' => $this->clean($img->getAttribute('alt')) ?? $this->clean($img->getAttribute('title')), 'full' => $path, 'youtube' => null, 'url' => null, 'title' => $this->clean($img->getAttribute('title')), 'caption' => null];
            }

            $this->removeColumn($strip);
        }

        return $items;
    }

    /**
     * Photo / video galleries rendered with html5lightbox on the legacy site
     * and the "portfolio" index of galleries.
     *
     * @return list<array<string, ?string>>
     */
    private function extractGallery(DOMXPath $xpath, DOMElement $root): array
    {
        $items = [];

        foreach (iterator_to_array($xpath->query('.//div[contains(@class, "gallery-image")]|.//div[contains(@class, "portfolio")]', $root)) as $block) {
            $img = $xpath->query('.//img', $block)->item(0);
            $link = $xpath->query('.//a[@href]', $block)->item(0);
            $href = $link instanceof DOMElement ? trim($link->getAttribute('href')) : '';
            $youtube = preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{11})~', $href, $match) ? $match[1] : null;
            $heading = $xpath->query('.//h3|.//h4|.//h5', $block)->item(0);
            $caption = $xpath->query('.//*[contains(@class, "port-desc")]//p', $block)->item(0);

            $items[] = [
                'image' => $img instanceof DOMElement ? $this->localPath($img->getAttribute('src')) : null,
                'alt' => $img instanceof DOMElement ? ($this->clean($img->getAttribute('alt')) ?? $this->clean($img->getAttribute('title'))) : null,
                'full' => $youtube === null && $href !== '' && preg_match('/\.(jpe?g|png|gif|webp)$/i', $href) ? $this->localPath($href) : null,
                'youtube' => $youtube,
                'url' => $youtube === null && preg_match('/\.html$/i', $href) ? $this->localPath($href) : null,
                'title' => ($heading ? $this->text($heading) : null) ?? $this->clean($link instanceof DOMElement ? $link->getAttribute('title') : null),
                'caption' => $caption ? $this->text($caption) : null,
            ];

            $this->removeColumn($block);
        }

        return array_values(array_filter($items, fn (array $item): bool => $item['image'] !== null || $item['youtube'] !== null));
    }

    /**
     * Remove a card together with its now-empty bootstrap column wrapper.
     */
    private function removeColumn(DOMElement $block): void
    {
        $target = $block;
        $parent = $block->parentNode;

        if ($parent instanceof DOMElement && str_contains($parent->getAttribute('class'), 'col-') && trim(str_replace($block->textContent, '', $parent->textContent)) === '') {
            $target = $parent;
        }

        $target->parentNode?->removeChild($target);
    }

    private function removeTitle(DOMXPath $xpath, DOMElement $root): void
    {
        foreach (iterator_to_array($xpath->query('.//*[contains(@class, "page-title")]', $root)) as $title) {
            $title->parentNode->removeChild($title);
        }

        $h1 = $xpath->query('.//h1', $root)->item(0);
        $h1?->parentNode->removeChild($h1);
    }

    /**
     * `<button class="collapsible">Day 1 : …</button><div class="content">…`
     * becomes a heading followed by its (always visible) content.
     */
    private function convertCollapsibles(DOMXPath $xpath, DOMElement $root): void
    {
        foreach (iterator_to_array($xpath->query('.//button[contains(@class, "collapsible")]', $root)) as $button) {
            $heading = $button->ownerDocument->createElement('h3', htmlspecialchars($this->text($button)));
            $button->parentNode->replaceChild($heading, $button);
        }
    }

    /**
     * Image-only links to the booking form become plain text links so the
     * internal link to book-now.php is preserved.
     */
    private function convertBookingImages(DOMXPath $xpath, DOMElement $root): void
    {
        foreach (iterator_to_array($xpath->query('.//a[contains(@href, "book-now")]', $root)) as $link) {
            if (trim($link->textContent) !== '') {
                continue;
            }

            while ($link->firstChild !== null) {
                $link->removeChild($link->firstChild);
            }

            $link->appendChild($link->ownerDocument->createTextNode('Book this tour now'));
        }

        foreach (iterator_to_array($xpath->query('.//img[contains(@src, "book-now") or contains(@src, "booknow")]', $root)) as $img) {
            $img->parentNode->removeChild($img);
        }
    }

    /**
     * Legacy pages format itinerary days ("Day 01: …") and FAQ questions as
     * bold text inside paragraphs. Promote them to real headings so the page
     * has a logical outline and the data can be extracted for schema.
     */
    private function splitBoldHeadings(DOMXPath $xpath, DOMElement $root): void
    {
        foreach (iterator_to_array($xpath->query('.//p', $root)) as $paragraph) {
            if ($paragraph->parentNode === null) {
                continue;
            }

            $segments = [[]];
            $headings = [];

            foreach (iterator_to_array($paragraph->childNodes) as $child) {
                if ($child instanceof DOMElement && in_array($child->nodeName, ['b', 'strong'], true) && $this->isHeadingText($this->text($child))) {
                    $headings[count($segments)] = $this->text($child);
                    $segments[] = [];

                    continue;
                }

                $segments[count($segments) - 1][] = $child;
            }

            if ($headings === []) {
                continue;
            }

            $level = $this->headingLevelBefore($xpath, $paragraph);
            $document = $paragraph->ownerDocument;
            $parent = $paragraph->parentNode;

            foreach ($segments as $index => $nodes) {
                if (isset($headings[$index])) {
                    $parent->insertBefore($document->createElement('h'.$level, htmlspecialchars($headings[$index])), $paragraph);
                }

                $newParagraph = $document->createElement('p');
                foreach ($nodes as $node) {
                    $newParagraph->appendChild($node);
                }
                $parent->insertBefore($newParagraph, $paragraph);
            }

            $parent->removeChild($paragraph);
        }
    }

    private function isHeadingText(string $text): bool
    {
        $text = trim($text);

        if (mb_strlen($text) < 6 || mb_strlen($text) > 160) {
            return false;
        }

        return (bool) preg_match('/^Day\s*-?\s*\d{1,2}\b/i', $text) || str_ends_with($text, '?');
    }

    private function headingLevelBefore(DOMXPath $xpath, DOMElement $node): int
    {
        $previous = $xpath->query('preceding::*[self::h2 or self::h3 or self::h4][1]', $node)->item(0);

        if ($previous === null) {
            return 3;
        }

        if (preg_match('/^(Day\s*-?\s*\d|.*\?$)/i', trim($previous->textContent))) {
            return (int) substr($previous->nodeName, 1);
        }

        return min(4, (int) substr($previous->nodeName, 1) + 1);
    }

    /**
     * Make same-site links and images root relative ("/page.html").
     */
    private function normaliseUrls(DOMXPath $xpath, DOMElement $root): void
    {
        foreach ($xpath->query('.//a[@href]', $root) as $link) {
            $link->setAttribute('href', $this->localUrl($link->getAttribute('href')));
        }

        foreach ($xpath->query('.//img[@src]', $root) as $img) {
            $img->setAttribute('src', $this->localPath($img->getAttribute('src')));
        }
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function extractFaqs(string $body): array
    {
        return collect($this->sections($body))
            ->filter(fn (array $section): bool => str_ends_with($section['heading'], '?') && ! preg_match('/^Day\s*\d/i', $section['heading']))
            ->map(fn (array $section): array => [
                'question' => trim(preg_replace('/^(Q(ues(tion)?)?\.?\s*\d*\s*[.:)\-]?|\d{1,2}\s*[:.)\-])\s*/i', '', $section['heading'])),
                'answer' => trim(str_replace('Book this tour now', '', preg_replace('/^Ans(wer)?\s*[.:\-]\s*/i', '', $section['text']))),
            ])
            ->filter(fn (array $faq): bool => mb_strlen($faq['answer']) >= 15 && mb_strlen($faq['question']) >= 8)
            ->values()
            ->all();
    }

    /**
     * @return list<array{day: int, title: string, description: string}>
     */
    private function extractItinerary(string $body): array
    {
        $days = [];

        foreach ($this->sections($body) as $section) {
            if (preg_match('/^Day\s*-?\s*0?(\d{1,2})\s*[:.\-–]*\s*(.*)$/iu', $section['heading'], $match)) {
                $days[] = [
                    'day' => (int) $match[1],
                    'title' => trim($match[2]) !== '' ? trim($match[2]) : 'Day '.$match[1],
                    'description' => Str::limit($section['text'], 600),
                ];
            }
        }

        return $days;
    }

    /**
     * Split sanitized HTML into heading → following text sections.
     *
     * @return list<array{heading: string, text: string}>
     */
    private function sections(string $body): array
    {
        $document = $this->sanitizer->load($body);
        $root = $document->getElementById('__root');
        $sections = [];
        $current = null;

        foreach ($root->childNodes as $node) {
            if ($node instanceof DOMElement && in_array($node->nodeName, ['h2', 'h3', 'h4'], true)) {
                if ($current !== null) {
                    $sections[] = $current;
                }
                $current = ['heading' => $this->text($node), 'text' => ''];

                continue;
            }

            if ($current !== null) {
                $current['text'] = trim($current['text'].' '.$this->text($node));
            }
        }

        if ($current !== null) {
            $sections[] = $current;
        }

        return $sections;
    }

    public function localPath(string $src): string
    {
        $src = trim(preg_replace('/\s+/', ' ', $src));
        $parts = parse_url($src);
        $path = $parts['path'] ?? $src;

        if (isset($parts['host']) && ! str_contains($parts['host'], 'haridwarrishikeshtourism.com')) {
            return $src;
        }

        return '/'.ltrim(str_replace('../', '', $path), '/');
    }

    public function localUrl(string $href): string
    {
        $href = HtmlSanitizer::cleanHref($href);

        if ($href === '' || str_starts_with($href, '#') || preg_match('/^(mailto|tel|javascript):/i', $href)) {
            return $href;
        }

        $parts = parse_url($href);

        if ($parts === false) {
            return $href;
        }

        if (isset($parts['host']) && ! str_contains(strtolower($parts['host']), 'haridwarrishikeshtourism.com')) {
            return $href;
        }

        $path = '/'.ltrim($parts['path'] ?? '', '/');

        if (in_array($path, ['/index.html', '/index.php'], true)) {
            $path = '/';
        }

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }

    private function text(DOMNode $node): string
    {
        return $this->clean($node->textContent) ?? '';
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));

        return $value === '' ? null : $value;
    }
}
