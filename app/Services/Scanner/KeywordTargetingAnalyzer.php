<?php

namespace App\Services\Scanner;

class KeywordTargetingAnalyzer
{
    private const STOP_WORDS = [
        'about', 'after', 'again', 'against', 'also', 'and', 'are', 'best', 'but', 'can', 'for', 'from', 'has', 'have', 'how', 'into', 'its', 'more', 'not', 'our', 'page', 'than', 'that', 'the', 'their', 'this', 'through', 'to', 'was', 'what', 'when', 'where', 'which', 'why', 'with', 'you', 'your', 'near', 'top', 'get', 'all', 'any', 'new', 'one', 'two', 'who', 'will', 'now', 'home',
    ];

    private const SERVICE_MODIFIERS = [
        'agency', 'audit', 'company', 'consultant', 'consulting', 'expert', 'experts', 'firm', 'management', 'marketing', 'optimization', 'provider', 'service', 'services', 'solution', 'solutions', 'strategy',
    ];

    private const LOCATIONS = [
        'india', 'jaipur', 'delhi', 'new delhi', 'mumbai', 'bangalore', 'bengaluru', 'pune', 'hyderabad', 'chennai', 'ahmedabad', 'gurgaon', 'gurugram', 'noida', 'kolkata', 'surat', 'dubai', 'london', 'usa', 'united states', 'uk', 'canada', 'australia',
    ];

    private const INTENT_WORDS = [
        'Commercial' => ['agency', 'company', 'consultant', 'consulting', 'expert', 'hire', 'provider', 'service', 'services', 'solution', 'solutions'],
        'Transactional' => ['buy', 'book', 'demo', 'download', 'free', 'get', 'order', 'package', 'packages', 'pricing', 'quote'],
        'Informational' => ['guide', 'how', 'learn', 'tips', 'what', 'why', 'tutorial', 'example', 'examples'],
        'Local' => ['near', 'jaipur', 'india', 'delhi', 'mumbai', 'bangalore', 'pune', 'dubai', 'london'],
    ];

    public function analyze(array $data): array
    {
        $title = (string) ($data['title'] ?? '');
        $meta = (string) ($data['meta_description'] ?? '');
        $url = (string) ($data['url'] ?? '');
        $h1 = array_values((array) data_get($data, 'heading_levels.h1', []));
        $h2 = array_values((array) data_get($data, 'heading_levels.h2', []));
        $h3 = array_values((array) data_get($data, 'heading_levels.h3', []));
        $headings = array_values(array_filter(array_merge($h1, $h2, $h3)));
        $content = (string) data_get($data, 'content.visible_text', '');
        $links = (array) ($data['links'] ?? []);
        $schemaTypes = (array) data_get($data, 'schema.types', []);

        $sources = [
            'title' => $title,
            'meta_description' => $meta,
            'h1' => implode(' ', $h1),
            'h2_h3' => implode(' ', array_merge($h2, $h3)),
            'url' => $this->urlWords($url),
            'body' => $content,
            'internal_anchors' => implode(' ', array_map(fn ($link) => (string) ($link['text'] ?? ''), $links)),
            'schema' => implode(' ', $schemaTypes),
        ];

        $candidates = $this->scoreCandidates($sources);
        $primary = $candidates[0] ?? null;
        $supporting = array_slice($candidates, 1, 12);
        $locations = $this->detectLocations(implode(' ', $sources));
        $services = $this->detectServices($candidates);
        $intent = $this->detectIntent(($primary['keyword'] ?? '').' '.implode(' ', $sources));
        $contentSupport = $this->contentSupportScore($primary['keyword'] ?? '', $title, $meta, $headings, $content, $url);
        $opportunities = $this->keywordOpportunities($primary['keyword'] ?? '', $services, $locations, $intent, $candidates);

        return [
            'primary_target_keyword' => $primary ? array_merge($primary, [
                'intent' => $intent,
                'content_support_score' => $contentSupport,
            ]) : null,
            'supporting_keywords' => $supporting,
            'keyword_opportunities' => $opportunities,
            'keyword_intent' => $intent,
            'content_support_score' => $contentSupport,
            'detected_locations' => $locations,
            'detected_services' => $services,
            'evidence' => [
                'title' => $title,
                'meta_description' => $meta,
                'h1' => $h1,
                'h2' => array_slice($h2, 0, 8),
                'url_terms' => $this->urlWords($url),
                'schema_types' => $schemaTypes,
            ],
            'summary' => $primary
                ? 'This page appears to primarily target "'.$primary['keyword'].'" with '.$primary['confidence'].'% confidence.'
                : 'Not enough keyword evidence was found in the current page HTML.',
        ];
    }

