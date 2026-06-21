<?php

namespace App\Services\Scanner;

class TopicIntelligenceAnalyzer
{
    private const STOP_WORDS = [
        'about', 'after', 'again', 'against', 'also', 'and', 'are', 'best', 'but', 'can', 'for', 'from', 'has', 'have', 'how', 'into', 'its', 'more', 'not', 'our', 'page', 'than', 'that', 'the', 'their', 'this', 'through', 'to', 'was', 'what', 'when', 'where', 'which', 'why', 'with', 'you', 'your',
    ];

    private const SERVICE_TERMS = [
        'agency', 'audit', 'consulting', 'consultant', 'development', 'management', 'marketing', 'optimization', 'platform', 'service', 'services', 'solution', 'solutions', 'strategy', 'support',
    ];

    private const INDUSTRIES = [
        'ecommerce', 'e-commerce', 'healthcare', 'real estate', 'education', 'finance', 'fintech', 'saas', 'travel', 'hospitality', 'legal', 'law', 'manufacturing', 'retail', 'automotive', 'restaurant', 'medical', 'dental', 'insurance', 'construction', 'logistics', 'technology', 'startup', 'b2b', 'b2c',
    ];

    private const LOCATIONS = [
        'india', 'jaipur', 'delhi', 'new delhi', 'mumbai', 'bangalore', 'bengaluru', 'pune', 'hyderabad', 'chennai', 'ahmedabad', 'gurgaon', 'gurugram', 'noida', 'kolkata', 'surat', 'london', 'dubai', 'usa', 'united states', 'uk', 'canada', 'australia',
    ];

    public function analyze(array $data): array
    {
        $title = (string) ($data['title'] ?? '');
        $description = (string) ($data['meta_description'] ?? '');
        $headings = array_values(array_filter((array) ($data['headings'] ?? [])));
        $headingLevels = (array) ($data['heading_levels'] ?? []);
        $links = (array) ($data['links'] ?? []);
        $schemaTypes = (array) data_get($data, 'schema.types', []);
        $content = (string) data_get($data, 'content.visible_text', '');
        $footer = (string) data_get($data, 'content.footer_text', '');
        $entities = (array) data_get($data, 'content.entities', []);
        $questions = (array) data_get($data, 'content.questions', []);
        $url = (string) ($data['url'] ?? '');
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $brand = $this->brandFromHost($host);

        $sourceText = trim($title.' '.$description.' '.implode(' ', $headings).' '.$content.' '.$footer.' '.implode(' ', $schemaTypes).' '.implode(' ', $entities));
        $weightedText = trim(str_repeat($title.' '.$description.' '.implode(' ', array_slice($headings, 0, 12)).' ', 3).' '.$sourceText);
        $phrases = $this->rankedPhrases($weightedText);
        $services = $this->services($phrases, $links, $headings);
        $industries = $this->dictionaryMatches($sourceText, self::INDUSTRIES, 10);
        $locations = $this->locations($sourceText);
        $topicIntelligence = [
            'primary_topics' => array_slice($phrases, 0, 8),
            'secondary_topics' => array_values(array_slice($phrases, 8, 14)),
            'services' => $services,
            'industries' => $industries,
            'locations' => $locations,
            'entities' => array_values(array_slice(array_unique(array_filter($entities)), 0, 18)),
            'brand_signals' => $this->brandSignals($brand, $sourceText, $title, $description, $links, $schemaTypes),
            'evidence' => [
                'title' => $title,
                'meta_description' => $description,
                'h1' => array_values((array) ($headingLevels['h1'] ?? [])),
                'h2' => array_values((array) ($headingLevels['h2'] ?? [])),
                'h3' => array_values((array) ($headingLevels['h3'] ?? [])),
                'schema_types' => $schemaTypes,
            ],
        ];

        $rankingPotential = $this->rankingPotential($topicIntelligence, $title, $description, $headings, $content);
        $promptIntelligence = $this->promptIntelligence($topicIntelligence, $questions, $sourceText);
        $contentCoverage = $this->contentCoverage($topicIntelligence, $promptIntelligence, $sourceText);
        $citationReadiness = $this->citationReadiness($data, $topicIntelligence, $sourceText, $links, $schemaTypes);

        return [
            'topic_intelligence_data' => $topicIntelligence,
            'ranking_potential_data' => $rankingPotential,
            'prompt_intelligence_data' => $promptIntelligence,
            'content_coverage_data' => $contentCoverage,
            'ai_citation_readiness_data' => $citationReadiness,
            'score_breakdown' => [
                'content_coverage_score' => $contentCoverage['coverage_percent'],
                'ai_citation_readiness_score' => $citationReadiness['score'],
            ],
            'opportunities' => $this->opportunities($contentCoverage, $citationReadiness, $promptIntelligence),
        ];
    }

