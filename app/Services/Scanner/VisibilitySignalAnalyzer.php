<?php

namespace App\Services\Scanner;

class VisibilitySignalAnalyzer
{
    public function analyze(array $data): array
    {
        $content = strtolower((string) data_get($data, 'content.visible_text', ''));
        $title = strtolower((string) ($data['title'] ?? ''));
        $description = strtolower((string) ($data['meta_description'] ?? ''));
        $schemaTypes = array_map('strtolower', (array) data_get($data, 'schema.types', []));
        $links = (array) ($data['links'] ?? []);
        $headings = array_map('strtolower', (array) ($data['headings'] ?? []));
        $questions = (array) data_get($data, 'content.questions', []);

        $aiSignals = [
            'organization_entity_detected' => $this->hasAnySchema($schemaTypes, ['organization', 'localbusiness', 'corporation', 'professionalservice']),
            'brand_entity_detected' => $this->brandDetected($data, $content, $title, $description),
            'contact_information_present' => $this->hasContactInfo($data, $content, $links),
            'about_page_detected' => $this->linkContains($links, ['about', 'about-us', 'company']),
            'author_expertise_signals' => $this->containsAny($content, ['author', 'expert', 'certified', 'specialist', 'years of experience', 'founded', 'team']),
            'faq_content_present' => $this->faqDetected($data, $content, $headings),
            'service_pages_present' => $this->linkContains($links, ['service', 'services', 'solutions', 'what-we-do']),
            'trust_signals_present' => $this->containsAny($content, ['testimonial', 'review', 'case study', 'clients', 'trusted by', 'award', 'certified', 'partner']),
            'structured_answer_content' => count($questions) >= 2 || $this->containsAny($content, ['what is', 'how to', 'why does', 'step by step']),
            'knowledge_graph_readiness' => $this->hasAnySchema($schemaTypes, ['organization', 'localbusiness']) && $this->hasContactInfo($data, $content, $links),
        ];

        $geoSignals = [
            'faq_coverage' => $this->faqDetected($data, $content, $headings),
            'conversational_content' => count($questions) >= 2 || substr_count($content, '?') >= 2,
            'question_answer_content' => $this->containsAny($content, ['q:', 'a:', 'question', 'answer']) || count($questions) >= 3,
            'topic_cluster_signals' => count($links) >= 8 && count($headings) >= 4,
            'semantic_coverage' => (int) data_get($data, 'content.unique_word_count', 0) >= 180,
            'entity_richness' => count((array) data_get($data, 'content.entities', [])) >= 8 || count($schemaTypes) >= 2,
            'content_depth' => (int) data_get($data, 'content.visible_word_count', 0) >= 700,
        ];

        $aeoSignals = [
            'faq_schema' => $this->hasAnySchema($schemaTypes, ['faqpage']),
            'how_to_content' => $this->hasAnySchema($schemaTypes, ['howto']) || $this->containsAny($content, ['how to', 'steps to', 'step 1', 'step-by-step']),
            'definition_content' => $this->containsAny($content, ['what is', 'refers to', 'is a ', 'is an ', 'means ']),
            'comparison_content' => $this->containsAny($content, [' vs ', ' versus ', 'compare', 'comparison', 'better than']),
            'featured_snippet_readiness' => count($questions) >= 2 && (int) data_get($data, 'content.visible_word_count', 0) >= 300,
            'direct_answer_formatting' => $this->containsAny($content, ['in short', 'quick answer', 'summary', 'key takeaways']) || count($headings) >= 4,
        ];

        $seoScore = $this->seoScore($data);
        $aiScore = $this->score($aiSignals);
        $geoScore = $this->score($geoSignals);
        $aeoScore = $this->score($aeoSignals);
        $overallVisibilityScore = (int) round(($seoScore * 0.35) + ($aiScore * 0.25) + ($geoScore * 0.20) + ($aeoScore * 0.20));

        return [
            'score_breakdown' => [
                'seo_score' => $seoScore,
                'ai_visibility_score' => $aiScore,
                'geo_score' => $geoScore,
                'aeo_score' => $aeoScore,
                'overall_visibility_score' => $overallVisibilityScore,
            ],
            'ai_visibility_data' => [
                'score' => $aiScore,
                'signals' => $aiSignals,
                'missing_signals' => $this->missing($aiSignals),
                'recommended_actions' => $this->actionsFor($aiSignals, 'AI Visibility'),
            ],
            'geo_data' => [
                'score' => $geoScore,
                'signals' => $geoSignals,
                'recommendations' => $this->actionsFor($geoSignals, 'GEO'),
            ],
            'aeo_data' => [
                'score' => $aeoScore,
                'signals' => $aeoSignals,
                'missing_opportunities' => $this->missing($aeoSignals),
                'missing_answer_opportunities' => $this->missing($aeoSignals),
            ],
            'visibility_data' => [
                'score' => $overallVisibilityScore,
                'summary' => $this->summary($overallVisibilityScore),
                'opportunities' => $this->opportunities($aiSignals, $geoSignals, $aeoSignals),
            ],
        ];
    }

