<?php

namespace Tests\Unit;

use App\Services\Scanner\VisibilitySignalAnalyzer;
use PHPUnit\Framework\TestCase;

class VisibilitySignalAnalyzerTest extends TestCase
{
    public function test_it_scores_ai_geo_and_aeo_signals(): void
    {
        $result = (new VisibilitySignalAnalyzer())->analyze([
            'url' => 'https://example.com',
            'title' => 'Example digital strategy services',
            'meta_description' => 'Example helps teams compare services and answer common questions.',
            'score' => 90,
            'score_breakdown' => [
                'technical_score' => 90,
                'on_page_score' => 90,
                'content_score' => 90,
                'performance_score' => 90,
                'security_score' => 90,
                'social_score' => 90,
                'structured_data_score' => 90,
            ],
            'schema' => [
                'types' => ['Organization', 'FAQPage', 'HowTo'],
            ],
            'links' => [
                ['href' => '/about', 'text' => 'About Example'],
                ['href' => '/contact', 'text' => 'Contact'],
                ['href' => '/services', 'text' => 'Services'],
                ['href' => '/solutions', 'text' => 'Solutions'],
                ['href' => '/case-studies', 'text' => 'Case studies'],
                ['href' => '/faq', 'text' => 'FAQ'],
                ['href' => '/industries', 'text' => 'Industries'],
                ['href' => '/blog', 'text' => 'Blog'],
            ],
            'headings' => [
                'What is digital strategy?',
                'How to choose a partner?',
                'Services',
                'Frequently Asked Questions',
            ],
            'content' => [
                'visible_word_count' => 850,
                'unique_word_count' => 220,
                'questions' => [
                    'What is digital strategy?',
                    'How does Example help?',
                    'Why compare services?',
                ],
                'entities' => ['Example', 'Google', 'Laravel', 'SEO', 'AI', 'GEO', 'AEO', 'SaaS'],
                'visible_text' => 'Example is a certified expert team with years of experience. Contact hello@example.com. Frequently asked questions explain what is digital strategy, how to compare options, and why clients trust by reviews, testimonials, partner awards, and case study proof. In short, the process includes step 1 discovery and step-by-step implementation.',
            ],
        ]);

        $this->assertGreaterThanOrEqual(80, $result['score_breakdown']['ai_visibility_score']);
        $this->assertGreaterThanOrEqual(80, $result['score_breakdown']['geo_score']);
        $this->assertGreaterThanOrEqual(80, $result['score_breakdown']['aeo_score']);
        $this->assertArrayHasKey('overall_visibility_score', $result['score_breakdown']);
    }

    public function test_recommendations_include_v2_fields(): void
    {
        $result = (new VisibilitySignalAnalyzer())->analyze([
            'url' => 'https://thin.example',
            'score' => 50,
            'content' => ['visible_text' => 'Thin page', 'visible_word_count' => 20],
        ]);

        $recommendation = $result['visibility_data']['opportunities'][0];

        $this->assertArrayHasKey('issue', $recommendation);
        $this->assertArrayHasKey('why_it_matters', $recommendation);
        $this->assertArrayHasKey('impact', $recommendation);
        $this->assertArrayHasKey('difficulty', $recommendation);
        $this->assertArrayHasKey('estimated_gain', $recommendation);
        $this->assertArrayHasKey('how_to_fix', $recommendation);
    }
}
