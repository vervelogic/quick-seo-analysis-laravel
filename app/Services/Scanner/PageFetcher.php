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
            return $this->attemptFetch($url, $started, $maxBytes);
        } catch (\Throwable $exception) {
            if ($this->canFallbackToHttp($url)) {
                try {
                    return $this->attemptFetch($this->withScheme($url, 'http'), $started, $maxBytes);
                } catch (\Throwable $fallbackException) {
                    $exception = $fallbackException;
                }
            }

            return new PageFetchResult(
                reachable: false,
                status: null,
                html: null,
                responseTimeMs: (int) round((microtime(true) - $started) * 1000),
                pageSizeBytes: 0,
                headers: [],
                finalUrl: $url,
                error: $exception->getMessage(),
            );
        }
    }

    private function attemptFetch(string $url, float $started, int $maxBytes): PageFetchResult
    {
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
    }

    private function canFallbackToHttp(string $url): bool
    {
        return strtolower(parse_url($url, PHP_URL_SCHEME) ?: '') === 'https';
    }

    private function withScheme(string $url, string $scheme): string
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$path.$query;
    }
}
