<?php

namespace App\Services\Scanner;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PageFetcher
{
    private const MAX_REDIRECTS = 5;

    public function __construct(private readonly PublicUrlGuard $urlGuard)
    {
    }

    public function fetch(string $url, bool $allowHttpFallback = false): PageFetchResult
    {
        $started = microtime(true);
        $maxBytes = config('qsa.scan_max_bytes');

        try {
            return $this->attemptFetch($url, $started, $maxBytes);
        } catch (\Throwable $exception) {
            if ($allowHttpFallback && $this->canFallbackToHttp($url)) {
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
                error: $this->friendlyError($exception),
            );
        }
    }

    private function attemptFetch(string $url, float $started, int $maxBytes): PageFetchResult
    {
        $currentUrl = $url;
        $redirectChain = [];

        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            $this->urlGuard->assertAllowed($currentUrl);

            $response = $this->sendRequest($currentUrl);
            $status = $response->status();

            if (! $this->isRedirect($status)) {
                return $this->resultFromResponse($response, $currentUrl, $started, $maxBytes, $redirectChain);
            }

            $location = $this->headerValue($response->headers(), 'Location');

            if (! $location) {
                return $this->failedRedirectResult($currentUrl, $status, $response->headers(), $started, $redirectChain, 'Redirect response did not include a Location header.');
            }

            $nextUrl = $this->resolveRedirectUrl($currentUrl, $location);
            $redirectChain[] = [
                'from' => $currentUrl,
                'to' => $nextUrl,
                'status' => $status,
            ];

            if (! $this->urlGuard->isAllowed($nextUrl)) {
                return $this->failedRedirectResult($currentUrl, $status, $response->headers(), $started, $redirectChain, 'Redirect target is not a public website URL.');
            }

            $currentUrl = $nextUrl;
        }

        return new PageFetchResult(
            reachable: false,
            status: null,
            html: null,
            responseTimeMs: (int) round((microtime(true) - $started) * 1000),
            pageSizeBytes: 0,
            headers: [],
            finalUrl: $currentUrl,
            error: 'Too many redirects.',
            redirectChain: $redirectChain,
        );
    }

    private function sendRequest(string $url): Response
    {
        return Http::timeout(config('qsa.scan_timeout'))
            ->connectTimeout((int) config('qsa.scan_connect_timeout', 8))
            ->retry(1, 350, throw: false)
            ->withOptions([
                'allow_redirects' => false,
                'decode_content' => true,
                'http_errors' => false,
                'curl' => $this->curlOptions(),
            ])
            ->withHeaders($this->browserHeaders($url))
            ->get($url);
    }

    private function resultFromResponse(Response $response, string $url, float $started, int $maxBytes, array $redirectChain): PageFetchResult
    {
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
            redirectChain: $redirectChain,
        );
    }

    private function failedRedirectResult(string $url, int $status, array $headers, float $started, array $redirectChain, string $error): PageFetchResult
    {
        return new PageFetchResult(
            reachable: false,
            status: $status,
            html: null,
            responseTimeMs: (int) round((microtime(true) - $started) * 1000),
            pageSizeBytes: 0,
            headers: $headers,
            finalUrl: $url,
            error: $error,
            redirectChain: $redirectChain,
        );
    }

    private function isRedirect(int $status): bool
    {
        return in_array($status, [301, 302, 303, 307, 308], true);
    }

    private function resolveRedirectUrl(string $currentUrl, string $location): string
    {
        $location = trim($location);

        if (preg_match('/^https?:\/\//i', $location)) {
            return $location;
        }

        $current = parse_url($currentUrl);
        $scheme = $current['scheme'] ?? 'https';
        $host = $current['host'] ?? '';
        $port = isset($current['port']) ? ':'.$current['port'] : '';

        if (str_starts_with($location, '//')) {
            return $scheme.':'.$location;
        }

        if (str_starts_with($location, '/')) {
            return $scheme.'://'.$host.$port.$location;
        }

        $path = $current['path'] ?? '/';
        $basePath = str_ends_with($path, '/') ? $path : dirname($path).'/';

        return $scheme.'://'.$host.$port.$basePath.$location;
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

    private function browserHeaders(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST) ?: '';

        return [
            'User-Agent' => config('qsa.scan_user_agent'),
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Upgrade-Insecure-Requests' => '1',
            'Referer' => 'https://'.$host.'/',
        ];
    }

    private function curlOptions(): array
    {
        $options = [];

        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }

        return $options;
    }

    private function friendlyError(\Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains(strtolower($message), 'timed out')) {
            return 'The page request timed out from the scanner server.';
        }

        if (str_contains(strtolower($message), 'could not resolve')) {
            return 'The domain could not be resolved from the scanner server.';
        }

        if (str_contains(strtolower($message), 'ssl') || str_contains(strtolower($message), 'certificate')) {
            return 'The page could not be fetched because of an SSL or certificate error.';
        }

        return $message;
    }
}
