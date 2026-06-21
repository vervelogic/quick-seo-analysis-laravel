<?php

namespace Tests\Unit;

use App\Services\Scanner\SeoScoreCalculator;
use PHPUnit\Framework\TestCase;

class SeoScoreCalculatorTest extends TestCase
{
    public function test_it_scores_a_strong_page_at_one_hundred(): void
    {
        $result = (new SeoScoreCalculator())->calculate([
            'is_reachable' => true,
            'uses_https' => true,
            'http_status' => 200,
            'title_length' => 48,
            'meta_description_length' => 140,
            'h1_count' => 1,
            'canonical' => 'https://example.com',
            'images_missing_alt_count' => 0,
            'response_time_ms' => 300,
            'page_size_bytes' => 100000,
            'internal_links_count' => 5,
            'external_links_count' => 2,
            'robots_meta' => null,
            'has_mobile_viewport' => true,
            'technical_data' => [
                'robots_txt' => ['exists' => true],
                'sitemap_xml' => ['exists' => true],
            ],
            'content' => [
                'visible_word_count' => 800,
                'content_html_ratio' => 18,
            ],
            'performance_data' => [
                'uses_compression' => true,
                'cache_control' => 'max-age=3600',
            ],
            'security_data' => [
                'strict_transport_security' => 'max-age=31536000',
                'x_frame_options' => 'SAMEORIGIN',
                'x_content_type_options' => 'nosniff',
                'content_security_policy' => "default-src 'self'",
                'referrer_policy' => 'strict-origin-when-cross-origin',
            ],
            'open_graph' => [
                'og:title' => 'Example',
                'og:description' => 'Description',
                'og:image' => 'https://example.com/image.jpg',
                'og:url' => 'https://example.com',
                'og:type' => 'website',
            ],
            'twitter_card' => [
                'twitter:card' => 'summary_large_image',
                'twitter:title' => 'Example',
                'twitter:description' => 'Description',
                'twitter:image' => 'https://example.com/image.jpg',
            ],
            'schema' => [
                'json_ld_count' => 1,
                'types' => ['Organization'],
                'has_microdata' => false,
                'has_rdfa' => false,
            ],
        ]);

        $this->assertSame(100, $result['score']);
        $this->assertSame(100, $result['score_breakdown']['overall_score']);
        $this->assertSame([], $result['recommendations']);
    }
}
