<?php

namespace App\Services\Scanner;

use DOMDocument;
use DOMXPath;

class HtmlSeoParser
{
    public function parse(string $html, string $url): array
    {
        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $title = $this->text($xpath, '//title');
        $description = $this->attr($xpath, '//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="description"]', 'content');
        $canonical = $this->attr($xpath, '//link[translate(@rel, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="canonical"]', 'href');
        $robots = $this->attr($xpath, '//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="robots"]', 'content');
        $viewport = $this->attr($xpath, '//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="viewport"]', 'content');
        $links = $xpath->query('//a[@href]');
        $images = $xpath->query('//img');
        $schema = $this->schemaData($xpath);
        $visibleText = $this->visibleText($document);
        $wordCount = str_word_count($visibleText);
        $htmlLength = max(1, strlen($html));
        $textLength = strlen($visibleText);
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
        $internal = 0;
        $external = 0;

        foreach ($links as $link) {
            $href = trim($link->attributes->getNamedItem('href')?->nodeValue ?? '');

            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }

            $linkHost = strtolower(parse_url($href, PHP_URL_HOST) ?: $host);
            $linkHost === $host ? $internal++ : $external++;
        }

        $missingAlt = 0;
        foreach ($images as $image) {
            $alt = $image->attributes->getNamedItem('alt')?->nodeValue;
            if ($alt === null || trim($alt) === '') {
                $missingAlt++;
            }
        }

        return [
            'title' => $title,
            'title_length' => mb_strlen($title),
            'meta_description' => $description,
            'meta_description_length' => mb_strlen($description),
            'h1_count' => $xpath->query('//h1')->length,
            'canonical' => $canonical,
            'robots_meta' => $robots,
            'viewport' => $viewport,
            'has_mobile_viewport' => filled($viewport),
            'internal_links_count' => $internal,
            'external_links_count' => $external,
            'images_count' => $images->length,
            'images_missing_alt_count' => $missingAlt,
            'open_graph' => [
                'og:title' => $this->metaProperty($xpath, 'og:title'),
                'og:description' => $this->metaProperty($xpath, 'og:description'),
                'og:image' => $this->metaProperty($xpath, 'og:image'),
                'og:url' => $this->metaProperty($xpath, 'og:url'),
                'og:type' => $this->metaProperty($xpath, 'og:type'),
            ],
            'twitter_card' => [
                'twitter:card' => $this->metaName($xpath, 'twitter:card'),
                'twitter:title' => $this->metaName($xpath, 'twitter:title'),
                'twitter:description' => $this->metaName($xpath, 'twitter:description'),
                'twitter:image' => $this->metaName($xpath, 'twitter:image'),
            ],
            'schema' => $schema,
            'content' => [
                'visible_word_count' => $wordCount,
                'thin_content' => $wordCount < 300,
                'content_html_ratio' => round(($textLength / $htmlLength) * 100, 2),
            ],
        ];
    }

    private function text(DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)->item(0);

        return trim($node?->textContent ?? '');
    }

    private function attr(DOMXPath $xpath, string $query, string $attribute): ?string
    {
        $node = $xpath->query($query)->item(0);
        $value = $node?->attributes?->getNamedItem($attribute)?->nodeValue;

        return $value ? trim($value) : null;
    }

    private function metaProperty(DOMXPath $xpath, string $property): ?string
    {
        return $this->attr($xpath, '//meta[translate(@property, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="'.strtolower($property).'"]', 'content');
    }

    private function metaName(DOMXPath $xpath, string $name): ?string
    {
        return $this->attr($xpath, '//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="'.strtolower($name).'"]', 'content');
    }

    private function schemaData(DOMXPath $xpath): array
    {
        $jsonLdBlocks = $xpath->query('//script[translate(@type, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="application/ld+json"]');
        $types = [];

        foreach ($jsonLdBlocks as $block) {
            $decoded = json_decode(trim($block->textContent), true);
            $types = array_merge($types, $this->schemaTypes($decoded));
        }

        return [
            'json_ld_count' => $jsonLdBlocks->length,
            'types' => array_values(array_unique(array_filter($types))),
            'has_microdata' => $xpath->query('//*[@itemscope or @itemtype or @itemprop]')->length > 0,
            'has_rdfa' => $xpath->query('//*[@typeof or @property or @vocab]')->length > 0,
        ];
    }

    private function schemaTypes(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $types = [];

        if (isset($data['@type'])) {
            $types = array_merge($types, (array) $data['@type']);
        }

        foreach (['@graph', 'graph', 'itemListElement'] as $key) {
            if (isset($data[$key])) {
                foreach ((array) $data[$key] as $item) {
                    $types = array_merge($types, $this->schemaTypes($item));
                }
            }
        }

        foreach ($data as $item) {
            if (is_array($item)) {
                $types = array_merge($types, $this->schemaTypes($item));
            }
        }

        return $types;
    }

    private function visibleText(DOMDocument $document): string
    {
        foreach (['script', 'style', 'noscript', 'svg'] as $tag) {
            while (($nodes = $document->getElementsByTagName($tag))->length > 0) {
                $node = $nodes->item(0);
                $node?->parentNode?->removeChild($node);
            }
        }

        return trim(preg_replace('/\s+/', ' ', $document->textContent));
    }
}
