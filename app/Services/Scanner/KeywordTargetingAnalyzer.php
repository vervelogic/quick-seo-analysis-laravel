<?php

namespace App\Services\Scanner;

class KeywordTargetingAnalyzer
{
    private const STOP_WORDS = [
        'about', 'after', 'again', 'against', 'also', 'and', 'are', 'best', 'but', 'can', 'for', 'from', 'has', 'have', 'how', 'into', 'its', 'more', 'not', 'our', 'page', 'than', 'that', 'the', 'their', 'this', 'through', 'to', 'was', 'what', 'when', 'where', 'which', 'why', 'with', 'you', 'your', 'near', 'top', 'get', 'all', 'any', 'new', 'one', 'two', 'who', 'will', 'now', 'home', 'read', 'learn', 'click',
    ];

    private const SERVICE_MODIFIERS = [
        'agency', 'audit', 'company', 'consultant', 'consulting', 'expert', 'experts', 'firm', 'management', 'marketing', 'optimization', 'provider', 'service', 'services', 'solution', 'solutions', 'strategy', 'package', 'packages',
    ];

    private const COMMERCIAL_MODIFIERS = [
        'cost', 'price', 'pricing', 'package', 'packages', 'booking', 'book', 'family', 'luxury', 'vip', 'service', 'services', 'company', 'agency', 'consultant', 'consulting', 'hire', 'quote', 'demo', '2026',
    ];

    private const LOCATIONS = [
        'india', 'jaipur', 'delhi', 'new delhi', 'mumbai', 'bangalore', 'bengaluru', 'pune', 'hyderabad', 'chennai', 'ahmedabad', 'gurgaon', 'gurugram', 'noida', 'kolkata', 'surat', 'dubai', 'london', 'usa', 'united states', 'uk', 'canada', 'australia',
    ];

