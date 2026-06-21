@php
    $topicIntel = $topicIntel ?? [];
    $rankingPotential = $rankingPotential ?? [];
    $promptIntel = $promptIntel ?? [];
    $coverage = $coverage ?? [];
    $citation = $citation ?? [];
    $keywordTargeting = $keywordTargeting ?? ($result?->keyword_targeting_data ?? []);
    $primaryKeyword = data_get($keywordTargeting, 'primary_target_keyword');
@endphp

<section class="{{ $sectionCard }}">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">Keyword Targeting Intelligence</p>
            <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">What keyword this page appears to target</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Inferred from title, meta description, URL, headings, content frequency, internal anchors, schema and location/service signals. No external keyword API used.</p>
        </div>
        @if ($primaryKeyword)
            <span class="w-fit rounded-full bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-800 ring-1 ring-emerald-100">{{ data_get($primaryKeyword, 'confidence', 0) }}% confidence</span>
        @endif
    </div>

    @if ($primaryKeyword)
        <div class="mt-6 grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Primary Target Keyword</p>
                <h3 class="mt-3 text-3xl font-black tracking-tight text-slate-950">{{ data_get($primaryKeyword, 'keyword') }}</h3>
                <p class="mt-3 text-sm leading-6 text-slate-700">{{ data_get($primaryKeyword, 'evidence') }}</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg bg-white p-4 ring-1 ring-emerald-100">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Search Intent</p>
                        <p class="mt-2 text-lg font-black text-slate-950">{{ data_get($primaryKeyword, 'intent', data_get($keywordTargeting, 'keyword_intent', 'N/A')) }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-4 ring-1 ring-emerald-100">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Content Support</p>
                        <p class="mt-2 text-lg font-black text-slate-950">{{ data_get($primaryKeyword, 'content_support_score', data_get($keywordTargeting, 'content_support_score', 0)) }}/100</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="font-black text-slate-950">Supporting Keywords</h3>
                <div class="mt-4 space-y-3">
                    @forelse (array_slice((array) data_get($keywordTargeting, 'supporting_keywords', []), 0, 8) as $item)
                        <div class="flex items-start justify-between gap-4 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                            <div>
                                <p class="font-bold text-slate-950">{{ data_get($item, 'keyword') }}</p>
                                <p class="mt-1 text-xs leading-5 text-slate-600">{{ data_get($item, 'evidence') }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-xs font-black text-slate-700 ring-1 ring-slate-200">{{ data_get($item, 'confidence', 0) }}%</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Supporting keywords will appear after the page has enough keyword evidence.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <h3 class="font-black text-slate-950">Detected Service & Location Signals</h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Services</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse ((array) data_get($keywordTargeting, 'detected_services', []) as $service)
                                <span class="rounded-full bg-white px-3 py-1.5 text-sm font-bold text-slate-700 ring-1 ring-slate-200">{{ $service }}</span>
                            @empty
                                <span class="text-sm text-slate-500">No service modifiers detected yet.</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Locations</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse ((array) data_get($keywordTargeting, 'detected_locations', []) as $location)
                                <span class="rounded-full bg-white px-3 py-1.5 text-sm font-bold text-slate-700 ring-1 ring-slate-200">{{ $location }}</span>
                            @empty
                                <span class="text-sm text-slate-500">No location modifiers detected yet.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h3 class="font-black text-slate-950">Keyword Opportunities</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Terms this page could support with stronger headings, FAQ answers, examples, pricing/process copy, local proof or internal links.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ((array) data_get($keywordTargeting, 'keyword_opportunities', []) as $keyword)
                        <span class="rounded-full bg-blue-50 px-3 py-1.5 text-sm font-bold text-blue-800 ring-1 ring-blue-100">{{ $keyword }}</span>
                    @empty
                        <span class="text-sm text-slate-500">No obvious keyword opportunities detected yet.</span>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-600">Keyword targeting intelligence will appear after a fresh scan with enough title, heading or content evidence.</div>
    @endif
</section>

<section class="{{ $sectionCard }}">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-violet-700">AI Topic Intelligence</p>
            <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">What this website appears authoritative about</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Derived from title, meta description, headings, content, schema, internal links, footer text, service cues, locations and brand signals.</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @foreach ([
            'Primary Topics' => data_get($topicIntel, 'primary_topics', []),
            'Secondary Topics' => data_get($topicIntel, 'secondary_topics', []),
            'Services' => data_get($topicIntel, 'services', []),
            'Industries' => data_get($topicIntel, 'industries', []),
            'Locations' => data_get($topicIntel, 'locations', []),
            'Entities' => data_get($topicIntel, 'entities', []),
        ] as $groupLabel => $items)
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <h3 class="font-black text-slate-950">{{ $groupLabel }}</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ((array) $items as $item)
                        <span class="rounded-full bg-white px-3 py-1.5 text-sm font-bold text-slate-700 ring-1 ring-slate-200">{{ $item }}</span>
                    @empty
                        <span class="text-sm text-slate-500">Not enough evidence detected yet.</span>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 rounded-lg border border-slate-200 bg-white p-4">
        <h3 class="font-black text-slate-950">Brand Signals</h3>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ((array) data_get($topicIntel, 'brand_signals', []) as $signal => $value)
                @if ($signal !== 'brand')
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                        <span class="text-sm font-semibold text-slate-800">{{ $label($signal) }}</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $value ? 'bg-teal-50 text-teal-800 ring-teal-100' : 'bg-red-50 text-red-700 ring-red-100' }}">{{ $value ? 'Yes' : 'No' }}</span>
                    </div>
                @endif
            @empty
                <p class="text-sm text-slate-500">Brand signals were not recorded for this scan.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="{{ $sectionCard }}">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-700">Ranking Potential</p>
            <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">Current content can potentially rank or be cited for</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ data_get($rankingPotential, 'summary', 'Inferred from current website content. No external keyword API used.') }}</p>
        </div>
    </div>
    <div class="mt-5 grid gap-3 lg:grid-cols-2">
        @forelse ((array) data_get($rankingPotential, 'items', []) as $item)
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-lg font-black text-slate-950">{{ $item['keyword'] ?? '' }}</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $item['evidence'] ?? 'Detected from current content relevance.' }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-blue-50 px-3 py-1 text-sm font-black text-blue-800 ring-1 ring-blue-100">{{ $item['confidence'] ?? 0 }}%</span>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">Ranking potential will appear after a new V4 scan.</div>
        @endforelse
    </div>
