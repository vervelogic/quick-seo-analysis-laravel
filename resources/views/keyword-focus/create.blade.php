<x-layouts.app :title="config('app.name').' - Keyword Focus Audit'">
    @php
        $oldKeywords = old('target_keywords');
        $keywordValue = is_array($oldKeywords) ? implode("\n", $oldKeywords) : (string) $oldKeywords;
    @endphp

    <section class="relative overflow-hidden bg-slate-950">
        <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(15,23,42,1),rgba(30,41,59,0.96)_48%,rgba(12,74,110,0.72))]"></div>
        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.06)_1px,transparent_1px)] bg-[size:48px_48px]"></div>
        <div class="relative mx-auto grid max-w-7xl gap-10 px-5 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-24">
            <div class="flex flex-col justify-center">
                <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-teal-300">Keyword Focus Audit · Beta</p>
                <h1 class="max-w-3xl text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">Check whether your page supports the keywords you are targeting.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">QSA compares your selected target keywords against page-level signals like URL, title, headings, meta description, body content, FAQs, schema and internal links.</p>
                <div class="mt-8 grid grid-cols-1 gap-3 text-sm text-slate-300 sm:grid-cols-2">
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">Keyword alignment</div>
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">Search intent match</div>
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">Content coverage</div>
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">On-page fixes</div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-white/10 bg-white p-6 shadow-2xl shadow-blue-950/40 sm:p-8">
                <div data-keyword-loading class="pointer-events-none absolute inset-0 z-10 hidden bg-white/95 p-6 backdrop-blur-sm sm:p-8">
                    <div class="flex h-full min-h-96 flex-col justify-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-50">
                            <div class="h-9 w-9 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600"></div>
                        </div>
                        <div class="mx-auto mt-6 max-w-sm text-center">
                            <h2 class="text-2xl font-black tracking-tight text-slate-950">Checking keyword alignment...</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600">Reviewing URL, title, headings, content, FAQs, schema and internal links. This usually takes a few seconds.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-950">Keyword Focus Audit</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">For teams already doing SEO and targeting specific phrases.</p>
                    </div>
                    <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-teal-700 ring-1 ring-teal-100">Beta</span>
                </div>

                <form data-keyword-focus-form method="POST" action="{{ route('keyword-focus.store') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="url" class="block text-sm font-semibold text-slate-800">Website/Page URL</label>
                        <input id="url" name="url" value="{{ old('url') }}" placeholder="example.com/services" class="mt-2 min-h-12 w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-blue-600 focus:ring-blue-600" required>
                        @error('url')
                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="target_keywords" class="block text-sm font-semibold text-slate-800">Target Keywords</label>
                        <p class="mt-1 text-xs font-medium text-slate-500">Enter the keywords you are actively targeting, one per line.</p>
                        <textarea id="target_keywords" name="target_keywords" rows="7" placeholder="seo services&#10;local seo company&#10;ai seo audit&#10;website visibility report" class="mt-2 w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-blue-600 focus:ring-blue-600" required>{{ $keywordValue }}</textarea>
                        <p class="mt-2 text-xs font-medium text-slate-500">Up to 20 keywords. Duplicates are removed automatically.</p>
                        @error('target_keywords')
                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button data-keyword-focus-button class="qsa-keyword-button inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-blue-600 px-6 font-bold text-white shadow-sm focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-not-allowed disabled:bg-blue-400" type="submit">Check Keyword Alignment</button>
                </form>
            </div>
        </div>
    </section>

    <section class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-5xl px-5 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 sm:p-8">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">What this audit does</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Alignment, not ranking tracking.</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">This audit checks whether your page clearly supports your selected keywords in important on-page areas. It does not claim search volume, keyword difficulty, ranking position or traffic.</p>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach (['URL, title and meta signals', 'H1, H2 and H3 support', 'Body copy and FAQ coverage', 'Schema and internal link signals'] as $item)
                        <div class="rounded-lg bg-white p-4 text-sm font-bold text-slate-800 shadow-sm ring-1 ring-slate-200">{{ $item }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <style>
        .qsa-keyword-button { position: relative; overflow: hidden; transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease; }
        .qsa-keyword-button::after { content: ''; position: absolute; inset: -40% auto -40% -60%; width: 45%; transform: rotate(20deg); background: linear-gradient(90deg, transparent, rgba(255,255,255,.45), transparent); transition: left .55s ease; }
        .qsa-keyword-button:hover { transform: translateY(-2px); box-shadow: 0 18px 35px rgba(37, 99, 235, .28); }
        .qsa-keyword-button:hover::after { left: 120%; }
    </style>

    <script>
        document.querySelectorAll('[data-keyword-focus-form]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('[data-keyword-focus-button]');
                const loading = form.closest('.relative')?.querySelector('[data-keyword-loading]');

                if (button) {
                    button.disabled = true;
                    button.textContent = 'Checking...';
                }

                if (loading) {
                    loading.classList.remove('hidden');
                    loading.classList.add('flex');
                }
            });
        });
    </script>
</x-layouts.app>
