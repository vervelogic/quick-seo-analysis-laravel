<?php

namespace Tests\Unit;

use App\Services\Scanner\PageTypeDetector;
use App\Services\Scanner\RecommendationGroupBuilder;
use PHPUnit\Framework\TestCase;

class RecommendationGroupBuilderTest extends TestCase
{
    public function test_it_detects_ecommerce_and_groups_recommendations(): void
    {
        $data = [
            'url' => 'https://example.com/collections/shirts',
            'title' => 'Cotton Shirts Collection',
            'meta_description' => 'Shop cotton shirts with size and price details.',
            'headings' => ['Cotton Shirts', 'Product sizes'],
            'schema' => ['types' => ['Product']],
            'content' => [
                'visible_text' => 'Add to cart product price size variant checkout Shopify shirts.',
                'visible_word_count' => 650,
                'unique_word_count' => 220,
                'questions' => ['What size should I choose?'],
            ],
            'links' => [
                ['text' => 'Cart', 'href' => '/cart'],
                ['text' => 'Checkout', 'href' => '/checkout'],
            ],
            'internal_links_count' => 8,
        ];

        $pageType = (new PageTypeDetector())->detect($data);
        $groups = (new RecommendationGroupBuilder())->build([
            [
                'category' => 'AI Visibility',
                'issue' => 'Service pages missing.',
                'impact' => 'medium',
                'difficulty' => 'low',
                'how_to_fix' => 'Create service pages.',
            ],
            [
                'category' => 'Structured Data',
                'issue' => 'Product schema can be strengthened.',
                'impact' => 'high',
                'difficulty' => 'medium',
                'how_to_fix' => 'Add product schema, review data and offer details.',
            ],
        ], $data, $pageType, ['status' => 'full_content_retrieved']);

        $this->assertSame('Ecommerce', $pageType['type']);
        $this->assertNotEmpty($groups);
        $this->assertContains('Commercial & Conversion Signals', array_column($groups, 'title'));
        $this->assertArrayHasKey('business_impact', $groups[0]);
        $this->assertArrayHasKey('top_missing_signals', $groups[0]);
    }

    public function test_retrieval_issue_returns_single_critical_group(): void
    {
        $groups = (new RecommendationGroupBuilder())->build([], [], ['type' => 'Unknown'], ['status' => 'retrieval_issue_detected']);

        $this->assertCount(1, $groups);
        $this->assertSame('Critical', $groups[0]['priority']);
        $this->assertSame('Content Retrieval Issue Detected', $groups[0]['issue']);
    }
}
