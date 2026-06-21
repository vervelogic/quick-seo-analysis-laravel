<?php

namespace App\Services\Scanner;

class SeoScoreCalculator
{
    public function calculate(array $data): array
    {
        $technicalScore = $this->average([
            (bool) ($data['is_reachable'] ?? false),
            in_array($data['http_status'] ?? null, [200, 201, 202, 204], true),
            (bool) ($data['uses_https'] ?? false),
            (bool) data_get($data, 'technical_data.robots_txt.exists'),
            (bool) data_get($data, 'technical_data.sitemap_xml.exists'),
            (bool) ($data['has_mobile_viewport'] ?? false),
        ]);

        $onPageScore = $this->average([
            ($data['title_length'] ?? 0) >= 20 && ($data['title_length'] ?? 0) <= 65,
            ($data['meta_description_length'] ?? 0) >= 70 && ($data['meta_description_length'] ?? 0) <= 170,
            ($data['h1_count'] ?? 0) === 1,
            trim((string) ($data['canonical'] ?? '')) !== '',
            ! str_contains(strtolower((string) ($data['robots_meta'] ?? '')), 'noindex'),
        ]);

        $contentScore = $this->average([
            (int) data_get($data, 'content.visible_word_count', 0) >= 300,
            (float) data_get($data, 'content.content_html_ratio', 0) >= 5,
            ($data['internal_links_count'] ?? 0) > 0,
            ($data['external_links_count'] ?? 0) > 0,
        ]);

        $performanceScore = $this->average([
            ($data['response_time_ms'] ?? 99999) <= 1500,
            ($data['page_size_bytes'] ?? 99999999) <= 1024 * 1024,
            (bool) data_get($data, 'performance_data.uses_compression'),
            filled(data_get($data, 'performance_data.cache_control')),
        ]);

        $securityScore = $this->average([
            filled(data_get($data, 'security_data.strict_transport_security')),
            filled(data_get($data, 'security_data.x_frame_options')),
            filled(data_get($data, 'security_data.x_content_type_options')),
            filled(data_get($data, 'security_data.content_security_policy')),
            filled(data_get($data, 'security_data.referrer_policy')),
        ]);

        $socialScore = $this->average([
            filled(data_get($data, 'open_graph.og:title')),
            filled(data_get($data, 'open_graph.og:description')),
            filled(data_get($data, 'open_graph.og:image')),
            filled(data_get($data, 'open_graph.og:url')),
            filled(data_get($data, 'open_graph.og:type')),
            filled(data_get($data, 'twitter_card.twitter:card')),
            filled(data_get($data, 'twitter_card.twitter:title')),
            filled(data_get($data, 'twitter_card.twitter:description')),
            filled(data_get($data, 'twitter_card.twitter:image')),
        ]);

        $structuredDataScore = $this->average([
            (int) data_get($data, 'schema.json_ld_count', 0) > 0,
            count((array) data_get($data, 'schema.types', [])) > 0,
            (bool) data_get($data, 'schema.has_microdata') || (bool) data_get($data, 'schema.has_rdfa') || (int) data_get($data, 'schema.json_ld_count', 0) > 0,
        ]);

        $aiReadinessData = [
            'clear_title' => ($data['title_length'] ?? 0) >= 20,
            'clear_description' => ($data['meta_description_length'] ?? 0) >= 70,
            'indexable' => ! str_contains(strtolower((string) ($data['robots_meta'] ?? '')), 'noindex'),
            'structured_data' => $structuredDataScore > 0,
            'substantial_content' => (int) data_get($data, 'content.visible_word_count', 0) >= 300,
            'crawl_guidance' => (bool) data_get($data, 'technical_data.robots_txt.exists') || (bool) data_get($data, 'technical_data.sitemap_xml.exists'),
        ];

        $aiReadinessScore = $this->average(array_values($aiReadinessData));

        $scoreBreakdown = [
            'technical_score' => $technicalScore,
            'on_page_score' => $onPageScore,
            'content_score' => $contentScore,
            'performance_score' => $performanceScore,
            'security_score' => $securityScore,
            'social_score' => $socialScore,
            'structured_data_score' => $structuredDataScore,
            'ai_readiness_score' => $aiReadinessScore,
        ];

        $overallScore = (int) round(
            ($technicalScore * 0.18)
            + ($onPageScore * 0.18)
            + ($contentScore * 0.14)
            + ($performanceScore * 0.12)
            + ($securityScore * 0.12)
            + ($socialScore * 0.10)
            + ($structuredDataScore * 0.08)
            + ($aiReadinessScore * 0.08)
        );

        $scoreBreakdown['overall_score'] = $overallScore;

        return [
            'score' => $overallScore,
            'checks' => $this->checks($data),
            'recommendations' => $this->recommendations($data, $scoreBreakdown),
            'score_breakdown' => $scoreBreakdown,
            'ai_readiness_data' => $aiReadinessData,
        ];
    }

