<?php

namespace App\Services\Scanner;

class PageTypeDetector
{
    private const SIGNALS = [
        'Ecommerce' => ['cart', 'checkout', 'product', 'collection', 'add to cart', 'shopify', 'woocommerce', 'price', 'size', 'variant', 'shipping', 'returns'],
        'Service Business' => ['services', 'solutions', 'consultation', 'agency', 'quote', 'request a call', 'book a call', 'get a proposal'],
        'Local Business' => ['near me', 'address', 'directions', 'local', 'hours', 'location', 'call now', 'map', 'appointment'],
        'SaaS' => ['pricing', 'features', 'dashboard', 'integration', 'api', 'trial', 'demo', 'subscription', 'software'],
        'Blog / Article' => ['author', 'published', 'article', 'blog', 'post', 'category', 'read more', 'updated on'],
        'Publisher' => ['news', 'editorial', 'journalist', 'magazine', 'publication', 'subscribe', 'newsletter'],
        'Corporate / Brand' => ['about us', 'leadership', 'investors', 'careers', 'press', 'company', 'mission', 'team'],
        'Marketplace' => ['seller', 'vendor', 'marketplace', 'list your', 'buyers', 'categories', 'compare sellers'],
    ];

    public function detect(array $data): array
    {
        $schemaTypes = array_map('strtolower', (array) data_get($data, 'schema.types', []));
        $links = collect((array) ($data['links'] ?? []))
            ->map(fn ($link) => trim((string) ($link['text'] ?? '').' '.(string) ($link['href'] ?? '')))
            ->implode(' ');

        $haystack = strtolower(trim(implode(' ', array_filter([
            $data['url'] ?? '',
            $data['title'] ?? '',
            $data['meta_description'] ?? '',
            implode(' ', (array) ($data['headings'] ?? [])),
            data_get($data, 'content.visible_text', ''),
            $links,
            implode(' ', $schemaTypes),
        ]))));

        $scores = [];
        $signals = [];

        foreach (self::SIGNALS as $type => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    $scores[$type] = ($scores[$type] ?? 0) + $this->weight($type, $needle, $schemaTypes);
                    $signals[$type][] = $needle;
                }
            }
        }

        foreach ($schemaTypes as $schemaType) {
            $schemaMap = [
                'product' => 'Ecommerce',
                'offer' => 'Ecommerce',
                'localbusiness' => 'Local Business',
                'professionalservice' => 'Service Business',
                'softwareapplication' => 'SaaS',
                'article' => 'Blog / Article',
                'blogposting' => 'Blog / Article',
                'newsarticle' => 'Publisher',
                'organization' => 'Corporate / Brand',
            ];

            if (isset($schemaMap[$schemaType])) {
                $type = $schemaMap[$schemaType];
                $scores[$type] = ($scores[$type] ?? 0) + 3;
                $signals[$type][] = $schemaType.' schema';
            }
        }

        arsort($scores);
        $type = array_key_first($scores) ?: 'Unknown';
        $score = (int) ($scores[$type] ?? 0);

        return [
            'type' => $type,
            'confidence' => $score >= 12 ? 'High' : ($score >= 6 ? 'Medium' : ($score > 0 ? 'Low' : 'Low')),
            'score' => $score,
            'signals' => array_values(array_unique($signals[$type] ?? [])),
            'scores' => $scores,
        ];
    }

    private function weight(string $type, string $needle, array $schemaTypes): int
    {
        if (in_array($needle, ['shopify', 'woocommerce', 'add to cart', 'checkout', 'product', 'pricing', 'trial', 'api'], true)) {
            return 3;
        }

        if (str_contains($needle, 'schema') || in_array(strtolower($needle), $schemaTypes, true)) {
            return 3;
        }

        return in_array($type, ['Ecommerce', 'SaaS', 'Blog / Article'], true) ? 2 : 1;
    }
}
