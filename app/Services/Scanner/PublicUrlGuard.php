<?php

namespace App\Services\Scanner;

use InvalidArgumentException;

class PublicUrlGuard
{
    public function assertAllowed(string $url): void
    {
        if (! $this->isAllowed($url)) {
            throw new InvalidArgumentException('Only public HTTP and HTTPS URLs can be scanned.');
        }
    }

    public function isAllowed(string $url): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');
        $port = $parts['port'] ?? null;

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        if ($port !== null && ! in_array((int) $port, [80, 443], true)) {
            return false;
        }

        return $this->hostResolvesPublicly($host);
    }

    private function hostResolvesPublicly(string $host): bool
    {
        $host = trim($host, '[]');

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isPublicIp($host);
        }

        $records = array_merge(
            dns_get_record($host, DNS_A) ?: [],
            dns_get_record($host, DNS_AAAA) ?: [],
        );

        if ($records === []) {
            return false;
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if (! $ip || ! $this->isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    private function isPublicIp(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
