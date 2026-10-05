<?php

namespace App\Support\Content;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Normalises article HTML to a small, semantic allow-list so content renders
 * consistently in the new design and is safe to output. Used both when
 * importing legacy pages and when saving content from the admin panel.
 */
class HtmlSanitizer
{
    /**
     * Allowed elements and the attributes each may keep.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [],
        'h2' => ['id'], 'h3' => ['id'], 'h4' => ['id'],
        'strong' => [], 'em' => [], 'u' => [], 'sup' => [], 'sub' => [], 'small' => [], 'mark' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'ul' => [], 'ol' => ['start'], 'li' => [],
        'blockquote' => [], 'figure' => [], 'figcaption' => [],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'table' => [], 'caption' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan', 'scope'], 'td' => ['colspan', 'rowspan'],
        'iframe' => ['src', 'title', 'width', 'height', 'allowfullscreen'],
    ];

    /**
     * Elements renamed to a semantic equivalent.
     *
     * @var array<string, string>
     */
    private const RENAME = [
        'b' => 'strong', 'i' => 'em', 'h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4',
    ];

    /**
     * Elements removed together with everything inside them.
     *
     * @var list<string>
     */
    private const DROP = [
        'script', 'style', 'noscript', 'form', 'input', 'select', 'textarea', 'button', 'label',
        'object', 'embed', 'svg', 'canvas', 'link', 'meta', 'head', 'title', 'template',
    ];

    /**
     * Hosts allowed as iframe sources (maps and video).
     *
     * @var list<string>
     */
    private const IFRAME_HOSTS = [
        'www.google.com', 'google.com', 'maps.google.com', 'maps.google.co.in',
        'www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com',
    ];

    /**
     * Elements whose whitespace-only text children carry no meaning.
     *
     * @var list<string>
     */
    private const STRUCTURAL = ['ul', 'ol', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'div'];

    /**
     * Block-level elements (text at their edges is trimmed).
     *
     * @var list<string>
     */
    private const BLOCKS = ['p', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'blockquote', 'figure', 'div'];

    public function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = $this->load($html);
        $wrapper = $document->getElementById('__root');

        $this->cleanChildren($wrapper);
        $this->convertIconBulletLists($wrapper);
        $this->removeEmptyBlocks($wrapper);
        $this->collapseBreaks($wrapper);
        $this->normaliseWhitespace($wrapper);

