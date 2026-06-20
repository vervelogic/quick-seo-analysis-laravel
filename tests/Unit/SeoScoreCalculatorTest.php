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
            'robots_meta' => null,
        ]);

        $this->assertSame(100, $result['score']);
        $this->assertSame([], $result['recommendations']);
    }
}