    private function checks(array $data): array
    {
        return [
            'reachable' => ['label' => 'URL reachable', 'passed' => (bool) ($data['is_reachable'] ?? false), 'weight' => 10],
            'https' => ['label' => 'HTTPS enabled', 'passed' => (bool) ($data['uses_https'] ?? false), 'weight' => 8],
            'status' => ['label' => 'Healthy HTTP status', 'passed' => in_array($data['http_status'] ?? null, [200, 201, 202, 204], true), 'weight' => 8],
            'title' => ['label' => 'Title tag length', 'passed' => ($data['title_length'] ?? 0) >= 20 && ($data['title_length'] ?? 0) <= 65, 'weight' => 8],
            'description' => ['label' => 'Meta description length', 'passed' => ($data['meta_description_length'] ?? 0) >= 70 && ($data['meta_description_length'] ?? 0) <= 170, 'weight' => 8],
            'h1' => ['label' => 'Single H1', 'passed' => ($data['h1_count'] ?? 0) === 1, 'weight' => 6],
            'canonical' => ['label' => 'Canonical tag present', 'passed' => filled($data['canonical'] ?? null), 'weight' => 6],
            'robots_txt' => ['label' => 'robots.txt available', 'passed' => (bool) data_get($data, 'technical_data.robots_txt.exists'), 'weight' => 5],
            'sitemap' => ['label' => 'Sitemap available', 'passed' => (bool) data_get($data, 'technical_data.sitemap_xml.exists'), 'weight' => 5],
            'schema' => ['label' => 'Structured data present', 'passed' => (int) data_get($data, 'schema.json_ld_count', 0) > 0 || (bool) data_get($data, 'schema.has_microdata') || (bool) data_get($data, 'schema.has_rdfa'), 'weight' => 5],
            'social' => ['label' => 'Social preview metadata', 'passed' => filled(data_get($data, 'open_graph.og:title')) && filled(data_get($data, 'twitter_card.twitter:card')), 'weight' => 5],
            'content' => ['label' => 'Substantial visible content', 'passed' => (int) data_get($data, 'content.visible_word_count', 0) >= 300, 'weight' => 6],
            'images_alt' => ['label' => 'Images include alt text', 'passed' => ($data['images_missing_alt_count'] ?? 0) === 0, 'weight' => 5],
            'performance' => ['label' => 'Fast response', 'passed' => ($data['response_time_ms'] ?? 99999) <= 1500 && ($data['page_size_bytes'] ?? 99999999) <= 1024 * 1024, 'weight' => 6],
            'security' => ['label' => 'Security headers present', 'passed' => filled(data_get($data, 'security_data.strict_transport_security')) && filled(data_get($data, 'security_data.x_content_type_options')), 'weight' => 5],
        ];
    }

