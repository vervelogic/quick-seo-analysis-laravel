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
        $canonical = $this->canonical($xpath, $url);
        $robots = $this->attr($xpath, '//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="robots"]', 'content');
        $viewport = $this->attr($xpath, '//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="viewport"]', 'content');
        $links = $xpath->query('//a[@href]');
        $images = $xpath->query('//img');
        $schema = $this->schemaData($xpath);
        $footerText = $this->footerText($xpath);
        $visibleText = $this->visibleText($document);
        $wordCount = str_word_count($visibleText);
        $linksData = [];
        $headings = [];
        $headingLevels = ['h1' => [], 'h2' => [], 'h3' => []];
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

            $absoluteHref = $this->resolveUrl($url, $href);
            $linkHost = strtolower(parse_url($absoluteHref, PHP_URL_HOST) ?: $host);
            $isInternal = $linkHost === $host;
            $isInternal ? $internal++ : $external++;

            $linksData[] = [
                'href' => $absoluteHref,
                'text' => trim(preg_replace('/\s+/', ' ', $link->textContent)),
                'host' => $linkHost,
                'internal' => $isInternal,
            ];
        }

        foreach (['h1', 'h2', 'h3'] as $tag) {
            foreach ($xpath->query('//'.$tag) as $heading) {
                $headingText = trim(preg_replace('/\s+/', ' ', $heading->textContent));

                if ($headingText !== '') {
                    $headings[] = $headingText;
                    $headingLevels[$tag][] = $headingText;
                }
            }
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
            'links' => $linksData,
            'headings' => array_values(array_filter($headings)),
            'heading_levels' => $headingLevels,
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
                'unique_word_count' => $this->uniqueWordCount($visibleText),
                'thin_content' => $wordCount < 300,
                'content_html_ratio' => round(($textLength / $htmlLength) * 100, 2),
                'questions' => $this->questions($visibleText, $headings),
                'entities' => $this->entities($visibleText),
                'footer_text' => mb_substr($footerText, 0, 4000),
                'visible_text' => mb_substr($visibleText, 0, 12000),
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

    private function canonical(DOMXPath $xpath, string $url): ?string
    {
        $node = $xpath->query('//link[contains(concat(" ", normalize-space(translate(@rel, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")), " "), " canonical ")]')->item(0);
        $href = $node?->attributes?->getNamedItem('href')?->nodeValue;

        if (! $href) {
            return null;
        }

        return $this->resolveUrl($url, trim($href));
    }

    private function resolveUrl(string $currentUrl, string $candidate): string
    {
        if (preg_match('/^https?:\/\//i', $candidate)) {
            return $candidate;
        }

        $current = parse_url($currentUrl);
        $scheme = $current['scheme'] ?? 'https';
        $host = $current['host'] ?? '';
        $port = isset($current['port']) ? ':'.$current['port'] : '';

        if (str_starts_with($candidate, '//')) {
            return $scheme.':'.$candidate;
        }

        if (str_starts_with($candidate, '/')) {
            return $scheme.'://'.$host.$port.$candidate;
        }

        $path = $current['path'] ?? '/';
        $basePath = str_ends_with($path, '/') ? $path : dirname($path).'/';

        return $scheme.'://'.$host.$port.$basePath.$candidate;
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
        $details = [
            'organization' => false,
            'website' => false,
            'faqpage' => false,
            'review' => false,
            'aggregaterating' => false,
            'localbusiness' => false,
            'searchaction' => false,
            'contactpoint' => false,
            'sameas_count' => 0,
        ];

        foreach ($jsonLdBlocks as $block) {
            $decoded = json_decode(trim($block->textContent), true);
            $types = array_merge($types, $this->schemaTypes($decoded));
            $details = $this->mergeSchemaDetails($details, $this->schemaDetails($decoded));
        }

        return [
            'json_ld_count' => $jsonLdBlocks->length,
            'types' => array_values(array_unique(array_filter($types))),
            'has_microdata' => $xpath->query('//*[@itemscope or @itemtype or @itemprop]')->length > 0,
            'has_rdfa' => $xpath->query('//*[@typeof or @vocab or starts-with(@property, "schema:")]')->length > 0,
            'details' => $details,
        ];
    }

    private function mergeSchemaDetails(array $carry, array $new): array
    {
        foreach ($new as $key => $value) {
            if (is_bool($value)) {
                $carry[$key] = $carry[$key] || $value;
                continue;
            }

            if (is_int($value)) {
                $carry[$key] = max((int) ($carry[$key] ?? 0), $value);
            }
        }

        return $carry;
    }

    private function schemaDetails(mixed $data): array
    {
        $details = [
            'organization' => false,
            'website' => false,
            'faqpage' => false,
            'review' => false,
            'aggregaterating' => false,
            'localbusiness' => false,
            'searchaction' => false,
            'contactpoint' => false,
            'sameas_count' => 0,
        ];

        if (! is_array($data)) {
            return $details;
        }

        $types = array_map('strtolower', (array) ($data['@type'] ?? []));
        $details['organization'] = in_array('organization', $types, true) || in_array('corporation', $types, true) || in_array('professionalservice', $types, true);
        $details['website'] = in_array('website', $types, true);
        $details['faqpage'] = in_array('faqpage', $types, true);
        $details['review'] = in_array('review', $types, true);
        $details['aggregaterating'] = in_array('aggregaterating', $types, true);
        $details['localbusiness'] = in_array('localbusiness', $types, true);
        $details['searchaction'] = in_array('searchaction', $types, true);
        $details['contactpoint'] = in_array('contactpoint', $types, true) || isset($data['contactPoint']);
        $details['sameas_count'] = count((array) ($data['sameAs'] ?? []));

        foreach (['@graph', 'graph', 'itemListElement'] as $key) {
            if (! isset($data[$key])) {
                continue;
            }

            foreach ((array) $data[$key] as $item) {
                $details = $this->mergeSchemaDetails($details, $this->schemaDetails($item));
            }
        }

        foreach ($data as $item) {
            if (is_array($item)) {
                $details = $this->mergeSchemaDetails($details, $this->schemaDetails($item));
            }
        }

        return $details;
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

    private function footerText(DOMXPath $xpath): string
    {
        $parts = [];

        foreach ($xpath->query('//footer') as $footer) {
            $parts[] = trim(preg_replace('/\s+/', ' ', $footer->textContent));
        }

        return trim(implode(' ', array_filter($parts)));
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

    private function uniqueWordCount(string $text): int
    {
        preg_match_all('/[a-zA-Z][a-zA-Z0-9-]{2,}/', strtolower($text), $matches);

        return count(array_unique($matches[0] ?? []));
    }

    private function questions(string $text, array $headings): array
    {
        preg_match_all('/[^.!?]*\?/', $text, $matches);

        return array_values(array_slice(array_unique(array_filter(array_map(
            fn (string $question): string => trim(preg_replace('/\s+/', ' ', $question)),
            array_merge($matches[0] ?? [], array_filter($headings, fn ($heading) => str_contains($heading, '?')))
        ))), 0, 20));
    }

    private function entities(string $text): array
    {
        preg_match_all('/\b[A-Z][a-zA-Z0-9&.-]*(?:\s+[A-Z][a-zA-Z0-9&.-]*){0,3}\b/', $text, $matches);

        $entities = array_filter($matches[0] ?? [], fn (string $entity) => mb_strlen($entity) >= 3);

        return array_values(array_slice(array_unique($entities), 0, 40));
    }
}
