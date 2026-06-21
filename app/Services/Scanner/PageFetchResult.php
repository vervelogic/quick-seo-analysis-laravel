<?php

namespace App\Services\Scanner;

class PageFetchResult
{
    public function __construct(
        public readonly bool $reachable,
        public readonly ?int $status,
        public readonly ?string $html,
        public readonly int $responseTimeMs,
        public readonly int $pageSizeBytes,
        public readonly array $headers = [],
        public readonly ?string $finalUrl = null,
        public readonly ?string $error = null,
        public readonly array $redirectChain = [],
    ) {
    }
}