    private function recommendations(array $data, array $scores): array
    {
        $items = [];

        $this->addIf($items, ! data_get($data, 'technical_data.robots_txt.exists'), 'Technical SEO', 'robots.txt was not found.', 'medium', 'Add a robots.txt file at the domain root.', 'Create /robots.txt and include crawler rules plus a Sitemap directive.');
        $this->addIf($items, ! data_get($data, 'technical_data.sitemap_xml.exists'), 'Technical SEO', 'sitemap.xml was not found.', 'high', 'Publish an XML sitemap and reference it in robots.txt.', 'Generate /sitemap.xml from your CMS or framework and add "Sitemap: https://domain.com/sitemap.xml" to robots.txt.');
        $this->addIf($items, ! ($data['has_mobile_viewport'] ?? false), 'Technical SEO', 'Mobile viewport meta tag is missing.', 'high', 'Add a responsive viewport meta tag.', 'Add <meta name="viewport" content="width=device-width, initial-scale=1"> inside <head>.');
        $this->addIf($items, ($data['title_length'] ?? 0) < 20 || ($data['title_length'] ?? 0) > 65, 'On-Page SEO', 'Title tag length is outside the recommended range.', 'high', 'Use a focused 20-65 character title.', 'Rewrite the title to include the main topic, brand, and search intent.');
        $this->addIf($items, ($data['meta_description_length'] ?? 0) < 70 || ($data['meta_description_length'] ?? 0) > 170, 'On-Page SEO', 'Meta description length is weak or missing.', 'medium', 'Add a clear 70-170 character meta description.', 'Write a benefit-led description that summarizes the page and encourages clicks.');
        $this->addIf($items, ($data['h1_count'] ?? 0) !== 1, 'On-Page SEO', 'The page should have exactly one H1.', 'medium', 'Use one descriptive H1.', 'Make the primary page headline an H1 and demote extra H1s to H2/H3.');
        $this->addIf($items, blank($data['canonical'] ?? null), 'On-Page SEO', 'Canonical URL is missing.', 'medium', 'Add a canonical tag.', 'Add <link rel="canonical" href="https://domain.com/page"> to the page head.');
        $this->addIf($items, str_contains(strtolower((string) ($data['robots_meta'] ?? '')), 'noindex'), 'Technical SEO', 'Robots meta contains noindex.', 'high', 'Remove noindex if the page should rank.', 'Update the robots meta tag or CMS SEO settings to allow indexing.');
        $this->addIf($items, (int) data_get($data, 'content.visible_word_count', 0) < 300, 'Content', 'Visible content appears thin.', 'high', 'Expand the page with useful, original content.', 'Add sections answering user questions, service details, proof points, FAQs, and internal links.');
        $this->addIf($items, (float) data_get($data, 'content.content_html_ratio', 0) < 5, 'Content', 'Content-to-HTML ratio is low.', 'low', 'Reduce template weight or add more useful text.', 'Trim unnecessary markup/scripts and strengthen body copy.');
        $this->addIf($items, ($data['internal_links_count'] ?? 0) === 0, 'Images & Links', 'No internal links were found.', 'medium', 'Add relevant internal links.', 'Link to related services, articles, pricing, contact, or important conversion pages.');
        $this->addIf($items, ($data['images_missing_alt_count'] ?? 0) > 0, 'Images & Links', 'Some images are missing alt text.', 'medium', 'Add descriptive alt attributes.', 'Use concise alt text for meaningful images; leave decorative images empty only when appropriate.');
        $this->addIf($items, (int) data_get($data, 'schema.json_ld_count', 0) === 0 && ! data_get($data, 'schema.has_microdata') && ! data_get($data, 'schema.has_rdfa'), 'Structured Data', 'No structured data was detected.', 'medium', 'Add JSON-LD schema.', 'Add Organization, WebSite, BreadcrumbList, Article, Product, LocalBusiness, or FAQ schema where relevant.');
        $this->addIf($items, $scores['social_score'] < 70, 'Social Preview', 'Social preview metadata is incomplete.', 'low', 'Add Open Graph and Twitter Card tags.', 'Include og:title, og:description, og:image, og:url, og:type, twitter:card, twitter:title, twitter:description, and twitter:image.');
        $this->addIf($items, ! data_get($data, 'performance_data.uses_compression'), 'Performance', 'Compression was not detected.', 'medium', 'Enable gzip or Brotli compression.', 'Enable compression in Apache/Nginx/CDN so text assets transfer faster.');
        $this->addIf($items, blank(data_get($data, 'performance_data.cache_control')), 'Performance', 'Cache-Control header is missing.', 'low', 'Add caching headers for static assets.', 'Configure long-lived cache headers for versioned CSS, JS, and images.');
        $this->addIf($items, $scores['security_score'] < 70, 'Security', 'Important security headers are missing.', 'medium', 'Add standard browser security headers.', 'Configure HSTS, X-Frame-Options, X-Content-Type-Options, CSP, and Referrer-Policy.');
        $this->addIf($items, $scores['ai_readiness_score'] < 75, 'AI/GEO Readiness', 'AI readability signals can be improved.', 'medium', 'Make the page easier for search and AI systems to interpret.', 'Use clear titles, strong summaries, structured data, crawl guidance, substantial copy, and entity-rich content.');

        return $items;
    }

    private function addIf(array &$items, bool $condition, string $category, string $issue, string $impact, string $recommendation, string $howToFix): void
    {
        if (! $condition) {
            return;
        }

        $items[] = [
            'category' => $category,
            'issue' => $issue,
            'impact' => $impact,
            'recommendation' => $recommendation,
            'how_to_fix' => $howToFix,
        ];
    }

    private function average(array $checks): int
    {
        if ($checks === []) {
            return 0;
        }

        $passed = count(array_filter($checks));

        return (int) round(($passed / count($checks)) * 100);
    }
}