    private function scoreCandidates(array $sources): array
    {
        $scores = [];

        foreach ($sources as $source => $text) {
            foreach ($this->phrases((string) $text) as $phrase) {
                $score = $this->sourceWeight($source) + $this->phraseQualityBoost($phrase);
                $key = strtolower($phrase);

                $scores[$key] ??= [
                    'keyword' => str($phrase)->headline()->toString(),
                    'score' => 0,
                    'sources' => [],
                    'frequency' => 0,
                ];

                $scores[$key]['score'] += $score;
                $scores[$key]['frequency']++;
                $scores[$key]['sources'][] = $source;
            }
        }

        $items = array_values(array_filter($scores, fn ($item) => $this->isUsefulCandidate($item['keyword'])));

        foreach ($items as &$item) {
            $item['sources'] = array_values(array_unique($item['sources']));
            $item['confidence'] = max(40, min(96, (int) round($item['score'] + min(18, $item['frequency'] * 2))));
            $item['evidence'] = $this->evidenceLabel($item['sources'], $item['frequency']);
            unset($item['score']);
        }

        usort($items, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);

        return array_values(array_slice($this->dedupeSimilar($items), 0, 30));
    }

    private function phrases(string $text): array
    {
        $normalized = strtolower((string) preg_replace('/[^a-zA-Z0-9+#.\s-]/', ' ', $text));
        $words = array_values(array_filter(preg_split('/\s+/', $normalized) ?: [], function ($word) {
            return strlen($word) > 1 && ! in_array($word, self::STOP_WORDS, true) && ! is_numeric($word);
        }));

        $phrases = [];
        for ($size = 2; $size <= 4; $size++) {
            for ($i = 0; $i <= count($words) - $size; $i++) {
                $phrase = trim(implode(' ', array_slice($words, $i, $size)));

                if ($this->isUsefulCandidate($phrase)) {
                    $phrases[] = $phrase;
                }
            }
        }

        return $phrases;
    }

    private function sourceWeight(string $source): int
    {
        return match ($source) {
            'title' => 28,
            'h1' => 24,
            'url' => 18,
            'meta_description' => 15,
            'h2_h3' => 12,
            'schema' => 10,
            'internal_anchors' => 8,
            default => 4,
        };
    }

    private function phraseQualityBoost(string $phrase): int
    {
        $boost = 0;
        $lower = strtolower($phrase);

        if ($this->containsAny($lower, self::SERVICE_MODIFIERS)) {
            $boost += 10;
        }

        if ($this->containsAny($lower, self::LOCATIONS)) {
            $boost += 8;
        }

        if (str_contains($lower, 'seo') || str_contains($lower, 'ai') || str_contains($lower, 'marketing')) {
            $boost += 6;
        }

        return $boost;
    }

    private function isUsefulCandidate(string $phrase): bool
    {
        $phrase = strtolower(trim($phrase));
        $words = preg_split('/\s+/', $phrase) ?: [];

        if (count($words) < 2 || count($words) > 5 || strlen($phrase) < 5) {
            return false;
        }

        if (count(array_unique($words)) === 1) {
            return false;
        }

        return ! $this->containsAny($phrase, ['click here', 'read more', 'learn more', 'view all', 'privacy policy', 'terms conditions']);
    }

