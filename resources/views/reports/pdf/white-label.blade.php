@php
    $reportData = $reportData ?? [];
    $metadata = data_get($reportData, 'metadata', []);
    $branding = data_get($reportData, 'branding', []);
    $scores = data_get($reportData, 'scores', []);
    $scoreExplanations = data_get($reportData, 'score_explanations', []);
    $executive = data_get($reportData, 'executive_summary', []);
    $opportunities = data_get($reportData, 'opportunity_scores', []);
    $searchFocus = data_get($reportData, 'current_search_focus', []);
    $keywordFocus = data_get($reportData, 'keyword_focus', []);
    $aiVisibility = data_get($reportData, 'ai_visibility', []);
    $geo = data_get($reportData, 'geo', []);
    $aeo = data_get($reportData, 'aeo', []);
    $citation = data_get($reportData, 'citation_readiness', []);
    $topic = data_get($reportData, 'topic_intelligence', []);
    $coverage = data_get($reportData, 'content_coverage', []);
    $competitor = data_get($reportData, 'competitor_summary', []);
    $technical = data_get($reportData, 'technical_seo', []);
    $recommendations = data_get($reportData, 'recommendations', []);
    $priorityMatrix = collect(data_get($recommendations, 'priority_matrix', []));
    $topActions = collect(data_get($recommendations, 'top_priority_actions', []));
    $groupedRecommendations = collect(data_get($recommendations, 'grouped', []));
    $roadmap = collect(data_get($reportData, 'roadmap_30_day', []));

    $brandColor = data_get($branding, 'primary_color') ?: ($company->primary_color ?: '#1d4ed8');
    $companyName = data_get($branding, 'company_name') ?: $company->name;
    $companyWebsite = data_get($branding, 'website') ?: ($company->website_url ?: $company->domain);
    $footerText = data_get($branding, 'footer_text') ?: data_get($company->white_label_settings, 'report_footer_text', 'Confidential visibility report prepared for internal review.');
    $logoPath = data_get($branding, 'logo_path') ?: $company->logo_path;
    $whiteLabelActive = (bool) (data_get($branding, 'white_label_active') ?? ($whiteLabelActive ?? false));

    $domain = data_get($metadata, 'domain') ?: (parse_url($scan->normalized_url ?: $scan->url, PHP_URL_HOST) ?: ($scan->normalized_domain ?: 'Website'));
    $finalUrl = data_get($metadata, 'final_url') ?: ($scan->normalized_url ?: $scan->url);
    $scanMode = data_get($metadata, 'scan_mode') ?: ($scan->scan_mode ?? 'current_visibility');
    $generatedAt = data_get($metadata, 'generated_at') ?: ($scan->completed_at ?? $scan->created_at ?? now());
    if (! $generatedAt instanceof \Carbon\CarbonInterface) {
        $generatedAt = \Carbon\Carbon::parse($generatedAt);
    }
    $generatedAt = $generatedAt->timezone(config('app.timezone'));

    $score = fn (string $key, int $default = 0): int => (int) data_get($scores, $key, $default);
    $explanation = fn (string $key, string $field, string $default = 'Review this signal.'): string => (string) data_get($scoreExplanations, $key.'.'.$field, $default);
    $asList = fn ($value): string => collect(is_array($value) ? $value : [$value])->filter()->implode(', ');
    $overallScore = $score('overall_visibility', (int) ($result?->score ?? 0));
    $scoreRows = [
        'SEO Health' => ['key' => 'seo', 'score' => $score('seo'), 'summary' => $explanation('seo', 'why_it_matters', 'Measures core SEO signals and crawlability.')],
        'AI Visibility' => ['key' => 'ai_visibility', 'score' => $score('ai_visibility'), 'summary' => $explanation('ai_visibility', 'why_it_matters', 'Measures how clearly AI systems can understand the page.')],
        'GEO Readiness' => ['key' => 'geo', 'score' => $score('geo'), 'summary' => $explanation('geo', 'why_it_matters', 'Measures readiness for generative search and AI answers.')],
        'AEO Readiness' => ['key' => 'aeo', 'score' => $score('aeo'), 'summary' => $explanation('aeo', 'why_it_matters', 'Measures answer-style content and snippet readiness.')],
        'Citation Readiness' => ['key' => 'citation_readiness', 'score' => $score('citation_readiness'), 'summary' => $explanation('citation_readiness', 'why_it_matters', 'Measures trust, source and authority signals.')],
    ];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visibility Report - {{ $domain }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f7; color: #0f172a; font-family: Arial, Helvetica, sans-serif; line-height: 1.45; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 20px; background: #fff; border-bottom: 1px solid #dbe3ef; }
        .toolbar a, .toolbar button { border: 0; border-radius: 10px; padding: 10px 14px; font-weight: 800; text-decoration: none; cursor: pointer; }
        .toolbar a { color: #334155; background: #f1f5f9; }
        .toolbar button { color: #fff; background: {{ $brandColor }}; }
        .sheet { position: relative; width: 210mm; min-height: 297mm; margin: 18px auto; background: #fff; box-shadow: 0 18px 50px rgba(15, 23, 42, .13); overflow: hidden; }
        .page { position: relative; min-height: 297mm; padding: 16mm; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .cover { color: #fff; background: linear-gradient(135deg, {{ $brandColor }}, #07111f 72%); }
        .watermark { position: fixed; inset: 0; z-index: 0; display: flex; align-items: center; justify-content: center; color: rgba(15, 23, 42, .045); font-size: 70px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; transform: rotate(-28deg); pointer-events: none; }
        .cover .watermark { color: rgba(255,255,255,.055); }
        .content { position: relative; z-index: 1; }
        .brand-row, .between { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .logo { width: 64px; height: 64px; border-radius: 16px; object-fit: contain; background: #fff; padding: 8px; }
        .logo-fallback { display: flex; width: 64px; height: 64px; align-items: center; justify-content: center; border-radius: 16px; background: rgba(255,255,255,.16); color: #fff; font-size: 26px; font-weight: 900; }
        .eyebrow { margin: 0 0 8px; color: #64748b; font-size: 10px; font-weight: 900; letter-spacing: .2em; text-transform: uppercase; }
        .cover .eyebrow { color: rgba(255,255,255,.72); }
        h1 { margin: 48px 0 0; font-size: 46px; line-height: 1.02; letter-spacing: -.04em; }
        h2 { margin: 0 0 16px; font-size: 25px; letter-spacing: -.02em; }
        h3 { margin: 0 0 8px; font-size: 15px; }
        p { margin: 0; }
        .muted { color: #64748b; }
        .cover .muted { color: rgba(255,255,255,.72); }
        .meta-grid, .grid-2, .grid-3, .score-grid { display: grid; gap: 14px; }
        .meta-grid { grid-template-columns: repeat(2, minmax(0,1fr)); margin-top: 42px; }
        .grid-2 { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0,1fr)); }
        .score-grid { grid-template-columns: repeat(5, minmax(0,1fr)); }
        .card, .score-card, .meta-card { border: 1px solid #dbe3ef; border-radius: 16px; background: #fff; padding: 15px; }
        .cover .meta-card { border-color: rgba(255,255,255,.18); background: rgba(255,255,255,.1); }
        .meta-card b { display: block; margin-bottom: 6px; font-size: 10px; text-transform: uppercase; letter-spacing: .14em; opacity: .72; }
        .score-card b { display: block; color: {{ $brandColor }}; font-size: 26px; line-height: 1; }
        .score-hero { margin-top: 42px; display: grid; grid-template-columns: 154px 1fr; gap: 28px; align-items: center; }
        .score-number { width: 154px; height: 154px; border-radius: 34px; display: flex; align-items: center; justify-content: center; background: #fff; color: {{ $brandColor }}; font-size: 58px; font-weight: 900; }
        .bar { height: 9px; border-radius: 999px; background: #e8eef6; overflow: hidden; margin: 9px 0; }
        .bar span { display: block; height: 100%; border-radius: inherit; background: {{ $brandColor }}; }
        .section { margin-top: 22px; }
        .pill { display: inline-block; border-radius: 999px; padding: 6px 10px; background: #f1f5f9; color: #334155; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; }
        .accent { color: {{ $brandColor }}; }
        .list { margin: 0; padding-left: 18px; }
        .list li { margin: 7px 0; }
        .recommendation { border-left: 4px solid {{ $brandColor }}; padding: 12px 14px; background: #f8fafc; border-radius: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { text-align: left; background: #f8fafc; color: #475569; font-size: 9px; text-transform: uppercase; letter-spacing: .12em; }
        th, td { border: 1px solid #dbe3ef; padding: 9px; vertical-align: top; }
        .footer { position: absolute; left: 16mm; right: 16mm; bottom: 10mm; display: flex; justify-content: space-between; gap: 20px; color: #64748b; font-size: 10px; }
        @media print { body { background: #fff; } .toolbar { display: none; } .sheet { width: auto; min-height: auto; margin: 0; box-shadow: none; } .page { min-height: 297mm; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('dashboard.reports') }}">Back to reports</a>
        <button type="button" onclick="window.print()">Download / Save PDF</button>
    </div>

    <main class="sheet">
        <section class="page cover">
            <div class="watermark">Confidential</div>
            <div class="content">
                <div class="brand-row">
                    <div class="brand">
                        @if ($logoPath)
                            <img class="logo" src="{{ asset('storage/'.$logoPath) }}" alt="{{ $companyName }}">
                        @else
                            <span class="logo-fallback">{{ \Illuminate\Support\Str::of($companyName)->substr(0, 1)->upper() }}</span>
                        @endif
                        <div>
                            <p class="eyebrow">Prepared by</p>
                            <p style="font-size:22px;font-weight:900;">{{ $companyName }}</p>
                            <p class="muted">{{ $companyWebsite }}</p>
                        </div>
                    </div>
                    @unless ($whiteLabelActive)
                        <p class="pill">Generated by QSA</p>
                    @endunless
                </div>

                <h1>Search & AI Visibility Report</h1>
                <p class="muted" style="margin-top:18px;font-size:19px;max-width:650px;">Executive intelligence for search visibility, AI readiness, content opportunity and technical health.</p>

                <div class="meta-grid">
                    <div class="meta-card"><b>Scanned URL</b>{{ $finalUrl }}</div>
                    <div class="meta-card"><b>Domain</b>{{ $domain }}</div>
                    <div class="meta-card"><b>Generated</b>{{ $generatedAt->format('d M Y, h:i A') }} IST</div>
                    <div class="meta-card"><b>Audit Type</b>{{ \Illuminate\Support\Str::of($scanMode)->replace('_', ' ')->title() }}</div>
                </div>

                <div class="score-hero">
                    <div class="score-number">{{ $overallScore }}</div>
                    <div>
                        <p class="eyebrow">Overall Visibility</p>
                        <h2 style="font-size:30px;margin-top:8px;">{{ $overallScore }}/100</h2>
                        <p class="muted">{{ $explanation('overall_visibility', 'why_it_matters', 'Combined view of SEO, AI Visibility, GEO and AEO readiness based on the current scan.') }}</p>
                    </div>
                </div>
            </div>
            <div class="footer"><span>{{ $footerText }}</span><span>{{ $company->contact_email ?: '' }}</span></div>
        </section>

        <section class="page">
            <div class="watermark">Confidential</div>
            <div class="content">
                <p class="eyebrow accent">Executive Summary</p>
                <h2>Business visibility brief</h2>
                <div class="score-grid">
                    @foreach ($scoreRows as $label => $row)
                        <div class="score-card">
                            <span class="muted">{{ $label }}</span>
                            <b>{{ $row['score'] }}</b>
                            <div class="bar"><span style="width: {{ max(0, min(100, $row['score'])) }}%;"></span></div>
                        </div>
                    @endforeach
                </div>

                <div class="grid-2 section">
                    <div class="card"><h3>Search Visibility Potential</h3><p>{{ data_get($executive, 'search_visibility_potential', data_get($executive, 'current_visibility_status', 'Needs review')) }}</p></div>
                    <div class="card"><h3>Commercial Intent Strength</h3><p>{{ data_get($executive, 'commercial_intent_strength', data_get($opportunities, 'commercial_intent', 'Not enough data')) }}</p></div>
                    <div class="card"><h3>AI Visibility</h3><p>{{ data_get($executive, 'ai_visibility', data_get($executive, 'ai_visibility_gap', 'Review AI readiness signals')) }}</p></div>
                    <div class="card"><h3>Biggest Opportunity</h3><p>{{ data_get($executive, 'biggest_opportunity', data_get($opportunities, 'biggest_opportunity', 'Improve priority visibility signals.')) }}</p></div>
                </div>

                <div class="section">
                    <h2>Priority actions</h2>
                    @forelse ($topActions->take(6) as $action)
                        <div class="recommendation">
                            <h3>{{ data_get($action, 'issue', data_get($action, 'title', 'Improvement opportunity')) }}</h3>
                            <p>{{ data_get($action, 'recommendation', data_get($action, 'how_to_fix', 'Review and improve this item.')) }}</p>
                        </div>
                    @empty
                        <div class="recommendation"><h3>No priority actions available</h3><p>The report engine did not receive recommendation data for this scan.</p></div>
                    @endforelse
                </div>
            </div>
            <div class="footer"><span>{{ $footerText }}</span><span>{{ $generatedAt->format('d M Y') }}</span></div>
        </section>

        <section class="page">
            <div class="watermark">Confidential</div>
            <div class="content">
                <p class="eyebrow accent">Score Explanation</p>
                <h2>What the scores mean</h2>
                @foreach ($scoreRows as $label => $row)
                    <div class="card section">
                        <div class="between"><h3>{{ $label }}</h3><span class="pill">{{ $row['score'] }}/100</span></div>
                        <div class="bar"><span style="width: {{ max(0, min(100, $row['score'])) }}%;"></span></div>
                        <p>{{ $row['summary'] }}</p>
                        <p class="muted" style="margin-top:8px;"><strong>Fix first:</strong> {{ $explanation($row['key'], 'fix_first', 'Prioritize the highest impact signals in this category.') }}</p>
                    </div>
                @endforeach
            </div>
            <div class="footer"><span>{{ $footerText }}</span><span>{{ $generatedAt->format('d M Y') }}</span></div>
        </section>

        <section class="page">
            <div class="watermark">Confidential</div>
            <div class="content">
                <p class="eyebrow accent">Search & AI Intelligence</p>
                <h2>What the page appears to communicate</h2>
                <div class="grid-2">
                    <div class="card"><h3>Current Search Focus</h3><p>{{ data_get($searchFocus, 'primary_focus', data_get($searchFocus, 'primary_search_focus', 'Not detected')) }}</p><p class="muted" style="margin-top:8px;">Intent: {{ data_get($searchFocus, 'search_intent', 'Not detected') }}</p></div>
                    <div class="card"><h3>AI Visibility Summary</h3><p>{{ data_get($aiVisibility, 'summary', data_get($aiVisibility, 'likely_ai_citation_chance', 'Not enough data')) }}</p></div>
                    <div class="card"><h3>GEO Readiness</h3><p>{{ data_get($geo, 'summary', 'Review generative engine readiness signals.') }}</p></div>
                    <div class="card"><h3>AEO Readiness</h3><p>{{ data_get($aeo, 'summary', 'Review answer engine optimization signals.') }}</p></div>
                    <div class="card"><h3>AI Citation Readiness</h3><p>{{ data_get($citation, 'summary', data_get($citation, 'likely_ai_citation_chance', 'Review trust and citation signals.')) }}</p></div>
                    <div class="card"><h3>Competitor Summary</h3><p>{{ data_get($competitor, 'summary', 'Future-ready competitor gap analysis placeholder. No competitor data is connected yet.') }}</p></div>
                </div>

                <div class="grid-2 section">
                    <div class="card"><h3>Topic Intelligence</h3><p><strong>Primary:</strong> {{ $asList(data_get($topic, 'primary_topics', [])) ?: 'Not detected' }}</p><p class="muted" style="margin-top:8px;"><strong>Entities:</strong> {{ $asList(data_get($topic, 'entities', [])) ?: 'Not detected' }}</p></div>
                    <div class="card"><h3>Content Coverage</h3><p>{{ data_get($coverage, 'summary', 'Review topic coverage and missing content opportunities.') }}</p><p class="muted" style="margin-top:8px;"><strong>Coverage:</strong> {{ data_get($coverage, 'coverage_score', data_get($coverage, 'score', 'N/A')) }}</p></div>
                </div>
            </div>
            <div class="footer"><span>{{ $footerText }}</span><span>{{ $generatedAt->format('d M Y') }}</span></div>
        </section>

        @if ($scanMode === 'keyword_focus' || collect(data_get($keywordFocus, 'keywords', []))->isNotEmpty())
            <section class="page">
                <div class="watermark">Confidential</div>
                <div class="content">
                    <p class="eyebrow accent">Keyword Focus Audit</p>
                    <h2>Your page alignment with selected keywords</h2>
                    <div class="grid-3 section">
                        <div class="card"><h3>Overall Alignment</h3><p class="accent" style="font-size:24px;font-weight:900;">{{ data_get($keywordFocus, 'overall_score', 0) }}/100</p></div>
                        <div class="card"><h3>Total Keywords</h3><p>{{ data_get($keywordFocus, 'summary.total', collect(data_get($keywordFocus, 'keywords', []))->count()) }}</p></div>
                        <div class="card"><h3>Needs Support</h3><p>{{ data_get($keywordFocus, 'summary.weak_or_missing', 'N/A') }}</p></div>
                    </div>
                    <table>
                        <thead><tr><th>Keyword</th><th>Score</th><th>Status</th><th>Found In</th><th>Suggested Fix</th></tr></thead>
                        <tbody>
                            @forelse (data_get($keywordFocus, 'keywords', []) as $row)
                                <tr>
                                    <td>{{ data_get($row, 'keyword') }}</td>
                                    <td>{{ data_get($row, 'alignment_score', data_get($row, 'score', 0)) }}</td>
                                    <td>{{ data_get($row, 'status', 'N/A') }}</td>
                                    <td>{{ $asList(data_get($row, 'found_in', [])) ?: 'N/A' }}</td>
                                    <td>{{ data_get($row, 'suggested_on_page_fix', data_get($row, 'suggested_fix', 'Add stronger page support for this keyword or a close variation.')) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">No keyword alignment rows available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="footer"><span>{{ $footerText }}</span><span>{{ $generatedAt->format('d M Y') }}</span></div>
            </section>
        @endif

        <section class="page">
            <div class="watermark">Confidential</div>
            <div class="content">
                <p class="eyebrow accent">Priority Matrix</p>
                <h2>What to fix first</h2>
                <table>
                    <thead><tr><th>Issue</th><th>Category</th><th>Impact</th><th>Difficulty</th><th>Estimated Gain</th><th>How to Fix</th></tr></thead>
                    <tbody>
                        @forelse ($priorityMatrix->take(12) as $item)
                            <tr>
                                <td>{{ data_get($item, 'issue', 'Improvement opportunity') }}</td>
                                <td>{{ data_get($item, 'category', 'General') }}</td>
                                <td>{{ data_get($item, 'impact', 'Medium') }}</td>
                                <td>{{ data_get($item, 'difficulty', 'Medium') }}</td>
                                <td>{{ data_get($item, 'estimated_gain', 'Not estimated') }}</td>
                                <td>{{ data_get($item, 'how_to_fix', data_get($item, 'recommendation', 'Review and improve this item.')) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No priority matrix data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="section">
                    <h2>Grouped recommendations</h2>
                    @forelse ($groupedRecommendations as $category => $items)
                        <div class="recommendation">
                            <h3>{{ $category }}</h3>
                            <ul class="list">
                                @foreach (collect($items)->take(3) as $item)
                                    <li>{{ data_get($item, 'issue', data_get($item, 'recommendation', 'Improvement opportunity')) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <div class="recommendation"><h3>No grouped recommendations available</h3><p>The scan did not produce grouped recommendation data.</p></div>
                    @endforelse
                </div>
            </div>
            <div class="footer"><span>{{ $footerText }}</span><span>{{ $generatedAt->format('d M Y') }}</span></div>
        </section>

        <section class="page">
            <div class="watermark">Confidential</div>
            <div class="content">
                <p class="eyebrow accent">30-Day Roadmap</p>
                <h2>Recommended implementation plan</h2>
                <div class="grid-2">
                    @forelse ($roadmap as $week => $items)
                        <div class="card">
                            <h3>{{ \Illuminate\Support\Str::of((string) $week)->replace('_', ' ')->title() }}</h3>
                            <ul class="list">
                                @foreach (collect($items)->take(6) as $item)
                                    <li>{{ is_array($item) ? data_get($item, 'action', data_get($item, 'issue', 'Roadmap action')) : $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <div class="card"><h3>Week 1</h3><p>Start with the highest impact actions from the priority matrix.</p></div>
                    @endforelse
                </div>

                <div class="section">
                    <p class="eyebrow accent">Technical SEO Summary</p>
                    <h2>Core technical signals</h2>
                    <table>
                        <tbody>
                            @forelse ($technical as $label => $value)
                                @continue(is_array($value) && count($value) > 5)
                                <tr><th style="width:34%;">{{ \Illuminate\Support\Str::of((string) $label)->replace('_', ' ')->title() }}</th><td>{{ is_array($value) ? $asList($value) : (is_bool($value) ? ($value ? 'Yes' : 'No') : $value) }}</td></tr>
                            @empty
                                <tr><th>HTTP Status</th><td>{{ $result?->http_status ?: 'N/A' }}</td></tr>
                                <tr><th>HTTPS</th><td>{{ $result?->uses_https ? 'Enabled' : 'Not detected' }}</td></tr>
                                <tr><th>Title</th><td>{{ $result?->title ?: 'Not detected' }}</td></tr>
                                <tr><th>Meta Description</th><td>{{ $result?->meta_description ?: 'Not detected' }}</td></tr>
                                <tr><th>H1 Count</th><td>{{ $result?->h1_count ?? 'N/A' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="footer"><span>{{ $whiteLabelActive ? $companyName : $companyName.' | Generated by QSA' }}</span><span>{{ $generatedAt->format('d M Y, h:i A') }} IST</span></div>
        </section>
    </main>
</body>
</html>
