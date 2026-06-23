<?php

namespace App\Services\Scanner;

class KeywordAlignmentAnalyzer
{
    private const AREAS = [
        'url' => ['label' => 'URL', 'weight' => 14],
        'title' => ['label' => 'Title', 'weight' => 20],
        'meta_description' => ['label' => 'Meta Description', 'weight' => 14],
        'h1' => ['label' => 'H1', 'weight' => 18],
        'h2_h3' => ['label' => 'H2/H3', 'weight' => 12],
        'body' => ['label' => 'Body Content', 'weight' => 14],
        'faq' => ['label' => 'FAQ', 'weight' => 8],
        'schema' => ['label' => 'Schema', 'weight' => 5],
        'internal_links' => ['label' => 'Internal Links', 'weight' => 5],
    ];

    public function analyze(array $data, array $targetKeywords): array
    {
        $keywords = $this->normalizeKeywords($targetKeywords);
        $areas = $this->extractAreas($data);

        $rows = array_map(fn (string $keyword): array => $this->analyzeKeyword($keyword, $areas), $keywords);
        $overallScore = $rows === [] ? 0 : (int) round(array_sum(array_column($rows, 'alignment_score')) / count($rows));

        return [
            'overall_score' => $overallScore,
            'summary' => [
                'total' => count($rows),
                'strongly_supported' => count(array_filter($rows, fn (array $row): bool => $row['status'] === 'Strongly Supported')),
                'partially_supported' => count(array_filter($rows, fn (array $row): bool => $row['status'] === 'Partially Supported')),
                'weak_or_missing' => count(array_filter($rows, fn (array $row): bool => in_array($row['status'], ['Weakly Supported', 'Missing'], true))),
            ],
            'keywords' => $rows,
            'intent_summary' => [
                'strong' => count(array_filter($rows, fn (array $row): bool => $row['search_intent_match'] === 'Strong')),
                'partial' => count(array_filter($rows, fn (array $row): bool => $row['search_intent_match'] === 'Partial')),
                'weak' => count(array_filter($rows, fn (array $row): bool => $row['search_intent_match'] === 'Weak')),
            ],
            'content_gaps' => $this->contentGaps($rows),
        ];
    }

    private function normalizeKeywords(array $keywords): array
    {
        $normalized = [];

        foreach ($keywords as $keyword) {
            $clean = trim(preg_replace('/\s+/', ' ', (string) $keyword));
            $key = strtolower($clean);

            if ($clean !== '' && ! isset($normalized[$key])) {
                $normalized[$key] = $clean;
            }
        }

        return array_values(array_slice($normalized, 0, 20));
    }

    private function extractAreas(array $data): array
    {
        $headingLevels = $data['heading_levels'] ?? [];
        $content = $data['content'] ?? [];
        $schema = $data['schema'] ?? [];
        $links = $data['links'] ?? [];

        return [
            'url' => (string) ($data['url'] ?? ''),
            'title' => (string) ($data['title'] ?? ''),
            'meta_description' => (string) ($data['meta_description'] ?? ''),
            'h1' => implode(' ', $this->arrayText($headingLevels['h1'] ?? [])),
            'h2_h3' => implode(' ', array_merge($this->arrayText($headingLevels['h2'] ?? []), $this->arrayText($headingLevels['h3'] ?? []))),
            'body' => (string) ($content['visible_text'] ?? ''),
            'faq' => implode(' ', $this->arrayText($content['questions'] ?? [])),
            'schema' => implode(' ', $this->arrayText($schema['types'] ?? $schema)),
            'internal_links' => implode(' ', $this->arrayText(array_column(is_array($links) ? $links : [], 'text'))),
        ];
    }

    private function arrayText(array $values): array
    {
        return array_values(array_filter(array_map(function ($value): string {
            if (is_array($value)) {
                return implode(' ', $this->arrayText($value));
            }

            return trim((string) $value);
        }, $values)));
    }

