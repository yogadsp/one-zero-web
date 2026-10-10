<?php

namespace App\Services\Content;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class RichTextSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'b', 'blockquote', 'br', 'code', 'em', 'h2', 'h3', 'hr', 'i', 'img',
        'li', 'ol', 'p', 'pre', 's', 'strike', 'strong', 'table', 'tbody', 'td',
        'th', 'thead', 'tr', 'u', 'ul',
    ];

    private const DROP_WITH_CONTENT = [
        'embed', 'form', 'iframe', 'input', 'object', 'script', 'style', 'svg',
    ];

    public function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="rich-text-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = (new DOMXPath($document))->query('//*[@id="rich-text-root"]')->item(0);
        if (! $root instanceof DOMElement) {
            return '';
        }

        $this->cleanChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($node);

                continue;
            }

            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($node);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->cleanChildren($node);
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);

                continue;
            }

            $this->cleanAttributes($node, $tag);
            $this->cleanChildren($node);
        }
    }

    private function cleanAttributes(DOMElement $element, string $tag): void
    {
        $allowed = match ($tag) {
            'a' => ['href', 'rel', 'target', 'title'],
            'img' => ['alt', 'src', 'title'],
            'td', 'th' => ['colspan', 'rowspan'],
            'h2', 'h3', 'p' => ['style'],
            default => [],
        };

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            if (! in_array($name, $allowed, true)) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if (($name === 'href' && ! $this->isSafeHref($attribute->value))
                || ($name === 'src' && ! $this->isSafeImageSource($attribute->value))) {
                $element->removeAttribute($name);

                continue;
            }

            if ($name === 'style' && ! preg_match('/^\s*text-align\s*:\s*(left|center|right|justify)\s*;?\s*$/i', $attribute->value)) {
                $element->removeAttribute('style');
            }
        }

        if ($tag === 'a' && $element->hasAttribute('target')) {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function isSafeHref(string $value): bool
    {
        return preg_match('/^(https?:\/\/|mailto:|\/)/i', trim($value)) === 1;
    }

    private function isSafeImageSource(string $value): bool
    {
        return preg_match('/^(https?:\/\/|\/storage\/)/i', trim($value)) === 1;
    }
}
