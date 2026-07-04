<?php

namespace Tests\Unit;

use App\Services\Scanner\HtmlSeoParser;
use PHPUnit\Framework\TestCase;

class HtmlSeoParserTest extends TestCase
{
    public function test_it_resolves_relative_canonical_urls(): void
    {
        $html = <<<'HTML'
        <!doctype html>
        <html>
        <head>
            <title>Example</title>
            <link rel="canonical stylesheet" href="/seo/checker">
        </head>
        <body>
            <h1>Example</h1>
        </body>
        </html>
        HTML;

        $parsed = (new HtmlSeoParser())->parse($html, 'https://qsa.vervelogic.com/tools/free-audit');

        $this->assertSame('https://qsa.vervelogic.com/seo/checker', $parsed['canonical']);
    }

    public function test_open_graph_tags_do_not_count_as_rdfa(): void
    {
        $html = <<<'HTML'
        <!doctype html>
        <html>
        <head>
            <title>Example</title>
            <meta property="og:title" content="OG title">
            <script type="application/ld+json">
                {"@context":"https://schema.org","@type":"Organization","contactPoint":{"@type":"ContactPoint","contactType":"support"}}
            </script>
        </head>
        <body>
            <h1>Example</h1>
        </body>
        </html>
        HTML;

        $parsed = (new HtmlSeoParser())->parse($html, 'https://qsa.vervelogic.com');

        $this->assertFalse($parsed['schema']['has_rdfa']);
        $this->assertTrue($parsed['schema']['details']['organization']);
        $this->assertTrue($parsed['schema']['details']['contactpoint']);
    }
}