    private function rankedPhrases(string $text): array
    {
        $normalized = strtolower((string) preg_replace('/[^a-zA-Z0-9+.#\s-]/', ' ', $text));
        $words = array_values(array_filter(preg_split('/\s+/', $normalized) ?: [], fn ($word) => strlen($word) > 2 && ! in_array($word, self::STOP_WORDS, true)));
        $scores = [];

        for ($size = 1; $size <= 3; $size++) {
            for ($i = 0; $i <= count($words) - $size; $i++) {
                $phrase = implode(' ', array_slice($words, $i, $size));

                if ($this->usefulPhrase($phrase)) {
                    $scores[$phrase] = ($scores[$phrase] ?? 0) + (4 - $size);
                }
            }
        }

        arsort($scores);

        return array_values(array_slice(array_map(fn ($phrase) => str($phrase)->headline()->toString(), array_keys($scores)), 0, 30));
    }

    private function usefulPhrase(string $phrase): bool
    {
        if (is_numeric(str_replace(' ', '', $phrase))) {
            return false;
        }

        if (strlen($phrase) < 4) {
            return false;
        }

        return ! in_array($phrase, self::STOP_WORDS, true);
    }

    private function services(array $phrases, array $links, array $headings): array
    {
        $candidates = [];

        foreach ($phrases as $phrase) {
            if ($this->containsAny(strtolower($phrase), self::SERVICE_TERMS)) {
                $candidates[] = $phrase;
            }
        }

        foreach (array_merge($links, array_map(fn ($heading) => ['text' => $heading], $headings)) as $item) {
            $text = trim((string) ($item['text'] ?? '').' '.(string) ($item['href'] ?? ''));
            if ($text !== '' && $this->containsAny(strtolower($text), self::SERVICE_TERMS)) {
                $candidates[] = str($text)->replace(['/', '-', '_'], ' ')->headline()->limit(60, '')->toString();
            }
        }

        return array_values(array_slice(array_unique(array_filter($candidates)), 0, 12));
    }

    private function dictionaryMatches(string $text, array $dictionary, int $limit): array
    {
        $haystack = strtolower($text);
        $matches = [];

        foreach ($dictionary as $term) {
            if (str_contains($haystack, $term)) {
                $matches[] = str($term)->headline()->toString();
            }
        }

        return array_values(array_slice(array_unique($matches), 0, $limit));
    }

    private function locations(string $text): array
    {
        $matches = $this->dictionaryMatches($text, self::LOCATIONS, 12);
        preg_match_all('/\b(?:in|near|from|across)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2})\b/', $text, $found);

        foreach ($found[1] ?? [] as $location) {
            if (! $this->containsAny(strtolower($location), ['the', 'our', 'your', 'this'])) {
                $matches[] = trim($location);
            }
        }

        return array_values(array_slice(array_unique($matches), 0, 12));
    }

    private function rankingPotential(array $topics, string $title, string $description, array $headings, string $content): array
    {
        $keywords = [];
        $locations = $topics['locations'] ?? [];
        $baseTerms = array_values(array_unique(array_merge($topics['services'] ?? [], $topics['primary_topics'] ?? [])));
        $evidenceText = strtolower($title.' '.$description.' '.implode(' ', $headings).' '.$content);

        foreach ($baseTerms as $term) {
            $keywords[] = $this->rankingItem($term, $evidenceText, $title, $headings, 'topic');
            foreach (array_slice($locations, 0, 4) as $location) {
                $keywords[] = $this->rankingItem($term.' '.$location, $evidenceText, $title, $headings, 'local');
            }
        }

        usort($keywords, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);

        return [
            'summary' => 'Inferred from page title, meta description, headings, content frequency, internal links, entities and schema. No external keyword API used.',
            'items' => array_values(array_slice($this->uniqueBy($keywords, 'keyword'), 0, 18)),
        ];
    }

    private function rankingItem(string $keyword, string $evidenceText, string $title, array $headings, string $type): array
    {
        $needle = strtolower($keyword);
        $confidence = 50;
        $confidence += str_contains(strtolower($title), $needle) ? 28 : 0;
        $confidence += $this->containsAny(strtolower(implode(' ', $headings)), [$needle]) ? 18 : 0;
        $confidence += min(22, substr_count($evidenceText, $needle) * 4);
        $confidence += $type === 'local' ? 4 : 8;

        return [
            'keyword' => trim($keyword),
            'confidence' => max(45, min(95, $confidence)),
            'type' => $type,
            'evidence' => $confidence >= 80 ? 'Strong title, heading or repeated content alignment.' : 'Detected from topical and semantic relevance in current page content.',
        ];
    }

