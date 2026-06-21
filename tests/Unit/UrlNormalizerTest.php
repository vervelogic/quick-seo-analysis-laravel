<?php

namespace Tests\Unit;

use App\Services\Scanner\UrlNormalizer;
use PHPUnit\Framework\TestCase;

class UrlNormalizerTest extends TestCase
{
    public function test_it_adds_https_when_scheme_is_missing(): void
    {
        $normalizer = new UrlNormalizer();

        $this->assertSame('https://example.com/path', $normalizer->normalize('example.com/path'));
    }

    public function test_it_strips_tracking_parameters_before_scanning(): void
    {
        $normalizer = new UrlNormalizer();

        $this->assertSame(
            'https://oesd.com/expert-embroidery-kit/',
            $normalizer->normalize('https://oesd.com/expert-embroidery-kit/?gad_source=1&gad_campaignid=20947086719&gclid=abc')
        );
    }

    public function test_it_preserves_non_tracking_query_parameters(): void
    {
        $normalizer = new UrlNormalizer();

        $this->assertSame(
            'https://example.com/products?category=shirts&page=2',
            $normalizer->normalize('https://example.com/products?utm_source=google&category=shirts&page=2&fbclid=abc')
        );
    }
}