    private const INTENT_WORDS = [
        'Transactional' => ['buy', 'book', 'booking', 'demo', 'download', 'free', 'get quote', 'order', 'package', 'packages', 'pricing', 'quote'],
        'Commercial Investigation' => ['agency', 'company', 'consultant', 'consulting', 'cost', 'expert', 'hire', 'price', 'pricing', 'provider', 'review', 'service', 'services', 'solution', 'solutions', 'vs'],
        'Navigational' => ['contact', 'login', 'near me', 'official', 'portal', 'support'],
        'Informational' => ['guide', 'how', 'learn', 'tips', 'what', 'why', 'tutorial', 'example', 'examples', 'meaning', 'definition'],
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
        $questions = array_values((array) data_get($data, 'content.questions', []));

        $sources = [
            'url' => $this->urlWords($url),
            'title' => $title,
            'h1' => implode(' ', $h1),
            'meta_description' => $meta,
            'faq' => implode(' ', $questions),
            'h2' => implode(' ', $h2),
            'h3' => implode(' ', $h3),
            'schema' => implode(' ', $schemaTypes),
            'internal_anchors' => implode(' ', array_map(fn ($link) => (string) ($link['text'] ?? ''), $links)),
            'body' => $content,
        ];

        $candidates = $this->scoreCandidates($sources);
        $primary = $candidates[0] ?? null;
        $supporting = array_slice($candidates, 1, 12);
        $combinedSignals = implode(' ', array_filter($sources));
        $locations = $this->detectLocations($combinedSignals);
        $services = $this->detectServices($candidates);
        $intent = $this->detectIntent(($primary['phrase'] ?? $primary['keyword'] ?? '').' '.$combinedSignals);
        $contentSupport = $this->contentSupportScore($primary['phrase'] ?? $primary['keyword'] ?? '', $title, $meta, $headings, $content, $url, $questions);
        $commercial = $this->commercialOpportunityAnalysis($combinedSignals);
        $theme = $this->themeAnalysis($primary, $supporting, $services, $locations, $schemaTypes);
        $coverage = $this->contentExpansionOpportunities($primary['phrase'] ?? $primary['keyword'] ?? '', $supporting, $commercial, $questions, $schemaTypes);
        $evidenceSignals = $this->evidenceSignals($sources, $schemaTypes, $content);

        return [
            'current_search_focus' => $primary ? [
                'phrase' => $primary['phrase'],
                'confidence' => $primary['confidence'],
                'intent' => $intent,
                'content_support_score' => $contentSupport,
                'evidence' => $primary['evidence'],
                'evidence_signals' => $evidenceSignals,
                'summary' => 'This page appears optimized for "'.$primary['phrase'].'" based on page signals.',
            ] : null,
            'search_theme_analysis' => $theme,
            'commercial_opportunity_analysis' => $commercial,
            'content_coverage_analysis' => $coverage,

            // Backward-compatible keys used by older report templates and stored scans.
            'primary_target_keyword' => $primary ? array_merge($primary, [
                'keyword' => $primary['phrase'],
                'intent' => $intent,
                'content_support_score' => $contentSupport,
            ]) : null,
            'supporting_keywords' => $supporting,
            'keyword_opportunities' => $coverage['content_expansion_opportunities'],
            'keyword_intent' => $intent,
            'content_support_score' => $contentSupport,
            'detected_locations' => $locations,
            'detected_services' => $services,
            'evidence' => [
                'title' => $title,
                'meta_description' => $meta,
                'h1' => $h1,
                'h2' => array_slice($h2, 0, 8),
                'faq_questions' => array_slice($questions, 0, 8),
                'url_terms' => $this->urlWords($url),
                'schema_types' => $schemaTypes,
            ],
            'summary' => $primary
                ? 'Likely search focus based on page signals: "'.$primary['phrase'].'" with '.$primary['confidence'].'% confidence.'
                : 'Not enough meaningful multi-word search-focus evidence was found in the current page HTML.',
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
                    'phrase' => str($phrase)->headline()->toString(),
                    'score' => 0,
                    'sources' => [],
                    'frequency' => 0,
                ];

                $scores[$key]['score'] += $score;
                $scores[$key]['frequency']++;
                $scores[$key]['sources'][] = $source;
            }
        }

        $items = array_values(array_filter($scores, fn ($item) => $this->isUsefulCandidate($item['phrase'])));

        foreach ($items as &$item) {
            $item['sources'] = array_values(array_unique($item['sources']));
            $item['confidence'] = max(35, min(96, (int) round($item['score'] + min(18, $item['frequency'] * 2))));
            $item['evidence'] = $this->evidenceLabel($item['sources'], $item['frequency']);
            $item['keyword'] = $item['phrase'];
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
        for ($size = 2; $size <= 5; $size++) {
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
            'url' => 32,
            'title' => 30,
            'h1' => 28,
            'meta_description' => 22,
            'faq' => 20,
            'h2' => 16,
            'h3' => 12,
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

        if ($this->containsAny($lower, self::COMMERCIAL_MODIFIERS)) {
            $boost += 8;
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
        if (in_array('url', $sources, true) && (in_array('title', $sources, true) || in_array('h1', $sources, true))) {
            return 'Strong URL plus title or H1 alignment, with '.$frequency.' supporting signal(s).';
        }

        if (in_array('title', $sources, true) && in_array('h1', $sources, true)) {
            return 'Strong title and H1 alignment, with '.$frequency.' supporting signal(s).';
        }

        if (in_array('title', $sources, true) || in_array('h1', $sources, true) || in_array('meta_description', $sources, true)) {
            return 'Detected in a high-priority page element with '.$frequency.' supporting signal(s).';
        }

        return 'Inferred from repeated headings, body copy, URL, FAQ, links or schema signals.';
    }

    private function contentSupportScore(string $phrase, string $title, string $meta, array $headings, string $content, string $url, array $questions): int
    {
        if ($phrase === '') {
            return 0;
        }

        $needle = strtolower($phrase);
        $score = 0;
        $score += str_contains(strtolower($this->urlWords($url)), $needle) ? 15 : 0;
        $score += str_contains(strtolower($title), $needle) ? 25 : 0;
        $score += str_contains(strtolower(implode(' ', $headings)), $needle) ? 25 : 0;
        $score += str_contains(strtolower($meta), $needle) ? 15 : 0;
        $score += str_contains(strtolower(implode(' ', $questions)), $needle) ? 10 : 0;
        $score += min(10, substr_count(strtolower($content), $needle) * 2);

        return min(100, $score);
    }

    private function commercialOpportunityAnalysis(string $text): array
    {
        $lower = strtolower($text);
        $present = [];
        $missing = [];

        foreach (self::COMMERCIAL_MODIFIERS as $modifier) {
            if (str_contains($lower, strtolower($modifier))) {
                $present[] = str($modifier)->headline()->toString();
            } else {
                $missing[] = str($modifier)->headline()->toString();
            }
        }

        $present = array_values(array_unique($present));
        $missing = array_values(array_unique($missing));
        $score = (int) round((count($present) / max(1, count(self::COMMERCIAL_MODIFIERS))) * 100);

        return [
            'present_modifiers' => array_slice($present, 0, 14),
            'missing_modifiers' => array_slice($missing, 0, 14),
            'opportunity_score' => $score,
            'summary' => $score >= 60
                ? 'The page has clear commercial intent signals in its current copy.'
                : 'The page could make its commercial intent clearer with stronger service, pricing, package, booking or trust language.',
        ];
    }

    private function themeAnalysis(?array $primary, array $supporting, array $services, array $locations, array $schemaTypes): array
    {
        $supportingTopics = array_values(array_unique(array_filter(array_map(
            fn ($item) => $item['phrase'] ?? $item['keyword'] ?? null,
            array_slice($supporting, 0, 8)
        ))));

        $entities = array_values(array_unique(array_filter(array_merge($services, $locations, array_slice($schemaTypes, 0, 6)))));

        return [
            'main_topic' => $primary['phrase'] ?? null,
            'supporting_topics' => $supportingTopics,
            'related_entities' => $entities,
            'summary' => $primary
                ? 'Google or an AI engine would likely understand this page as being about '.$primary['phrase'].'.'
                : 'The page does not yet provide enough consistent signals to infer one main topic.',
        ];
    }

    private function contentExpansionOpportunities(string $primary, array $supporting, array $commercial, array $questions, array $schemaTypes): array
    {
        $covered = array_values(array_unique(array_filter(array_merge(
            [$primary],
            array_map(fn ($item) => $item['phrase'] ?? $item['keyword'] ?? null, array_slice($supporting, 0, 8))
        ))));

        $missing = [];
        if (count($questions) < 3) {
            $missing[] = 'More question-and-answer coverage';
        }
        if (! $this->containsAny(strtolower(implode(' ', $schemaTypes)), ['faqpage', 'howto', 'article'])) {
            $missing[] = 'Answer-focused structured data';
        }
        foreach (array_slice((array) $commercial['missing_modifiers'], 0, 5) as $modifier) {
            $missing[] = $modifier.' signal';
        }

        $opportunities = array_values(array_unique(array_filter(array_merge($missing, [
            $primary ? 'Add examples and proof around '.$primary : null,
            'Add comparison, pricing, process or eligibility details where relevant',
            'Add clearer author, source or expertise signals for AI citation confidence',
        ]))));

        return [
            'topics_covered' => array_slice($covered, 0, 12),
            'potential_topics_missing' => array_slice(array_values(array_unique($missing)), 0, 10),
            'content_expansion_opportunities' => array_slice($opportunities, 0, 12),
        ];
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
        $topIntent = array_key_first($scores) ?: 'Informational';

        return ($scores[$topIntent] ?? 0) > 0 ? $topIntent : 'Informational';
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
            fn ($item) => $item['phrase'] ?? $item['keyword'],
            array_filter($candidates, fn ($item) => $this->containsAny(strtolower($item['phrase'] ?? $item['keyword']), self::SERVICE_MODIFIERS))
        )), 0, 8));
    }

    private function evidenceSignals(array $sources, array $schemaTypes, string $content): array
    {
        return [
            'url' => trim((string) $sources['url']) !== '',
            'title' => trim((string) $sources['title']) !== '',
            'meta_description' => trim((string) $sources['meta_description']) !== '',
            'h1' => trim((string) $sources['h1']) !== '',
            'h2' => trim((string) $sources['h2']) !== '',
            'faq' => trim((string) $sources['faq']) !== '' || str_contains(strtolower($content), 'faq'),
            'schema' => $schemaTypes !== [],
            'body_content' => str_word_count(strip_tags($content)) >= 150,
        ];
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
            $key = strtolower((string) ($item['phrase'] ?? $item['keyword']));
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