    private function promptIntelligence(array $topics, array $existingQuestions, string $sourceText): array
    {
        $topicSeeds = array_values(array_slice(array_unique(array_merge($topics['primary_topics'] ?? [], $topics['services'] ?? [])), 0, 8));
        $prompts = [];

        foreach ($topicSeeds as $topic) {
            $prompts[] = 'What is '.$topic.'?';
            $prompts[] = 'How can '.$topic.' help a business?';
            $prompts[] = 'How much does '.$topic.' cost?';
            $prompts[] = 'What is the best '.$topic.' provider?';
        }

        foreach ($existingQuestions as $question) {
            $prompts[] = trim($question);
        }

        $items = [];
        foreach (array_values(array_unique(array_filter($prompts))) as $prompt) {
            $coverage = $this->coverageForPrompt($prompt, $sourceText);
            $items[] = [
                'prompt' => $prompt,
                'coverage' => $coverage,
                'reason' => $coverage === 'Covered' ? 'The current content appears to answer this directly.' : ($coverage === 'Partially Covered' ? 'The topic is present, but the answer could be clearer.' : 'The prompt is relevant but not clearly answered yet.'),
            ];
        }

        return [
            'covered' => array_values(array_filter($items, fn ($item) => $item['coverage'] === 'Covered')),
            'partially_covered' => array_values(array_filter($items, fn ($item) => $item['coverage'] === 'Partially Covered')),
            'missing' => array_values(array_filter($items, fn ($item) => $item['coverage'] === 'Missing')),
            'items' => array_values(array_slice($items, 0, 24)),
        ];
    }

    private function coverageForPrompt(string $prompt, string $sourceText): string
    {
        $promptTerms = array_filter(preg_split('/\s+/', strtolower(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $prompt))) ?: [], fn ($word) => strlen($word) > 3 && ! in_array($word, self::STOP_WORDS, true));
        $hits = 0;
        $haystack = strtolower($sourceText);

        foreach ($promptTerms as $term) {
            $hits += str_contains($haystack, $term) ? 1 : 0;
        }

        $ratio = $promptTerms === [] ? 0 : $hits / count($promptTerms);

