<x-layouts.app :title="'SEO Report - '.$scan->normalized_url">
    @php
        $scoreBreakdown = $result?->score_breakdown ?? ['overall_score' => $result?->score ?? 0];
        $overallScore = $scoreBreakdown['overall_score'] ?? $result?->score ?? 0;
        $technical = $result?->technical_data ?? [];
        $onPage = $result?->on_page_data ?? [];
        $content = $result?->content_data ?? [];
        $performance = $result?->performance_data ?? [];
        $security = $result?->security_data ?? [];
        $social = $result?->social_data ?? [];
        $structured = $result?->structured_data ?? [];
        $ai = $result?->ai_readiness_data ?? [];
        $recommendations = $result?->recommendations ?? [];
        $scoreClass = $overallScore >= 80 ? 'text-teal-600' : ($overallScore >= 55 ? 'text-amber-600' : 'text-red-600');
        $scorePill = fn ($score) => $score >= 80 ? 'bg-teal-100 text-teal-800' : ($score >= 55 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700');
        $present = fn ($value) => filled($value) ? 'Present' : 'Missing';
        $statusPill = fn ($ok) => $ok ? 'bg-teal-100 text-teal-800' : 'bg-red-100 text-red-700';
    @endphp

    <section class="bg-slate-950 py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-300">SEO report v2</p>
            <div class="mt-4 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="break-words text-3xl font-black tracking-tight text-white sm:text-5xl">{{ $scan->normalized_url }}</h1>
                    <p class="mt-3 text-slate-300">Status: <span class="font-semibold capitalize">{{ $scan->status }}</span></p>
                </div>
                <div class="rounded-xl bg-white p-6 text-center shadow-xl">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-slate-500">Overall score</p>
                    <p class="mt-2 text-6xl font-black {{ $scoreClass }}">{{ $overallScore }}</p>
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
                            'Recommendations' => count($recommendations),
                        ] as $label => $value)
                            <div class="rounded-lg border border-slate-200 p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $label }}</p>
                                <p class="mt-2 text-2xl font-black text-slate-950">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Score Breakdown</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            'Technical' => $scoreBreakdown['technical_score'] ?? null,
                            'On-Page' => $scoreBreakdown['on_page_score'] ?? null,
                            'Content' => $scoreBreakdown['content_score'] ?? null,
                            'Performance' => $scoreBreakdown['performance_score'] ?? null,
                            'Security' => $scoreBreakdown['security_score'] ?? null,
                            'Social' => $scoreBreakdown['social_score'] ?? null,
                            'Structured Data' => $scoreBreakdown['structured_data_score'] ?? null,
                            'AI/GEO Readiness' => $scoreBreakdown['ai_readiness_score'] ?? null,
                        ] as $label => $score)
                            @if ($score !== null)
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <p class="font-bold text-slate-900">{{ $label }}</p>
                                        <span class="rounded-full px-3 py-1 text-sm font-bold {{ $scorePill($score) }}">{{ $score }}</span>
                                    </div>
                                    <div class="mt-3 h-2 rounded-full bg-slate-100">
                                        <div class="h-2 rounded-full bg-blue-600" style="width: {{ max(0, min(100, $score)) }}%"></div>
                                    </div>
                                </div>
                            @endif
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
                        ] as $label => $ok)
                            <div class="flex items-center justify-between rounded-lg border border-slate-200 p-4">
                                <span class="font-semibold text-slate-900">{{ $label }}</span>
                                <span class="rounded-full px-3 py-1 text-sm font-bold {{ $statusPill((bool) $ok) }}">{{ $ok ? 'Passed' : 'Needs work' }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-4 text-sm text-slate-600">robots.txt status: {{ data_get($technical, 'robots_txt.status', 'N/A') }}. Sitemap status: {{ data_get($technical, 'sitemap_xml.status', 'N/A') }}.</p>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">On-Page SEO</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            'Title length' => $result?->title_length ?? 0,
                            'Meta length' => $result?->meta_description_length ?? 0,
                            'H1 count' => $result?->h1_count ?? 0,
                            'Canonical' => $present($result?->canonical),
                            'Robots meta' => $result?->robots_meta ?: 'Not set',
                        ] as $label => $value)
                            <div class="rounded-lg border border-slate-200 p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $label }}</p>
                                <p class="mt-2 break-words text-lg font-black text-slate-950">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Content</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Visible words</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">{{ data_get($content, 'visible_word_count', 0) }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Thin content</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">{{ data_get($content, 'thin_content') ? 'Yes' : 'No' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Content/HTML ratio</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">{{ data_get($content, 'content_html_ratio', 0) }}%</p>
                        </div>
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
                        ] as $label => $value)
                            <div class="rounded-lg border border-slate-200 p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $label }}</p>
                                <p class="mt-2 text-2xl font-black text-slate-950">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Structured Data</h2>
                    <div class="mt-5 rounded-lg border border-slate-200 p-5">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">JSON-LD blocks</p>
                                <p class="mt-2 text-2xl font-black text-slate-950">{{ data_get($structured, 'json_ld_count', 0) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Microdata</p>
                                <p class="mt-2 text-lg font-black text-slate-950">{{ data_get($structured, 'has_microdata') ? 'Detected' : 'Not found' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">RDFa</p>
                                <p class="mt-2 text-lg font-black text-slate-950">{{ data_get($structured, 'has_rdfa') ? 'Detected' : 'Not found' }}</p>
                            </div>
                        </div>
                        <p class="mt-4 text-sm text-slate-600">Types: {{ implode(', ', data_get($structured, 'types', [])) ?: 'None detected' }}</p>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Social Preview</h2>
                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 p-5">
                            <h3 class="font-bold text-slate-950">Open Graph</h3>
                            <dl class="mt-4 space-y-2 text-sm">
                                @foreach ((array) data_get($social, 'open_graph', []) as $key => $value)
                                    <div class="flex justify-between gap-4">
                                        <dt class="font-semibold text-slate-600">{{ $key }}</dt>
                                        <dd class="text-right text-slate-900">{{ $present($value) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-5">
                            <h3 class="font-bold text-slate-950">Twitter Card</h3>
                            <dl class="mt-4 space-y-2 text-sm">
                                @foreach ((array) data_get($social, 'twitter_card', []) as $key => $value)
                                    <div class="flex justify-between gap-4">
                                        <dt class="font-semibold text-slate-600">{{ $key }}</dt>
                                        <dd class="text-right text-slate-900">{{ $present($value) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Performance</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            'Compression' => data_get($performance, 'uses_compression') ? 'Yes' : 'No',
                            'Content-Encoding' => data_get($performance, 'content_encoding') ?: 'None',
                            'Cache-Control' => data_get($performance, 'cache_control') ?: 'Missing',
                            'Server header' => data_get($performance, 'server') ?: 'Missing',
                        ] as $label => $value)
                            <div class="rounded-lg border border-slate-200 p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $label }}</p>
                                <p class="mt-2 break-words text-lg font-black text-slate-950">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Security</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            'Strict-Transport-Security' => data_get($security, 'strict_transport_security'),
                            'X-Frame-Options' => data_get($security, 'x_frame_options'),
                            'X-Content-Type-Options' => data_get($security, 'x_content_type_options'),
                            'Content-Security-Policy' => data_get($security, 'content_security_policy'),
                            'Referrer-Policy' => data_get($security, 'referrer_policy'),
                        ] as $label => $value)
                            <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-4">
                                <span class="font-semibold text-slate-900">{{ $label }}</span>
                                <span class="rounded-full px-3 py-1 text-sm font-bold {{ $statusPill(filled($value)) }}">{{ $present($value) }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">AI/GEO Readiness</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ($ai as $label => $ok)
                            <div class="flex items-center justify-between rounded-lg border border-slate-200 p-4">
                                <span class="font-semibold text-slate-900">{{ str_replace('_', ' ', ucfirst($label)) }}</span>
                                <span class="rounded-full px-3 py-1 text-sm font-bold {{ $statusPill((bool) $ok) }}">{{ $ok ? 'Ready' : 'Improve' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Priority Recommendations</h2>
                    <div class="mt-5 space-y-3">
                        @forelse ($recommendations as $recommendation)
                            @if (is_array($recommendation))
                                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-blue-900">{{ $recommendation['category'] ?? 'SEO' }}</span>
                                        <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] {{ ($recommendation['impact'] ?? 'low') === 'high' ? 'bg-red-100 text-red-700' : (($recommendation['impact'] ?? 'low') === 'medium' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">{{ $recommendation['impact'] ?? 'low' }} impact</span>
                                    </div>
                                    <p class="mt-3 font-bold text-blue-950">{{ $recommendation['issue'] ?? '' }}</p>
                                    <p class="mt-2 text-sm leading-6 text-blue-950">{{ $recommendation['recommendation'] ?? '' }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ $recommendation['how_to_fix'] ?? '' }}</p>
                                </div>
                            @else
                                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm font-medium leading-6 text-blue-950">{{ $recommendation }}</div>
                            @endif
                        @empty
                            <div class="rounded-lg border border-teal-100 bg-teal-50 p-4 text-sm font-medium text-teal-900">Strong baseline. Keep monitoring content quality, links, technical SEO, and AI visibility.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="h-fit rounded-xl border border-slate-200 bg-slate-50 p-6">
                <h2 class="text-xl font-black tracking-tight text-slate-950">Lead Capture CTA</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Save contact details for follow-up and future email report delivery.</p>
                <form method="POST" action="{{ route('lead.capture') }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="scan_uuid" value="{{ $scan->uuid }}">
                    <div>
                        <label class="text-sm font-semibold text-slate-800" for="name">Name</label>
                        <input id="name" name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-800" for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600" required>
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-800" for="phone">Phone</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-800" for="company_name">Company</label>
                        <input id="company_name" name="company_name" value="{{ old('company_name') }}" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                    </div>
                    <button class="w-full rounded-lg bg-slate-950 px-5 py-3 font-bold text-white hover:bg-slate-800" type="submit">Save details</button>
                </form>
            </aside>
        </div>
    </section>
</x-layouts.app>