        return $this->blocksHtml($wrapper);
    }

    public function load(string $html): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8"?><!DOCTYPE html><html><body><div id="__root">'.$html.'</div></body></html>',
            LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();

        return $document;
    }

    public function innerHtml(DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }

        return trim($html);
    }

    private function cleanChildren(DOMNode $parent): void
    {
        $child = $parent->firstChild;

        while ($child !== null) {
            $next = $child->nextSibling;

            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $parent->removeChild($child);
            } elseif ($child instanceof DOMElement) {
                $this->cleanElement($child);
            }

            $child = $next;
        }
    }

    private function cleanElement(DOMElement $element): void
    {
        $tag = strtolower($element->nodeName);

        if (in_array($tag, self::DROP, true)) {
            $element->parentNode->removeChild($element);

            return;
        }

        if (isset(self::RENAME[$tag])) {
            $element = $this->rename($element, self::RENAME[$tag]);
            $tag = self::RENAME[$tag];
        }

        if ($tag === 'iframe' && ! $this->isAllowedIframe($element->getAttribute('src'))) {
            $element->parentNode->removeChild($element);

            return;
        }

        if (! isset(self::ALLOWED[$tag])) {
            $this->cleanChildren($element);
            $this->unwrap($element);

            return;
        }

        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array($attribute->nodeName, self::ALLOWED[$tag], true)) {
                $element->removeAttribute($attribute->nodeName);
            }
        }

        if ($tag === 'a') {
            $this->cleanLink($element);
            if ($element->parentNode === null) {
                return;
            }
        }

        if ($tag === 'img' && trim($element->getAttribute('src')) === '') {
            $element->parentNode->removeChild($element);

            return;
        }

        $this->cleanChildren($element);
    }

    private function cleanLink(DOMElement $link): void
    {
        $href = self::cleanHref($link->getAttribute('href'));

        if ($href === '' || $href === '#' || str_starts_with(strtolower($href), 'javascript:')) {
            $this->cleanChildren($link);
            $this->unwrap($link);

            return;
        }

        $link->setAttribute('href', $href);

        if ($link->getAttribute('target') !== '' && $link->getAttribute('target') !== '_blank') {
            $link->removeAttribute('target');
        }

        if (preg_match('#^https?://#i', $href)) {
            $rel = array_filter(preg_split('/\s+/', strtolower($link->getAttribute('rel'))));
            $allowedRel = array_intersect($rel, ['nofollow', 'sponsored', 'ugc', 'noopener', 'noreferrer']);
            $allowedRel[] = 'noopener';
            $link->setAttribute('rel', implode(' ', array_unique($allowedRel)));
        } else {
            $link->removeAttribute('rel');
            $link->removeAttribute('target');
        }
    }

    /**
     * Drop stray line breaks/tabs (common in legacy markup) and encode spaces,
     * so "Cradle Of Life.docx" keeps pointing at the right file.
     */
    public static function cleanHref(string $href): string
    {
        return str_replace(' ', '%20', trim(preg_replace('/[\r\n\t]+/', '', $href)));
    }

    private function isAllowedIframe(string $src): bool
    {
        $host = strtolower((string) parse_url(trim($src), PHP_URL_HOST));

        return in_array($host, self::IFRAME_HOSTS, true);
    }

    /**
     * Legacy pages bullet lines with an icon shrunk by inline CSS
     * ("<img src=arrow.png> Name<br><img src=arrow.png> Name"). Without that
     * CSS the icon renders full size, so a paragraph where two or more lines
     * start with the same image becomes a real list without the icons.
     */
    private function convertIconBulletLists(DOMElement $root): void
    {
        $xpath = new DOMXPath($root->ownerDocument);

        foreach (iterator_to_array($xpath->query('.//p', $root)) as $paragraph) {
            $lines = $this->paragraphLines($paragraph);
            $bullet = $this->sharedLeadingImage($lines);

            if ($bullet === null || $xpath->query('.//img', $paragraph)->length !== count(array_filter($lines, fn (array $line): bool => $this->leadingImage($line)?->getAttribute('src') === $bullet))) {
                continue;
            }

            $list = $root->ownerDocument->createElement('ul');

            foreach ($lines as $line) {
                $icon = $this->leadingImage($line);
                $item = $root->ownerDocument->createElement('li');

                foreach ($line as $node) {
                    if ($node !== $icon) {
                        $item->appendChild($node);
                    }
                }

                $list->appendChild($item);
            }

            $paragraph->parentNode->replaceChild($list, $paragraph);
        }
    }

    /**
     * A paragraph's child nodes split at each <br>, without blank lines.
     *
     * @return list<list<DOMNode>>
     */
    private function paragraphLines(DOMElement $paragraph): array
    {
        $lines = [[]];

        foreach (iterator_to_array($paragraph->childNodes) as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'br') {
                $lines[] = [];
            } else {
                $lines[array_key_last($lines)][] = $child;
            }
        }

        return array_values(array_filter($lines, fn (array $line): bool => $this->leadingNode($line) !== null));
    }

    /**
     * The image used as a bullet when at least two lines, and at least half
     * of all lines, start with it.
     *
     * @param  list<list<DOMNode>>  $lines
     */
    private function sharedLeadingImage(array $lines): ?string
    {
        $sources = array_count_values(array_filter(array_map(
            fn (array $line): ?string => $this->leadingImage($line)?->getAttribute('src'),
            $lines,
        )));

        if ($sources === []) {
            return null;
        }

        arsort($sources);
        $source = (string) array_key_first($sources);

        return $sources[$source] >= 2 && count($lines) <= $sources[$source] * 2 ? $source : null;
    }

    /**
     * @param  list<DOMNode>  $line
     */
    private function leadingImage(array $line): ?DOMElement
    {
        $first = $this->leadingNode($line);

        return $first instanceof DOMElement && $first->nodeName === 'img' && trim(implode('', array_map(fn (DOMNode $node): string => $node->textContent, $line))) !== ''
            ? $first
            : null;
    }

    /**
     * @param  list<DOMNode>  $line
     */
    private function leadingNode(array $line): ?DOMNode
    {
        foreach ($line as $node) {
            if ($node instanceof DOMElement || trim(str_replace("\u{00A0}", ' ', $node->textContent)) !== '') {
                return $node;
            }
        }

        return null;
    }

    /**
     * Remove paragraphs, headings and list items that contain no text or media.
     */
    private function removeEmptyBlocks(DOMElement $root): void
    {
        $xpath = new DOMXPath($root->ownerDocument);

        do {
            $removed = 0;
            foreach ($xpath->query('.//p|.//h2|.//h3|.//h4|.//li|.//ul|.//ol|.//strong|.//em|.//blockquote|.//a|.//figure|.//table', $root) as $node) {
                if ($node->parentNode === null) {
                    continue;
                }

                $hasMedia = $xpath->query('.//img|.//iframe|.//table', $node)->length > 0;
                $text = trim(str_replace("\u{00A0}", ' ', $node->textContent));

                if ($text === '' && ! $hasMedia) {
                    $node->parentNode->removeChild($node);
                    $removed++;
                }
            }
        } while ($removed > 0);
    }

    /**
     * Collapse runs of <br> elements and strip leading/trailing ones in blocks.
     */
    private function collapseBreaks(DOMElement $root): void
    {
        $xpath = new DOMXPath($root->ownerDocument);

        foreach ($xpath->query('.//br', $root) as $br) {
            if ($br->parentNode === null) {
                continue;
            }

            $next = $br->nextSibling;
            while ($next !== null && $next->nodeType === XML_TEXT_NODE && trim($next->textContent) === '') {
                $next = $next->nextSibling;
            }

            if ($next instanceof DOMElement && $next->nodeName === 'br') {
                $sibling = $next->nextSibling;
                while ($sibling instanceof DOMElement && $sibling->nodeName === 'br') {
                    $following = $sibling->nextSibling;
                    $sibling->parentNode->removeChild($sibling);
                    $sibling = $following;
                }
            }
        }

        foreach ($xpath->query('.//p|.//li|.//td|.//h2|.//h3|.//h4', $root) as $block) {
            foreach (['firstChild', 'lastChild'] as $edge) {
                $node = $block->{$edge};
                while ($node !== null && ($node->nodeName === 'br' || ($node->nodeType === XML_TEXT_NODE && trim($node->textContent) === ''))) {
                    $remove = $node;
                    $node = $edge === 'firstChild' ? $node->nextSibling : $node->previousSibling;
                    $block->removeChild($remove);
                }
            }
        }

        foreach ($xpath->query('./br', $root) as $br) {
            $br->parentNode->removeChild($br);
        }
    }

    /**
     * Collapse source-code indentation inside text and drop whitespace-only
     * text between structural elements.
     */
    private function normaliseWhitespace(DOMElement $root): void
    {
        $xpath = new DOMXPath($root->ownerDocument);

        foreach (iterator_to_array($xpath->query('.//text()', $root)) as $text) {
            $collapsed = preg_replace('/[ \t\r\n]+/u', ' ', $text->nodeValue);
            $parent = $text->parentNode;
            $parentIsBlock = $parent instanceof DOMElement && (in_array($parent->nodeName, self::BLOCKS, true) || $parent->getAttribute('id') === '__root');

            if ($parentIsBlock && trim($collapsed) === '' && in_array($parent->nodeName, self::STRUCTURAL, true)) {
                $parent->removeChild($text);

                continue;
            }

            if ($parentIsBlock && $text->previousSibling === null) {
                $collapsed = ltrim($collapsed);
            }

            if ($parentIsBlock && $text->nextSibling === null) {
                $collapsed = rtrim($collapsed);
            }

            if ($collapsed === '') {
                $parent->removeChild($text);
            } else {
                $text->nodeValue = $collapsed;
            }
        }
    }

    /**
     * Serialise the root children one block per line, keeping a line break
     * between adjacent cells, rows, list items and blocks so tools that read
     * plain text (AI crawlers, feeds, search indexing) never glue words.
     */
    private function blocksHtml(DOMElement $root): string
    {
        $lines = [];
        foreach ($root->childNodes as $child) {
            $html = trim($root->ownerDocument->saveHTML($child));
            if ($html !== '') {
                $lines[] = $html;
            }
        }

        return preg_replace(
            '#</(td|th|tr|li|p|h[2-4]|thead|tbody|tfoot|caption|blockquote|figure|figcaption)>(?=<)#',
            "</\$1>\n",
            implode("\n", $lines),
        );
    }

    public function rename(DOMElement $element, string $tag): DOMElement
    {
        $replacement = $element->ownerDocument->createElement($tag);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $replacement->setAttribute($attribute->nodeName, $attribute->nodeValue);
        }

        while ($element->firstChild !== null) {
            $replacement->appendChild($element->firstChild);
        }

        $element->parentNode->replaceChild($replacement, $element);

        return $replacement;
    }

    public function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        $isBlock = in_array(strtolower($element->nodeName), ['div', 'section', 'article', 'center', 'main', 'aside', 'header', 'footer', 'nav', 'dl', 'dd', 'dt'], true);

        if ($isBlock && $this->hasInlineContent($element) && ! $this->insideParagraph($element)) {
            $paragraph = $element->ownerDocument->createElement('p');
            while ($element->firstChild !== null) {
                $paragraph->appendChild($element->firstChild);
            }
            $parent->replaceChild($paragraph, $element);

            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    /**
     * True when a block wrapper directly holds text/inline nodes (but no block
     * children), so unwrapping it would leave loose text in the article.
     */
    private function hasInlineContent(DOMElement $element): bool
    {
        $hasText = false;

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && in_array($child->nodeName, ['p', 'h2', 'h3', 'h4', 'ul', 'ol', 'table', 'blockquote', 'figure', 'div', 'section', 'iframe', 'hr'], true)) {
                return false;
            }

            if (trim($child->textContent) !== '') {
                $hasText = true;
            }
        }

        return $hasText;
    }

    private function insideParagraph(DOMElement $element): bool
    {
        for ($node = $element->parentNode; $node !== null; $node = $node->parentNode) {
            if (in_array($node->nodeName, ['p', 'li', 'td', 'th', 'h2', 'h3', 'h4', 'a', 'strong', 'em'], true)) {
                return true;
            }
        }

        return false;
    }
}
