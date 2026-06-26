<?php

namespace App\Services\Reports;

use App\Models\Company;
use App\Models\Scan;
use App\Models\ScanResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportDataBuilder
{
    public function build(Scan $scan, ?Company $company = null): array
    {
        $scan->loadMissing(['result', 'company.plan']);

        $result = $scan->result;
        $company ??= $scan->company;
        $raw = $result?->raw ?? [];
        $scoreBreakdown = $result?->score_breakdown ?? [];
        $recommendations = collect($result?->recommendations ?? []);
        $finalUrl = data_get($raw, 'final_url') ?: $scan->normalized_url ?: $scan->url;
        $domain = parse_url($finalUrl, PHP_URL_HOST) ?: $scan->normalized_domain ?: parse_url($scan->normalized_url ?? '', PHP_URL_HOST) ?: 'Unknown domain';
        $scanFailed = $scan->status === 'failed' || (($result?->is_reachable) === false) || ! $result;

        $scores = $this->scores($result, $scoreBreakdown, $scanFailed);
        $scoreExplanations = $this->scoreExplanations($result, $scores, $recommendations);
        $businessSummary = $this->businessSummary($scan, $result, $scores, $recommendations, $scanFailed);
        $opportunity = $this->opportunityScores($result, $recommendations, $scores);
        $sections = $this->sections($scan, $result, $scores, $scoreExplanations, $recommendations);
        $roadmap = $this->roadmap($recommendations, $scanFailed);

        return [
            'metadata' => [
                'scan_id' => $scan->id,
                'uuid' => $scan->uuid,
                'scan_mode' => $scan->scan_mode ?: 'current_visibility',
                'status' => $scan->status,
                'requested_url' => data_get($raw, 'requested_url') ?: $scan->url,
                'final_url' => $finalUrl,
                'domain' => $domain,
                'generated_at' => ($scan->completed_at ?? $result?->created_at ?? $scan->updated_at ?? $scan->created_at)?->timezone(config('app.timezone')),
                'is_legacy' => filled($scan->legacy_source),
                'scan_failed' => $scanFailed,
                'fetch_error' => data_get($raw, 'error') ?: $scan->error_message,
            ],
            'branding' => $this->branding($company),
            'executive_summary' => $businessSummary,
            'scores' => $scores,
            'score_explanations' => $scoreExplanations,
            'opportunity_scores' => $opportunity,
            'current_search_focus' => $this->currentSearchFocus($result),
            'keyword_focus' => $result?->keyword_alignment_data ?? [],
            'ai_visibility' => $result?->ai_visibility_data ?? [],
            'geo' => $result?->geo_data ?? [],
            'aeo' => $result?->aeo_data ?? [],
            'commercial_intent' => data_get($result?->opportunity_data ?? [], 'commercial_intent', data_get($result?->visibility_data ?? [], 'commercial_intent_strength')),
            'content_depth' => data_get($result?->content_coverage_data ?? [], 'content_depth', data_get($result?->visibility_data ?? [], 'content_coverage')),
            'topic_intelligence' => $result?->topic_intelligence_data ?? [],
            'ranking_potential' => $result?->ranking_potential_data ?? [],
            'ai_prompt_intelligence' => $result?->prompt_intelligence_data ?? [],
            'content_coverage' => $result?->content_coverage_data ?? [],
            'citation_readiness' => $result?->ai_citation_readiness_data ?? [],
            'technical_seo' => $this->technicalSeo($result),
            'structured_data' => $result?->structured_data ?? [],
            'headings' => data_get($result?->on_page_data ?? [], 'headings', []),
            'links' => $this->links($result),
            'image_audit' => $this->imageAudit($result),
            'social_preview' => $result?->social_data ?? [],
            'recommendations' => [
                'top_priority_actions' => $this->topPriorityActions($recommendations),
                'grouped' => $this->groupRecommendations($recommendations),
                'all' => $recommendations->values()->all(),
            ],
            'roadmap_30_day' => $roadmap,
            'sections' => $sections,
        ];
    }

    private function branding(?Company $company): array
    {
        return [
            'company_id' => $company?->id,
            'name' => $company?->name,
            'website' => $company?->website_url ?: $company?->domain,
            'contact_email' => $company?->contact_email,
            'contact_phone' => $company?->contact_phone,
            'logo_path' => $company?->logo_path,
            'primary_color' => $company?->primary_color ?: '#1d4ed8',
            'footer_text' => data_get($company?->white_label_settings ?? [], 'report_footer_text'),
            'white_label_active' => (bool) ($company?->white_label_enabled && $company->featureEnabled('white_label_reports')),
            'plan' => $company?->plan?->name,
        ];
    }

    private function scores(?ScanResult $result, array $scoreBreakdown, bool $scanFailed): array
    {
        if ($scanFailed) {
            return [
                'overall_visibility' => 0,
                'seo' => 0,
                'ai_visibility' => 0,
                'geo' => 0,
                'aeo' => 0,
                'commercial_intent' => 0,
                'content_depth' => 0,
                'citation_readiness' => 0,
            ];
        }

        return [
            'overall_visibility' => (int) ($scoreBreakdown['overall_visibility_score'] ?? $scoreBreakdown['overall_score'] ?? $result?->score ?? 0),
            'seo' => (int) ($scoreBreakdown['seo_score'] ?? $scoreBreakdown['overall_score'] ?? $result?->score ?? 0),
            'ai_visibility' => (int) ($scoreBreakdown['ai_visibility_score'] ?? data_get($result?->ai_visibility_data ?? [], 'score', 0)),
            'geo' => (int) ($scoreBreakdown['geo_score'] ?? data_get($result?->geo_data ?? [], 'score', 0)),
            'aeo' => (int) ($scoreBreakdown['aeo_score'] ?? data_get($result?->aeo_data ?? [], 'score', 0)),
            'commercial_intent' => (int) data_get($result?->opportunity_data ?? [], 'commercial_modifier_coverage_score', 0),
            'content_depth' => (int) data_get($result?->content_coverage_data ?? [], 'coverage_score', 0),
            'citation_readiness' => (int) data_get($result?->ai_citation_readiness_data ?? [], 'score', 0),
        ];
    }

    private function scoreExplanations(?ScanResult $result, array $scores, Collection $recommendations): array
    {
        $technicalMissing = collect([
            'Meta description' => blank($result?->meta_description),
            'H1' => (int) ($result?->h1_count ?? 0) < 1,
            'Image alt text' => (int) ($result?->images_missing_alt_count ?? 0) > 0,
            'HTTPS' => ! (bool) ($result?->uses_https),
        ])->filter()->keys()->values()->all();

        return [
            'seo' => $this->scoreExplanation($scores['seo'], ['HTTPS enabled' => (bool) $result?->uses_https, 'Title detected' => filled($result?->title)], $technicalMissing, 'Search engines and AI systems need clear page signals to understand, rank and cite the page.'),
            'ai_visibility' => $this->scoreExplanation($scores['ai_visibility'], data_get($result?->ai_visibility_data ?? [], 'strengths', []), data_get($result?->ai_visibility_data ?? [], 'weaknesses', []), 'AI systems need entity, trust and expertise signals before they can confidently mention a brand.'),
            'geo' => $this->scoreExplanation($scores['geo'], data_get($result?->geo_data ?? [], 'strengths', []), data_get($result?->geo_data ?? [], 'recommendations', []), 'Generative engines reward pages that answer topics deeply and conversationally.'),
            'aeo' => $this->scoreExplanation($scores['aeo'], data_get($result?->aeo_data ?? [], 'strengths', []), data_get($result?->aeo_data ?? [], 'missing_answer_opportunities', []), 'Answer engines prefer pages with direct answers, FAQs and clear supporting structure.'),
            'commercial_intent' => $this->scoreExplanation($scores['commercial_intent'], data_get($result?->opportunity_data ?? [], 'present_modifiers', []), data_get($result?->opportunity_data ?? [], 'missing_modifiers', []), 'Commercial signals help visitors and AI systems understand whether the page can solve a buying problem.'),
            'content_depth' => $this->scoreExplanation($scores['content_depth'], data_get($result?->content_coverage_data ?? [], 'topics_covered', []), data_get($result?->content_coverage_data ?? [], 'topics_missing', []), 'Depth and coverage reduce ambiguity and improve topical authority.'),
            'citation_readiness' => $this->scoreExplanation($scores['citation_readiness'], data_get($result?->ai_citation_readiness_data ?? [], 'strengths', []), data_get($result?->ai_citation_readiness_data ?? [], 'missing_citation_signals', []), 'Citation readiness improves the chance that AI tools can trust, cite and summarize the page.'),
        ];
    }

    private function scoreExplanation(int $score, mixed $good, mixed $missing, string $why): array
    {
        $good = collect(is_array($good) ? $good : [$good])->filter(fn ($value, $key) => is_bool($value) ? $value : filled($value))->map(fn ($value, $key) => is_string($key) ? $key : $value)->values()->take(6)->all();
        $missing = collect(is_array($missing) ? $missing : [$missing])->filter()->values()->take(6)->all();

        return [
            'score' => $score,
            'good' => $good,
            'missing' => $missing,
            'why_it_matters' => $why,
            'fix_first' => $missing[0] ?? 'Keep strengthening this signal with clearer page evidence.',
        ];
    }

    private function businessSummary(Scan $scan, ?ScanResult $result, array $scores, Collection $recommendations, bool $scanFailed): array
    {
        if ($scanFailed) {
            return [
                'current_visibility_status' => 'Retrieval issue detected',
                'business_opportunity' => 'Confirm that the page can be fetched before using this scan for decisions.',
                'ai_visibility_gap' => 'AI visibility cannot be evaluated until readable page content is available.',
                'commercial_intent_gap' => 'Commercial intent cannot be evaluated until readable page content is available.',
                'trust_authority_gap' => 'Trust signals cannot be evaluated until readable page content is available.',
                'overall_priority' => 'Critical',
                'biggest_risks' => ['The scanner did not receive enough readable page content.'],
                'biggest_opportunities' => ['Allow QSA to fetch the page or scan an accessible canonical URL.'],
            ];
        }

        $overall = $scores['overall_visibility'];
        $priority = $overall < 35 ? 'Critical' : ($overall < 60 ? 'High' : ($overall < 80 ? 'Medium' : 'Low'));

        return [
            'current_visibility_status' => $overall >= 80 ? 'Strong visibility foundation' : ($overall >= 60 ? 'Moderate visibility foundation' : 'Visibility foundation needs work'),
            'business_opportunity' => data_get($result?->opportunity_data ?? [], 'biggest_opportunity', 'Improve page clarity, content depth and trust signals.'),
            'ai_visibility_gap' => $scores['ai_visibility'] >= 70 ? 'AI visibility signals are reasonably clear.' : 'AI systems may not confidently identify, trust or cite this page yet.',
            'commercial_intent_gap' => $scores['commercial_intent'] >= 70 ? 'Commercial intent is visible.' : 'The page may need stronger buying, service or enquiry signals.',
            'trust_authority_gap' => $scores['citation_readiness'] >= 70 ? 'Trust and citation signals are reasonably present.' : 'Add stronger proof, expertise, organization and citation signals.',
            'overall_priority' => $priority,
            'biggest_risks' => $recommendations->pluck('issue')->filter()->take(3)->values()->all(),
            'biggest_opportunities' => collect(data_get($result?->content_coverage_data ?? [], 'content_expansion_opportunities', []))->take(3)->values()->all(),
        ];
    }

    private function opportunityScores(?ScanResult $result, Collection $recommendations, array $scores): array
    {
        $weakSignals = collect($scores)->filter(fn (int $score): bool => $score < 60)->count();
        $level = fn (int $score): string => $score < 40 ? 'Very High' : ($score < 65 ? 'High' : ($score < 80 ? 'Medium' : 'Low'));

        return [
            'traffic_opportunity' => $level($scores['seo']),
            'lead_generation_opportunity' => $level($scores['commercial_intent'] ?: $scores['overall_visibility']),
            'ai_visibility_opportunity' => $level($scores['ai_visibility']),
            'brand_authority_opportunity' => $level($scores['citation_readiness'] ?: $scores['ai_visibility']),
            'commercial_opportunity' => $level($scores['commercial_intent'] ?: $scores['seo']),
            'summary' => $weakSignals >= 4 ? 'Several visibility areas have room for meaningful improvement.' : 'The page has focused opportunities rather than a complete rebuild need.',
        ];
    }

    private function currentSearchFocus(?ScanResult $result): array
    {
        return [
            'primary_focus' => data_get($result?->visibility_data ?? [], 'primary_search_focus', data_get($result?->topic_intelligence_data ?? [], 'primary_search_focus', 'Not detected')),
            'intent' => data_get($result?->visibility_data ?? [], 'search_intent', 'Not detected'),
            'confidence' => data_get($result?->visibility_data ?? [], 'confidence_score', null),
            'evidence' => data_get($result?->visibility_data ?? [], 'evidence_signals', []),
        ];
    }

    private function technicalSeo(?ScanResult $result): array
    {
        return [
            'http_status' => $result?->http_status,
            'is_reachable' => $result?->is_reachable,
            'uses_https' => $result?->uses_https,
            'title' => $result?->title,
            'title_length' => $result?->title_length,
            'meta_description' => $result?->meta_description,
            'meta_description_length' => $result?->meta_description_length,
            'h1_count' => $result?->h1_count,
            'canonical' => $result?->canonical,
            'robots_meta' => $result?->robots_meta,
            'page_size_bytes' => $result?->page_size_bytes,
            'response_time_ms' => $result?->response_time_ms,
            'security' => $result?->security_data ?? [],
            'performance' => $result?->performance_data ?? [],
        ];
    }

    private function links(?ScanResult $result): array
    {
        return [
            'internal_links_count' => $result?->internal_links_count,
            'external_links_count' => $result?->external_links_count,
            'details' => $result?->on_page_data['links'] ?? [],
        ];
    }

    private function imageAudit(?ScanResult $result): array
    {
        return [
            'images_count' => $result?->images_count,
            'images_missing_alt_count' => $result?->images_missing_alt_count,
            'details' => $result?->on_page_data['images'] ?? [],
        ];
    }

    private function topPriorityActions(Collection $recommendations): array
    {
        return $recommendations
            ->sortBy(fn (array $item): int => ['high' => 0, 'medium' => 1, 'low' => 2][strtolower((string) data_get($item, 'impact', 'medium'))] ?? 1)
            ->take(5)
            ->values()
            ->all();
    }

    private function groupRecommendations(Collection $recommendations): array
    {
        return $recommendations
            ->groupBy(fn (array $item): string => (string) data_get($item, 'category', 'General'))
            ->map(fn (Collection $items): array => $items->values()->all())
            ->all();
    }

    private function roadmap(Collection $recommendations, bool $scanFailed): array
    {
        if ($scanFailed) {
            return [
                ['week' => 'Week 1', 'focus' => 'Fix retrieval/access issue', 'actions' => ['Confirm the URL is publicly accessible and not blocking server-side scanners.']],
                ['week' => 'Week 2', 'focus' => 'Re-scan and validate page signals', 'actions' => ['Run a fresh scan after access is restored.']],
                ['week' => 'Week 3', 'focus' => 'Review content and trust gaps', 'actions' => ['Use the complete report to prioritize content and trust improvements.']],
                ['week' => 'Week 4', 'focus' => 'Optimize for AI/AEO/GEO', 'actions' => ['Apply AI visibility and answer-readiness recommendations.']],
            ];
        }

        $byCategory = $recommendations->groupBy(fn (array $item): string => Str::lower((string) data_get($item, 'category', 'general')));

        return [
            ['week' => 'Week 1', 'focus' => 'Critical technical/search signal fixes', 'actions' => $this->roadmapActions($byCategory, ['technical', 'seo', 'on-page'], 'Fix the highest-impact search signal issues first.')],
            ['week' => 'Week 2', 'focus' => 'Content and FAQ expansion', 'actions' => $this->roadmapActions($byCategory, ['content', 'aeo'], 'Expand weak content areas and answer common customer questions.')],
            ['week' => 'Week 3', 'focus' => 'Authority, trust and citation signals', 'actions' => $this->roadmapActions($byCategory, ['security', 'structured data', 'ai visibility'], 'Add proof, organization signals, authorship and trust evidence.')],
            ['week' => 'Week 4', 'focus' => 'AI/AEO/GEO optimization and internal linking', 'actions' => $this->roadmapActions($byCategory, ['geo', 'links', 'social'], 'Strengthen semantic coverage and connect important pages internally.')],
        ];
    }

    private function roadmapActions(Collection $byCategory, array $categories, string $fallback): array
    {
        $actions = collect($categories)
            ->flatMap(fn (string $category): Collection => $byCategory->get($category, collect()))
            ->map(fn (array $item): string => (string) (data_get($item, 'how_to_fix') ?: data_get($item, 'recommendation') ?: data_get($item, 'issue')))
            ->filter()
            ->take(3)
            ->values();

        return $actions->isNotEmpty() ? $actions->all() : [$fallback];
    }

    private function sections(Scan $scan, ?ScanResult $result, array $scores, array $scoreExplanations, Collection $recommendations): array
    {
        return [
            $this->section('executive_summary', 'Executive Business Summary', 'Plain-language status, risks and opportunities.', $scores['overall_visibility'], [], $this->topPriorityActions($recommendations), 10),
            $this->section('score_breakdown', 'Score Breakdown With Explanations', 'SEO, AI, GEO, AEO, commercial, content and citation score detail.', $scores['overall_visibility'], $scoreExplanations, [], 20),
            $this->section('opportunity_scores', 'Opportunity Score', 'Qualitative opportunity levels without fake revenue or traffic claims.', null, [], [], 30),
            $this->section('search_focus', 'Current Search Focus', 'What the page appears optimized to communicate.', null, $this->currentSearchFocus($result), [], 40),
            $this->section('keyword_focus', 'Keyword Focus Alignment', 'Target keyword support for keyword-focus scans.', data_get($result?->keyword_alignment_data ?? [], 'overall_score'), $result?->keyword_alignment_data ?? [], [], 45, ($scan->scan_mode ?? '') === 'keyword_focus'),
            $this->section('ai_visibility', 'AI Visibility Summary', 'Readiness for AI systems and answer engines.', $scores['ai_visibility'], $result?->ai_visibility_data ?? [], [], 50),
            $this->section('geo', 'GEO Summary', 'Generative Engine Optimization signals.', $scores['geo'], $result?->geo_data ?? [], [], 55),
            $this->section('aeo', 'AEO Summary', 'Answer Engine Optimization signals.', $scores['aeo'], $result?->aeo_data ?? [], [], 60),
            $this->section('topic_intelligence', 'Content & Topic Intelligence', 'Topics, entities, coverage, prompts and ranking potential.', $scores['content_depth'], $result?->topic_intelligence_data ?? [], [], 70),
            $this->section('recommendations', 'Grouped Recommendations', 'Prioritized fixes grouped by business-friendly category.', null, [], $this->groupRecommendations($recommendations), 80),
            $this->section('technical_seo', 'Technical SEO Details', 'Compact technical diagnostics lower in the report.', $scores['seo'], $this->technicalSeo($result), [], 90),
            $this->section('roadmap_30_day', '30-Day Roadmap', 'Week-by-week action plan based on findings.', null, [], [], 100),
        ];
    }

    private function section(string $key, string $title, string $summary, mixed $score, array $findings, array $recommendations, int $priority, bool $visible = true): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'summary' => $summary,
            'score' => $score,
            'findings' => $findings,
            'recommendations' => $recommendations,
            'priority' => $priority,
            'visible_web' => $visible,
            'visible_pdf' => $visible,
            'minimum_plan' => null,
        ];
    }
}
