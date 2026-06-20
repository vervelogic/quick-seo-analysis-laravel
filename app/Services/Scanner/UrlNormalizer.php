<?php

namespace App\Services\Scanner;

class UrlNormalizer
{
    public function normalize(string $url): string
    {
        $url = trim($url);

        if (! preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return rtrim($scheme.'://'.$host.$path.$query, '/');
    }

    public function host(string $url): string
    {
        return strtolower(parse_url($url, PHP_URL_HOST) ?: '');
    }
}
