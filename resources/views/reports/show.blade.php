<x-layouts.app :title="'Visibility Report - '.$scan->normalized_url">
    @php
        $result ??= $scan->result;
        $scoreBreakdown = $result?->score_breakdown ?? [];
        $technical = $result?->technical_data ?? [];
        $content = $result?->content_data ?? [];
        $security = $result?->security_data ?? [];
        $performance = $result?->performance_data ?? [];
        $social = $result?->social_data ?? [];
        $structured = $result?->structured_data ?? [];
        $aiVisibility = $result?->ai_visibility_data ?? [];
        $geo = $result?->geo_data ?? [];
        $aeo = $result?->aeo_data ?? [];
        $topicIntel = $result?->topic_intelligence_data ?? [];
        $rankingPotential = $result?->ranking_potential_data ?? [];
        $promptIntel = $result?->prompt_intelligence_data ?? [];
        $coverage = $result?->content_coverage_data ?? [];
        $citation = $result?->ai_citation_readiness_data ?? [];
        $raw = $result?->raw ?? [];
        $timezone = config('app.timezone', 'Asia/Kolkata');
        $formatDate = fn ($date) => $date ? $date->copy()->timezone($timezone)->format('d M Y, h:i A').' IST' : 'N/A';
        $generatedAt = $scan->completed_at ?: $result?->created_at ?: $scan->updated_at ?: $scan->created_at;
        $effectiveUrl = data_get($raw, 'final_url') ?: $scan->normalized_url;
        $requestedUrl = data_get($raw, 'requested_url') ?: $scan->normalized_url;
        $host = parse_url($effectiveUrl, PHP_URL_HOST) ?: parse_url($scan->normalized_url, PHP_URL_HOST) ?: $scan->normalized_url;
        $scheme = parse_url($effectiveUrl, PHP_URL_SCHEME) ?: parse_url($scan->normalized_url, PHP_URL_SCHEME) ?: 'https';
        $pageTitle = $result?->title ?: data_get($result?->on_page_data ?? [], 'title');
        $metaDescription = $result?->meta_description ?: data_get($result?->on_page_data ?? [], 'meta_description');
        $opportunities = collect($result?->opportunity_data ?? $result?->recommendations ?? []);
        $scores = [
            'Overall Visibility' => $scoreBreakdown['overall_visibility_score'] ?? $result?->score ?? 0,
            'SEO' => $scoreBreakdown['seo_score'] ?? $scoreBreakdown['overall_score'] ?? $result?->score ?? 0,
            'AI Visibility' => $scoreBreakdown['ai_visibility_score'] ?? data_get($aiVisibility, 'score', 0),
            'GEO' => $scoreBreakdown['geo_score'] ?? data_get($geo, 'score', 0),
            'AEO' => $scoreBreakdown['aeo_score'] ?? data_get($aeo, 'score', 0),
        ];
        $scoreExplanations = [
            'SEO' => 'Core technical, on-page, content, link and performance signals search engines rely on.',
            'AI Visibility' => 'Brand, entity, trust and expertise signals that help AI systems understand the business.',
            'GEO' => 'Generative Engine Optimization signals for semantic coverage, content depth and conversational discovery.',
            'AEO' => 'Answer Engine Optimization signals for FAQ, direct-answer and featured-snippet readiness.',
            'Overall Visibility' => 'A combined view of search visibility plus AI, GEO and answer-engine readiness.',
        ];
        $overall = (int) $scores['Overall Visibility'];
        $scoreText = fn ($score) => $score >= 80 ? 'text-teal-600' : ($score >= 55 ? 'text-amber-600' : 'text-red-600');
        $scoreBg = fn ($score) => $score >= 80 ? 'bg-teal-600' : ($score >= 55 ? 'bg-amber-500' : 'bg-red-500');
        $statusPill = fn ($ok) => $ok ? 'bg-teal-100 text-teal-800 ring-teal-200' : 'bg-red-100 text-red-700 ring-red-200';
        $softPill = fn ($score) => $score >= 80 ? 'bg-teal-50 text-teal-800 ring-teal-100' : ($score >= 55 ? 'bg-amber-50 text-amber-800 ring-amber-100' : 'bg-red-50 text-red-700 ring-red-100');
        $impactPill = fn ($impact) => $impact === 'high' ? 'bg-red-50 text-red-700 ring-red-100' : ($impact === 'medium' ? 'bg-amber-50 text-amber-800 ring-amber-100' : 'bg-slate-50 text-slate-700 ring-slate-200');
        $label = fn ($key) => str($key)->replace('_', ' ')->headline();
        $sectionCard = 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6';
        $metricCard = 'rounded-lg border border-slate-200 bg-white p-4 shadow-sm';
    @endphp

    <section class="relative overflow-hidden bg-slate-950 py-12 sm:py-16">
        <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(15,23,42,1),rgba(15,118,110,.26),rgba(37,99,235,.18))]"></div>
        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.06)_1px,transparent_1px)] bg-[size:44px_44px]"></div>
        <div class="relative mx-auto grid max-w-7xl gap-8 px-5 sm:px-6 lg:grid-cols-[1fr_380px] lg:px-8">
            <div class="flex flex-col justify-center">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-300">AI visibility intelligence report</p>
                <h1 class="mt-4 break-words text-3xl font-black tracking-tight text-white sm:text-5xl">{{ $host }}</h1>
                <p class="mt-4 max-w-3xl break-words text-base leading-7 text-slate-300">{{ $effectiveUrl }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-bold text-white ring-1 ring-white/15">Status: {{ str($scan->status)->headline() }}</span>
                    <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-bold text-white ring-1 ring-white/15">HTTP {{ $result?->http_status ?? 'N/A' }}</span>
                    <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-bold text-white ring-1 ring-white/15">Generated: {{ $formatDate($generatedAt) }}</span>
                    <span class="rounded-full px-4 py-2 text-sm font-bold ring-1 {{ $result?->uses_https ? 'bg-teal-400/15 text-teal-100 ring-teal-300/30' : 'bg-amber-400/15 text-amber-100 ring-amber-300/30' }}">HTTPS {{ $result?->uses_https ? 'enabled' : 'not detected' }}</span>
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-2xl shadow-slate-950/30">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-slate-500">Overall Visibility</p>
                <div class="mt-4 flex items-end gap-4">
                    <p class="text-7xl font-black {{ $scoreText($overall) }}">{{ $overall }}</p>
                    <p class="pb-3 text-sm font-semibold text-slate-500">/ 100</p>
                </div>
                <div class="mt-5 h-3 rounded-full bg-slate-100"><div class="h-3 rounded-full {{ $scoreBg($overall) }}" style="width: {{ max(0, min(100, $overall)) }}%"></div></div>
                <p class="mt-4 text-sm leading-6 text-slate-600">Combined SEO, AI Visibility, GEO and AEO readiness based on the current scan.</p>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-10 sm:py-14">
        <div class="mx-auto grid max-w-7xl gap-6 px-5 sm:px-6 lg:grid-cols-[1fr_380px] lg:px-8">
            <div class="space-y-6">
                @if (session('status'))
                    <div class="rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm font-medium text-teal-800">{{ session('status') }}</div>
                @endif
                @if ($scan->error_message)
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">{{ $scan->error_message }}</div>
                @endif

                <section class="{{ $sectionCard }}">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-700">Executive Summary</p><h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">Visibility Dashboard</h2></div><span class="w-fit rounded-full px-4 py-2 text-sm font-bold ring-1 {{ $softPill($overall) }}">Overall {{ $overall }}/100</span></div>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ([
                            'Scan completed' => $formatDate($scan->completed_at),
                            'Report generated' => $formatDate($generatedAt),
                            'HTTP status' => $result?->http_status ?? 'N/A',
                            'Response time' => $result ? $result->response_time_ms.' ms' : 'N/A',
                            'Page size' => $result ? number_format(($result->page_size_bytes ?? 0) / 1024, 1).' KB' : 'N/A',
                            'Opportunities' => $opportunities->count(),
                        ] as $summaryLabel => $value)
                            <div class="{{ $metricCard }}"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $summaryLabel }}</p><p class="mt-2 break-words text-xl font-black text-slate-950">{{ $value }}</p></div>
                        @endforeach
                    </div>
                </section>

                <section class="{{ $sectionCard }}">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">Score Breakdown</p><h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">Overall Visibility, SEO, AI Visibility, GEO and AEO</h2></div></div>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        @foreach ($scores as $scoreLabel => $score)
                            <div class="rounded-lg border border-slate-200 bg-gradient-to-b from-white to-slate-50 p-4 shadow-sm"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $scoreLabel }}</p><p class="mt-3 text-3xl font-black {{ $scoreText($score) }}">{{ (int) $score }}</p><div class="mt-3 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full {{ $scoreBg($score) }}" style="width: {{ max(0, min(100, (int) $score)) }}%"></div></div></div>
                        @endforeach
                    </div>
                    <div class="mt-6 grid gap-5 xl:grid-cols-[0.9fr_1.1fr]">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5"><h3 class="font-black text-slate-950">Visibility Score Explanation</h3><div class="mt-4 space-y-3">@foreach ($scoreExplanations as $scoreLabel => $description)<div class="rounded-lg bg-white p-4 ring-1 ring-slate-200"><div class="flex items-start justify-between gap-4"><p class="font-bold text-slate-950">{{ $scoreLabel }}</p><span class="rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $softPill($scores[$scoreLabel] ?? 0) }}">{{ (int) ($scores[$scoreLabel] ?? 0) }}</span></div><p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p></div>@endforeach</div></div>
                        <div class="rounded-lg border border-slate-200 bg-white p-5"><h3 class="font-black text-slate-950">Category score chart</h3><div class="mt-5 space-y-4">@foreach ($scores as $scoreLabel => $score)<div><div class="flex justify-between gap-4 text-sm"><span class="font-bold text-slate-800">{{ $scoreLabel }}</span><span class="font-black">{{ (int) $score }}</span></div><div class="mt-2 h-3 rounded-full bg-slate-100"><div class="h-3 rounded-full {{ $scoreBg($score) }}" style="width: {{ max(0, min(100, (int) $score)) }}%"></div></div></div>@endforeach</div></div>
                    </div>
                </section>

                @foreach ([
                    'AI Visibility Engine' => ['data' => (array) data_get($aiVisibility, 'signals', []), 'description' => 'Signals that help AI systems understand the brand, entity, expertise and trust context.', 'positive' => 'Detected', 'negative' => 'Missing'],
                    'GEO' => ['data' => (array) data_get($geo, 'signals', []), 'description' => 'Generative Engine Optimization signals for conversational discovery and semantic coverage.', 'positive' => 'Ready', 'negative' => 'Improve'],
                    'AEO' => ['data' => (array) data_get($aeo, 'signals', []), 'description' => 'Answer Engine Optimization checks for direct answers, FAQ coverage and snippet readiness.', 'positive' => 'Ready', 'negative' => 'Missing'],
                ] as $sectionTitle => $section)
                    <section class="{{ $sectionCard }}"><h2 class="text-2xl font-black tracking-tight text-slate-950">{{ $sectionTitle }}</h2><p class="mt-2 text-sm leading-6 text-slate-600">{{ $section['description'] }}</p><div class="mt-5 grid gap-3 sm:grid-cols-2">@forelse ($section['data'] as $signal => $ok)<div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-white p-4"><span class="font-semibold text-slate-900">{{ $label($signal) }}</span><span class="rounded-full px-3 py-1 text-sm font-bold ring-1 {{ $statusPill((bool) $ok) }}">{{ $ok ? $section['positive'] : $section['negative'] }}</span></div>@empty<div class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">No {{ $sectionTitle }} signals were recorded for this scan.</div>@endforelse</div></section>
                @endforeach

                @include('reports.partials.topic-intelligence')

                <section class="{{ $sectionCard }}"><h2 class="text-2xl font-black tracking-tight text-slate-950">Technical SEO</h2><div class="mt-5 grid gap-3 sm:grid-cols-2">@foreach (['Reachable' => $result?->is_reachable, 'HTTPS' => $result?->uses_https, 'robots.txt' => data_get($technical, 'robots_txt.exists'), 'sitemap.xml' => data_get($technical, 'sitemap_xml.exists'), 'Mobile viewport' => data_get($technical, 'mobile_viewport.exists')] as $technicalLabel => $ok)<div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-white p-4"><span class="font-semibold text-slate-900">{{ $technicalLabel }}</span><span class="rounded-full px-3 py-1 text-sm font-bold ring-1 {{ $statusPill((bool) $ok) }}">{{ $ok ? 'Passed' : 'Needs work' }}</span></div>@endforeach</div></section>

                <section class="{{ $sectionCard }}"><h2 class="text-2xl font-black tracking-tight text-slate-950">Content</h2><div class="mt-5 grid gap-4 sm:grid-cols-3">@foreach (['Visible words' => data_get($content, 'visible_word_count', 0), 'Thin content' => data_get($content, 'thin_content') ? 'Yes' : 'No', 'Content/HTML ratio' => data_get($content, 'content_html_ratio', 0).'%'] as $contentLabel => $value)<div class="{{ $metricCard }}"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $contentLabel }}</p><p class="mt-2 text-2xl font-black text-slate-950">{{ $value }}</p></div>@endforeach</div></section>

                <section class="{{ $sectionCard }}"><h2 class="text-2xl font-black tracking-tight text-slate-950">Images & Links</h2><div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">@foreach (['Internal links' => $result?->internal_links_count ?? 0, 'External links' => $result?->external_links_count ?? 0, 'Images' => $result?->images_count ?? 0, 'Images missing alt' => $result?->images_missing_alt_count ?? 0] as $metric => $value)<div class="{{ $metricCard }}"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $metric }}</p><p class="mt-2 text-2xl font-black text-slate-950">{{ $value }}</p></div>@endforeach</div></section>

                <section class="{{ $sectionCard }}"><h2 class="text-2xl font-black tracking-tight text-slate-950">Structured Data</h2><div class="mt-5 rounded-lg border border-slate-200 bg-white p-5"><p class="text-sm text-slate-600">JSON-LD blocks: <span class="font-bold text-slate-950">{{ data_get($structured, 'json_ld_count', 0) }}</span></p><p class="mt-2 text-sm text-slate-600">Schema types: <span class="font-bold text-slate-950">{{ implode(', ', data_get($structured, 'types', [])) ?: 'None detected' }}</span></p><p class="mt-2 text-sm text-slate-600">Microdata/RDFa: <span class="font-bold text-slate-950">{{ data_get($structured, 'has_microdata') || data_get($structured, 'has_rdfa') ? 'Detected' : 'Not detected' }}</span></p></div></section>

                <section class="{{ $sectionCard }}"><h2 class="text-2xl font-black tracking-tight text-slate-950">Social Preview</h2><div class="mt-5 grid gap-4 lg:grid-cols-2"><div class="rounded-lg border border-slate-200 bg-white p-5"><h3 class="font-bold text-slate-950">Open Graph</h3><p class="mt-3 text-sm text-slate-600">{{ collect(data_get($social, 'open_graph', []))->filter()->count() }} of 5 tags detected.</p></div><div class="rounded-lg border border-slate-200 bg-white p-5"><h3 class="font-bold text-slate-950">Twitter Card</h3><p class="mt-3 text-sm text-slate-600">{{ collect(data_get($social, 'twitter_card', []))->filter()->count() }} of 4 tags detected.</p></div></div></section>

                <section class="{{ $sectionCard }}"><h2 class="text-2xl font-black tracking-tight text-slate-950">Performance</h2><div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">@foreach (['Compression' => data_get($performance, 'uses_compression') ? 'Yes' : 'No', 'Cache-Control' => data_get($performance, 'cache_control') ?: 'Missing', 'Server header' => data_get($performance, 'server') ?: 'Missing', 'Response time' => ($result?->response_time_ms ?? 0).' ms'] as $metric => $value)<div class="{{ $metricCard }}"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $metric }}</p><p class="mt-2 break-words text-lg font-black text-slate-950">{{ $value }}</p></div>@endforeach</div></section>

                <section class="{{ $sectionCard }}"><h2 class="text-2xl font-black tracking-tight text-slate-950">Security</h2><div class="mt-5 grid gap-3 sm:grid-cols-2">@foreach (['strict_transport_security', 'x_frame_options', 'x_content_type_options', 'content_security_policy', 'referrer_policy'] as $header)<div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-white p-4"><span class="font-semibold text-slate-900">{{ $label($header) }}</span><span class="rounded-full px-3 py-1 text-sm font-bold ring-1 {{ $statusPill(filled(data_get($security, $header))) }}">{{ filled(data_get($security, $header)) ? 'Present' : 'Missing' }}</span></div>@endforeach</div></section>

                <section class="{{ $sectionCard }}"><h2 class="text-2xl font-black tracking-tight text-slate-950">Improvement Opportunities</h2><div class="mt-5 space-y-4">@forelse ($opportunities as $item)@if (is_array($item))@php $impact = strtolower($item['impact'] ?? 'medium'); @endphp<div class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm"><div class="flex flex-wrap gap-2"><span class="rounded-full bg-white px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-blue-900 ring-1 ring-blue-100">{{ $item['category'] ?? 'Visibility' }}</span><span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] ring-1 {{ $impactPill($impact) }}">Impact: {{ $item['impact'] ?? 'medium' }}</span><span class="rounded-full bg-white px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-slate-700 ring-1 ring-slate-200">Difficulty: {{ $item['difficulty'] ?? 'medium' }}</span><span class="rounded-full bg-teal-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-teal-800">Estimated gain: +{{ $item['estimated_gain'] ?? 3 }}</span></div><p class="mt-4 text-lg font-black text-blue-950">{{ $item['issue'] ?? '' }}</p><p class="mt-2 text-sm leading-6 text-blue-950">{{ $item['why_it_matters'] ?? $item['recommendation'] ?? '' }}</p><div class="mt-4 rounded-lg bg-white p-4 text-sm leading-6 text-slate-700 ring-1 ring-slate-200"><p class="font-black text-slate-950">How to Fix</p><p class="mt-1">{{ $item['how_to_fix'] ?? 'Review this item with your SEO team and update the page accordingly.' }}</p></div></div>@else<div class="rounded-xl border border-blue-100 bg-blue-50 p-5 text-sm font-medium leading-6 text-blue-950">{{ $item }}</div>@endif@empty<div class="rounded-lg border border-teal-100 bg-teal-50 p-4 text-sm font-medium text-teal-900">Strong baseline. Keep monitoring visibility.</div>@endforelse</div></section>

                <section class="{{ $sectionCard }}"><h2 class="text-2xl font-black tracking-tight text-slate-950">Scan History</h2><div class="mt-5 overflow-x-auto rounded-lg border border-slate-200 bg-white"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left"><tr><th class="px-4 py-3 font-bold text-slate-700">Date & Time</th><th class="px-4 py-3 font-bold text-slate-700">Domain</th><th class="px-4 py-3 font-bold text-slate-700">Status</th><th class="px-4 py-3 font-bold text-slate-700">Visibility Score</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($history ?? collect() as $item)@php $historyDomain = parse_url($item->normalized_url, PHP_URL_HOST) ?: $item->normalized_url; @endphp<tr><td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $formatDate($item->created_at) }}</td><td class="px-4 py-3 font-semibold text-slate-900">{{ $historyDomain }}</td><td class="px-4 py-3 font-semibold capitalize text-slate-900">{{ $item->status }}</td><td class="px-4 py-3 font-black text-slate-950">{{ $item->result?->score ?? 'N/A' }}</td></tr>@empty<tr><td colspan="4" class="px-4 py-5 text-center text-slate-500">No previous scans for this domain yet.</td></tr>@endforelse</tbody></table></div></section>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-6 lg:h-fit">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div class="flex items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Website Preview</p><h2 class="mt-1 break-words text-xl font-black tracking-tight text-slate-950">{{ $host }}</h2></div><span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $result?->uses_https ? 'bg-teal-50 text-teal-800 ring-teal-100' : 'bg-amber-50 text-amber-800 ring-amber-100' }}">{{ strtoupper($scheme) }}</span></div><div class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-slate-950"><div class="flex gap-1 border-b border-white/10 bg-slate-900 px-4 py-3"><span class="h-2.5 w-2.5 rounded-full bg-red-400"></span><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span><span class="h-2.5 w-2.5 rounded-full bg-teal-400"></span></div><div class="bg-white p-5"><p class="break-words text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Scanned URL</p><p class="mt-2 break-words text-sm font-bold text-slate-950">{{ $effectiveUrl }}</p><div class="mt-5 rounded-lg bg-slate-50 p-4 ring-1 ring-slate-200"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Page title</p><p class="mt-2 break-words text-sm font-black leading-6 text-slate-950">{{ filled($pageTitle) ? $pageTitle : 'No title detected' }}</p></div><div class="mt-3 rounded-lg bg-slate-50 p-4 ring-1 ring-slate-200"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Meta description</p><p class="mt-2 break-words text-sm leading-6 text-slate-700">{{ filled($metaDescription) ? $metaDescription : 'No meta description detected' }}</p></div></div></div>@if ($requestedUrl !== $effectiveUrl)<p class="mt-3 break-words text-xs font-medium text-slate-500">Requested: {{ $requestedUrl }}</p>@endif</section>
            </aside>
        </div>
    </section>
</x-layouts.app>