    private function seoScore(array $data): int
    {
        $breakdown = (array) ($data['score_breakdown'] ?? []);
        $scores = array_filter([
            $breakdown['technical_score'] ?? null,
            $breakdown['on_page_score'] ?? null,
            $breakdown['content_score'] ?? null,
            $breakdown['performance_score'] ?? null,
            $breakdown['security_score'] ?? null,
            $breakdown['social_score'] ?? null,
            $breakdown['structured_data_score'] ?? null,
        ], fn ($score) => is_numeric($score));

        return $scores === [] ? (int) ($data['score'] ?? 0) : (int) round(array_sum($scores) / count($scores));
    }

    private function score(array $signals): int
    {
        return $signals === [] ? 0 : (int) round((count(array_filter($signals)) / count($signals)) * 100);
    }

    private function missing(array $signals): array
    {
        return array_keys(array_filter($signals, fn ($passed) => ! $passed));
    }

    private function actionsFor(array $signals, string $category): array
    {
        return array_map(
            fn (string $signal) => [
                'issue' => str_replace('_', ' ', ucfirst($signal)).' is missing.',
                'category' => $category,
                'impact' => in_array($signal, ['organization_entity_detected', 'faq_schema', 'content_depth', 'contact_information_present'], true) ? 'high' : 'medium',
                'difficulty' => in_array($signal, ['faq_schema', 'organization_entity_detected'], true) ? 'medium' : 'low',
                'estimated_gain' => in_array($signal, ['organization_entity_detected', 'faq_schema', 'content_depth'], true) ? 12 : 7,
                'why_it_matters' => $this->whyItMatters($signal),
                'recommendation' => 'Strengthen this signal so AI and answer engines can identify, cite, and summarize the page more confidently.',
                'how_to_fix' => $this->howToFix($signal),
            ],
            $this->missing($signals)
        );
    }

    private function opportunities(array $aiSignals, array $geoSignals, array $aeoSignals): array
    {
        return array_values(array_merge(
            $this->actionsFor($aiSignals, 'AI Visibility'),
            $this->actionsFor($geoSignals, 'GEO'),
            $this->actionsFor($aeoSignals, 'AEO'),
        ));
    }

    private function howToFix(string $signal): string
    {
        return match ($signal) {
            'organization_entity_detected' => 'Add Organization or LocalBusiness JSON-LD with name, logo, URL, sameAs, address, and contact points.',
            'brand_entity_detected' => 'Mention the brand consistently in title, headings, body copy, footer, and structured data.',
            'contact_information_present' => 'Add visible email, phone, address, or contact page links and mark them up where relevant.',
            'about_page_detected' => 'Add an About page and link it clearly from navigation or footer.',
            'author_expertise_signals' => 'Add author, team, credentials, case studies, certifications, and experience proof.',
            'faq_content_present', 'faq_coverage', 'faq_schema' => 'Add helpful FAQs and FAQPage schema for important buyer questions.',
            'service_pages_present' => 'Create service or solution pages and link them from the homepage and navigation.',
            'trust_signals_present' => 'Add reviews, testimonials, clients, awards, certifications, or partner proof.',
            'structured_answer_content', 'question_answer_content', 'direct_answer_formatting' => 'Use short answer blocks under question-style headings before expanding the detail.',
            'knowledge_graph_readiness' => 'Combine Organization schema, consistent NAP/contact details, sameAs links, and about/trust content.',
            'conversational_content' => 'Rewrite sections around natural questions users would ask AI assistants.',
            'topic_cluster_signals' => 'Build internal links between pillar pages, services, FAQs, and related articles.',
            'semantic_coverage', 'entity_richness' => 'Add related terms, named entities, service details, use cases, industries, and proof points.',
            'content_depth' => 'Expand shallow pages with original sections, examples, FAQs, process details, and evidence.',
            'how_to_content' => 'Add step-by-step how-to sections where the topic calls for instructions.',
            'definition_content' => 'Add concise “What is…” definitions near the top of informational pages.',
            'comparison_content' => 'Add comparison sections for alternatives, use cases, pros/cons, or service choices.',
            'featured_snippet_readiness' => 'Use concise answers, lists, tables, and clear headings that can be extracted as snippets.',
            default => 'Add clear, crawlable content and structured data for this signal.',
        };
    }

