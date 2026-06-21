<?php

namespace App\Services\Scanner;

use App\Models\Scan;

class SeoScanner
{
    public function __construct(
        private readonly PageFetcher $fetcher,
        private readonly HtmlSeoParser $parser,
        private readonly SeoScoreCalculator $scorer,
        private readonly VisibilitySignalAnalyzer $visibility,
        private readonly TopicIntelligenceAnalyzer $topicIntelligence,
    ) {
    }

    public function scan(Scan $scan): Scan
    {
        $scan->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $fetch = $this->fetcher->fetch($scan->normalized_url);
        $effectiveUrl = $fetch->finalUrl ?: $scan->normalized_url;
        $robotsUrl = $this->domainAssetUrl($effectiveUrl, 'robots.txt');
        $sitemapUrl = $this->domainAssetUrl($effectiveUrl, 'sitemap.xml');
        $robotsFetch = $this->fetcher->fetch($robotsUrl);
        $sitemapUrlFromRobots = $this->sitemapFromRobots($robotsFetch->html);
        $sitemapFetch = $this->fetcher->fetch($sitemapUrlFromRobots ?: $sitemapUrl);
        $parsed = $fetch->html ? $this->parser->parse($fetch->html, $effectiveUrl) : [
            'title' => '',
            'title_length' => 0,
            'meta_description' => null,
            'meta_description_length' => 0,
            'h1_count' => 0,
            'canonical' => null,
            'robots_meta' => null,
            'viewport' => null,
            'has_mobile_viewport' => false,
            'internal_links_count' => 0,
            'external_links_count' => 0,
            'images_count' => 0,
            'images_missing_alt_count' => 0,
            'open_graph' => [],
            'twitter_card' => [],
            'schema' => [],
            'content' => [
                'visible_word_count' => 0,
                'unique_word_count' => 0,
                'thin_content' => true,
                'content_html_ratio' => 0,
                'questions' => [],
                'entities' => [],
                'footer_text' => '',
                'visible_text' => '',
            ],
            'links' => [],
            'headings' => [],
            'heading_levels' => ['h1' => [], 'h2' => [], 'h3' => []],
        ];

        $security = $this->securityHeaders($fetch->headers);
        $performance = $this->performanceHeaders($fetch->headers, $fetch->responseTimeMs, $fetch->pageSizeBytes);
        $technical = [
            'robots_txt' => [
                'exists' => $robotsFetch->reachable,
                'status' => $robotsFetch->status,
                'url' => $robotsUrl,
            ],
            'sitemap_xml' => [
                'exists' => $sitemapFetch->reachable,
                'status' => $sitemapFetch->status,
                'url' => $sitemapUrlFromRobots ?: $sitemapUrl,
                'discovered_from_robots' => filled($sitemapUrlFromRobots),
            ],
            'mobile_viewport' => [
                'exists' => (bool) $parsed['has_mobile_viewport'],
                'content' => $parsed['viewport'],
            ],
        ];

        $data = array_merge($parsed, [
            'http_status' => $fetch->status,
            'is_reachable' => $fetch->reachable,
            'uses_https' => parse_url($effectiveUrl, PHP_URL_SCHEME) === 'https',
            'page_size_bytes' => $fetch->pageSizeBytes,
            'response_time_ms' => $fetch->responseTimeMs,
            'technical_data' => $technical,
            'security_data' => $security,
            'performance_data' => $performance,
        ]);

        $score = $this->scorer->calculate($data);
        $visibility = $this->visibility->analyze(array_merge($data, $score, [
            'url' => $effectiveUrl,
        ]));
        $topicIntelligence = $this->topicIntelligence->analyze(array_merge($data, $score, $visibility, [
            'url' => $effectiveUrl,
        ]));
        $scoreBreakdown = array_merge($score['score_breakdown'], $visibility['score_breakdown'], $topicIntelligence['score_breakdown'], [
            'overall_score' => $visibility['score_breakdown']['overall_visibility_score'],
        ]);
        $recommendations = array_values(array_merge(
            $score['recommendations'],
            $visibility['visibility_data']['opportunities'],
            $topicIntelligence['opportunities']
        ));

        $scan->result()->updateOrCreate(
            ['scan_id' => $scan->id],
            array_merge($data, [
                'score' => $scoreBreakdown['overall_visibility_score'],
                'checks' => $score['checks'],
                'recommendations' => $recommendations,
                'technical_data' => $technical,
                'on_page_data' => [
                    'title' => $parsed['title'],
                    'title_length' => $parsed['title_length'],
                    'meta_description' => $parsed['meta_description'],
                    'meta_description_length' => $parsed['meta_description_length'],
                    'h1_count' => $parsed['h1_count'],
                    'canonical' => $parsed['canonical'],
                    'robots_meta' => $parsed['robots_meta'],
                    'heading_levels' => $parsed['heading_levels'] ?? ['h1' => [], 'h2' => [], 'h3' => []],
                ],
                'content_data' => $parsed['content'],
                'performance_data' => $performance,
                'security_data' => $security,
                'social_data' => [
                    'open_graph' => $parsed['open_graph'],
                    'twitter_card' => $parsed['twitter_card'],
                ],
                'structured_data' => $parsed['schema'],
                'ai_readiness_data' => $score['ai_readiness_data'],
                'ai_visibility_data' => $visibility['ai_visibility_data'],
                'geo_data' => $visibility['geo_data'],
                'aeo_data' => $visibility['aeo_data'],
                'topic_intelligence_data' => $topicIntelligence['topic_intelligence_data'],
                'ranking_potential_data' => $topicIntelligence['ranking_potential_data'],
                'prompt_intelligence_data' => $topicIntelligence['prompt_intelligence_data'],
                'content_coverage_data' => $topicIntelligence['content_coverage_data'],
                'ai_citation_readiness_data' => $topicIntelligence['ai_citation_readiness_data'],
                'visibility_data' => $visibility['visibility_data'],
                'opportunity_data' => $recommendations,
                'score_breakdown' => $scoreBreakdown,
                'raw' => [
                    'requested_url' => $scan->normalized_url,
                    'final_url' => $effectiveUrl,
                    'redirect_chain' => $fetch->redirectChain,
                    'error' => $fetch->error,
                    'headers' => $fetch->headers,
                ],
            ])
        );

        $scan->update([
            'status' => $fetch->reachable ? 'completed' : 'failed',
            'error_message' => $fetch->reachable ? null : ($fetch->error ?: 'The page was not reachable.'),
            'completed_at' => now(),
        ]);

        return $scan->refresh();
    }

