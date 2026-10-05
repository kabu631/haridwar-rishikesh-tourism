<?php

namespace App\Support\Content;

use App\Support\Media\MediaLibrary;
use Closure;
use DOMComment;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Turns stored article HTML into the HTML served to visitors: responsive
 * <picture> images, heading anchors + table of contents, an itinerary
 * timeline, lazy map embeds and click-to-load YouTube players. The stored
 * content (and therefore the text Google sees) is not altered.
 */
class ContentRenderer
{
    public function __construct(private HtmlSanitizer $sanitizer, private MediaLibrary $media) {}

    /**
     * $sectionBlock may return HTML to show at the end of a heading's section
     * (e.g. the cards that sat under that heading on the legacy site). It
     * receives the heading text and whether the heading has no content of its own.
     *
     * @param  (Closure(string, bool): ?string)|null  $sectionBlock
     * @return array{html: string, toc: list<array{id: string, text: string, level: int}>}
     */
    public function render(?string $html, string $imageAltFallback = '', ?Closure $sectionBlock = null): array
    {
        if (blank($html)) {
            return ['html' => '', 'toc' => []];
        }

        $document = $this->sanitizer->load($html);
        $root = $document->getElementById('__root');
        $xpath = new DOMXPath($document);

        $this->normaliseHeadingLevels($xpath, $root);
        $this->noteHeadings($root);
        $toc = $this->anchorHeadings($xpath, $root);
        $this->timeline($xpath, $root);
        $this->tables($xpath, $root);
        $this->embeds($xpath, $root);
        $this->links($xpath, $root);
        $this->mediaCards($xpath, $root);

        $blocks = $sectionBlock ? $this->sectionBlocks($root, $sectionBlock) : [];
        $this->groupHeadings($root);
        $placeholders = $this->images($xpath, $root, $imageAltFallback);

        $output = $this->sanitizer->innerHtml($root);

        return ['html' => strtr($output, $placeholders + $blocks), 'toc' => $toc];
    }

    /**
     * Legacy pages often wrote a sentence as a heading (an FAQ answer, "Best
     * time is during July…", "Note: …"). When such a heading has nothing under
     * it, it is styled as a note instead of a big empty heading. The tag stays.
     */
    private function noteHeadings(DOMElement $root): void
    {
        foreach ($this->rootHeadings($root) as $heading) {
            $text = trim(preg_replace('/\s+/u', ' ', $heading->textContent));

            if ($this->isEmptyHeading($heading) && (mb_strlen($text) > 70 || str_ends_with($text, '.') || preg_match('/^(note|including|altitudes?)\b/i', $text))) {
                $heading->setAttribute('class', trim($heading->getAttribute('class').' heading-note'));
            }
        }
    }

    /**
     * A heading directly followed by another heading of the same level (e.g.
     * "Other Packages" before "Haridwar Rishikesh Tour Packages From Delhi")
     * titles the sections below it; it is styled as a section label.
     */
    private function groupHeadings(DOMElement $root): void
    {
        foreach ($this->rootHeadings($root) as $heading) {
            if ($this->sectionEnd($heading) !== null && $this->isEmptyHeading($heading) && ! str_contains($heading->getAttribute('class'), 'heading-note')) {
                $heading->setAttribute('class', trim($heading->getAttribute('class').' heading-group'));
            }
        }
    }

    /**
     * @param  Closure(string, bool): ?string  $sectionBlock
     * @return array<string, string> placeholder => HTML
     */
    private function sectionBlocks(DOMElement $root, Closure $sectionBlock): array
    {
        $blocks = [];

        foreach ($this->rootHeadings($root) as $heading) {
            $html = $sectionBlock(trim(preg_replace('/\s+/u', ' ', $heading->textContent)), $this->isEmptyHeading($heading));

            if (blank($html)) {
                continue;
            }

            $placeholder = '%%SECTION-BLOCK-'.count($blocks).'%%';
            $marker = $root->ownerDocument->createTextNode($placeholder);
            $end = $this->sectionEnd($heading);
            $end ? $root->insertBefore($marker, $end) : $root->appendChild($marker);
            $blocks[$placeholder] = $html;
        }

        return $blocks;
    }

    /**
     * @return list<DOMElement>
     */
    private function rootHeadings(DOMElement $root): array
    {
        $headings = [];

        foreach ($root->childNodes as $node) {
            if ($node instanceof DOMElement && preg_match('/^h[2-6]$/', $node->nodeName)) {
                $headings[] = $node;
            }
        }

        return $headings;
    }

