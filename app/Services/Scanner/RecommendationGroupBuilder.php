<?php

namespace App\Services\Scanner;

class RecommendationGroupBuilder
{
    public function build(array $recommendations, array $data, array $pageType, array $scanQuality): array
    {
        if (($scanQuality['status'] ?? null) === 'retrieval_issue_detected') {
            $fixes = [
                'Check whether bot protection, CDN rules or server firewalls block server-side requests.',
                'Allow standard crawler-like requests or provide crawlable HTML for the page.',
                'Re-run the scan after fetch access is confirmed.',
            ];

            return [[
                'title' => 'Content Retrieval Issue Detected',
                'category' => 'Scan Quality',
                'issue' => 'Content Retrieval Issue Detected',
                'group_score' => 0,
                'business_impact' => 'High',
                'fix_difficulty' => 'Medium',
                'priority' => 'Critical',
                'impact' => 'high',
                'difficulty' => 'medium',
                'estimated_gain' => 0,
                'summary' => 'QSA could not retrieve enough readable page content to produce a normal visibility audit.',
                'why_it_matters' => 'QSA could not retrieve enough readable page content to produce a normal visibility audit.',
                'recommendation' => 'Resolve the retrieval issue before relying on scoring or recommendations.',
                'how_to_fix' => implode(' ', $fixes),
                'top_missing_signals' => ['Readable page HTML', 'Title/meta/body confirmation', 'Reliable content extraction'],
                'fixes' => $fixes,
                'details' => [],
            ]];
        }

        $type = (string) ($pageType['type'] ?? 'Unknown');
        $context = $this->contextFor($type);
        $groups = [
            'Authority & Trust Signals' => [
                'keywords' => ['brand', 'authority', 'trust', 'about', 'author', 'citation', 'organization', 'contact', 'review', 'testimonial', 'schema'],
                'score' => $this->scoreAuthority($data),
                'impact' => 'High',
                'priority' => in_array($type, ['Corporate / Brand', 'Local Business', 'Service Business'], true) ? 'Critical' : 'Important',
            ],
            'Commercial & Conversion Signals' => [
                'keywords' => ['service', 'product', 'pricing', 'price', 'cost', 'booking', 'quote', 'cta', 'review', 'collection', 'shipping', 'return', 'case stud'],
                'score' => $this->scoreCommercial($data, $type),
                'impact' => in_array($type, ['Ecommerce', 'Service Business', 'SaaS', 'Local Business'], true) ? 'High' : 'Medium',
                'priority' => in_array($type, ['Ecommerce', 'Service Business', 'SaaS'], true) ? 'Critical' : 'Important',
            ],
            'AI & Answer Engine Readiness' => [
                'keywords' => ['faq', 'answer', 'question', 'snippet', 'direct', 'ai', 'geo', 'aeo', 'conversational'],
                'score' => $this->scoreAnswers($data),
                'impact' => 'High',
                'priority' => 'Important',
            ],
            'Content Depth & Topic Coverage' => [
                'keywords' => ['content', 'topic', 'semantic', 'entity', 'comparison', 'how-to', 'definition', 'cluster', 'coverage', 'depth'],
                'score' => $this->scoreCoverage($data),
                'impact' => 'Medium',
                'priority' => 'Important',
            ],
        ];

        $grouped = [];
        foreach ($groups as $title => $config) {
            $relevant = $this->relevantItems($recommendations, $config['keywords'], $type);
            $missingSignals = $this->missingSignals($title, $data, $type, $relevant);
            $fixes = $this->fixesFor($title, $type, $relevant, $context);

            if ($relevant === [] && $missingSignals === []) {
                continue;
            }

            $difficulty = $this->difficultyFor($relevant);
            $summary = $this->summaryFor($title, $type);
            $fixes = array_values(array_slice(array_unique($fixes), 0, 4));

            $grouped[] = [
                'title' => $title,
                'category' => $title,
                'issue' => $title,
                'group_score' => $config['score'],
                'business_impact' => $config['impact'],
                'fix_difficulty' => $difficulty,
                'priority' => $config['priority'],
                'impact' => strtolower($config['impact']),
                'difficulty' => strtolower($difficulty),
                'estimated_gain' => $this->estimatedGain($config['score'], $config['priority']),
                'summary' => $summary,
                'why_it_matters' => $summary,
                'recommendation' => 'Focus on the highest-impact missing signals in this group before expanding lower-priority checks.',
                'how_to_fix' => implode(' ', array_slice($fixes, 0, 2)),
                'top_missing_signals' => array_values(array_slice(array_unique($missingSignals), 0, 5)),
                'fixes' => $fixes,
                'details' => array_values(array_slice($relevant, 0, 6)),
            ];
        }

        usort($grouped, function (array $a, array $b) {
            $priority = ['Critical' => 3, 'Important' => 2, 'Optional' => 1];

            return [$priority[$b['priority']] ?? 0, 100 - (int) $b['group_score']]
                <=> [$priority[$a['priority']] ?? 0, 100 - (int) $a['group_score']];
        });

        return array_values(array_slice($grouped, 0, 4));
    }

