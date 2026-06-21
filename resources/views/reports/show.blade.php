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
        $opportunities = $result?->opportunity_data ?? $result?->recommendations ?? [];
        $scores = [
            'SEO' => $scoreBreakdown['seo_score'] ?? $scoreBreakdown['overall_score'] ?? $result?->score ?? 0,
            'AI Visibility' => $scoreBreakdown['ai_visibility_score'] ?? data_get($aiVisibility, 'score', 0),
            'GEO' => $scoreBreakdown['geo_score'] ?? data_get($geo, 'score', 0),
            'AEO' => $scoreBreakdown['aeo_score'] ?? data_get($aeo, 'score', 0),
            'Overall Visibility' => $scoreBreakdown['overall_visibility_score'] ?? $result?->score ?? 0,
        ];
        $overall = (int) $scores['Overall Visibility'];
        $scorePill = fn ($score) => $score >= 80 ? 'bg-teal-100 text-teal-800' : ($score >= 55 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700');
        $bar = fn ($score) => $score >= 80 ? 'bg-teal-600' : ($score >= 55 ? 'bg-amber-500' : 'bg-red-500');
        $statusPill = fn ($ok) => $ok ? 'bg-teal-100 text-teal-800' : 'bg-red-100 text-red-700';
        $label = fn ($key) => str($key)->replace('_', ' ')->headline();
        $radarPoints = collect(array_values($scores))->map(function ($score, $index) use ($scores) {
            $angle = -90 + ($index * (360 / count($scores)));
            $radius = 18 + (max(0, min(100, (int) $score)) / 100) * 72;
            return round(100 + cos(deg2rad($angle)) * $radius, 1).','.round(100 + sin(deg2rad($angle)) * $radius, 1);
        })->implode(' ');
    @endphp

    <section class="bg-slate-950 py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-300">AI visibility intelligence report v3</p>
            <div class="mt-4 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="break-words text-3xl font-black tracking-tight text-white sm:text-5xl">{{ $scan->normalized_url }}</h1>
                    <p class="mt-3 text-slate-300">Status: <span class="font-semibold capitalize">{{ $scan->status }}</span></p>
                </div>
                <div class="rounded-xl bg-white p-6 text-center shadow-xl">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-slate-500">Overall visibility</p>
                    <p class="mt-2 text-6xl font-black {{ $overall >= 80 ? 'text-teal-600' : ($overall >= 55 ? 'text-amber-600' : 'text-red-600') }}">{{ $overall }}</p>
                    <p class="text-sm text-slate-500">out of 100</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-12 sm:py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 sm:px-6 lg:grid-cols-[1fr_380px] lg:px-8">
            <div class="space-y-8">
                @if (session('status'))
                    <div class="rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm font-medium text-teal-800">{{ session('status') }}</div>
                @endif

                @if ($scan->error_message)
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">{{ $scan->error_message }}</div>
                @endif

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Executive Summary</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            'HTTP status' => $result?->http_status ?? 'N/A',
                            'Response time' => $result ? $result->response_time_ms.' ms' : 'N/A',
                            'Page size' => $result ? number_format($result->page_size_bytes / 1024, 1).' KB' : 'N/A',
                            'Opportunities' => count($opportunities),
                        ] as $summaryLabel => $value)
                            <div class="rounded-lg border border-slate-200 p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $summaryLabel }}</p>
                                <p class="mt-2 text-2xl font-black text-slate-950">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-700">Visibility Dashboard</p>
                            <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">SEO, AI Visibility, GEO, and AEO</h2>
                        </div>
                        <span class="w-fit rounded-full bg-slate-950 px-4 py-2 text-sm font-bold text-white">Overall {{ $overall }}/100</span>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        @foreach ($scores as $scoreLabel => $score)
                            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $scoreLabel }}</p>
                                <p class="mt-3 text-3xl font-black {{ $score >= 80 ? 'text-teal-600' : ($score >= 55 ? 'text-amber-600' : 'text-red-600') }}">{{ (int) $score }}</p>
                                <div class="mt-3 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full {{ $bar($score) }}" style="width: {{ max(0, min(100, (int) $score)) }}%"></div></div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 grid gap-5 lg:grid-cols-[320px_1fr]">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <h3 class="font-black text-slate-950">Radar chart</h3>
                            <svg viewBox="0 0 200 200" class="mt-4 h-64 w-full" role="img" aria-label="Visibility radar chart">
                                <polygon points="100,20 176,75 147,165 53,165 24,75" fill="none" stroke="#cbd5e1" />
                                <polygon points="100,45 152,83 132,145 68,145 48,83" fill="none" stroke="#e2e8f0" />
                                <polygon points="{{ $radarPoints }}" fill="rgba(15,118,110,.24)" stroke="#0f766e" stroke-width="3" />
                            </svg>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-5">
                            <h3 class="font-black text-slate-950">Category score chart</h3>
                            <div class="mt-5 space-y-4">
                                @foreach ($scores as $scoreLabel => $score)
                                    <div>
                                        <div class="flex justify-between text-sm"><span class="font-bold text-slate-800">{{ $scoreLabel }}</span><span class="font-black">{{ (int) $score }}</span></div>
                                        <div class="mt-2 h-3 rounded-full bg-slate-100"><div class="h-3 rounded-full {{ $bar($score) }}" style="width: {{ max(0, min(100, (int) $score)) }}%"></div></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">AI Visibility Engine</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ((array) data_get($aiVisibility, 'signals', []) as $signal => $ok)
                            <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-4">
                                <span class="font-semibold text-slate-900">{{ $label($signal) }}</span>
                                <span class="rounded-full px-3 py-1 text-sm font-bold {{ $statusPill((bool) $ok) }}">{{ $ok ? 'Detected' : 'Missing' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Generative Engine Optimization</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ((array) data_get($geo, 'signals', []) as $signal => $ok)
                            <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-4">
                                <span class="font-semibold text-slate-900">{{ $label($signal) }}</span>
                                <span class="rounded-full px-3 py-1 text-sm font-bold {{ $statusPill((bool) $ok) }}">{{ $ok ? 'Ready' : 'Improve' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Answer Engine Optimization</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ((array) data_get($aeo, 'signals', []) as $signal => $ok)
                            <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-4">
                                <span class="font-semibold text-slate-900">{{ $label($signal) }}</span>
                                <span class="rounded-full px-3 py-1 text-sm font-bold {{ $statusPill((bool) $ok) }}">{{ $ok ? 'Ready' : 'Missing' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Technical SEO</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            'Reachable' => $result?->is_reachable,
                            'HTTPS' => $result?->uses_https,
                            'robots.txt' => data_get($technical, 'robots_txt.exists'),
                            'sitemap.xml' => data_get($technical, 'sitemap_xml.exists'),
                            'Mobile viewport' => data_get($technical, 'mobile_viewport.exists'),
                        ] as $technicalLabel => $ok)
                            <div class="flex items-center justify-between rounded-lg border border-slate-200 p-4"><span class="font-semibold text-slate-900">{{ $technicalLabel }}</span><span class="rounded-full px-3 py-1 text-sm font-bold {{ $statusPill((bool) $ok) }}">{{ $ok ? 'Passed' : 'Needs work' }}</span></div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Content</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-3">
                        @foreach ([
                            'Visible words' => data_get($content, 'visible_word_count', 0),
                            'Thin content' => data_get($content, 'thin_content') ? 'Yes' : 'No',
                            'Content/HTML ratio' => data_get($content, 'content_html_ratio', 0).'%',
                        ] as $contentLabel => $value)
                            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $contentLabel }}</p><p class="mt-2 text-2xl font-black text-slate-950">{{ $value }}</p></div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Images & Links</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            'Internal links' => $result?->internal_links_count ?? 0,
                            'External links' => $result?->external_links_count ?? 0,
                            'Images' => $result?->images_count ?? 0,
                            'Images missing alt' => $result?->images_missing_alt_count ?? 0,
                        ] as $metric => $value)
                            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $metric }}</p><p class="mt-2 text-2xl font-black text-slate-950">{{ $value }}</p></div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Structured Data</h2>
                    <div class="mt-5 rounded-lg border border-slate-200 p-5">
                        <p class="text-sm text-slate-600">JSON-LD blocks: <span class="font-bold text-slate-950">{{ data_get($structured, 'json_ld_count', 0) }}</span></p>
                        <p class="mt-2 text-sm text-slate-600">Types: <span class="font-bold text-slate-950">{{ implode(', ', data_get($structured, 'types', [])) ?: 'None detected' }}</span></p>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Social Preview</h2>
                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 p-5"><h3 class="font-bold text-slate-950">Open Graph</h3><p class="mt-3 text-sm text-slate-600">{{ collect(data_get($social, 'open_graph', []))->filter()->count() }} of 5 tags detected.</p></div>
                        <div class="rounded-lg border border-slate-200 p-5"><h3 class="font-bold text-slate-950">Twitter Card</h3><p class="mt-3 text-sm text-slate-600">{{ collect(data_get($social, 'twitter_card', []))->filter()->count() }} of 4 tags detected.</p></div>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Performance</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            'Compression' => data_get($performance, 'uses_compression') ? 'Yes' : 'No',
                            'Cache-Control' => data_get($performance, 'cache_control') ?: 'Missing',
                            'Server header' => data_get($performance, 'server') ?: 'Missing',
                            'Response time' => ($result?->response_time_ms ?? 0).' ms',
                        ] as $metric => $value)
                            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $metric }}</p><p class="mt-2 break-words text-lg font-black text-slate-950">{{ $value }}</p></div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Security</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach (['strict_transport_security', 'x_frame_options', 'x_content_type_options', 'content_security_policy', 'referrer_policy'] as $header)
                            <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-4"><span class="font-semibold text-slate-900">{{ $label($header) }}</span><span class="rounded-full px-3 py-1 text-sm font-bold {{ $statusPill(filled(data_get($security, $header))) }}">{{ filled(data_get($security, $header)) ? 'Present' : 'Missing' }}</span></div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Improvement Opportunities</h2>
                    <div class="mt-5 space-y-3">
                        @forelse ($opportunities as $item)
                            @if (is_array($item))
                                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                                    <div class="flex flex-wrap gap-2"><span class="rounded-full bg-white px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-blue-900">{{ $item['category'] ?? 'Visibility' }}</span><span class="rounded-full bg-teal-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-teal-800">+{{ $item['estimated_gain'] ?? 3 }} gain</span></div>
                                    <p class="mt-3 font-bold text-blue-950">{{ $item['issue'] ?? '' }}</p>
                                    <p class="mt-2 text-sm leading-6 text-blue-950">{{ $item['why_it_matters'] ?? $item['recommendation'] ?? '' }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-700"><span class="font-bold">How to fix:</span> {{ $item['how_to_fix'] ?? '' }}</p>
                                </div>
                            @else
                                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm font-medium leading-6 text-blue-950">{{ $item }}</div>
                            @endif
                        @empty
                            <div class="rounded-lg border border-teal-100 bg-teal-50 p-4 text-sm font-medium text-teal-900">Strong baseline. Keep monitoring visibility.</div>
                        @endforelse
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Scan History</h2>
                    <div class="mt-5 rounded-lg border border-slate-200 p-5 text-sm text-slate-600">{{ ($history ?? collect())->count() }} previous scans found for this domain.</div>
                </section>
            </div>

            <aside class="h-fit rounded-xl border border-slate-200 bg-slate-50 p-6">
                <h2 class="text-xl font-black tracking-tight text-slate-950">Lead Capture CTA</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Save contact details for follow-up and future email report delivery.</p>
                <form method="POST" action="{{ route('lead.capture') }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="scan_uuid" value="{{ $scan->uuid }}">
                    <div><label class="text-sm font-semibold text-slate-800" for="name">Name</label><input id="name" name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"></div>
                    <div><label class="text-sm font-semibold text-slate-800" for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600" required>@error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-semibold text-slate-800" for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"></div>
                    <div><label class="text-sm font-semibold text-slate-800" for="company_name">Company</label><input id="company_name" name="company_name" value="{{ old('company_name') }}" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"></div>
                    <button class="w-full rounded-lg bg-slate-950 px-5 py-3 font-bold text-white hover:bg-slate-800" type="submit">Save details</button>
                </form>
            </aside>
        </div>
    </section>
</x-layouts.app>