    /**
     * True when the next content after the heading is a heading of the same
     * or a higher level (or nothing at all).
     */
    private function isEmptyHeading(DOMElement $heading): bool
    {
        for ($node = $heading->nextSibling; $node !== null; $node = $node->nextSibling) {
            if ($node instanceof DOMText && trim($node->textContent) === '') {
                continue;
            }

            if ($node instanceof DOMComment) {
                continue;
            }

            return $node instanceof DOMElement
                && preg_match('/^h([2-6])$/', $node->nodeName, $next)
                && (int) $next[1] <= (int) substr($heading->nodeName, 1);
        }

        return true;
    }

    /**
     * The first following heading of the same or a higher level: where this heading's section ends.
     */
    private function sectionEnd(DOMElement $heading): ?DOMNode
    {
        $level = (int) substr($heading->nodeName, 1);

        for ($node = $heading->nextSibling; $node !== null; $node = $node->nextSibling) {
            if ($node instanceof DOMElement && preg_match('/^h([2-6])$/', $node->nodeName, $match) && (int) $match[1] <= $level) {
                return $node;
            }
        }

        return null;
    }

    /**
     * The page H1 is followed by H2s: when the article starts at a deeper
     * level (legacy content often used H3), shift its headings up so the
     * outline is sequential (H1 → H2 → H3).
     */
    private function normaliseHeadingLevels(DOMXPath $xpath, DOMElement $root): void
    {
        $headings = iterator_to_array($xpath->query('.//h2|.//h3|.//h4', $root));

        if ($headings === []) {
            return;
        }

        $shift = min(array_map(fn (DOMElement $heading): int => (int) substr($heading->nodeName, 1), $headings)) - 2;

        if ($shift <= 0) {
            return;
        }

        foreach ($headings as $heading) {
            $this->sanitizer->rename($heading, 'h'.((int) substr($heading->nodeName, 1) - $shift));
        }
    }