</section>

<section class="{{ $sectionCard }}">
    <div>
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-indigo-700">AI Prompt Intelligence</p>
        <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">Likely AI-user questions</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Questions people may ask ChatGPT, Gemini, Claude, Perplexity or Google AI answers based on this website's current content themes.</p>
    </div>
    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        @foreach ([
            'Covered' => ['items' => data_get($promptIntel, 'covered', []), 'class' => 'bg-teal-50 text-teal-800 ring-teal-100'],
            'Partially Covered' => ['items' => data_get($promptIntel, 'partially_covered', []), 'class' => 'bg-amber-50 text-amber-800 ring-amber-100'],
            'Missing' => ['items' => data_get($promptIntel, 'missing', []), 'class' => 'bg-red-50 text-red-700 ring-red-100'],
        ] as $status => $group)
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-black text-slate-950">{{ $status }}</h3>
                    <span class="rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $group['class'] }}">{{ count((array) $group['items']) }}</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse (array_slice((array) $group['items'], 0, 6) as $item)
                        <div class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                            <p class="text-sm font-bold text-slate-950">{{ $item['prompt'] ?? '' }}</p>
                            <p class="mt-1 text-xs leading-5 text-slate-600">{{ $item['reason'] ?? '' }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No prompts in this group yet.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="{{ $sectionCard }}">
    <div class="grid gap-5 lg:grid-cols-[0.8fr_1.2fr]">
        <div class="rounded-lg border border-slate-200 bg-gradient-to-b from-white to-slate-50 p-5">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">Content Coverage</p>
            <div class="mt-4 flex items-end gap-3">
                <p class="text-6xl font-black {{ $scoreText((int) data_get($coverage, 'coverage_percent', 0)) }}">{{ (int) data_get($coverage, 'coverage_percent', 0) }}</p>
                <p class="pb-2 text-sm font-bold text-slate-500">%</p>
            </div>
            <div class="mt-5 grid gap-3">
                @foreach ([
                    'Topics Identified' => data_get($coverage, 'topics_identified', 0),
                    'Topics Covered' => data_get($coverage, 'topics_covered', 0),
                    'Topics Missing' => data_get($coverage, 'topics_missing', 0),
                ] as $coverageLabel => $value)
                    <div class="flex items-center justify-between rounded-lg bg-white p-3 ring-1 ring-slate-200"><span class="text-sm font-semibold text-slate-700">{{ $coverageLabel }}</span><span class="font-black text-slate-950">{{ $value }}</span></div>
                @endforeach
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h3 class="font-black text-slate-950">Missing coverage opportunities</h3>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse ((array) data_get($coverage, 'missing_topics', []) as $topic)
                    <span class="rounded-full bg-red-50 px-3 py-1.5 text-sm font-bold text-red-700 ring-1 ring-red-100">{{ $topic }}</span>
                @empty
                    <span class="text-sm text-slate-500">No major missing topics detected yet.</span>
                @endforelse
            </div>
        </div>
    </div>
</section>

<section class="{{ $sectionCard }}">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">AI Citation Readiness</p>
            <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">Can AI systems confidently cite this website?</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Measures authorship, about/contact signals, organization/entity clarity, FAQ, schema, brand consistency and trust proof.</p>
        </div>
        <span class="w-fit rounded-full px-4 py-2 text-sm font-black ring-1 {{ $softPill((int) data_get($citation, 'score', 0)) }}">{{ (int) data_get($citation, 'score', 0) }}/100</span>
    </div>
    <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @forelse ((array) data_get($citation, 'factors', []) as $factor => $passed)
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-4">
                <span class="text-sm font-semibold text-slate-800">{{ $label($factor) }}</span>
                <span class="rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $passed ? 'bg-teal-50 text-teal-800 ring-teal-100' : 'bg-red-50 text-red-700 ring-red-100' }}">{{ $passed ? 'Ready' : 'Missing' }}</span>
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">Citation readiness will appear after a new V4 scan.</div>
        @endforelse
    </div>
</section>