        return $ratio >= 0.75 ? 'Covered' : ($ratio >= 0.4 ? 'Partially Covered' : 'Missing');
    }

    private function contentCoverage(array $topics, array $promptData, string $sourceText): array
    {
        $identified = array_values(array_unique(array_merge(
            $topics['primary_topics'] ?? [],
            $topics['secondary_topics'] ?? [],
            $topics['services'] ?? [],
            $topics['industries'] ?? [],
            $topics['locations'] ?? [],
            array_column($promptData['items'] ?? [], 'prompt')
        )));

        $covered = array_values(array_filter($identified, fn ($topic) => $this->coverageForPrompt((string) $topic, $sourceText) !== 'Missing'));
        $missing = array_values(array_diff($identified, $covered));
        $coveragePercent = count($identified) === 0 ? 0 : (int) round((count($covered) / count($identified)) * 100);

        return [
            'topics_identified' => count($identified),
            'topics_covered' => count($covered),
            'topics_missing' => count($missing),
            'coverage_percent' => $coveragePercent,
            'covered_topics' => array_values(array_slice($covered, 0, 20)),
            'missing_topics' => array_values(array_slice($missing, 0, 20)),
        ];
    }

    private function citationReadiness(array $data, array $topics, string $sourceText, array $links, array $schemaTypes): array
    {
        $content = strtolower($sourceText);
        $schema = array_map('strtolower', $schemaTypes);
        $factors = [
            'author_presence' => $this->containsAny($content, ['author', 'written by', 'reviewed by', 'expert', 'team']),
            'about_page' => $this->linkContains($links, ['about', 'about-us', 'company']),
            'contact_page' => $this->linkContains($links, ['contact', 'contact-us']) || $this->containsAny($content, ['contact us', '@']),
            'organization_signals' => $this->hasAnySchema($schema, ['organization', 'localbusiness', 'corporation', 'professionalservice']),
            'faq_presence' => $this->hasAnySchema($schema, ['faqpage']) || $this->containsAny($content, ['faq', 'frequently asked questions']),
            'schema_coverage' => count($schemaTypes) >= 2,
            'brand_consistency' => count(array_filter((array) data_get($topics, 'brand_signals', []))) >= 2,
            'trust_signals' => $this->containsAny($content, ['testimonial', 'review', 'case study', 'clients', 'trusted by', 'award', 'certified', 'partner']),
        ];
        $score = (int) round((count(array_filter($factors)) / count($factors)) * 100);

        return [
            'score' => $score,
            'factors' => $factors,
            'missing_factors' => array_keys(array_filter($factors, fn ($value) => ! $value)),
            'recommendations' => array_map(fn ($factor) => 'Improve '.str_replace('_', ' ', $factor).' to increase AI citation confidence.', array_keys(array_filter($factors, fn ($value) => ! $value))),
        ];
    }

    private function opportunities(array $coverage, array $citation, array $prompts): array
    {
        $items = [];

        if (($coverage['coverage_percent'] ?? 0) < 70) {
            $items[] = [
                'category' => 'Content Coverage',
                'issue' => 'The site does not fully cover its inferred topic universe.',
                'impact' => 'high',
                'difficulty' => 'medium',
                'estimated_gain' => 14,
                'why_it_matters' => 'AI systems cite sources that answer related questions with depth and breadth.',
                'recommendation' => 'Add content for missing topics and prompts that are relevant to the current authority area.',
                'how_to_fix' => 'Build short answer sections, FAQs, service pages, and supporting articles for the missing topics listed in Content Coverage.',
            ];
        }

        if (($citation['score'] ?? 0) < 75) {
            $items[] = [
                'category' => 'AI Citation Readiness',
                'issue' => 'Citation readiness signals are incomplete.',
                'impact' => 'high',
                'difficulty' => 'medium',
                'estimated_gain' => 12,
                'why_it_matters' => 'AI engines are more likely to cite pages with clear authorship, trust, organization and schema signals.',
                'recommendation' => 'Strengthen author, about, contact, schema, FAQ, trust and brand consistency signals.',
                'how_to_fix' => 'Add organization schema, visible contact/about links, FAQ content, author/expert proof, testimonials and consistent brand mentions.',
            ];
        }

        if (count($prompts['missing'] ?? []) > 0) {
            $items[] = [
                'category' => 'AI Prompt Intelligence',
                'issue' => 'Relevant AI-user questions are missing clear answers.',
                'impact' => 'medium',
                'difficulty' => 'low',
                'estimated_gain' => 8,
                'why_it_matters' => 'Prompt-aligned content improves eligibility for answer engines and AI assistant citations.',
                'recommendation' => 'Answer the missing prompts directly on the page or in supporting FAQ/content pages.',
                'how_to_fix' => 'Turn each missing prompt into a concise question heading followed by a direct answer and supporting detail.',
            ];
        }

        return $items;
    }

    private function brandSignals(string $brand, string $sourceText, string $title, string $description, array $links, array $schemaTypes): array
    {
        $content = strtolower($sourceText);

        return [
            'brand' => $brand,
            'in_title' => $brand !== '' && str_contains(strtolower($title), $brand),
            'in_meta_description' => $brand !== '' && str_contains(strtolower($description), $brand),
            'in_body' => $brand !== '' && str_contains($content, $brand),
            'in_navigation_or_footer' => $brand !== '' && $this->linkContains($links, [$brand]),
            'schema_present' => count($schemaTypes) > 0,
        ];
    }

    private function brandFromHost(string $host): string
    {
        $host = preg_replace('/^www\./', '', $host);
        $brand = preg_replace('/\.[a-z.]+$/', '', (string) $host);

        return strtolower((string) $brand);
    }

    private function linkContains(array $links, array $needles): bool
    {
        foreach ($links as $link) {
            if ($this->containsAny(strtolower((string) ($link['href'] ?? '').' '.(string) ($link['text'] ?? '')), $needles)) {
                return true;
            }
        }

        return false;
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

    private function hasAnySchema(array $schemaTypes, array $expectedTypes): bool
    {
        foreach ($schemaTypes as $type) {
            foreach ($expectedTypes as $expected) {
                if (strtolower((string) $type) === strtolower($expected)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function uniqueBy(array $items, string $key): array
    {
        $seen = [];

        return array_values(array_filter($items, function ($item) use (&$seen, $key) {
            $value = strtolower((string) ($item[$key] ?? ''));

            if ($value === '' || isset($seen[$value])) {
                return false;
            }

            $seen[$value] = true;

            return true;
        }));
    }
}