    private function analyzeKeyword(string $keyword, array $areas): array
    {
        $foundIn = [];
        $missingFrom = [];
        $score = 0;
        $signals = [];

        foreach (self::AREAS as $area => $meta) {
            $strength = $this->matchStrength($keyword, $areas[$area] ?? '');

            if ($strength > 0) {
                $foundIn[] = $meta['label'];
                $signals[$area] = $strength;
            } else {
                $missingFrom[] = $meta['label'];
            }

            $score += (int) round($meta['weight'] * $strength);
        }

        $score = min(100, $score);

        return [
            'keyword' => $keyword,
            'alignment_score' => $score,
            'status' => $this->status($score),
            'found_in' => $foundIn,
            'missing_from' => $missingFrom,
            'search_intent_match' => $this->intentMatch($keyword, $score, $signals),
            'content_support' => $this->contentSupport($signals),
            'suggested_on_page_fix' => $this->suggestedFix($missingFrom),
        ];
    }

    private function matchStrength(string $keyword, string $text): float
    {
        $keyword = $this->normalizeText($keyword);
        $text = $this->normalizeText($text);

        if ($keyword === '' || $text === '') {
            return 0.0;
        }

        if (str_contains($text, $keyword)) {
            return 1.0;
        }

        $keywordTokens = array_values(array_unique(array_filter(explode(' ', $keyword))));

        if ($keywordTokens === []) {
            return 0.0;
        }

        $matched = 0;
        foreach ($keywordTokens as $token) {
            if (str_contains($text, $token)) {
                $matched++;
            }
        }

        $coverage = $matched / count($keywordTokens);

        return $coverage >= 0.75 ? 0.6 : 0.0;
    }

    private function normalizeText(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/https?:\/\//', ' ', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function status(int $score): string
    {
        return match (true) {
            $score >= 75 => 'Strongly Supported',
            $score >= 45 => 'Partially Supported',
            $score >= 20 => 'Weakly Supported',
            default => 'Missing',
        };
    }

    private function intentMatch(string $keyword, int $score, array $signals): string
    {
        $commercialTerms = ['service', 'services', 'company', 'agency', 'consultant', 'audit', 'pricing', 'cost', 'buy', 'hire', 'near me', 'local'];
        $hasCommercialIntent = collect($commercialTerms)->contains(fn (string $term): bool => str_contains(strtolower($keyword), $term));
        $hasHighPrioritySignal = max($signals['title'] ?? 0, $signals['h1'] ?? 0, $signals['meta_description'] ?? 0) > 0;

        if ($score >= 70 && ($hasHighPrioritySignal || ! $hasCommercialIntent)) {
            return 'Strong';
        }

        if ($score >= 35) {
            return 'Partial';
        }

        return 'Weak';
    }

    private function contentSupport(array $signals): string
    {
        $support = ($signals['body'] ?? 0) + ($signals['h2_h3'] ?? 0) + ($signals['faq'] ?? 0);

        return match (true) {
            $support >= 2 => 'Strong',
            $support >= 1 => 'Medium',
            default => 'Weak',
        };
    }

    private function suggestedFix(array $missingFrom): string
    {
        if (array_intersect(['Title', 'H1'], $missingFrom) !== []) {
            return 'Add this keyword or a close variation to a high-priority page area if it matches the page focus.';
        }

        if (in_array('H2/H3', $missingFrom, true)) {
            return 'Add this keyword or a close variation to an H2 and expand the supporting section.';
        }

        if (in_array('Body Content', $missingFrom, true)) {
            return 'Add a concise section that explains this topic with examples, proof and related terms.';
        }

        if (in_array('FAQ', $missingFrom, true)) {
            return 'Add a relevant FAQ that answers a buyer or research question around this keyword.';
        }

        if (in_array('Internal Links', $missingFrom, true)) {
            return 'Use this keyword or a close variation in a relevant internal link anchor.';
        }

        return 'Keep this keyword naturally supported across high-priority page areas.';
    }

    private function contentGaps(array $rows): array
    {
        return [
            'body_copy' => $this->keywordsMissing($rows, 'Body Content'),
            'faqs' => $this->keywordsMissing($rows, 'FAQ'),
            'headings' => $this->keywordsMissing($rows, 'H2/H3'),
            'internal_links' => $this->keywordsMissing($rows, 'Internal Links'),
        ];
    }

    private function keywordsMissing(array $rows, string $area): array
    {
        return array_values(array_map(
            fn (array $row): string => $row['keyword'],
            array_filter($rows, fn (array $row): bool => in_array($area, $row['missing_from'], true))
        ));
    }
}