    private function evidenceLabel(array $sources, int $frequency): string
    {
        if (in_array('title', $sources, true) && in_array('h1', $sources, true)) {
            return 'Strong title and H1 alignment, with '.$frequency.' supporting mentions.';
        }

        if (in_array('title', $sources, true) || in_array('h1', $sources, true)) {
            return 'Detected in a high-priority page element with '.$frequency.' supporting mentions.';
        }

        return 'Inferred from repeated headings, body copy, URL, links or schema signals.';
    }

    private function contentSupportScore(string $keyword, string $title, string $meta, array $headings, string $content, string $url): int
    {
        if ($keyword === '') {
            return 0;
        }

        $needle = strtolower($keyword);
        $score = 0;
        $score += str_contains(strtolower($title), $needle) ? 25 : 0;
        $score += str_contains(strtolower($meta), $needle) ? 15 : 0;
        $score += str_contains(strtolower(implode(' ', $headings)), $needle) ? 25 : 0;
        $score += str_contains(strtolower($this->urlWords($url)), $needle) ? 10 : 0;
        $score += min(25, substr_count(strtolower($content), $needle) * 5);

        return min(100, $score);
    }

    private function keywordOpportunities(string $primary, array $services, array $locations, string $intent, array $candidates): array
    {
        $existing = array_map(fn ($item) => strtolower($item['keyword'] ?? ''), $candidates);
        $ideas = [];
        $base = $services ?: ($primary ? [$primary] : []);

        foreach ($base as $service) {
            foreach ($locations ?: [''] as $location) {
                $location = trim((string) $location);
                $ideas[] = trim($service.' '.$location);
                $ideas[] = trim('best '.$service.' '.$location);
                $ideas[] = trim($service.' cost '.$location);
                $ideas[] = trim($service.' pricing '.$location);
                $ideas[] = trim($service.' agency '.$location);
            }
        }

        return array_values(array_slice(array_filter(array_unique($ideas), function ($idea) use ($existing) {
            $idea = strtolower(trim($idea));

            return $idea !== '' && ! in_array($idea, $existing, true) && $this->isUsefulCandidate($idea);
        }), 0, 14));
    }

    private function detectIntent(string $text): string
    {
        $lower = strtolower($text);
        $scores = [];

        foreach (self::INTENT_WORDS as $intent => $words) {
            $scores[$intent] = 0;
            foreach ($words as $word) {
                $scores[$intent] += substr_count($lower, $word);
            }
        }

        arsort($scores);

        return array_key_first($scores) ?: 'Informational';
    }

    private function detectLocations(string $text): array
    {
        $locations = [];
        $lower = strtolower($text);

        foreach (self::LOCATIONS as $location) {
            if (str_contains($lower, $location)) {
                $locations[] = str($location)->headline()->toString();
            }
        }

        return array_values(array_unique(array_slice($locations, 0, 8)));
    }

    private function detectServices(array $candidates): array
    {
        return array_values(array_slice(array_unique(array_map(
            fn ($item) => $item['keyword'],
            array_filter($candidates, fn ($item) => $this->containsAny(strtolower($item['keyword']), self::SERVICE_MODIFIERS))
        )), 0, 8));
    }

    private function urlWords(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';

        return trim((string) preg_replace('/\s+/', ' ', str_replace(['/', '-', '_'], ' ', $path)));
    }

    private function dedupeSimilar(array $items): array
    {
        $seen = [];

        return array_values(array_filter($items, function ($item) use (&$seen) {
            $key = strtolower((string) $item['keyword']);
            $compact = preg_replace('/[^a-z0-9]/', '', $key);

            if (isset($seen[$compact])) {
                return false;
            }

            $seen[$compact] = true;

            return true;
        }));
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, strtolower((string) $needle))) {
                return true;
            }
        }

        return false;
    }
}