    public function filterDetailedItems(array $recommendations, string $pageType): array
    {
        return array_values(array_filter($recommendations, fn ($item) => $this->isRelevantToType((array) $item, $pageType)));
    }

    private function relevantItems(array $items, array $keywords, string $pageType): array
    {
        return array_values(array_filter($items, function ($item) use ($keywords, $pageType) {
            $item = (array) $item;

            if (! $this->isRelevantToType($item, $pageType)) {
                return false;
            }

            $haystack = strtolower(implode(' ', array_filter([
                $item['category'] ?? '',
                $item['issue'] ?? '',
                $item['why_it_matters'] ?? '',
                $item['recommendation'] ?? '',
                $item['how_to_fix'] ?? '',
            ])));

            foreach ($keywords as $keyword) {
                if (str_contains($haystack, strtolower($keyword))) {
                    return true;
                }
            }

            return false;
        }));
    }

    private function isRelevantToType(array $item, string $pageType): bool
    {
        $text = strtolower(implode(' ', array_map(fn ($value) => is_array($value) ? implode(' ', $value) : (string) $value, array_filter($item))));

        if ($pageType === 'Ecommerce') {
            return ! str_contains($text, 'service pages missing')
                && ! str_contains($text, 'definition content')
                && ! str_contains($text, 'how-to content missing');
        }

        if (in_array($pageType, ['Service Business', 'Local Business'], true)) {
            return ! str_contains($text, 'product discovery content')
                && ! str_contains($text, 'collection descriptions');
        }

        if ($pageType === 'SaaS') {
            return ! str_contains($text, 'collection descriptions')
                && ! str_contains($text, 'size/fit');
        }

        if ($pageType === 'Blog / Article') {
            return ! str_contains($text, 'service pages missing')
                && ! str_contains($text, 'pricing pages');
        }

        return true;
    }

    private function missingSignals(string $group, array $data, string $type, array $items): array
    {
        $signals = array_values(array_filter(array_map(fn ($item) => (string) (($item['issue'] ?? '') ?: ($item['category'] ?? '')), $items)));

        if ($group === 'Commercial & Conversion Signals') {
            if ($type === 'Ecommerce') {
                return array_merge($signals, $this->missingTextSignals($data, ['collection', 'product details', 'reviews', 'shipping', 'returns', 'size', 'fabric', 'care']));
            }

            if (in_array($type, ['Service Business', 'Local Business'], true)) {
                return array_merge($signals, $this->missingTextSignals($data, ['services', 'case studies', 'testimonials', 'consultation', 'quote', 'contact']));
            }

            if ($type === 'SaaS') {
                return array_merge($signals, $this->missingTextSignals($data, ['features', 'pricing', 'use cases', 'integrations', 'demo', 'trial']));
            }
        }

        return $signals;
    }

    private function missingTextSignals(array $data, array $needles): array
    {
        $text = strtolower((string) ($data['title'] ?? '').' '.(string) ($data['meta_description'] ?? '').' '.data_get($data, 'content.visible_text', '').' '.collect((array) ($data['links'] ?? []))->pluck('text')->implode(' '));
        $missing = [];

        foreach ($needles as $needle) {
            if (! str_contains($text, strtolower($needle))) {
                $missing[] = str($needle)->headline()->toString();
            }
        }

        return $missing;
    }

    private function fixesFor(string $group, string $type, array $items, array $context): array
    {
        $fixes = array_values(array_filter(array_map(fn ($item) => (string) ($item['how_to_fix'] ?? $item['recommendation'] ?? ''), $items)));

        return array_merge($context[$group] ?? [], $fixes);
    }

