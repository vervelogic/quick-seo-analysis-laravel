<?php

namespace App\Services\Scanner;

class UrlNormalizer
{
    private const TRACKING_PARAMETERS = [
        'gclid',
        'fbclid',
        'msclkid',
        'gad_source',
        'gad_campaignid',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ];

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
        $query = $this->cleanQuery($parts['query'] ?? '');

        $normalized = $scheme.'://'.$host.$path;

        if ($query !== '') {
            $normalized .= '?'.$query;
        }

        return $path === '' ? rtrim($normalized, '/') : $normalized;
    }

    public function host(string $url): string
    {
        return strtolower(parse_url($url, PHP_URL_HOST) ?: '');
    }

    private function cleanQuery(string $query): string
    {
        if ($query === '') {
            return '';
        }

        parse_str($query, $parameters);

        $filtered = collect($parameters)
            ->reject(fn ($value, string $key): bool => in_array(strtolower($key), self::TRACKING_PARAMETERS, true))
            ->all();

        return http_build_query($filtered, '', '&', PHP_QUERY_RFC3986);
    }
}
