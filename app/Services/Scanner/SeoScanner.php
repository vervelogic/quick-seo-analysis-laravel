<?php

namespace App\Services\Scanner;

use App\Models\Scan;

class SeoScanner
{
    public function __construct(
        private readonly PageFetcher $fetcher,
        private readonly HtmlSeoParser $parser,
        private readonly SeoScoreCalculator $scorer,
    ) {
    }

    public function scan(Scan $scan): Scan
    {
        $scan->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $fetch = $this->fetcher->fetch($scan->normalized_url);
        $parsed = $fetch->html ? $this->parser->parse($fetch->html, $fetch->finalUrl ?: $scan->normalized_url) : [
            'title' => '',
            'title_length' => 0,
            'meta_description' => null,
            'meta_description_length' => 0,
            'h1_count' => 0,
            'canonical' => null,
            'robots_meta' => null,
            'internal_links_count' => 0,
            'external_links_count' => 0,
            'images_count' => 0,
            'images_missing_alt_count' => 0,
        ];

        $data = array_merge($parsed, [
            'http_status' => $fetch->status,
            'is_reachable' => $fetch->reachable,
            'uses_https' => parse_url($scan->normalized_url, PHP_URL_SCHEME) === 'https',
            'page_size_bytes' => $fetch->pageSizeBytes,
            'response_time_ms' => $fetch->responseTimeMs,
        ]);

        $score = $this->scorer->calculate($data);

        $scan->result()->updateOrCreate(
            ['scan_id' => $scan->id],
            array_merge($data, [
                'score' => $score['score'],
                'checks' => $score['checks'],
                'recommendations' => $score['recommendations'],
                'raw' => [
                    'final_url' => $fetch->finalUrl,
                    'error' => $fetch->error,
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
}
