<?php

namespace Tests\Unit;

use App\Services\Scanner\KeywordTargetingAnalyzer;
use PHPUnit\Framework\TestCase;

class KeywordTargetingAnalyzerTest extends TestCase
{
    public function test_it_detects_current_search_focus_from_page_signals(): void
    {
        $analyzer = new KeywordTargetingAnalyzer();

        $result = $analyzer->analyze([
            'url' => 'https://example.com/seo/company/jaipur',
            'title' => 'Best SEO Company in Jaipur | Example Agency',
            'meta_description' => 'Example Agency provides SEO services in Jaipur for local businesses.',
            'heading_levels' => [
                'h1' => ['SEO Company in Jaipur'],
                'h2' => ['SEO Services Jaipur', 'Local SEO Experts'],
                'h3' => [],
            ],
            'content' => [
                'visible_text' => 'Our SEO company in Jaipur helps businesses with local SEO services, technical SEO audits, ecommerce SEO and content optimization.',
                'questions' => ['How do SEO services in Jaipur help local businesses?'],
            ],
            'links' => [
                ['text' => 'SEO Services', 'href' => '/seo-services'],
            ],
            'schema' => [
                'types' => ['Organization', 'Service', 'FAQPage'],
            ],
        ]);

        $this->assertNotNull($result['current_search_focus']);
        $this->assertNotNull($result['primary_target_keyword']);
        $this->assertStringContainsString('Seo', $result['current_search_focus']['phrase']);
        $this->assertGreaterThanOrEqual(2, str_word_count($result['current_search_focus']['phrase']));
        $this->assertContains($result['current_search_focus']['intent'], [
            'Informational',
            'Commercial Investigation',
            'Transactional',
            'Navigational',
        ]);
        $this->assertTrue($result['current_search_focus']['evidence_signals']['url']);
        $this->assertTrue($result['current_search_focus']['evidence_signals']['title']);
        $this->assertContains('Jaipur', $result['detected_locations']);
        $this->assertNotEmpty($result['search_theme_analysis']['supporting_topics']);
        $this->assertNotEmpty($result['commercial_opportunity_analysis']['present_modifiers']);
        $this->assertArrayHasKey('content_expansion_opportunities', $result['content_coverage_analysis']);
    }
}
