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
}