    private function domainAssetUrl(string $url, string $path): string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($url, PHP_URL_HOST) ?: '';

        return $scheme.'://'.$host.'/'.ltrim($path, '/');
    }

    private function sitemapFromRobots(?string $robots): ?string
    {
        if (! $robots) {
            return null;
        }

        foreach (preg_split('/\r\n|\r|\n/', $robots) as $line) {
            if (str_starts_with(strtolower(trim($line)), 'sitemap:')) {
                return trim(substr(trim($line), strlen('sitemap:')));
            }
        }

        return null;
    }

    private function securityHeaders(array $headers): array
    {
        return [
            'strict_transport_security' => $this->headerValue($headers, 'Strict-Transport-Security'),
            'x_frame_options' => $this->headerValue($headers, 'X-Frame-Options'),
            'x_content_type_options' => $this->headerValue($headers, 'X-Content-Type-Options'),
            'content_security_policy' => $this->headerValue($headers, 'Content-Security-Policy'),
            'referrer_policy' => $this->headerValue($headers, 'Referrer-Policy'),
        ];
    }

    private function performanceHeaders(array $headers, int $responseTimeMs, int $pageSizeBytes): array
    {
        $encoding = strtolower((string) $this->headerValue($headers, 'Content-Encoding'));

        return [
            'content_encoding' => $encoding ?: null,
            'uses_compression' => str_contains($encoding, 'gzip') || str_contains($encoding, 'br'),
            'cache_control' => $this->headerValue($headers, 'Cache-Control'),
            'server' => $this->headerValue($headers, 'Server'),
            'response_time_ms' => $responseTimeMs,
            'page_size_bytes' => $pageSizeBytes,
        ];
    }

    private function headerValue(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $values) {
            if (strtolower($key) === strtolower($name)) {
                return is_array($values) ? ($values[0] ?? null) : $values;
            }
        }

        return null;
    }
}