    private function contextFor(string $type): array
    {
        return match ($type) {
            'Ecommerce' => [
                'Commercial & Conversion Signals' => ['Add stronger collection/category descriptions, product discovery copy, review proof, shipping/return clarity, and internal links to key products.'],
                'Authority & Trust Signals' => ['Show reviews, guarantees, secure checkout, return policy, brand story, and organization/product schema.'],
                'AI & Answer Engine Readiness' => ['Add buyer FAQs around sizing, fit, fabric, care, delivery, returns, and product selection.'],
                'Content Depth & Topic Coverage' => ['Expand category and product-support content before adding generic how-to or definition sections.'],
            ],
            'Service Business', 'Local Business' => [
                'Commercial & Conversion Signals' => ['Clarify service pages, local proof, testimonials, case studies, conversion CTAs, and consultation/quote paths.'],
                'Authority & Trust Signals' => ['Add about, contact, team, credentials, reviews, case studies, and LocalBusiness or Organization schema.'],
                'AI & Answer Engine Readiness' => ['Answer service buyer questions in FAQ blocks and mark them up where relevant.'],
                'Content Depth & Topic Coverage' => ['Build supporting pages for service types, industries, locations, outcomes, process, and pricing context.'],
            ],
            'SaaS' => [
                'Commercial & Conversion Signals' => ['Strengthen feature, pricing, use-case, integration, comparison, trial and demo signals.'],
                'Authority & Trust Signals' => ['Add customer proof, security/trust pages, product schema where relevant, and company entity signals.'],
                'AI & Answer Engine Readiness' => ['Answer product-fit, integration, pricing, migration and comparison questions directly.'],
                'Content Depth & Topic Coverage' => ['Build topic clusters around use cases, integrations, alternatives and workflows.'],
            ],
            'Blog / Article', 'Publisher' => [
                'Authority & Trust Signals' => ['Add author expertise, publication dates, references, editorial proof and Article schema.'],
                'AI & Answer Engine Readiness' => ['Use answer-first headings, FAQs, definitions, comparisons and concise summaries.'],
                'Content Depth & Topic Coverage' => ['Connect this article to topic clusters, related guides and internal links.'],
            ],
            default => [],
        };
    }

    private function summaryFor(string $group, string $type): string
    {
        return match ($group) {
            'Authority & Trust Signals' => 'Checks whether search and AI systems can verify who is behind the page and why it should be trusted.',
            'Commercial & Conversion Signals' => 'Checks whether the page gives buyers enough business context to take the next step.',
            'AI & Answer Engine Readiness' => 'Checks whether the page answers likely user questions clearly enough for AI and answer engines.',
            'Content Depth & Topic Coverage' => 'Checks whether the page covers its topic deeply enough for the detected page type: '.$type.'.',
            default => 'Grouped visibility improvements for this page type.',
        };
    }

    private function difficultyFor(array $items): string
    {
        $difficulties = array_map(fn ($item) => strtolower((string) ($item['difficulty'] ?? 'medium')), $items);

        if (in_array('high', $difficulties, true)) {
            return 'High';
        }

        return in_array('medium', $difficulties, true) ? 'Medium' : 'Low';
    }

    private function estimatedGain(int $score, string $priority): int
    {
        $base = $priority === 'Critical' ? 14 : 9;

        return max(4, min(18, $base + (int) round((100 - $score) / 12)));
    }

    private function scoreAuthority(array $data): int
    {
        return $this->average([
            filled(data_get($data, 'meta_description')),
            (bool) data_get($data, 'schema.json_ld_count'),
            count((array) data_get($data, 'schema.types', [])) > 0,
            $this->hasAny($data, ['about', 'contact', 'testimonial', 'review', 'trusted', 'certified']),
        ]);
    }

    private function scoreCommercial(array $data, string $type): int
    {
        $needles = match ($type) {
            'Ecommerce' => ['product', 'price', 'cart', 'shipping', 'return', 'review'],
            'SaaS' => ['features', 'pricing', 'demo', 'trial', 'integration', 'use case'],
            'Service Business', 'Local Business' => ['service', 'consultation', 'quote', 'contact', 'testimonial', 'case study'],
            default => ['contact', 'pricing', 'service', 'product', 'review'],
        };

        return $this->average(array_map(fn ($needle) => $this->hasAny($data, [$needle]), $needles));
    }

    private function scoreAnswers(array $data): int
    {
        return $this->average([
            count((array) data_get($data, 'content.questions', [])) >= 2,
            $this->hasAny($data, ['faq', 'frequently asked questions', 'what is', 'how to']),
            in_array('FAQPage', (array) data_get($data, 'schema.types', []), true),
            count((array) ($data['headings'] ?? [])) >= 4,
        ]);
    }

    private function scoreCoverage(array $data): int
    {
        return $this->average([
            (int) data_get($data, 'content.visible_word_count', 0) >= 500,
            (int) data_get($data, 'content.unique_word_count', 0) >= 180,
            count((array) ($data['headings'] ?? [])) >= 5,
            ($data['internal_links_count'] ?? 0) >= 5,
        ]);
    }

    private function average(array $checks): int
    {
        return $checks === [] ? 0 : (int) round((count(array_filter($checks)) / count($checks)) * 100);
    }

    private function hasAny(array $data, array $needles): bool
    {
        $text = strtolower((string) ($data['title'] ?? '').' '.(string) ($data['meta_description'] ?? '').' '.implode(' ', (array) ($data['headings'] ?? [])).' '.data_get($data, 'content.visible_text', '').' '.collect((array) ($data['links'] ?? []))->pluck('text')->implode(' '));

        foreach ($needles as $needle) {
            if (str_contains($text, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }
}
