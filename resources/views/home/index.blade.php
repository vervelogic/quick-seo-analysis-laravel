<x-layouts.app :title="config('app.name').' - Free SEO Report'">
    <section id="scan" class="relative overflow-hidden bg-slate-950">
        <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(15,23,42,1),rgba(30,41,59,0.96)_48%,rgba(12,74,110,0.72))]"></div>
        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.06)_1px,transparent_1px)] bg-[size:48px_48px]"></div>
        <div class="relative mx-auto grid max-w-7xl gap-10 px-5 py-16 sm:px-6 sm:py-20 lg:grid-cols-[1.08fr_0.92fr] lg:px-8 lg:py-24">
            <div class="flex flex-col justify-center">
                <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-teal-300">SEO clarity in minutes</p>
                <h1 class="max-w-3xl text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">Scan a website and turn SEO gaps into a focused action list.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">Quick SEO Analysis checks the essentials that affect crawlability, trust, page quality, and performance, then scores the page in a report built for follow-up.</p>
                <div class="mt-8 grid grid-cols-1 gap-3 text-sm text-slate-300 sm:grid-cols-3">
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">No account needed</div>
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">Lead-ready reports</div>
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">SaaS-ready base</div>
                </div>
            </div>

            <div class="rounded-xl border border-white/10 bg-white p-6 shadow-2xl shadow-blue-950/40 sm:p-8">
                <h2 class="text-2xl font-bold tracking-tight text-slate-950">Get a free SEO report</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Enter a homepage or landing page URL. The first scan runs instantly.</p>

                <form method="POST" action="{{ route('scan.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <label for="url" class="block text-sm font-semibold text-slate-800">Website URL</label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input id="url" name="url" value="{{ old('url') }}" placeholder="https://example.com" class="min-h-12 flex-1 rounded-lg border-slate-300 text-base shadow-sm focus:border-blue-600 focus:ring-blue-600" required>
                        <button class="min-h-12 rounded-lg bg-blue-600 px-6 font-bold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200" type="submit">Scan</button>
                    </div>
                    @error('url')
                        <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </form>

                <div class="mt-8 grid grid-cols-3 gap-3 border-t border-slate-200 pt-6 text-center">
                    <div>
                        <p class="text-2xl font-black text-slate-950">12</p>
                        <p class="text-xs font-medium text-slate-500">Core checks</p>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-950">100</p>
                        <p class="text-xs font-medium text-slate-500">Point score</p>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-950">0</p>
                        <p class="text-xs font-medium text-slate-500">Setup needed</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="benefits" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Built for agencies and growth teams</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">A practical SEO report funnel today, a SaaS platform tomorrow.</h2>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                <article class="rounded-lg border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-950">Lead capture baked in</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Collect contact details on the report page and connect future email delivery or CRM syncs.</p>
                </article>
                <article class="rounded-lg border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-950">White-label ready</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Company, branding, templates, widget keys, plans, and API keys are modeled from day one.</p>
                </article>
                <article class="rounded-lg border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-950">Scanner services</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">SEO checks live in service classes so queue jobs, APIs, widgets, and AI modules can share the same core.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="checks" class="bg-slate-50 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-700">Included in v1</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">The essentials that make a first SEO conversation useful.</h2>
                    <p class="mt-4 text-slate-600">QSA v1 focuses on reliable signals that can be explained clearly to a prospect or client.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (['Reachability and HTTP status', 'Title and meta description', 'H1 count and canonical URL', 'Robots meta and HTTPS', 'Page size and response time', 'Internal and external links', 'Images missing alt text', 'SEO score and recommendations'] as $check)
                        <div class="rounded-lg bg-white p-4 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-200">{{ $check }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
