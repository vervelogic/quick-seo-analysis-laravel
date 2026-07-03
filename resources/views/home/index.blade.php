<x-layouts.app :title="config('app.name').' - Search & AI Visibility Intelligence'">
    <section id="scan" class="relative overflow-hidden bg-slate-950">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_18%_16%,rgba(20,184,166,.24),transparent_34%),radial-gradient(circle_at_80%_10%,rgba(37,99,235,.24),transparent_32%),linear-gradient(135deg,#020617,#0f172a_48%,#082f49)]"></div>
        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.055)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.055)_1px,transparent_1px)] bg-[size:48px_48px]"></div>

        <div class="relative mx-auto grid max-w-7xl gap-10 px-5 py-14 sm:px-6 sm:py-18 lg:grid-cols-[1.03fr_0.97fr] lg:px-8 lg:py-20">
            <div class="flex flex-col justify-center">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-teal-300">Search &amp; AI visibility intelligence</p>
                <h1 class="mt-5 max-w-4xl text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">Check where you are on Search Engines and AI Visibility.</h1>
                <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-300">Understand what your page communicates, how search engines interpret it and how AI systems evaluate your content.</p>

                <div class="mt-7 flex flex-wrap gap-2.5" aria-label="Platforms QSA helps analyze for visibility context">
                    @foreach ([
                        ['label' => 'Google', 'initial' => 'G'],
                        ['label' => 'Bing', 'initial' => 'B'],
                        ['label' => 'ChatGPT', 'initial' => 'C'],
                        ['label' => 'Gemini', 'initial' => 'Gm'],
                        ['label' => 'Claude', 'initial' => 'Cl'],
                        ['label' => 'Perplexity', 'initial' => 'P'],
                    ] as $platform)
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.07] px-3 py-2 text-sm font-bold text-slate-100 shadow-sm">
                            <span class="flex h-7 min-w-7 items-center justify-center rounded-full bg-white text-xs font-black text-slate-950">{{ $platform['initial'] }}</span>
                            {{ $platform['label'] }}
                        </span>
                    @endforeach
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ([
                        ['title' => 'Search Engine Visibility', 'abbr' => 'SEO', 'icon' => 'S'],
                        ['title' => 'AI Engine Visibility', 'abbr' => 'AEO', 'icon' => 'AI'],
                        ['title' => 'Geo Visibility', 'abbr' => 'GEO', 'icon' => 'G'],
                        ['title' => 'Answer Engine Optimization', 'abbr' => 'Answers', 'icon' => 'A'],
                        ['title' => 'Intent & Content Alignment', 'abbr' => 'Intent', 'icon' => 'I'],
                        ['title' => 'Trust & Authority Signals', 'abbr' => 'Trust', 'icon' => 'T'],
                    ] as $capability)
                        <div class="rounded-xl border border-white/10 bg-white/[0.06] p-4 shadow-sm backdrop-blur">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-300/15 text-sm font-black text-teal-200 ring-1 ring-teal-300/20">{{ $capability['icon'] }}</span>
                                <div>
                                    <p class="text-sm font-black leading-5 text-white">{{ $capability['title'] }}</p>
                                    <p class="mt-1 text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ $capability['abbr'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-5 lg:self-center">
                <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-white p-6 shadow-2xl shadow-blue-950/40 sm:p-7">
                    <div data-scan-loading class="pointer-events-none absolute inset-0 z-10 hidden bg-white/95 p-6 backdrop-blur-sm sm:p-8">
                        <div class="flex h-full min-h-80 flex-col justify-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-50">
                                <div class="h-9 w-9 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600"></div>
                            </div>
                            <div class="mx-auto mt-6 max-w-sm text-center">
                                <div class="text-2xl font-black tracking-tight text-slate-950">Scanning your website...</div>
                                <p class="mt-3 text-sm leading-6 text-slate-600">Checking SEO, AI Visibility, GEO and AEO signals. This usually takes a few seconds.</p>
                            </div>
                            <div class="mx-auto mt-6 w-full max-w-sm overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2 w-2/3 animate-pulse rounded-full bg-blue-600"></div>
                            </div>
                        </div>
                    </div>

                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Run a free visibility audit</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Enter a domain, homepage, or landing page URL. The first scan runs instantly.</p>

                    <form data-scan-form method="POST" action="{{ route('scan.store') }}" class="mt-6 space-y-4">
                        @csrf
                        <label for="url" class="block text-sm font-semibold text-slate-800">Website URL</label>
                        <div class="flex flex-col gap-3 sm:flex-row lg:flex-col xl:flex-row">
                            <input id="url" name="url" value="{{ old('url') }}" placeholder="example.com" class="min-h-12 flex-1 rounded-lg border-slate-300 text-base shadow-sm focus:border-blue-600 focus:ring-blue-600" required>
                            <button data-scan-button class="qsa-scan-button inline-flex min-h-12 items-center justify-center rounded-lg bg-blue-600 px-6 font-bold text-white shadow-sm focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-not-allowed disabled:bg-blue-400" type="submit">Run Free Visibility Scan</button>
                        </div>
                        <p class="text-sm text-slate-500">Free preview available. Login to download full PDF report.</p>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-medium text-slate-500">
                            <span>You can enter example.com, https://example.com, or http://example.com.</span>
                            <span>✓ No credit card required. No signup.</span>
                        </div>
                        <p class="text-xs font-medium text-slate-500">Free scans include anonymous limits to stop abuse. If you hit a protection step, sign in to continue.</p>
                        <a href="{{ route('keyword-focus.create') }}" class="inline-flex text-sm font-bold text-blue-700 hover:text-blue-800">Need keyword alignment instead? Start Keyword Focus Audit.</a>
                        @error('url')
                            <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </form>
                </div>

                <article class="rounded-2xl border border-white/10 bg-white/[0.08] p-5 text-white shadow-2xl shadow-blue-950/30 backdrop-blur sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-teal-200">Industry benchmark, not live data</p>
                            <h2 class="mt-2 text-2xl font-black tracking-tight">Market Visibility Landscape</h2>
                        </div>
                        <span class="w-fit rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.12em] text-slate-950">Benchmark</span>
                    </div>

                    <div class="mt-5 space-y-4">
                        @foreach ([
                            ['name' => 'Google Search', 'value' => '~90%', 'note' => 'Search benchmark', 'bar' => 90],
                            ['name' => 'Bing Search', 'value' => '~5%', 'note' => 'Search benchmark', 'bar' => 5],
                            ['name' => 'Other Search', 'value' => '~4-5%', 'note' => 'Search benchmark', 'bar' => 5],
                            ['name' => 'Zero-Click Search', 'value' => '~68%', 'note' => 'Behavior benchmark', 'bar' => 68],
                            ['name' => 'AI Answer Engines', 'value' => 'Rising fast', 'note' => 'Discovery shift', 'bar' => 72],
                        ] as $stat)
                            <div>
                                <div class="flex items-center justify-between gap-4 text-sm">
                                    <div>
                                        <p class="font-black text-white">{{ $stat['name'] }}</p>
                                        <p class="mt-0.5 text-xs font-medium text-slate-400">{{ $stat['note'] }}</p>
                                    </div>
                                    <p class="shrink-0 font-black text-teal-200">{{ $stat['value'] }}</p>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-white/10">
                                    <div class="h-2 rounded-full bg-gradient-to-r from-teal-300 to-blue-300" style="width: {{ $stat['bar'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-5 text-xs leading-5 text-slate-400">Industry benchmarks from StatCounter, SparkToro/Datos and public AI/search reports. Review periodically.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="audit-path" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Choose Your Audit Path</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Start with the right audit for your current search and AI visibility questions.</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">Use the live visibility audit to see what a page communicates today, or review keyword alignment when you already have an SEO target in mind.</p>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-2xl font-bold text-slate-950">Current Visibility Audit</h3>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-emerald-700">Live</span>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-slate-600">Understand what your page currently communicates to search engines, AI answer engines, AI overviews, and discovery systems.</p>
                    <ul class="mt-6 space-y-3 text-sm font-medium text-slate-700">
                        <li>Google Search</li>
                        <li>Bing Search</li>
                        <li>AI Answer Engines</li>
                        <li>AI Overviews</li>
                        <li>Search Systems</li>
                    </ul>
                    <a href="#scan" class="mt-6 inline-flex rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white hover:bg-slate-800">Run Current Visibility Audit</a>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-7 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-2xl font-bold text-slate-950">Keyword Focus Audit</h3>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-amber-700">Beta</span>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-slate-600">Already doing SEO? Check whether your page actually supports the keywords you are targeting.</p>
                    <ul class="mt-6 space-y-3 text-sm font-medium text-slate-700">
                        <li>Keyword support</li>
                        <li>Search intent match</li>
                        <li>Content coverage</li>
                        <li>Commercial signals</li>
                    </ul>
                    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm leading-6 text-slate-600">
                        We do not measure rankings, traffic, or keyword difficulty here. This audit focuses on on-page alignment only.
                    </div>
                    <a href="{{ route('keyword-focus.create') }}" class="mt-6 inline-flex rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white hover:bg-slate-800">Start Keyword Focus Audit</a>
                </article>
            </div>
        </div>
    </section>

    <section id="checks" class="bg-slate-50 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-700">What QSA Checks</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">A visibility report built for search performance, AI discovery, and business action.</h2>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['title' => 'SEO Health', 'copy' => 'Core crawl, metadata, on-page, structure, and page quality signals that affect discoverability.'],
                    ['title' => 'AI Visibility', 'copy' => 'Signals that help answer engines understand what your page is about and when it should be cited.'],
                    ['title' => 'Technical SEO', 'copy' => 'Reachability, status, HTTPS, performance, links, images, and technical trust indicators.'],
                    ['title' => 'Content & Keyword Gaps', 'copy' => 'Topic coverage, commercial intent, phrase support, and missing areas that reduce relevance.'],
                ] as $check)
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-950">{{ $check['title'] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $check['copy'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="benefits" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Report Preview</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">See the kind of action plan your team can work from immediately.</h2>
                    <p class="mt-4 text-base leading-7 text-slate-600">QSA turns a page scan into a stakeholder-friendly summary with scores, opportunities, fixes, and a roadmap you can actually prioritize.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        ['title' => 'SEO Score', 'copy' => 'A fast snapshot of overall search health and technical completeness.'],
                        ['title' => 'AI Visibility Score', 'copy' => 'How clearly your page communicates topics, intent, and trust signals to AI systems.'],
                        ['title' => 'Priority Fixes', 'copy' => 'The highest-impact improvements surfaced first so teams know where to start.'],
                        ['title' => '30-Day Roadmap', 'copy' => 'A practical follow-up structure instead of a long checklist with no order.'],
                        ['title' => 'PDF Report After Login', 'copy' => 'Logged-in users can download a PDF report for sharing, review, and follow-up.'],
                    ] as $item)
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-6 shadow-sm {{ $loop->last ? 'sm:col-span-2' : '' }}">
                            <h3 class="text-lg font-bold text-slate-950">{{ $item['title'] }}</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $item['copy'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section id="platforms" class="bg-slate-950 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-cyan-300">Supported Search &amp; AI Platforms</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">Built for the way discovery now happens across search results and AI answers.</h2>
            </div>

            <div class="mt-10 flex flex-wrap gap-4">
                @foreach (['Google', 'Bing', 'ChatGPT', 'Gemini', 'Claude', 'Perplexity'] as $platform)
                    <div class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-100">{{ $platform }}</div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">How It Works</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">A simple flow for moving from page uncertainty to a plan.</h2>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['step' => '01', 'title' => 'Enter Website', 'copy' => 'Paste a full URL or domain and start the scan from the homepage.'],
                    ['step' => '02', 'title' => 'Analyze Visibility', 'copy' => 'QSA reviews search, AI, technical, and content signals in one pass.'],
                    ['step' => '03', 'title' => 'Get Action Plan', 'copy' => 'See scores, focus areas, and improvements in a business-ready report.'],
                    ['step' => '04', 'title' => 'Track Progress', 'copy' => 'Return with fresh scans and compare how the page evolves over time.'],
                ] as $step)
                    <article class="rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-slate-400">{{ $step['step'] }}</p>
                        <h3 class="mt-4 text-xl font-bold text-slate-950">{{ $step['title'] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $step['copy'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="legacy-login" class="bg-slate-50 py-16 sm:py-20">
        <div class="mx-auto max-w-5xl px-5 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm sm:p-10">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-700">Legacy User Access</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Used Quick SEO Analysis before? Login with Google to claim your previous reports.</h2>
                <p class="mt-4 max-w-3xl text-base leading-7 text-slate-600">If you have historical scans from the earlier platform, use your current email to sign in and reconnect that history to your new workspace as the claim flow becomes available.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('login') }}" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white hover:bg-slate-800">Login</a>
                    <a href="#scan" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold text-slate-800 hover:border-slate-400 hover:bg-slate-50">Run a new scan first</a>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">FAQ</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">A few things teams usually want to know before they start.</h2>
            </div>

            <div class="mt-10 grid gap-5 lg:grid-cols-2">
                @foreach ([
                    ['question' => 'Do I need an account to run a scan?', 'answer' => 'No. You can run a public scan and review the preview before deciding to log in for PDF access.'],
                    ['question' => 'What is the difference between the two audit paths?', 'answer' => 'Current Visibility shows what the page communicates today. Keyword Focus reviews whether the page supports the keywords you are already targeting.'],
                    ['question' => 'Does QSA track rankings or search volume?', 'answer' => 'Not in these audits. QSA focuses on page signals, intent alignment, visibility readiness, and what should be improved next.'],
                    ['question' => 'Can I share the report with clients or stakeholders?', 'answer' => 'Yes. Logged-in users can download a PDF report for review and follow-up conversations.'],
                ] as $faq)
                    <article class="rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-950">{{ $faq['question'] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $faq['answer'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <style>
        .qsa-scan-button { position: relative; overflow: hidden; transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease; }
        .qsa-scan-button::after { content: ''; position: absolute; inset: -40% auto -40% -60%; width: 45%; transform: rotate(20deg); background: linear-gradient(90deg, transparent, rgba(255,255,255,.45), transparent); transition: left .55s ease; }
        .qsa-scan-button:hover { transform: translateY(-2px); box-shadow: 0 18px 35px rgba(37, 99, 235, .28); }
        .qsa-scan-button:hover::after { left: 120%; }
    </style>

    <script>
        document.querySelectorAll('[data-scan-form]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('[data-scan-button]');
                const loading = form.closest('.relative')?.querySelector('[data-scan-loading]');

                if (button) {
                    button.disabled = true;
                    button.textContent = 'Scanning...';
                }

                if (loading) {
                    loading.classList.remove('hidden');
                    loading.classList.add('flex');
                }
            });
        });
    </script>
</x-layouts.app>
