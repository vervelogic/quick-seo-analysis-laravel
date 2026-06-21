<?php

namespace Tests\Unit;

use App\Services\Scanner\TopicIntelligenceAnalyzer;
use PHPUnit\Framework\TestCase;

class TopicIntelligenceAnalyzerTest extends TestCase
{
    public function test_it_derives_topics_ranking_prompts_coverage_and_citation_readiness(): void
    {
        $result = (new TopicIntelligenceAnalyzer())->analyze([
            'url' => 'https://example-seo.com',
            'title' => 'SEO Company Jaipur | AI SEO and GEO Services',
            'meta_description' => 'Example SEO provides technical SEO, local SEO, ecommerce SEO, GEO consulting and AI visibility services in Jaipur India.',
            'headings' => [
                'SEO Services Jaipur',
                'Technical SEO Audit',
                'AI SEO and GEO Consulting',
                'Frequently Asked Questions',
            ],
            'heading_levels' => [
                'h1' => ['SEO Services Jaipur'],
                'h2' => ['Technical SEO Audit', 'AI SEO and GEO Consulting'],
                'h3' => ['Frequently Asked Questions'],
            ],
            'links' => [
                ['href' => '/about', 'text' => 'About Example SEO'],
                ['href' => '/contact', 'text' => 'Contact'],
                ['href' => '/services/seo-audit', 'text' => 'SEO Audit Services'],
                ['href' => '/industries/ecommerce', 'text' => 'Ecommerce SEO'],
            ],
            'schema' => [
                'types' => ['Organization', 'FAQPage', 'ProfessionalService'],
            ],
            'content' => [
                'questions' => ['What is GEO?', 'How much does SEO cost?'],
                'entities' => ['Example SEO', 'Jaipur', 'India', 'Google', 'ChatGPT'],
                'footer_text' => 'Example SEO Jaipur India contact hello@example-seo.com',
                'visible_text' => 'Example SEO is an expert SEO company in Jaipur India. We provide SEO services, technical SEO audit, local SEO, ecommerce SEO, AI SEO, GEO consulting, AI visibility strategy, FAQ content, reviews, case studies, testimonials and certified SEO support. What is GEO? GEO helps improve visibility in AI answers. Contact hello@example-seo.com for SEO services Jaipur.',
            ],
        ]);

        $this->assertNotEmpty($result['topic_intelligence_data']['primary_topics']);
        $this->assertContains('Jaipur', $result['topic_intelligence_data']['locations']);
        $this->assertNotEmpty($result['ranking_potential_data']['items']);
        $this->assertArrayHasKey('covered', $result['prompt_intelligence_data']);
        $this->assertGreaterThan(0, $result['content_coverage_data']['topics_identified']);
        $this->assertGreaterThanOrEqual(50, $result['ai_citation_readiness_data']['score']);
        $this->assertArrayHasKey('ai_citation_readiness_score', $result['score_breakdown']);
    }
}
