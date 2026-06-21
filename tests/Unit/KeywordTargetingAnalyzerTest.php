<?php

namespace Tests\Unit;

use App\Services\Scanner\KeywordTargetingAnalyzer;
use PHPUnit\Framework\TestCase;

class KeywordTargetingAnalyzerTest extends TestCase
{
    public function test_it_detects_primary_local_service_keyword(): void
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
            ],
            'links' => [
                ['text' => 'SEO Services', 'href' => '/seo-services'],
            ],
            'schema' => [
                'types' => ['Organization', 'Service'],
            ],
        ]);

        $this->assertNotNull($result['primary_target_keyword']);
        $this->assertStringContainsString('Seo', $result['primary_target_keyword']['keyword']);
        $this->assertContains('Jaipur', $result['detected_locations']);
        $this->assertNotEmpty($result['supporting_keywords']);
        $this->assertNotEmpty($result['keyword_opportunities']);
        $this->assertGreaterThan(0, $result['content_support_score']);
    }
}
