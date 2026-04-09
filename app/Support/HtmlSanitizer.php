<?php

namespace App\Support;

/**
 * Focused HTML sanitizer for rich-text note bodies.
 *
 * Allows only a narrow whitelist of formatting tags/attributes
 * and strips everything else to prevent XSS while preserving
 * bold, italic, underline, strikethrough, font-size, and highlight colours.
 */
class HtmlSanitizer
{
    /**
     * Tags that are allowed to pass through.
     */
    private const ALLOWED_TAGS = [
        'b', 'strong', 'i', 'em', 'u', 's', 'del', 'strike',
        'span', 'br', 'p', 'div',
    ];

    /**
     * CSS properties allowed inside a style attribute (on <span> only).
     */
    private const ALLOWED_CSS_PROPERTIES = [
        'font-size',
        'background-color',
        'background',
        'color',
    ];

    /**
     * Sanitize an HTML string by stripping everything except
     * the whitelisted tags and safe style attributes.
     */
    public static function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        // Fast path: if input has no HTML at all, return as-is.
        if ($html === strip_tags($html)) {
            return $html;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');

        // Suppress warnings from malformed HTML.
        $previousUseErrors = libxml_use_internal_errors(true);

        // Wrap in a root element so DOMDocument doesn't add <html><body> wrappers.
        $wrapped = '<div>' . mb_encode_numericentity(
            $html,
            [0x80, 0x10FFFF, 0, ~0],
            'UTF-8'
        ) . '</div>';

        $dom->loadHTML(
            '<?xml encoding="UTF-8"><body>' . $wrapped . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );

        libxml_use_internal_errors($previousUseErrors);

        // Find our wrapper div.
        $body = $dom->getElementsByTagName('div')->item(0);

        if ($body === null) {
            return strip_tags($html);
        }

        self::walkAndSanitize($body);

        // Serialize the children of the wrapper.
        $output = '';
        foreach ($body->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        // Decode any numeric entities we previously encoded.
        $output = mb_decode_numericentity($output, [0x80, 0x10FFFF, 0, ~0], 'UTF-8');

        $trimmed = trim($output);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Recursively walk the DOM and strip disallowed nodes/attributes.
     */
    private static function walkAndSanitize(\DOMNode $node): void
    {
        $children = [];

        // Snapshot child nodes because we'll be mutating the tree.
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMText || $child instanceof \DOMDocumentType) {
                continue;
            }

            if (! ($child instanceof \DOMElement)) {
                // Remove comments and processing instructions.
                $node->removeChild($child);
                continue;
            }

            $tagName = strtolower($child->nodeName);

            if (! in_array($tagName, self::ALLOWED_TAGS, true)) {
                // Move children up and remove the disallowed element.
                self::unwrapNode($child);
                continue;
            }

            // Sanitize attributes.
            self::sanitizeAttributes($child);

            // Recurse into children.
            self::walkAndSanitize($child);
        }
    }

    /**
     * Replace a node with its children (effectively removing the tag but keeping content).
     */
    private static function unwrapNode(\DOMElement $node): void
    {
        $parent = $node->parentNode;

        if ($parent === null) {
            return;
        }

        // Move all children before the node.
        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }

    /**
     * Strip all attributes except whitelisted style properties on <span>.
     */
    private static function sanitizeAttributes(\DOMElement $element): void
    {
        $tagName = strtolower($element->nodeName);

        // Collect attribute names first (can't modify during iteration).
        $attributeNames = [];
        foreach ($element->attributes as $attr) {
            $attributeNames[] = $attr->nodeName;
        }

        foreach ($attributeNames as $attrName) {
            $attrNameLower = strtolower($attrName);

            // Only <span> may keep a sanitized `style`.
            if ($attrNameLower === 'style' && $tagName === 'span') {
                $sanitizedStyle = self::sanitizeCss($element->getAttribute($attrName));

                if ($sanitizedStyle !== '') {
                    $element->setAttribute('style', $sanitizedStyle);
                } else {
                    $element->removeAttribute($attrName);
                }

                continue;
            }

            // Remove any other attribute.
            $element->removeAttribute($attrName);
        }
    }

    /**
     * Parse a style string and keep only allowed CSS properties with safe values.
     */
    private static function sanitizeCss(string $style): string
    {
        $declarations = array_filter(array_map('trim', explode(';', $style)));
        $safe = [];

        foreach ($declarations as $declaration) {
            $parts = explode(':', $declaration, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $property = strtolower(trim($parts[0]));
            $value = trim($parts[1]);

            if (! in_array($property, self::ALLOWED_CSS_PROPERTIES, true)) {
                continue;
            }

            // Block javascript: URLs and expression().
            $valueLower = strtolower($value);
            if (
                str_contains($valueLower, 'javascript') ||
                str_contains($valueLower, 'expression') ||
                str_contains($valueLower, 'url(')
            ) {
                continue;
            }

            $safe[] = $property . ': ' . $value;
        }

        return implode('; ', $safe);
    }
}