    private function whyItMatters(string $signal): string
    {
        return match ($signal) {
            'organization_entity_detected', 'knowledge_graph_readiness' => 'AI systems need clear entity data to connect the site with a real organization and cite it confidently.',
            'brand_entity_detected' => 'Consistent brand mentions help language models associate the content with the right business.',
            'contact_information_present' => 'Contact signals improve trust and make the business easier to validate.',
            'about_page_detected', 'author_expertise_signals', 'trust_signals_present' => 'Experience and trust signals help search and AI systems judge credibility.',
            'faq_content_present', 'faq_coverage', 'faq_schema' => 'FAQ content maps directly to conversational and answer-engine queries.',
            'service_pages_present' => 'Clear service pages help engines understand what the business offers and when to recommend it.',
            'structured_answer_content', 'question_answer_content', 'direct_answer_formatting' => 'Answer-first formatting makes content easier to extract into AI answers and snippets.',
            'conversational_content' => 'Conversational phrasing matches how users ask AI assistants and answer engines questions.',
            'topic_cluster_signals', 'semantic_coverage', 'entity_richness' => 'Topical depth and entity richness help engines understand subject authority.',
            'content_depth' => 'Deeper original content gives AI systems more evidence to summarize and cite.',
            'how_to_content', 'definition_content', 'comparison_content', 'featured_snippet_readiness' => 'These formats match common answer-engine result types and improve extraction opportunities.',
            default => 'This signal helps search, answer, and AI systems understand the page with more confidence.',
        };
    }

    private function summary(int $score): string
    {
        return $score >= 80
            ? 'Strong visibility foundation for search, answer engines, and AI assistants.'
            : ($score >= 55 ? 'Promising visibility foundation with clear AI and answer-engine gaps.' : 'Low AI visibility readiness; prioritize entity, FAQ, trust, and structured-answer improvements.');
    }

    private function brandDetected(array $data, string $content, string $title, string $description): bool
    {
        $host = strtolower(parse_url((string) ($data['url'] ?? ''), PHP_URL_HOST) ?: '');
        $brand = preg_replace('/^www\./', '', $host);
        $brand = trim((string) preg_replace('/\.[a-z.]+$/', '', $brand));

        return $brand !== '' && str_contains($title.' '.$description.' '.$content, $brand);
    }

    private function hasContactInfo(array $data, string $content, array $links): bool
    {
        return (bool) preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $content)
            || (bool) preg_match('/\+?\d[\d\s().-]{7,}\d/', $content)
            || $this->linkContains($links, ['contact', 'mailto:', 'tel:']);
    }

    private function faqDetected(array $data, string $content, array $headings): bool
    {
        return $this->hasAnySchema(array_map('strtolower', (array) data_get($data, 'schema.types', [])), ['faqpage'])
            || str_contains($content, 'frequently asked questions')
            || str_contains($content, 'faq')
            || count(array_filter($headings, fn ($heading) => str_contains($heading, '?'))) >= 2;
    }

    private function linkContains(array $links, array $needles): bool
    {
        foreach ($links as $link) {
            $haystack = strtolower((string) ($link['href'] ?? '').' '.(string) ($link['text'] ?? ''));

            if ($this->containsAny($haystack, $needles)) {
                return true;
            }
        }

        return false;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    private function hasAnySchema(array $schemaTypes, array $expectedTypes): bool
    {
        foreach ($schemaTypes as $type) {
            foreach ($expectedTypes as $expected) {
                if (strtolower($type) === strtolower($expected)) {
                    return true;
                }
            }
        }

        return false;
    }
}
