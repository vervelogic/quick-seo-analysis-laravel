@php
    $topicIntel = $topicIntel ?? [];
    $coverage = $coverage ?? [];
    $citation = $citation ?? [];
    $aiVisibility = $aiVisibility ?? [];
    $geo = $geo ?? [];
    $aeo = $aeo ?? [];
    $keywordTargeting = $keywordTargeting ?? ($result?->keyword_targeting_data ?? []);

    $focus = data_get($keywordTargeting, 'current_search_focus');
    $legacyPrimary = data_get($keywordTargeting, 'primary_target_keyword');
    $focusPhrase = data_get($focus, 'phrase') ?: data_get($legacyPrimary, 'keyword');
    $focusConfidence = (int) (data_get($focus, 'confidence') ?: data_get($legacyPrimary, 'confidence', 0));
    $focusIntent = data_get($focus, 'intent') ?: data_get($legacyPrimary, 'intent', data_get($keywordTargeting, 'keyword_intent', 'Informational'));
    $focusSupport = (int) (data_get($focus, 'content_support_score') ?: data_get($legacyPrimary, 'content_support_score', data_get($keywordTargeting, 'content_support_score', 0)));
    $focusEvidence = (array) (data_get($focus, 'evidence_signals') ?: []);

    $theme = (array) data_get($keywordTargeting, 'search_theme_analysis', []);
    $commercial = (array) data_get($keywordTargeting, 'commercial_opportunity_analysis', []);
    $coverageAnalysis = (array) data_get($keywordTargeting, 'content_coverage_analysis', []);

    $supportingTopics = (array) (data_get($theme, 'supporting_topics') ?: collect((array) data_get($keywordTargeting, 'supporting_keywords', []))->pluck('keyword')->filter()->take(8)->values()->all());
    $relatedEntities = (array) (data_get($theme, 'related_entities') ?: array_values(array_unique(array_merge(
        (array) data_get($keywordTargeting, 'detected_services', []),
        (array) data_get($keywordTargeting, 'detected_locations', [])
    ))));
    $topicsCovered = (array) (data_get($coverageAnalysis, 'topics_covered') ?: data_get($topicIntel, 'primary_topics', []));
    $topicsMissing = (array) (data_get($coverageAnalysis, 'potential_topics_missing') ?: data_get($coverage, 'missing_topics', []));
    $expansionOpportunities = (array) (data_get($coverageAnalysis, 'content_expansion_opportunities') ?: data_get($keywordTargeting, 'keyword_opportunities', []));

    $aiScore = (int) data_get($aiVisibility, 'score', data_get($scores, 'AI Visibility', 0));
    $geoScore = (int) data_get($geo, 'score', data_get($scores, 'GEO', 0));
    $aeoScore = (int) data_get($aeo, 'score', data_get($scores, 'AEO', 0));
    $citationScore = (int) data_get($citation, 'score', data_get($scores, 'Citation Readiness', 0));
    $coveragePercent = (int) data_get($coverage, 'coverage_percent', $focusSupport);

    $engineCards = [
        'ChatGPT Readiness' => [
            'score' => (int) round(($aiScore + $aeoScore + $coveragePercent) / 3),
            'strengths' => array_filter(['Clear topic signals' => $focusPhrase, 'Answer-oriented coverage' => $aeoScore >= 60, 'Structured content support' => data_get($focusEvidence, 'schema')]),
            'weaknesses' => array_filter(['Needs clearer FAQ coverage' => ! data_get($focusEvidence, 'faq'), 'Needs deeper body content' => ! data_get($focusEvidence, 'body_content')]),
        ],
        'Claude Readiness' => [
            'score' => (int) round(($citationScore + $coveragePercent + $aiScore) / 3),
            'strengths' => array_filter(['Clear topical context' => $focusPhrase, 'Entity signals detected' => $relatedEntities !== []]),
            'weaknesses' => array_filter(['Needs stronger expertise signals' => $citationScore < 70, 'Needs more sources or references' => $citationScore < 80]),
        ],
        'Gemini Readiness' => [
            'score' => (int) round(($aiScore + $geoScore + $focusSupport) / 3),
            'strengths' => array_filter(['Search focus is identifiable' => $focusPhrase, 'Commercial modifiers detected' => data_get($commercial, 'present_modifiers')]),
            'weaknesses' => array_filter(['Needs richer supporting topics' => count($supportingTopics) < 4, 'Needs stronger schema coverage' => ! data_get($focusEvidence, 'schema')]),
        ],
        'Perplexity Readiness' => [
            'score' => (int) round(($citationScore + $aiScore + $aeoScore) / 3),
            'strengths' => array_filter(['Direct answer signals' => $aeoScore >= 60, 'Citation signals present' => $citationScore >= 60]),
            'weaknesses' => array_filter(['Needs citeable facts and references' => $citationScore < 80, 'Needs stronger author or organization proof' => $aiScore < 70]),
        ],
    ];

    $citationMissing = array_values(array_unique(array_filter(array_merge(
        (array) data_get($citation, 'missing_signals', []),
        $citationScore < 80 ? ['Sources or references', 'Statistics or proof points', 'Expertise indicators'] : []
    ))));
