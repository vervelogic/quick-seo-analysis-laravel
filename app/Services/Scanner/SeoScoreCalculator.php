<?php

namespace App\Services\Scanner;

class SeoScoreCalculator
{
    public function calculate(array $data): array
    {
        $checks = [
            'reachable' => [
                'label' => 'URL reachable',
                'passed' => (bool) $data['is_reachable'],
                'weight' => 18,
            ],
            'https' => [
                'label' => 'HTTPS enabled',
                'passed' => (bool) $data['uses_https'],
                'weight' => 10,
            ],
            'status' => [
                'label' => 'Healthy HTTP status',
                'passed' => in_array($data['http_status'], [200, 201, 202, 204], true),
                'weight' => 10,
            ],
            'title' => [
                'label' => 'Title tag present',
                'passed' => $data['title_length'] >= 20 && $data['title_length'] <= 65,
                'weight' => 12,
            ],
            'description' => [
                'label' => 'Meta description present',
                'passed' => $data['meta_description_length'] >= 70 && $data['meta_description_length'] <= 170,
                'weight' => 12,
            ],
            'h1' => [
                'label' => 'Single H1',
                'passed' => $data['h1_count'] === 1,
                'weight' => 9,
            ],
            'canonical' => [
                'label' => 'Canonical tag present',
                'passed' => trim((string) ($data['canonical'] ?? '')) !== '',
                'weight' => 8,
            ],
            'images_alt' => [
                'label' => 'Images include alt text',
                'passed' => $data['images_missing_alt_count'] === 0,
                'weight' => 8,
            ],
            'performance' => [
                'label' => 'Fast response',
                'passed' => $data['response_time_ms'] <= 1500 && $data['page_size_bytes'] <= 1024 * 1024,
                'weight' => 8,
            ],
            'links' => [
                'label' => 'Internal links found',
                'passed' => $data['internal_links_count'] > 0,
                'weight' => 5,
            ],
        ];

        $score = collect($checks)
            ->filter(fn (array $check) => $check['passed'])
            ->sum('weight');

        return [
            'score' => min(100, (int) $score),
            'checks' => $checks,
            'recommendations' => $this->recommendations($data, $checks),
        ];
    }

    private function recommendations(array $data, array $checks): array
    {
        $items = [];

        foreach ($checks as $key => $check) {
            if ($check['passed']) {
                continue;
            }

            $items[] = match ($key) {
                'reachable' => 'Make sure the page is publicly reachable and not blocking common HTTP clients.',
                'https' => 'Serve the page over HTTPS and redirect HTTP traffic to the secure version.',
                'status' => 'Return a successful 2xx HTTP status for the primary page URL.',
                'title' => 'Add a focused title tag around 20-65 characters.',
                'description' => 'Write a clear meta description around 70-170 characters.',
                'h1' => 'Use exactly one descriptive H1 on the page.',
                'canonical' => 'Add a canonical URL to reduce duplicate-content ambiguity.',
                'images_alt' => 'Add descriptive alt text to images that communicate content.',
                'performance' => 'Improve response time and reduce page weight where possible.',
                'links' => 'Add useful internal links so crawlers and visitors can discover more pages.',
                default => 'Review this SEO signal and improve it where possible.',
            };
        }

        if (($data['robots_meta'] ?? null) && str_contains(strtolower($data['robots_meta']), 'noindex')) {
            $items[] = 'The robots meta tag contains noindex. Remove it if this page should appear in search.';
        }

        return array_values(array_unique($items));
    }
}
