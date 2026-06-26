@php
    $brandColor = $company->primary_color ?: '#1d4ed8';
    $domain = parse_url($scan->normalized_url ?: $scan->url, PHP_URL_HOST) ?: ($scan->normalized_domain ?: 'Website');
    $generatedAt = ($scan->completed_at ?? $scan->created_at ?? now())->timezone(config('app.timezone'));
    $footerText = data_get($company->white_label_settings, 'report_footer_text');
    $scoreBreakdown = $result?->score_breakdown ?? [];
    $visibility = $result?->visibility_data ?? [];
    $aiVisibility = $result?->ai_visibility_data ?? [];
    $contentCoverage = $result?->content_coverage_data ?? [];
    $opportunity = $result?->opportunity_data ?? [];
    $keywordAlignment = $result?->keyword_alignment_data ?? [];
    $topic = $result?->topic_intelligence_data ?? [];
    $raw = $result?->raw ?? [];
    $overallScore = data_get($scoreBreakdown, 'overall_score', data_get($visibility, 'overall_visibility_score', $result?->score ?? 0));
    $seoScore = data_get($scoreBreakdown, 'seo_score', $result?->score ?? 0);
    $aiScore = data_get($scoreBreakdown, 'ai_visibility_score', data_get($aiVisibility, 'score', 0));
    $geoScore = data_get($scoreBreakdown, 'geo_score', 0);
    $aeoScore = data_get($scoreBreakdown, 'aeo_score', 0);
    $commercialIntent = data_get($opportunity, 'commercial_intent', data_get($visibility, 'commercial_intent_strength', 'Not enough data'));
    $contentDepth = data_get($contentCoverage, 'content_depth', data_get($visibility, 'content_coverage', 'Not enough data'));
    $businessOpportunity = data_get($opportunity, 'biggest_opportunity', data_get($visibility, 'biggest_opportunity', 'Improve high-priority page signals and content coverage.'));
    $searchFocus = data_get($visibility, 'primary_search_focus', data_get($topic, 'primary_search_focus', data_get($raw, 'search_focus.primary_search_focus', 'Not detected')));
    $actions = collect($result?->recommendations ?? [])->take(8);
    $groupedRecommendations = collect($result?->recommendations ?? [])->groupBy(fn ($item) => data_get($item, 'category', 'General'));
    $technicalSummary = $result ? [
        'HTTP Status' => $result->http_status ?: 'N/A',
        'HTTPS' => $result->uses_https ? 'Enabled' : 'Not detected',
        'Title' => $result->title ?: 'Not detected',
        'Meta Description' => $result->meta_description ?: 'Not detected',
        'H1 Count' => $result->h1_count ?? 'N/A',
        'Internal Links' => $result->internal_links_count ?? 'N/A',
        'External Links' => $result->external_links_count ?? 'N/A',
        'Images Missing Alt' => $result->images_missing_alt_count ?? 'N/A',
    ] : [];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visibility Report - {{ $domain }}</title>
    <style>
        @page { size: A4; margin: 16mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; color: #0f172a; font-family: Arial, Helvetica, sans-serif; line-height: 1.45; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 20px; background: #ffffff; border-bottom: 1px solid #e2e8f0; }
        .toolbar a, .toolbar button { border: 0; border-radius: 10px; padding: 10px 14px; font-weight: 800; text-decoration: none; cursor: pointer; }
        .toolbar a { color: #334155; background: #f1f5f9; }
        .toolbar button { color: #ffffff; background: {{ $brandColor }}; }
        .sheet { position: relative; width: 210mm; min-height: 297mm; margin: 18px auto; background: #ffffff; box-shadow: 0 18px 50px rgba(15, 23, 42, .12); overflow: hidden; }
        .page { position: relative; padding: 18mm; page-break-after: always; min-height: 297mm; }
        .page:last-child { page-break-after: auto; }
        .watermark { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; z-index: 0; color: rgba(15, 23, 42, .045); font-size: 72px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; transform: rotate(-28deg); }
        .content { position: relative; z-index: 1; }
        .cover { background: linear-gradient(135deg, {{ $brandColor }}, #0f172a); color: #ffffff; }
        .brand-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .logo { width: 64px; height: 64px; border-radius: 16px; object-fit: contain; background: #ffffff; padding: 8px; }
        .logo-fallback { display: flex; width: 64px; height: 64px; align-items: center; justify-content: center; border-radius: 16px; background: rgba(255,255,255,.16); color: #ffffff; font-size: 26px; font-weight: 900; }
        .eyebrow { font-size: 11px; font-weight: 900; letter-spacing: .22em; text-transform: uppercase; opacity: .76; }
        h1 { margin: 48px 0 0; font-size: 46px; line-height: 1.02; letter-spacing: -.04em; }
        h2 { margin: 0 0 16px; font-size: 24px; letter-spacing: -.02em; }
        h3 { margin: 0 0 8px; font-size: 16px; }
        p { margin: 0; }
        .muted { color: #64748b; }
        .cover .muted { color: rgba(255,255,255,.72); }
        .meta-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 12px; margin-top: 42px; }
        .meta-card { border: 1px solid rgba(255,255,255,.18); border-radius: 16px; padding: 16px; background: rgba(255,255,255,.1); }
        .meta-card b { display: block; margin-bottom: 6px; font-size: 11px; text-transform: uppercase; letter-spacing: .14em; opacity: .76; }
        .score-hero { margin-top: 40px; display: grid; grid-template-columns: 150px 1fr; gap: 26px; align-items: center; }
        .score-number { width: 150px; height: 150px; border-radius: 32px; display: flex; align-items: center; justify-content: center; background: #ffffff; color: {{ $brandColor }}; font-size: 58px; font-weight: 900; }
        .scores { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; margin-top: 26px; }
        .score-card, .card { border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff; padding: 16px; }
        .score-card b { display: block; font-size: 24px; color: {{ $brandColor }}; }
        .section { margin-bottom: 22px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 14px; }
        .pill { display: inline-block; border-radius: 999px; padding: 6px 10px; background: #f1f5f9; color: #334155; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; }
        .accent { color: {{ $brandColor }}; }
        .list { margin: 0; padding-left: 18px; }
        .list li { margin: 7px 0; }
        .recommendation { border-left: 4px solid {{ $brandColor }}; padding: 12px 14px; background: #f8fafc; border-radius: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { text-align: left; background: #f8fafc; color: #475569; font-size: 10px; text-transform: uppercase; letter-spacing: .12em; }
        th, td { border: 1px solid #e2e8f0; padding: 10px; vertical-align: top; }
        .footer { position: absolute; left: 18mm; right: 18mm; bottom: 12mm; display: flex; justify-content: space-between; gap: 20px; color: #64748b; font-size: 10px; }
        .print-note { margin-top: 10px; font-size: 12px; color: #64748b; }
        @media print {
            body { background: #ffffff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: auto; margin: 0; box-shadow: none; }
            .page { min-height: 297mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('dashboard.reports') }}">Back to reports</a>
        <div>
            <button type="button" onclick="window.print()">Download / Save PDF</button>
            <p class="print-note">Use Save as PDF in the print dialog.</p>
        </div>
    </div>

    <main class="sheet">
        <div class="watermark">Confidential</div>

        <section class="page cover">
            <div class="content">
                <div class="brand-row">
                    <div class="brand">
                        @if ($company->logo_path)
                            <img class="logo" src="{{ asset('storage/'.$company->logo_path) }}" alt="{{ $company->name }}">
                        @else
                            <span class="logo-fallback">{{ \Illuminate\Support\Str::of($company->name)->substr(0, 1)->upper() }}</span>
                        @endif
                        <div>
                            <p class="eyebrow">Prepared by</p>
                            <p style="font-size:22px;font-weight:900;">{{ $company->name }}</p>
                            <p class="muted">{{ $company->website_url ?: $company->domain }}</p>
                        </div>
                    </div>
                    @unless ($whiteLabelActive)
                        <p class="pill">Generated by QSA</p>
                    @endunless
                </div>

                <h1>Search & AI Visibility Report</h1>
                <p style="margin-top:18px;font-size:19px;max-width:620px;" class="muted">A stakeholder-focused summary of search visibility, AI readiness, commercial opportunity and technical health.</p>

                <div class="meta-grid">
                    <div class="meta-card"><b>Scanned URL</b>{{ $scan->normalized_url ?: $scan->url }}</div>
                    <div class="meta-card"><b>Domain</b>{{ $domain }}</div>
                    <div class="meta-card"><b>Generated</b>{{ $generatedAt->format('d M Y, h:i A') }} IST</div>
                    <div class="meta-card"><b>Scan Mode</b>{{ str_replace('_', ' ', $scan->scan_mode ?? 'current_visibility') }}</div>
                </div>

                <div class="score-hero">
                    <div class="score-number">{{ (int) $overallScore }}</div>
                    <div>
                        <p class="eyebrow">Overall Visibility</p>
                        <h2 style="font-size:30px;margin-top:8px;">{{ (int) $overallScore }}/100</h2>
                        <p class="muted">Combined view of SEO, AI Visibility, GEO and AEO readiness based on the current scan.</p>
                    </div>
                </div>
            </div>
            <div class="footer">
                <span>{{ $footerText ?: 'Confidential visibility report prepared for internal review.' }}</span>
                <span>{{ $company->contact_email ?: '' }}</span>
            </div>
        </section>

        <section class="page">
            <div class="content">
                <p class="eyebrow accent">Executive Summary</p>
                <h2>Visibility brief for {{ $domain }}</h2>
                <div class="scores">
                    <div class="score-card"><span class="muted">SEO</span><b>{{ (int) $seoScore }}</b></div>
                    <div class="score-card"><span class="muted">AI Visibility</span><b>{{ (int) $aiScore }}</b></div>
                    <div class="score-card"><span class="muted">GEO</span><b>{{ (int) $geoScore }}</b></div>
                    <div class="score-card"><span class="muted">AEO</span><b>{{ (int) $aeoScore }}</b></div>
                </div>

                <div class="grid-2 section" style="margin-top:22px;">
                    <div class="card"><h3>Commercial Intent</h3><p>{{ is_array($commercialIntent) ? json_encode($commercialIntent) : $commercialIntent }}</p></div>
                    <div class="card"><h3>Content Depth</h3><p>{{ is_array($contentDepth) ? json_encode($contentDepth) : $contentDepth }}</p></div>
                    <div class="card"><h3>Business Opportunity</h3><p>{{ is_array($businessOpportunity) ? json_encode($businessOpportunity) : $businessOpportunity }}</p></div>
                    <div class="card"><h3>Current Search Focus</h3><p>{{ is_array($searchFocus) ? implode(', ', $searchFocus) : $searchFocus }}</p></div>
                </div>

                <div class="section">
                    <h2>Top Priority Actions</h2>
                    @forelse ($actions as $action)
                        <div class="recommendation">
                            <h3>{{ data_get($action, 'issue', data_get($action, 'title', 'Improvement opportunity')) }}</h3>
                            <p>{{ data_get($action, 'recommendation', data_get($action, 'how_to_fix', 'Review and improve this item.')) }}</p>
                        </div>
                    @empty
                        <div class="recommendation"><h3>No priority actions available</h3><p>The scan did not produce recommendation data for this report.</p></div>
                    @endforelse
                </div>

                <div class="section">
                    <h2>AI Visibility Summary</h2>
                    <div class="grid-3">
                        <div class="card"><h3>AI Score</h3><p class="accent" style="font-size:24px;font-weight:900;">{{ (int) $aiScore }}/100</p></div>
                        <div class="card"><h3>Citation Readiness</h3><p>{{ data_get($result?->ai_citation_readiness_data ?? [], 'score', 'N/A') }}</p></div>
                        <div class="card"><h3>Likely Citation Chance</h3><p>{{ data_get($aiVisibility, 'likely_ai_citation_chance', 'Not enough data') }}</p></div>
                    </div>
                </div>
            </div>
            <div class="footer">
                <span>{{ $footerText ?: 'Confidential visibility report.' }}</span>
                <span>{{ $generatedAt->format('d M Y') }}</span>
            </div>
        </section>

        @if (($scan->scan_mode ?? '') === 'keyword_focus')
            <section class="page">
                <div class="content">
                    <p class="eyebrow accent">Keyword Focus Audit</p>
                    <h2>Target keyword alignment</h2>
                    <div class="grid-3 section">
                        <div class="card"><h3>Overall Alignment</h3><p class="accent" style="font-size:24px;font-weight:900;">{{ data_get($keywordAlignment, 'overall_score', 0) }}/100</p></div>
                        <div class="card"><h3>Total Keywords</h3><p>{{ data_get($keywordAlignment, 'summary.total', count($scan->target_keywords ?? [])) }}</p></div>
                        <div class="card"><h3>Weak or Missing</h3><p>{{ data_get($keywordAlignment, 'summary.weak_or_missing', 'N/A') }}</p></div>
                    </div>
                    <table>
                        <thead><tr><th>Keyword</th><th>Score</th><th>Status</th><th>Found In</th><th>Suggested Fix</th></tr></thead>
                        <tbody>
                            @forelse (data_get($keywordAlignment, 'keywords', []) as $row)
                                <tr>
                                    <td>{{ data_get($row, 'keyword') }}</td>
                                    <td>{{ data_get($row, 'alignment_score', 0) }}</td>
                                    <td>{{ data_get($row, 'status', 'N/A') }}</td>
                                    <td>{{ implode(', ', data_get($row, 'found_in', [])) ?: 'N/A' }}</td>
                                    <td>{{ data_get($row, 'suggested_on_page_fix', 'Improve page support for this keyword.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">No keyword alignment data available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="footer"><span>{{ $footerText ?: 'Confidential visibility report.' }}</span><span>{{ $generatedAt->format('d M Y') }}</span></div>
            </section>
        @endif

        <section class="page">
            <div class="content">
                <p class="eyebrow accent">Recommendations</p>
                <h2>Grouped improvement opportunities</h2>
                @forelse ($groupedRecommendations as $category => $items)
                    <div class="section">
                        <h3>{{ $category }}</h3>
                        @foreach ($items->take(5) as $item)
                            <div class="recommendation">
                                <h3>{{ data_get($item, 'issue', 'Improvement opportunity') }}</h3>
                                <p><strong>Impact:</strong> {{ data_get($item, 'impact', 'Medium') }} &nbsp; <strong>Difficulty:</strong> {{ data_get($item, 'difficulty', 'Medium') }} &nbsp; <strong>Gain:</strong> {{ data_get($item, 'estimated_gain', 'Not estimated') }}</p>
                                <p style="margin-top:6px;">{{ data_get($item, 'how_to_fix', data_get($item, 'recommendation', 'Review and improve this item.')) }}</p>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <div class="recommendation"><h3>No grouped recommendations available</h3><p>The scan did not produce recommendation data for this report.</p></div>
                @endforelse
            </div>
            <div class="footer"><span>{{ $footerText ?: 'Confidential visibility report.' }}</span><span>{{ $generatedAt->format('d M Y') }}</span></div>
        </section>

        <section class="page">
            <div class="content">
                <p class="eyebrow accent">Technical SEO Summary</p>
                <h2>Technical signals</h2>
                <table>
                    <tbody>
                        @foreach ($technicalSummary as $label => $value)
                            <tr><th style="width:34%;">{{ $label }}</th><td>{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</td></tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="section" style="margin-top:24px;">
                    <h2>Footer</h2>
                    <p>{{ $footerText ?: 'This report is confidential and intended for the company workspace that generated it.' }}</p>
                    @unless ($whiteLabelActive)
                        <p style="margin-top:10px;" class="muted">Generated by QSA. White-label removal is available on supported plans when enabled by the workspace.</p>
                    @endunless
                </div>
            </div>
            <div class="footer"><span>{{ $company->name }}</span><span>{{ $generatedAt->format('d M Y, h:i A') }} IST</span></div>
        </section>
    </main>
</body>
</html>