@endphp

<section class="{{ $sectionCard }}">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">Current Search Focus Intelligence</p>
            <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">What this page appears optimized for</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Likely search focus based on page signals from the URL, title, meta description, headings, FAQ, schema and body content. No external keyword API is used.</p>
        </div>
        @if ($focusPhrase)
            <span class="w-fit rounded-full bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-800 ring-1 ring-emerald-100">{{ $focusConfidence }}% confidence</span>
        @endif
    </div>

    @if ($focusPhrase)
        <div class="mt-6 grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Primary Search Focus</p>
                <h3 class="mt-3 text-3xl font-black tracking-tight text-slate-950">{{ $focusPhrase }}</h3>
                <p class="mt-3 text-sm leading-6 text-slate-700">{{ data_get($focus, 'summary', 'This page appears optimized for this topic based on its current page signals.') }}</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg bg-white p-4 ring-1 ring-emerald-100">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Search Intent</p>
                        <p class="mt-2 text-lg font-black text-slate-950">{{ $focusIntent }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-4 ring-1 ring-emerald-100">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Content Support</p>
                        <p class="mt-2 text-lg font-black text-slate-950">{{ $focusSupport }}/100</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="font-black text-slate-950">Evidence Signals</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">These are the page elements QSA used to infer the current search focus.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        'URL' => data_get($focusEvidence, 'url'),
                        'Title' => data_get($focusEvidence, 'title'),
                        'Meta Description' => data_get($focusEvidence, 'meta_description'),
                        'H1' => data_get($focusEvidence, 'h1'),
                        'H2' => data_get($focusEvidence, 'h2'),
                        'FAQ' => data_get($focusEvidence, 'faq'),
                        'Schema' => data_get($focusEvidence, 'schema'),
                        'Body Content' => data_get($focusEvidence, 'body_content'),
                    ] as $signal => $present)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                            <span class="text-sm font-semibold text-slate-800">{{ $signal }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $present ? 'bg-teal-50 text-teal-800 ring-teal-100' : 'bg-amber-50 text-amber-800 ring-amber-100' }}">{{ $present ? 'Present' : 'Weak' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-600">Current search focus intelligence will appear after a fresh scan with enough title, heading or content evidence.</div>
    @endif
</section>

