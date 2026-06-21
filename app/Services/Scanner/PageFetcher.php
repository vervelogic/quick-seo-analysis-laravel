<?php

namespace App\Services\Scanner;

use Illuminate\Support\Facades\Http;

class PageFetcher
{
    public function __construct(private readonly PublicUrlGuard $urlGuard)
    {
    }

    public function fetch(string $url): PageFetchResult
    {
        $started = microtime(true);
        $maxBytes = config('qsa.scan_max_bytes');

        try {
            $this->urlGuard->assertAllowed($url);

            $response = Http::timeout(config('qsa.scan_timeout'))
                ->connectTimeout(6)
                ->withOptions([
                    'allow_redirects' => false,
                ])
                ->withHeaders([
                    'User-Agent' => config('app.name').' SEO Scanner (+'.config('app.url').')',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($url);

            $body = substr($response->body(), 0, $maxBytes);

            $handlerStats = $response->handlerStats();

            return new PageFetchResult(
                reachable: $response->successful(),
                status: $response->status(),
                html: $body,
                responseTimeMs: (int) round((microtime(true) - $started) * 1000),
                pageSizeBytes: strlen($body),
                headers: $response->headers(),
                finalUrl: $handlerStats['url'] ?? $url,
            );
        } catch (\Throwable $exception) {
            return new PageFetchResult(
                reachable: false,
                status: null,
                html: null,
                responseTimeMs: (int) round((microtime(true) - $started) * 1000),
                pageSizeBytes: 0,
                headers: [],
                error: $exception->getMessage(),
            );
        }
    }
}
