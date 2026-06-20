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
        $links = $xpath->query('//a[@href]');
        $images = $xpath->query('//img');
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
            'internal_links_count' => $internal,
            'external_links_count' => $external,
            'images_count' => $images->length,
            'images_missing_alt_count' => $missingAlt,
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
}