<section class="{{ $sectionCard }}">
    <div>
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-violet-700">Search Theme Analysis</p>
        <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">What this page is likely about</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">This explains what Google or an AI engine would likely understand from the current page content and structure.</p>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        <div class="rounded-xl border border-violet-100 bg-violet-50 p-5">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-violet-700">Main Topic</p>
            <p class="mt-3 text-2xl font-black text-slate-950">{{ data_get($theme, 'main_topic', $focusPhrase ?: 'Not enough evidence') }}</p>
            <p class="mt-3 text-sm leading-6 text-slate-700">{{ data_get($theme, 'summary', 'The page needs clearer repeated topic signals before QSA can infer one main topic confidently.') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="font-black text-slate-950">Supporting Topics</h3>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse ($supportingTopics as $topic)
                    <span class="rounded-full bg-slate-50 px-3 py-1.5 text-sm font-bold text-slate-700 ring-1 ring-slate-200">{{ $topic }}</span>
                @empty
                    <span class="text-sm text-slate-500">Supporting topics need stronger headings or body copy.</span>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="font-black text-slate-950">Related Entities</h3>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse ($relatedEntities as $entity)
                    <span class="rounded-full bg-blue-50 px-3 py-1.5 text-sm font-bold text-blue-800 ring-1 ring-blue-100">{{ $entity }}</span>
                @empty
                    <span class="text-sm text-slate-500">No strong entity signals detected yet.</span>
                @endforelse
            </div>
        </div>
    </div>
</section>

<section class="{{ $sectionCard }}">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-700">Commercial Opportunity Analysis</p>
            <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">How clearly the page signals buyer intent</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">This is content and commercial signal analysis, not keyword research.</p>
        </div>
        <span class="w-fit rounded-full px-4 py-2 text-sm font-black ring-1 {{ $softPill((int) data_get($commercial, 'opportunity_score', 0)) }}">{{ (int) data_get($commercial, 'opportunity_score', 0) }}/100</span>
    </div>
    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h3 class="font-black text-slate-950">Present Modifiers</h3>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse ((array) data_get($commercial, 'present_modifiers', []) as $modifier)
                    <span class="rounded-full bg-teal-50 px-3 py-1.5 text-sm font-bold text-teal-800 ring-1 ring-teal-100">{{ $modifier }}</span>
                @empty
                    <span class="text-sm text-slate-500">No clear commercial modifiers detected yet.</span>
                @endforelse
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h3 class="font-black text-slate-950">Missing Modifiers</h3>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse ((array) data_get($commercial, 'missing_modifiers', []) as $modifier)
                    <span class="rounded-full bg-amber-50 px-3 py-1.5 text-sm font-bold text-amber-800 ring-1 ring-amber-100">{{ $modifier }}</span>
                @empty
                    <span class="text-sm text-slate-500">Commercial language coverage looks broad.</span>
                @endforelse
            </div>
        </div>
    </div>
    <p class="mt-4 text-sm leading-6 text-slate-600">{{ data_get($commercial, 'summary') }}</p>
</section>

<section class="{{ $sectionCard }}">
    <div class="grid gap-5 lg:grid-cols-[0.8fr_1.2fr]">
        <div class="rounded-lg border border-slate-200 bg-gradient-to-b from-white to-slate-50 p-5">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">Content Coverage Analysis</p>
            <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Coverage depth</h2>
            <div class="mt-4 flex items-end gap-3">
                <p class="text-6xl font-black {{ $scoreText($coveragePercent) }}">{{ $coveragePercent }}</p>
                <p class="pb-2 text-sm font-bold text-slate-500">/100</p>
            </div>
            <p class="mt-4 text-sm leading-6 text-slate-600">Identifies areas where content depth appears weak based on the page's own topic universe.</p>
        </div>
        <div class="grid gap-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <h3 class="font-black text-slate-950">Topics Covered</h3>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ($topicsCovered as $topic)
                        <span class="rounded-full bg-teal-50 px-3 py-1.5 text-sm font-bold text-teal-800 ring-1 ring-teal-100">{{ $topic }}</span>
                    @empty
                        <span class="text-sm text-slate-500">Topic coverage needs clearer evidence.</span>
                    @endforelse
                </div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <h3 class="font-black text-slate-950">Potential Topics Missing</h3>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ($topicsMissing as $topic)
                        <span class="rounded-full bg-red-50 px-3 py-1.5 text-sm font-bold text-red-700 ring-1 ring-red-100">{{ $topic }}</span>
                    @empty
                        <span class="text-sm text-slate-500">No major missing topics detected yet.</span>
                    @endforelse
                </div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <h3 class="font-black text-slate-950">Content Expansion Opportunities</h3>
                <div class="mt-4 space-y-2">
                    @forelse ($expansionOpportunities as $item)
                        <p class="rounded-lg bg-slate-50 p-3 text-sm font-semibold text-slate-800 ring-1 ring-slate-200">{{ $item }}</p>
                    @empty
                        <p class="text-sm text-slate-500">Expansion opportunities will appear after stronger content evidence is detected.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>

<section class="{{ $sectionCard }}">
    <div>
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-indigo-700">AI Readiness</p>
        <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">How ready this page is for major AI engines</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">A practical read of answer clarity, topic depth, entity confidence and citeable proof.</p>
    </div>
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @foreach ($engineCards as $engine => $card)
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <h3 class="text-lg font-black text-slate-950">{{ $engine }}</h3>
                    <span class="rounded-full px-3 py-1 text-sm font-black ring-1 {{ $softPill((int) $card['score']) }}">{{ (int) $card['score'] }}/100</span>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-teal-700">Strengths</p>
                        <ul class="mt-2 space-y-2 text-sm leading-6 text-slate-700">
                            @forelse (array_keys((array) $card['strengths']) as $strength)
                                <li>{{ $strength }}</li>
                            @empty
                                <li>Clear strengths need more page evidence.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-red-700">Weaknesses</p>
                        <ul class="mt-2 space-y-2 text-sm leading-6 text-slate-700">
                            @forelse (array_keys((array) $card['weaknesses']) as $weakness)
                                <li>{{ $weakness }}</li>
                            @empty
                                <li>No major weakness detected from current signals.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="{{ $sectionCard }}">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">Citation Readiness</p>
            <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">Can AI systems confidently cite this page?</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Measures source clarity, references, statistics, expertise indicators, organization/entity confidence, FAQ and schema support.</p>
        </div>
        <span class="w-fit rounded-full px-4 py-2 text-sm font-black ring-1 {{ $softPill($citationScore) }}">{{ $citationScore }}/100</span>
    </div>
    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h3 class="font-black text-slate-950">Strengths</h3>
            <div class="mt-4 space-y-2 text-sm leading-6 text-slate-700">
                @forelse ((array) data_get($citation, 'strengths', []) as $strength)
                    <p class="rounded-lg bg-teal-50 p-3 font-semibold text-teal-800 ring-1 ring-teal-100">{{ $strength }}</p>
                @empty
                    <p class="text-slate-500">Citation strengths need clearer source and trust signals.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h3 class="font-black text-slate-950">Weaknesses</h3>
            <div class="mt-4 space-y-2 text-sm leading-6 text-slate-700">
                @forelse ((array) data_get($citation, 'weaknesses', []) as $weakness)
                    <p class="rounded-lg bg-amber-50 p-3 font-semibold text-amber-800 ring-1 ring-amber-100">{{ $weakness }}</p>
                @empty
                    <p class="text-slate-500">No major citation weaknesses were recorded.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h3 class="font-black text-slate-950">Missing Citation Signals</h3>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse ($citationMissing as $missing)
                    <span class="rounded-full bg-red-50 px-3 py-1.5 text-sm font-bold text-red-700 ring-1 ring-red-100">{{ $missing }}</span>
                @empty
                    <span class="text-sm text-slate-500">No major missing citation signals detected.</span>
                @endforelse
            </div>
        </div>
    </div>
</section>
