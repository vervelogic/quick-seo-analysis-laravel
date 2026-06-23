<x-layouts.app :title="config('app.name').' - Search & AI Visibility Intelligence'">
    @php
        $marketStats = [
            ['name' => 'Google Search', 'value' => '~90%', 'note' => 'Search benchmark', 'bar' => 90],
            ['name' => 'Bing Search', 'value' => '~5%', 'note' => 'Search benchmark', 'bar' => 5],
            ['name' => 'Other Search', 'value' => '~4-5%', 'note' => 'Search benchmark', 'bar' => 5],
            ['name' => 'Zero-Click Search', 'value' => '~68%', 'note' => 'Behavior benchmark', 'bar' => 68],
            ['name' => 'AI Answer Engines', 'value' => 'Rising Fast', 'note' => 'Discovery shift', 'bar' => 72],
        ];

        $platforms = [
            ['label' => 'Google', 'initial' => 'G'],
            ['label' => 'Bing', 'initial' => 'B'],
            ['label' => 'ChatGPT', 'initial' => 'C'],
            ['label' => 'Gemini', 'initial' => 'Gm'],
            ['label' => 'Claude', 'initial' => 'Cl'],
            ['label' => 'Perplexity', 'initial' => 'P'],
            ['label' => 'and more', 'initial' => '+'],
        ];

        $capabilities = [
            ['title' => 'Search Engine Visibility', 'abbr' => 'SEO', 'icon' => 'S'],
            ['title' => 'AI Engine Visibility', 'abbr' => 'AEO', 'icon' => 'AI'],
            ['title' => 'Geo Visibility', 'abbr' => 'GEO', 'icon' => 'G'],
            ['title' => 'Answer Engine Optimization', 'abbr' => 'Answers', 'icon' => 'A'],
            ['title' => 'Intent & Content Alignment', 'abbr' => 'Intent', 'icon' => 'I'],
            ['title' => 'Trust & Authority Signals', 'abbr' => 'Trust', 'icon' => 'T'],
        ];

        $suite = [
            ['title' => 'Current Visibility Audit', 'status' => 'Live', 'copy' => 'Understand what search engines and AI systems currently see.', 'icon' => 'CV'],
            ['title' => 'Keyword Focus Audit', 'status' => 'Beta', 'copy' => 'Validate whether your page actually supports the keywords you are targeting.', 'icon' => 'KF'],
            ['title' => 'AI Readiness Audit', 'status' => 'Coming Soon', 'copy' => 'Measure readiness for ChatGPT, Gemini, Claude and Perplexity.', 'icon' => 'AI'],
            ['title' => 'Brand Visibility Audit', 'status' => 'Coming Soon', 'copy' => 'Evaluate entity recognition, trust signals and citation readiness.', 'icon' => 'BV'],
            ['title' => 'Competitive Gap Audit', 'status' => 'Coming Soon', 'copy' => 'Identify visibility gaps compared with competitors.', 'icon' => 'CG'],
            ['title' => 'Content Opportunity Audit', 'status' => 'Coming Soon', 'copy' => 'Find missing topics, questions and content opportunities.', 'icon' => 'CO'],
        ];
    @endphp

    <section id="scan" class="relative overflow-hidden bg-slate-950">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_18%_16%,rgba(20,184,166,.24),transparent_34%),radial-gradient(circle_at_80%_10%,rgba(37,99,235,.24),transparent_32%),linear-gradient(135deg,#020617,#0f172a_48%,#082f49)]"></div>
        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.055)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.055)_1px,transparent_1px)] bg-[size:48px_48px]"></div>

        <div class="relative mx-auto grid max-w-7xl gap-10 px-5 py-14 sm:px-6 sm:py-18 lg:grid-cols-[1.03fr_0.97fr] lg:px-8 lg:py-20">
            <div class="flex flex-col justify-center">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-teal-300">Search & AI visibility intelligence</p>
                <h1 class="mt-5 max-w-4xl text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">Check where you are on Search Engines and AI Visibility.</h1>
                <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-300">Understand what your page communicates, how search engines interpret it and how AI systems evaluate your content.</p>

                <div class="mt-7 flex flex-wrap gap-2.5" aria-label="Platforms QSA helps analyze for visibility context">
                    @foreach ($platforms as $platform)
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.07] px-3 py-2 text-sm font-bold text-slate-100 shadow-sm">
                            <span class="flex h-7 min-w-7 items-center justify-center rounded-full bg-white text-xs font-black text-slate-950">{{ $platform['initial'] }}</span>
                            {{ $platform['label'] }}
                        </span>
                    @endforeach
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($capabilities as $capability)
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
                <article class="rounded-2xl border border-white/10 bg-white/[0.08] p-5 text-white shadow-2xl shadow-blue-950/30 backdrop-blur sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-teal-200">Industry benchmark, not live data</p>
                            <h2 class="mt-2 text-2xl font-black tracking-tight">Market Visibility Landscape</h2>
                        </div>
                        <span class="w-fit rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.12em] text-slate-950">Benchmark</span>
                    </div>

                    <div class="mt-5 space-y-4">
                        @foreach ($marketStats as $stat)
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

                <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-white p-6 shadow-2xl shadow-blue-950/40 sm:p-7">
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

                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Run a free visibility audit</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Enter a domain, homepage, or landing page URL. The first scan runs instantly.</p>

                    <form data-scan-form method="POST" action="{{ route('scan.store') }}" class="mt-6 space-y-4">
                        @csrf
                        <label for="url" class="block text-sm font-semibold text-slate-800">Website URL</label>
                        <div class="flex flex-col gap-3 sm:flex-row lg:flex-col xl:flex-row">
                            <input id="url" name="url" value="{{ old('url') }}" placeholder="example.com" class="min-h-12 flex-1 rounded-lg border-slate-300 text-base shadow-sm focus:border-blue-600 focus:ring-blue-600" required>
                            <button data-scan-button class="qsa-scan-button inline-flex min-h-12 items-center justify-center rounded-lg bg-blue-600 px-6 font-bold text-white shadow-sm focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-not-allowed disabled:bg-blue-400" type="submit">Run Free Visibility Scan</button>
                        </div>
                        <p class="text-xs font-medium text-slate-500">You can enter example.com, https://example.com, or http://example.com.</p>
                        <p class="text-xs font-black text-teal-700">✓ No credit card required. No signup.</p>
                        @error('url')
                            <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Choose Your Audit Path</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Start with the question you need answered.</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">Use Current Visibility Audit to understand what search engines and AI systems currently see. Use Keyword Focus Audit when you already have target keywords and need to validate alignment.</p>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-2">
                <article class="relative overflow-hidden rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 via-white to-slate-50 p-6 shadow-sm sm:p-8">
                    <div class="absolute right-6 top-6 rounded-full bg-blue-600/10 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-blue-700">Live</div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-sm font-black text-white shadow-sm">CV</div>
                    <h3 class="mt-5 text-2xl font-black tracking-tight text-slate-950">Current Visibility Audit</h3>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-600">Understand what your page currently communicates to Google Search, Bing Search, AI Answer Engines, AI Overviews and search systems.</p>
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

                <article class="relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 p-6 text-white shadow-sm sm:p-8">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-teal-300 via-blue-400 to-indigo-400"></div>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-sm font-black text-slate-950 shadow-sm">KF</div>
                            <h3 class="mt-5 text-2xl font-black tracking-tight">Keyword Focus Audit</h3>
                            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300">Answer one key question: does your page truly support the keywords you are targeting?</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-teal-200 ring-1 ring-white/10">Beta</span>
                    </div>
                    <div class="mt-7 grid gap-5 lg:grid-cols-2">
                        <div class="rounded-xl border border-white/10 bg-white/[0.06] p-5">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-red-200">We do not measure</p>
                            <div class="mt-4 space-y-2 text-sm font-bold text-slate-200">
                                @foreach (['Search volume', 'Keyword difficulty', 'Rankings', 'Traffic'] as $item)
                                    <p>{{ $item }}</p>
                                @endforeach
                            </div>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/[0.06] p-5">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-200">We do measure</p>
                            <div class="mt-4 space-y-2 text-sm font-bold text-slate-100">
                                @foreach (['Keyword support', 'Intent alignment', 'Content coverage', 'Commercial signals', 'On-page signal strength'] as $item)
                                    <p>{{ $item }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('keyword-focus.create') }}" class="mt-7 inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-white px-6 font-black text-slate-950 shadow-sm transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-white/20 sm:w-auto">Start Keyword Focus Audit</a>
                </article>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">QSA Intelligence Suite</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Built for modern visibility across search engines, AI answer engines and discovery platforms.</h2>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($suite as $audit)
                    @php $isLive = $audit['status'] === 'Live'; $isBeta = $audit['status'] === 'Beta'; @endphp
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex items-start justify-between gap-4">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl {{ $isLive ? 'bg-blue-600 text-white' : ($isBeta ? 'bg-teal-100 text-teal-800' : 'bg-slate-100 text-slate-700') }} text-sm font-black">{{ $audit['icon'] }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.14em] {{ $isLive ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-100' : ($isBeta ? 'bg-teal-50 text-teal-800 ring-1 ring-teal-100' : 'bg-slate-50 text-slate-600 ring-1 ring-slate-200') }}">{{ $audit['status'] }}</span>
                        </div>
                        <h3 class="mt-5 text-xl font-black tracking-tight text-slate-950">{{ $audit['title'] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $audit['copy'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="benefits" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Why traditional SEO reports are no longer enough</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Modern visibility needs more than meta tags and headings.</h2>
                    <p class="mt-4 text-base leading-7 text-slate-600">Traditional audits are useful, but they rarely explain what a page means to search engines, AI answer engines or commercial buyers. QSA bridges that gap with visibility intelligence built for decision makers.</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                        <h3 class="text-xl font-black text-slate-950">Traditional audits focus on</h3>
                        <div class="mt-4 space-y-3 text-sm font-bold text-slate-700">
                            @foreach (['Meta tags', 'Headings', 'Technical SEO'] as $item)
                                <p>{{ $item }}</p>
                            @endforeach
                        </div>
                    </article>
                    <article class="rounded-2xl border border-blue-100 bg-blue-50 p-6">
                        <h3 class="text-xl font-black text-slate-950">Modern visibility requires</h3>
                        <div class="mt-4 space-y-3 text-sm font-bold text-slate-700">
                            @foreach (['Search engines', 'AI answer engines', 'Entity recognition', 'Citation readiness', 'Commercial intent', 'Content coverage', 'Discovery platforms'] as $item)
                                <p>{{ $item }}</p>
                            @endforeach
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section id="checks" class="bg-slate-50 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-700">What QSA checks</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">The signals behind the executive intelligence brief.</h2>
                    <p class="mt-4 text-slate-600">QSA keeps technical SEO, but places it inside a larger visibility picture: search focus, AI visibility, commercial opportunity, content coverage and priority actions.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (['Current search focus', 'SEO and technical health', 'AI Visibility, GEO and AEO', 'Commercial intent signals', 'Content coverage gaps', 'Keyword focus alignment', 'Trust and citation readiness', 'Priority business actions'] as $check)
                        <div class="rounded-xl bg-white p-4 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-200">{{ $check }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-slate-950 p-6 text-white shadow-sm sm:p-8 lg:flex lg:items-center lg:justify-between lg:gap-8">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-300">Start with one page</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">See what search and AI systems understand before you decide what to fix.</h2>
                    <p class="mt-4 max-w-3xl text-sm leading-6 text-slate-300">Run a Current Visibility Audit now, then use Keyword Focus Audit when you want to validate a specific SEO campaign page.</p>
                </div>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row lg:mt-0 lg:shrink-0">
                    <a href="#scan" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-white px-6 font-black text-slate-950 shadow-sm">Run Free Visibility Scan</a>
                    <a href="{{ route('keyword-focus.create') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-teal-400 px-6 font-black text-slate-950 shadow-sm">Start Keyword Focus Audit</a>
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
