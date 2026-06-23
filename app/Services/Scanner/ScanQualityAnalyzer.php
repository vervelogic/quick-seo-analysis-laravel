<?php

namespace App\Services\Scanner;

class ScanQualityAnalyzer
{
    public function analyze(PageFetchResult $fetch, array $parsed, ?string $parseError = null): array
    {
        $html = (string) ($fetch->html ?? '');
        $title = trim((string) ($parsed['title'] ?? ''));
        $meta = trim((string) ($parsed['meta_description'] ?? ''));
        $h1Count = (int) ($parsed['h1_count'] ?? 0);
        $bodyText = (string) data_get($parsed, 'content.visible_text', '');
        $bodyTextLength = mb_strlen(trim($bodyText));
        $bodyWordCount = (int) data_get($parsed, 'content.visible_word_count', 0);
        $contentType = $this->headerValue($fetch->headers, 'Content-Type');
        $challengeDetected = $this->challengeDetected($html);
        $reasons = [];

        if (! $fetch->reachable) {
            $reasons[] = 'HTTP request was not successful.';
        }

        if ($html === '') {
            $reasons[] = 'Empty response body.';
        }

        if ($contentType && ! str_contains(strtolower($contentType), 'text/html')) {
            $reasons[] = 'Response Content-Type is not HTML.';
        }

        if ($title === '') {
            $reasons[] = 'Missing title tag.';
        }

        if ($meta === '') {
            $reasons[] = 'Missing meta description.';
        }

        if ($h1Count === 0) {
            $reasons[] = 'No H1 headings found.';
        }

        if ($bodyTextLength < 120 || $bodyWordCount < 25) {
            $reasons[] = 'Very little readable body content was extracted.';
        }

        if ($challengeDetected) {
            $reasons[] = 'Challenge, bot protection, or access restriction page detected.';
        }

        if ($parseError) {
            $reasons[] = 'Parser error: '.$parseError;
        }

        $status = match (true) {
            ! $fetch->reachable || $html === '' || $challengeDetected || ($title === '' && $bodyTextLength < 120) => 'retrieval_issue_detected',
            $title !== '' && $bodyTextLength >= 120 && ($meta === '' || $h1Count === 0) => 'partial_content_retrieved',
            $title !== '' && $meta !== '' && $bodyTextLength >= 120 => 'full_content_retrieved',
            default => 'partial_content_retrieved',
        };

        return [
            'status' => $status,
            'label' => str($status)->replace('_', ' ')->headline()->toString(),
            'reliable_for_scoring' => $status !== 'retrieval_issue_detected',
            'reasons' => array_values(array_unique($reasons)),
            'signals' => [
                'title_found' => $title !== '',
                'meta_description_found' => $meta !== '',
                'h1_found' => $h1Count > 0,
                'body_content_found' => $bodyTextLength >= 120,
                'challenge_detected' => $challengeDetected,
            ],
            'metrics' => [
                'content_type' => $contentType,
                'response_size_bytes' => $fetch->pageSizeBytes,
                'body_text_length' => $bodyTextLength,
                'body_word_count' => $bodyWordCount,
                'html_preview_bytes' => strlen(mb_substr($html, 0, 2048)),
            ],
        ];
    }

    private function challengeDetected(string $html): bool
    {
        $lower = strtolower(mb_substr($html, 0, 12000));

        foreach ([
            'cf-chl',
            'cloudflare ray id',
            'attention required',
            'captcha',
            'g-recaptcha',
            'hcaptcha',
            'access denied',
            'request blocked',
            'bot protection',
            'verify you are human',
            'shopify-checkout-api-token',
            'checkpoint',
        ] as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return false;
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