    /**
     * @return list<array{id: string, text: string, level: int}>
     */
    private function anchorHeadings(DOMXPath $xpath, DOMElement $root): array
    {
        $toc = [];
        $used = [];

        foreach ($xpath->query('.//h2|.//h3', $root) as $heading) {
            $text = trim(preg_replace('/\s+/u', ' ', $heading->textContent));
            $id = Str::slug(Str::limit($text, 60, '')) ?: 'section';
            $base = $id;
            $suffix = 2;

            while (isset($used[$id])) {
                $id = $base.'-'.$suffix++;
            }

            $used[$id] = true;
            $heading->setAttribute('id', $id);

            if (! preg_match('/^Day\s*-?\s*\d/i', $text) && ! str_contains($heading->getAttribute('class'), 'heading-note')) {
                $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) substr($heading->nodeName, 1)];
            }
        }

        return $toc;
    }

    /**
     * Wrap each "Day N" heading and its content in a timeline step.
     */
    private function timeline(DOMXPath $xpath, DOMElement $root): void
    {
        foreach (iterator_to_array($xpath->query('./h2|./h3|./h4', $root)) as $heading) {
            if (! preg_match('/^Day\s*-?\s*0?(\d{1,2})/i', trim($heading->textContent), $match)) {
                continue;
            }

            $level = (int) substr($heading->nodeName, 1);
            $step = $heading->ownerDocument->createElement('div');
            $step->setAttribute('class', 'itinerary-step');
            $step->setAttribute('data-day', $match[1]);
            $heading->parentNode->insertBefore($step, $heading);

            $node = $heading;
            while ($node !== null) {
                $next = $node->nextSibling;
                $step->appendChild($node);

                if ($next instanceof DOMElement && preg_match('/^h([2-4])$/', $next->nodeName, $nextLevel) && (int) $nextLevel[1] <= $level) {
                    break;
                }

                $node = $next;
            }
        }
    }

    private function tables(DOMXPath $xpath, DOMElement $root): void
    {
        foreach (iterator_to_array($xpath->query('.//table', $root)) as $table) {
            $wrapper = $table->ownerDocument->createElement('div');
            $wrapper->setAttribute('class', 'table-scroll');
            $wrapper->setAttribute('tabindex', '0');
            $wrapper->setAttribute('role', 'region');
            $wrapper->setAttribute('aria-label', 'Scrollable table');
            $table->parentNode->replaceChild($wrapper, $table);
            $wrapper->appendChild($table);
        }
    }

    private function embeds(DOMXPath $xpath, DOMElement $root): void
    {
        foreach (iterator_to_array($xpath->query('.//iframe', $root)) as $iframe) {
            $src = $iframe->getAttribute('src');
            $document = $iframe->ownerDocument;

            if (preg_match('~youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{11})~', $src, $match)) {
                $button = $document->createElement('button');
                $button->setAttribute('type', 'button');
                $button->setAttribute('class', 'yt-facade');
                $button->setAttribute('data-youtube', $match[1]);
                $button->setAttribute('aria-label', 'Play video');
                $button->setAttribute('style', "background-image:url('https://i.ytimg.com/vi/{$match[1]}/hqdefault.jpg')");
                $iframe->parentNode->replaceChild($button, $iframe);

                continue;
            }

            $iframe->setAttribute('loading', 'lazy');
            $iframe->setAttribute('title', $iframe->getAttribute('title') ?: 'Map');
            $iframe->setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
            $iframe->removeAttribute('width');
            $iframe->removeAttribute('height');

            $wrapper = $document->createElement('div');
            $wrapper->setAttribute('class', 'embed');
            $iframe->parentNode->replaceChild($wrapper, $iframe);
            $wrapper->appendChild($iframe);
        }
    }

    private function links(DOMXPath $xpath, DOMElement $root): void
    {
        foreach ($xpath->query('.//a[@href]', $root) as $link) {
            if (str_ends_with($link->getAttribute('href'), '/book-now.php') && trim($link->textContent) === 'Book this tour now') {
                $link->setAttribute('class', 'content-cta');
            }
        }
    }

    /**
     * Legacy listings (hotels, ashrams, temples, attractions) start each
     * item's paragraph with its photo: "<p><img><strong>Name</strong> :
     * description</p>". Show each item as a card – photo beside its name
     * and description – and group consecutive cards into one list.
     */
    private function mediaCards(DOMXPath $xpath, DOMElement $root): void
    {
        $paragraphs = iterator_to_array($xpath->query('.//p[not(ancestor::table) and not(ancestor::li) and not(ancestor::blockquote)]', $root));

        foreach ($paragraphs as $paragraph) {
            if ($paragraph->parentNode === null) {
                continue;
            }

            $nodes = $this->meaningfulChildren($paragraph);
            $photo = $nodes[0] ?? null;

            if (! $photo instanceof DOMElement || ! $this->isLonePhoto($photo)) {
                continue;
            }

            $details = array_slice($nodes, 1);
            $source = $paragraph;

            if ($details === []) {
                // The photo has a paragraph of its own: pair it with the next one when that starts with "Name : …".
                $next = $this->nextElement($paragraph);

                if ($next === null || $next->nodeName !== 'p' || $next->getElementsByTagName('img')->length > 0 || ! $this->startsWithName($this->meaningfulChildren($next))) {
                    continue;
                }

                $details = $this->meaningfulChildren($next);
                $source = $next;
            } elseif ($source->getElementsByTagName('img')->length > 1 || mb_strlen(trim(implode('', array_map(fn ($node) => $node->textContent, $details)))) < 30) {
                continue;
            }

            $card = $this->buildMediaCard($photo, $details);

            if ($source !== $paragraph) {
                $source->parentNode->removeChild($source);
            }

            $previous = $this->previousElement($paragraph);

            if ($previous !== null && $previous->getAttribute('class') === 'media-list') {
                $previous->appendChild($card);
                $paragraph->parentNode->removeChild($paragraph);
            } else {
                $list = $paragraph->ownerDocument->createElement('div');
                $list->setAttribute('class', 'media-list');
                $paragraph->parentNode->replaceChild($list, $paragraph);
                $list->appendChild($card);
            }
        }
    }

    /**
     * @param  list<DOMNode>  $details
     */
    private function buildMediaCard(DOMElement $photo, array $details): DOMElement
    {
        $document = $photo->ownerDocument;

        $card = $document->createElement('div');
        $card->setAttribute('class', 'media-card');

        $image = $document->createElement('div');
        $image->setAttribute('class', 'media-card-image');
        $image->appendChild($photo);
        $card->appendChild($image);

        foreach ($photo->nodeName === 'img' ? [$photo] : iterator_to_array($photo->getElementsByTagName('img')) as $img) {
            $img->setAttribute('data-sizes', '(min-width: 640px) 240px, 100vw');
        }

        $body = $document->createElement('div');
        $body->setAttribute('class', 'media-card-body');
        $card->appendChild($body);

        $link = null;

        if ($this->startsWithName($details)) {
            $title = $document->createElement('p');
            $title->setAttribute('class', 'media-card-title');
            $title->appendChild($details[0]);
            $body->appendChild($title);

            $separator = $details[1];
            $separator->nodeValue = ltrim(mb_substr(ltrim($separator->nodeValue), 1));
            $details = array_slice($details, 1);

            $href = $title->getElementsByTagName('a')->item(0)?->getAttribute('href');
            $link = $href !== null && str_starts_with($href, '/') ? $href : null;
        }

        $text = $document->createElement('p');
        foreach ($details as $node) {
            $text->appendChild($node);
        }

        while ($text->firstChild !== null && ($text->firstChild->nodeName === 'br' || ($text->firstChild instanceof DOMText && trim($text->firstChild->nodeValue) === ''))) {
            $text->removeChild($text->firstChild);
        }

        if (trim($text->textContent) !== '') {
            $body->appendChild($text);
        }

        if ($link !== null) {
            // Same destination as the name link, so it is hidden from keyboard and screen reader users.
            $more = $document->createElement('a', 'View details');
            $more->setAttribute('href', $link);
            $more->setAttribute('class', 'media-card-more');
            $more->setAttribute('tabindex', '-1');
            $more->setAttribute('aria-hidden', 'true');
            $body->appendChild($more);
        }

        return $card;
    }

    /**
     * An <img>, or a wrapper (usually a link) holding nothing but one image.
     */
    private function isLonePhoto(DOMElement $node): bool
    {
        if ($node->nodeName === 'img') {
            return true;
        }

        return in_array($node->nodeName, ['a', 'strong', 'b', 'span'], true)
            && $node->getElementsByTagName('img')->length === 1
            && trim($node->textContent) === '';
    }

    /**
     * Whether the nodes start with a short bold name followed by ":", "–",
     * "—" or "-", as in "<strong>Name</strong> : description".
     *
     * @param  list<DOMNode>  $nodes
     */
    private function startsWithName(array $nodes): bool
    {
        [$name, $separator] = [$nodes[0] ?? null, $nodes[1] ?? null];

        if (! $name instanceof DOMElement || ! in_array($name->nodeName, ['strong', 'b'], true) || ! $separator instanceof DOMText) {
            return false;
        }

        $length = mb_strlen(trim($name->textContent));

        return $length > 0 && $length <= 90 && preg_match('/^\s*[:\x{2013}\x{2014}\-]/u', $separator->nodeValue) === 1;
    }

    /**
     * Child nodes without the whitespace-only text between tags.
     *
     * @return list<DOMNode>
     */
    private function meaningfulChildren(DOMElement $element): array
    {
        return array_values(array_filter(
            iterator_to_array($element->childNodes),
            fn (DOMNode $node): bool => ! ($node instanceof DOMText && trim($node->nodeValue) === '') && ! $node instanceof DOMComment,
        ));
    }

    private function nextElement(DOMElement $element): ?DOMElement
    {
        for ($node = $element->nextSibling; $node !== null; $node = $node->nextSibling) {
            if ($node instanceof DOMElement) {
                return $node;
            }

            if (trim($node->textContent) !== '') {
                return null;
            }
        }

        return null;
    }

    private function previousElement(DOMElement $element): ?DOMElement
    {
        for ($node = $element->previousSibling; $node !== null; $node = $node->previousSibling) {
            if ($node instanceof DOMElement) {
                return $node;
            }

            if (trim($node->textContent) !== '') {
                return null;
            }
        }

        return null;
    }

    /**
     * Replace <img> elements with placeholders that are swapped for
     * <picture> markup after serialisation.
     *
     * @return array<string, string>
     */
    private function images(DOMXPath $xpath, DOMElement $root, string $altFallback): array
    {
        $placeholders = [];

        foreach (iterator_to_array($xpath->query('.//img', $root)) as $index => $img) {
            $src = $img->getAttribute('src');
            $alt = trim($img->getAttribute('alt'));

            if ($alt === '' || preg_match('/^[a-z0-9_\-]+$/i', $alt)) {
                $alt = $alt === '' ? $altFallback : Str::headline($alt);
            }

            $key = '<!--img'.$index.'-->';
            $placeholders[$key] = $this->media->picture($src, [
                'alt' => $alt,
                'title' => $img->getAttribute('title') ?: null,
                'width' => (int) $img->getAttribute('width') ?: null,
                'height' => (int) $img->getAttribute('height') ?: null,
                'sizes' => $img->getAttribute('data-sizes') ?: '(min-width: 1024px) 760px, 100vw',
            ]);

            $img->parentNode->replaceChild($img->ownerDocument->createComment('img'.$index), $img);
        }

        return $placeholders;
    }
}
