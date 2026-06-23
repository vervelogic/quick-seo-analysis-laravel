<?php

namespace App\Services\Scanner;

use App\Models\Scan;
use Illuminate\Support\Facades\Log;

class SeoScanner
{
    public function __construct(
        private readonly PageFetcher $fetcher,
        private readonly HtmlSeoParser $parser,
        private readonly SeoScoreCalculator $scorer,
        private readonly VisibilitySignalAnalyzer $visibility,
        private readonly TopicIntelligenceAnalyzer $topicIntelligence,
        private readonly KeywordTargetingAnalyzer $keywordTargeting,
        private readonly KeywordAlignmentAnalyzer $keywordAlignment,
        private readonly ScanQualityAnalyzer $qualityAnalyzer,
        private readonly PageTypeDetector $pageTypeDetector,
        private readonly RecommendationGroupBuilder $recommendationGroups,
    ) {
    }

    public function scan(Scan $scan, bool $allowHttpFallback = false): Scan
    {
        $scan->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $fetch = $this->fetcher->fetch($scan->normalized_url, $allowHttpFallback);
        $effectiveUrl = $fetch->finalUrl ?: $scan->normalized_url;
        $robotsUrl = $this->domainAssetUrl($effectiveUrl, 'robots.txt');
        $sitemapUrl = $this->domainAssetUrl($effectiveUrl, 'sitemap.xml');
        $robotsFetch = $this->fetcher->fetch($robotsUrl);
        $sitemapUrlFromRobots = $this->sitemapFromRobots($robotsFetch->html);
        $sitemapFetch = $this->fetcher->fetch($sitemapUrlFromRobots ?: $sitemapUrl);
        $parseError = null;
        $parsed = $this->emptyParsedData();

        if ($fetch->html) {
            try {
                $parsed = $this->parser->parse($fetch->html, $effectiveUrl);
            } catch (\Throwable $exception) {
                $parseError = $exception->getMessage();

                Log::warning('SEO scan HTML parse failed; continuing with fetch data.', [
                    'scan_id' => $scan->id,
                    'url' => $effectiveUrl,
                    'message' => $parseError,
                ]);
            }
        }

        $scanQuality = $this->qualityAnalyzer->analyze($fetch, $parsed, $parseError);
        $fetchDiagnostics = $this->fetchDiagnostics($scan, $fetch, $effectiveUrl, $parsed, $scanQuality);

        if ($this->shouldLogDiagnostics($effectiveUrl, $scanQuality)) {
            Log::info('QSA scan fetch diagnostics', $fetchDiagnostics);
        }

        $retrievalIssue = $scanQuality['status'] === 'retrieval_issue_detected';
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
            'is_reachable' => $fetch->reachable && ! $retrievalIssue,
            'uses_https' => parse_url($effectiveUrl, PHP_URL_SCHEME) === 'https',
            'page_size_bytes' => $fetch->pageSizeBytes,
            'response_time_ms' => $fetch->responseTimeMs,
            'technical_data' => $technical,
            'security_data' => $security,
            'performance_data' => $performance,
        ]);

        $analysisInput = array_merge($data, [
            'url' => $effectiveUrl,
        ]);
        $pageType = $this->pageTypeDetector->detect($analysisInput);
        $score = $this->scorer->calculate($data);
        $visibility = $this->visibility->analyze(array_merge($analysisInput, $score));
        $topicIntelligence = $this->topicIntelligence->analyze(array_merge($analysisInput, $score, $visibility));
        $keywordTargeting = $this->keywordTargeting->analyze($analysisInput);
        $keywordAlignment = $scan->scan_mode === 'keyword_focus'
            ? $this->keywordAlignment->analyze($analysisInput, $scan->target_keywords ?? [])
            : null;
        $scoreBreakdown = array_merge($score['score_breakdown'], $visibility['score_breakdown'], $topicIntelligence['score_breakdown'], [
            'overall_score' => $visibility['score_breakdown']['overall_visibility_score'],
        ]);
        $rawRecommendations = array_values(array_merge(
            $score['recommendations'],
            $visibility['visibility_data']['opportunities'],
            $topicIntelligence['opportunities']
        ));
        $recommendations = $this->recommendationGroups->filterDetailedItems($rawRecommendations, $pageType['type'] ?? 'Unknown');
        $groupedRecommendations = $this->recommendationGroups->build($rawRecommendations, $analysisInput, $pageType, $scanQuality);

        if ($retrievalIssue) {
            $scoreBreakdown = array_map(fn () => 0, $scoreBreakdown);
            $scoreBreakdown['overall_visibility_score'] = 0;
            $scoreBreakdown['overall_score'] = 0;
            $recommendations = [[
                'category' => 'Scan Quality',
                'issue' => 'Content Retrieval Issue Detected',
                'impact' => 'high',
                'difficulty' => 'medium',
                'estimated_gain' => 0,
                'recommendation' => 'Review fetch diagnostics before using this audit for SEO or AI visibility decisions.',
                'why_it_matters' => 'Critical page signals such as title, meta description or readable body content were missing from the server-side response.',
                'how_to_fix' => 'Check whether the website blocks server-side requests, uses bot protection, serves a challenge page, times out, or returns different HTML to non-browser clients.',
            ]];
            $groupedRecommendations = $this->recommendationGroups->build($recommendations, $analysisInput, $pageType, $scanQuality);
        }

        $visibilityData = array_merge($visibility['visibility_data'], [
            'page_type' => $pageType,
            'scan_quality' => $scanQuality,
            'recommendation_groups' => $groupedRecommendations,
            'recommendation_count' => count($recommendations),
        ]);

        $scan->result()->updateOrCreate(
            ['scan_id' => $scan->id],
            array_merge($data, [
                'score' => $retrievalIssue ? 0 : $scoreBreakdown['overall_visibility_score'],
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
                'keyword_targeting_data' => $keywordTargeting,
                'keyword_alignment_data' => $keywordAlignment,
                'visibility_data' => $visibilityData,
                'opportunity_data' => $groupedRecommendations,
                'score_breakdown' => $scoreBreakdown,
                'raw' => [
                    'requested_url' => $scan->url,
                    'scan_target_url' => $scan->normalized_url,
                    'final_url' => $effectiveUrl,
                    'redirect_chain' => $fetch->redirectChain,
                    'error' => $fetch->error,
                    'parse_error' => $parseError,
                    'headers' => $fetch->headers,
                    'scan_quality' => $scanQuality,
                    'page_type' => $pageType,
                    'fetch_diagnostics' => $fetchDiagnostics,
                ],
            ])
        );

        $scan->update([
            'status' => ($fetch->reachable && ! $retrievalIssue) ? 'completed' : 'failed',
            'error_message' => match (true) {
                $retrievalIssue => 'Content retrieval issue detected. QSA could not extract reliable page content for scoring.',
                ! $fetch->reachable => $fetch->error ?: 'The page was not reachable.',
                default => null,
            },
            'completed_at' => now(),
        ]);

        return $scan->refresh();
    }

    private function emptyParsedData(): array
    {
        return [
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
    }

    private function fetchDiagnostics(Scan $scan, PageFetchResult $fetch, string $effectiveUrl, array $parsed, array $scanQuality): array
    {
        $html = (string) ($fetch->html ?? '');

        return [
            'scan_id' => $scan->id,
            'original_url' => $scan->url,
            'scan_target_url' => $scan->normalized_url,
            'final_url' => $effectiveUrl,
            'http_status' => $fetch->status,
            'redirect_chain' => $fetch->redirectChain,
            'content_type' => $this->headerValue($fetch->headers, 'Content-Type'),
            'response_size_bytes' => $fetch->pageSizeBytes,
            'first_2048_html' => mb_substr($html, 0, 2048),
            'extracted_title' => $parsed['title'] ?? '',
            'extracted_meta_description' => $parsed['meta_description'] ?? null,
            'extracted_h1_count' => (int) ($parsed['h1_count'] ?? 0),
            'extracted_body_text_length' => mb_strlen((string) data_get($parsed, 'content.visible_text', '')),
            'scan_quality' => $scanQuality,
        ];
    }

    private function shouldLogDiagnostics(string $effectiveUrl, array $scanQuality): bool
    {
        $host = strtolower(parse_url($effectiveUrl, PHP_URL_HOST) ?: '');

        return str_contains($host, 'truffleindia.com') || $scanQuality['status'] !== 'full_content_retrieved';
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
