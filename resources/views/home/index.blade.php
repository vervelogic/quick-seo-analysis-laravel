<x-layouts.app :title="config('app.name').' - Search & AI Visibility Intelligence'">
    @php
        $discoveryStats = [
            ['name' => 'Google Search', 'type' => 'Search Engine', 'value' => '~90%', 'label' => 'Global search market share', 'source' => 'StatCounter Global Stats, May 2026', 'bar' => 90],
            ['name' => 'Bing Search', 'type' => 'Search Engine', 'value' => '~5%', 'label' => 'Global search market share', 'source' => 'StatCounter Global Stats, May 2026', 'bar' => 5],
            ['name' => 'Other Search Engines', 'type' => 'Search Engines', 'value' => '~4-5%', 'label' => 'Yahoo, Yandex, DuckDuckGo, Baidu and regional search engines', 'source' => 'StatCounter Global Stats, May 2026', 'bar' => 5],
            ['name' => 'Zero-Click Search', 'type' => 'Search Behavior', 'value' => '~68%', 'label' => 'Google searches ending without a click', 'source' => 'SparkToro / Datos, early 2026', 'bar' => 68],
            ['name' => 'AI Answer Engines', 'type' => 'AI Discovery', 'value' => 'Rising Fast', 'label' => 'ChatGPT, Gemini, Claude and Perplexity are changing how people discover answers', 'source' => 'Public AI usage and market reports', 'bar' => 72],
            ['name' => 'AI Overview Impact', 'type' => 'Google AI Search', 'value' => 'Growing', 'label' => 'AI-generated answers reduce traditional click behavior', 'source' => 'Public search industry research', 'bar' => 64],
        ];
    @endphp

    <section id="scan" class="relative overflow-hidden bg-slate-950">
        <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(15,23,42,1),rgba(30,41,59,0.96)_48%,rgba(12,74,110,0.72))]"></div>
        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.06)_1px,transparent_1px)] bg-[size:48px_48px]"></div>
        <div class="relative mx-auto grid max-w-7xl gap-10 px-5 py-16 sm:px-6 sm:py-20 lg:grid-cols-[1.08fr_0.92fr] lg:px-8 lg:py-24">
            <div class="flex flex-col justify-center">
                <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-teal-300">Search & AI visibility intelligence</p>
                <h1 class="max-w-3xl text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">See what your page currently communicates to Google and AI.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">QSA turns page signals into a practical visibility report covering SEO, AI readiness, citation confidence, commercial intent and content gaps.</p>
                <div class="mt-8 grid grid-cols-1 gap-3 text-sm text-slate-300 sm:grid-cols-3">
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">Current visibility audit</div>
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">AI answer readiness</div>
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">Lead-ready reports</div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-white/10 bg-white p-6 shadow-2xl shadow-blue-950/40 sm:p-8">
                <div data-scan-loading class="pointer-events-none absolute inset-0 z-10 hidden bg-white/95 p-6 backdrop-blur-sm sm:p-8">
                    <div class="flex h-full min-h-80 flex-col justify-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-50">
                            <div class="h-9 w-9 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600"></div>
                        </div>
                        <div class="mx-auto mt-6 max-w-sm text-center">
                            <h2 class="text-2xl font-black tracking-tight text-slate-950">Scanning your website...</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600">Checking SEO, AI Visibility, GEO and AEO signals. This usually takes a few seconds.</p>
                        </div>
                        <div class="mx-auto mt-6 w-full max-w-sm overflow-hidden rounded-full bg-slate-100">
                            <div class="h-2 w-2/3 animate-pulse rounded-full bg-blue-600"></div>
                        </div>
                    </div>
                </div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-950">Get a free visibility report</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Enter a domain, homepage, or landing page URL. The first scan runs instantly.</p>

                <form data-scan-form method="POST" action="{{ route('scan.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <label for="url" class="block text-sm font-semibold text-slate-800">Website URL</label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input id="url" name="url" value="{{ old('url') }}" placeholder="example.com" class="min-h-12 flex-1 rounded-lg border-slate-300 text-base shadow-sm focus:border-blue-600 focus:ring-blue-600" required>
                        <button data-scan-button class="qsa-scan-button inline-flex min-h-12 items-center justify-center rounded-lg bg-blue-600 px-6 font-bold text-white shadow-sm focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-not-allowed disabled:bg-blue-400" type="submit">Run Free Visibility Scan</button>
                    </div>
                    <p class="text-xs font-medium text-slate-500">You can enter example.com, https://example.com, or http://example.com.</p>
                    @error('url')
                        <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </form>
            </div>
        </div>
    </section>

    <section class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Audit paths</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Choose Your Audit Path</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">Start with a Current Visibility Audit to understand what search engines and AI systems currently see. Coming soon: Keyword Focus Audit for businesses already targeting specific keywords through SEO campaigns.</p>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-2">
                <article class="relative overflow-hidden rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-6 shadow-sm sm:p-8">
                    <div class="absolute right-6 top-6 rounded-full bg-blue-600/10 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-blue-700">Live</div>
                    <h3 class="text-2xl font-black tracking-tight text-slate-950">Current Visibility Audit</h3>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-600">Understand what your page currently communicates to:</p>
                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        @foreach (['Google Search', 'Bing Search', 'AI Answer Engines', 'AI Overviews', 'Search Systems'] as $signal)
                            <div class="flex items-center gap-3 rounded-lg bg-white p-3 text-sm font-bold text-slate-800 shadow-sm ring-1 ring-blue-100">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-50 text-xs font-black text-teal-700">✓</span>
                                <span>{{ $signal }}</span>
                            </div>
                        @endforeach
                    </div>
                    <a href="#scan" class="qsa-scan-button mt-7 inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-blue-600 px-6 font-bold text-white shadow-sm focus:outline-none focus:ring-4 focus:ring-blue-200 sm:w-auto">Run Visibility Audit</a>
                </article>

                <article class="relative overflow-hidden rounded-xl border border-slate-800 bg-slate-950 p-6 text-white shadow-sm sm:p-8">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-teal-300 via-blue-400 to-indigo-400"></div>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-2xl font-black tracking-tight">Keyword Focus Audit</h3>
                            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300">Designed for businesses already investing in SEO. Validate whether your page aligns with the keywords you are targeting.</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-teal-200 ring-1 ring-white/10">Coming Soon</span>
                    </div>
                    <div class="mt-7 rounded-xl border border-white/10 bg-white/[0.06] p-5">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-200">Future capabilities</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach (['Keyword Alignment Analysis', 'Search Intent Matching', 'Content Coverage Analysis', 'Commercial Intent Validation', 'SEO Campaign Verification'] as $capability)
                                <div class="flex items-center gap-3 rounded-lg bg-white/5 p-3 text-sm font-bold text-slate-100 ring-1 ring-white/10">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-300/15 text-xs font-black text-teal-200">✓</span>
                                    <span>{{ $capability }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="bg-slate-950 py-16 text-white sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-300">Market Intelligence Foundation</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">The Modern Discovery Ecosystem</h2>
                    <p class="mt-4 text-base leading-7 text-slate-300">Customers no longer discover brands only through Google links. Visibility now happens across search engines, AI answer engines, video, communities and zero-click results.</p>
                    <p class="mt-5 text-sm leading-6 text-slate-400">QSA helps you understand what your website currently communicates to search engines and AI answer engines — before you spend more on SEO.</p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach (['Google', 'Bing', 'Yahoo', 'DuckDuckGo', 'ChatGPT', 'Gemini', 'Claude', 'Perplexity', 'YouTube', 'Reddit'] as $platform)
                            <span class="rounded-full bg-white/10 px-3 py-1.5 text-sm font-bold text-slate-200 ring-1 ring-white/10">{{ $platform }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($discoveryStats as $stat)
                        <article class="rounded-xl border border-white/10 bg-white/[0.06] p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-teal-200">{{ $stat['type'] }}</p>
                                    <h3 class="mt-2 text-lg font-black">{{ $stat['name'] }}</h3>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-sm font-black text-slate-950">{{ $stat['value'] }}</span>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-slate-300">{{ $stat['label'] }}</p>
                            <div class="mt-4 h-2 rounded-full bg-white/10"><div class="h-2 rounded-full bg-teal-300" style="width: {{ $stat['bar'] }}%"></div></div>
                            <p class="mt-3 text-xs leading-5 text-slate-500">{{ $stat['source'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
            <p class="mt-8 rounded-lg border border-white/10 bg-white/[0.04] p-4 text-xs leading-5 text-slate-400">Sources: StatCounter Global Stats, SparkToro/Datos zero-click research and public AI/search industry reports. Figures are industry benchmarks based on latest available public data and should be reviewed periodically.</p>
        </div>
    </section>

    <section id="benefits" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Built for agencies and growth teams</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">A practical SEO report funnel today, a visibility intelligence platform tomorrow.</h2>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                <article class="rounded-lg border border-slate-200 p-6"><h3 class="font-bold text-slate-950">Lead capture baked in</h3><p class="mt-3 text-sm leading-6 text-slate-600">Collect contact details on the report page and connect future email delivery or CRM syncs.</p></article>
                <article class="rounded-lg border border-slate-200 p-6"><h3 class="font-bold text-slate-950">White-label ready</h3><p class="mt-3 text-sm leading-6 text-slate-600">Company, branding, templates, widget keys, plans, and API keys are modeled from day one.</p></article>
                <article class="rounded-lg border border-slate-200 p-6"><h3 class="font-bold text-slate-950">Scanner services</h3><p class="mt-3 text-sm leading-6 text-slate-600">SEO checks live in service classes so queue jobs, APIs, widgets, and AI modules can share the same core.</p></article>
            </div>
        </div>
    </section>

    <section id="checks" class="bg-slate-50 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-700">What we check</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">The essentials that make a first visibility conversation useful.</h2>
                    <p class="mt-4 text-slate-600">QSA focuses on reliable signals that can be explained clearly to a prospect or client.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (['Reachability and HTTP status', 'Title and meta description', 'H1 count and canonical URL', 'Robots meta and HTTPS', 'Page size and response time', 'Internal and external links', 'Images missing alt text', 'SEO, AI, GEO and AEO recommendations'] as $check)
                        <div class="rounded-lg bg-white p-4 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-200">{{ $check }}</div>
                    @endforeach
                </div>
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
