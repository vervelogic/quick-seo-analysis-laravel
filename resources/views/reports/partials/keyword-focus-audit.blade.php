@php
    $summary = $alignment['summary'] ?? [];
    $keywords = collect($alignment['keywords'] ?? []);
    $intent = $alignment['intent_summary'] ?? [];
    $gaps = $alignment['content_gaps'] ?? [];
    $overall = (int) ($alignment['overall_score'] ?? 0);
    $topOpportunities = $keywords
        ->filter(fn ($row) => in_array($row['status'] ?? '', ['Weakly Supported', 'Missing', 'Partially Supported'], true))
        ->sortBy('alignment_score')
        ->take(4)
        ->values();
    $priorityFor = fn ($score) => $score < 30 ? 'High' : ($score < 60 ? 'Medium' : 'Low');
    $impactFor = fn ($row) => in_array('Title', $row['missing_from'] ?? [], true) || in_array('H1', $row['missing_from'] ?? [], true) ? 'High' : ((int) ($row['alignment_score'] ?? 0) < 50 ? 'Medium' : 'Low');
@endphp

<section data-keyword-focus-audit class="mt-8 rounded-xl border border-blue-100 bg-white p-6 shadow-sm sm:p-8">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-3xl">
            <p class="text-sm font-black uppercase tracking-[0.18em] text-blue-700">Keyword Focus Audit</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Your page alignment with selected keywords</h2>
            <p class="mt-4 text-base leading-7 text-slate-600">This section compares your selected target keywords with visible page signals. It does not estimate rankings, search volume, keyword difficulty or traffic.</p>
        </div>
        <div class="rounded-xl bg-slate-950 p-5 text-white shadow-sm lg:min-w-60">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-teal-200">Alignment Score</p>
            <div class="mt-3 flex items-end gap-2">
                <span class="text-5xl font-black">{{ $overall }}</span>
                <span class="pb-2 text-sm font-bold text-slate-300">/ 100</span>
            </div>
            <div class="mt-4 h-2 rounded-full bg-white/10"><div class="h-2 rounded-full bg-teal-300" style="width: {{ min(100, max(0, $overall)) }}%"></div></div>
        </div>
    </div>

    <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg bg-blue-50 p-4 ring-1 ring-blue-100"><p class="text-xs font-black uppercase tracking-[0.14em] text-blue-700">Target Keywords</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $summary['total'] ?? $keywords->count() }}</p></div>
        <div class="rounded-lg bg-emerald-50 p-4 ring-1 ring-emerald-100"><p class="text-xs font-black uppercase tracking-[0.14em] text-emerald-700">Strong Alignment</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $summary['strongly_supported'] ?? 0 }}</p></div>
        <div class="rounded-lg bg-amber-50 p-4 ring-1 ring-amber-100"><p class="text-xs font-black uppercase tracking-[0.14em] text-amber-700">Partial Alignment</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $summary['partially_supported'] ?? 0 }}</p></div>
        <div class="rounded-lg bg-red-50 p-4 ring-1 ring-red-100"><p class="text-xs font-black uppercase tracking-[0.14em] text-red-700">Weak Alignment</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $summary['weak_or_missing'] ?? 0 }}</p></div>
    </div>

    <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-black uppercase tracking-[0.18em] text-indigo-700">Top Opportunities</p>
                <h3 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Keywords needing the clearest on-page improvements</h3>
            </div>
            <span class="w-fit rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-slate-600 ring-1 ring-slate-200">Shown before detailed table</span>
        </div>
        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            @forelse ($topOpportunities as $row)
                @php
                    $score = (int) ($row['alignment_score'] ?? 0);
                    $missing = array_slice($row['missing_from'] ?? [], 0, 4);
                    $priority = $priorityFor($score);
                    $impact = $impactFor($row);
                @endphp
                <article class="rounded-xl border border-white bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Keyword</p>
                            <h4 class="mt-2 text-xl font-black text-slate-950">{{ $row['keyword'] ?? '' }}</h4>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-black text-slate-700 ring-1 ring-slate-200">{{ $row['status'] ?? 'Missing' }}</span>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Alignment</p><p class="mt-1 font-black text-slate-950">{{ $score }}/100</p></div>
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Priority</p><p class="mt-1 font-black text-slate-950">{{ $priority }}</p></div>
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Business Impact</p><p class="mt-1 font-black text-slate-950">{{ $impact }}</p></div>
                    </div>
                    <div class="mt-4">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-red-700">Missing Signals</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse ($missing as $signal)
                                <span class="rounded-full bg-red-50 px-3 py-1.5 text-sm font-bold text-red-700 ring-1 ring-red-100">{{ $signal }}</span>
                            @empty
                                <span class="text-sm text-slate-500">No major missing signal.</span>
                            @endforelse
                        </div>
                    </div>
                    <p class="mt-4 rounded-lg bg-blue-50 p-4 text-sm font-bold leading-6 text-blue-950 ring-1 ring-blue-100">{{ $row['suggested_on_page_fix'] ?? 'Add clearer support for this keyword in relevant page areas.' }}</p>
                </article>
            @empty
                <div class="rounded-lg bg-white p-5 text-sm font-bold text-teal-900 ring-1 ring-teal-100">No weak keyword opportunities detected. Review the detailed table below for confirmation.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-8 grid gap-5 lg:grid-cols-2">
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-xl font-black tracking-tight text-slate-950">Search Intent Match</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">How clearly selected keywords are represented by high-priority page signals.</p>
            <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg bg-white p-4 ring-1 ring-slate-200"><p class="text-xs font-black uppercase tracking-[0.12em] text-emerald-700">Strong</p><p class="mt-2 text-2xl font-black">{{ $intent['strong'] ?? 0 }}</p></div>
                <div class="rounded-lg bg-white p-4 ring-1 ring-slate-200"><p class="text-xs font-black uppercase tracking-[0.12em] text-amber-700">Partial</p><p class="mt-2 text-2xl font-black">{{ $intent['partial'] ?? 0 }}</p></div>
                <div class="rounded-lg bg-white p-4 ring-1 ring-slate-200"><p class="text-xs font-black uppercase tracking-[0.12em] text-red-700">Weak</p><p class="mt-2 text-2xl font-black">{{ $intent['weak'] ?? 0 }}</p></div>
            </div>
        </article>

        <article class="rounded-xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-xl font-black tracking-tight text-slate-950">Content Coverage Gaps</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">These keywords are not clearly represented in selected content areas.</p>
            <div class="mt-5 space-y-3 text-sm">
                @foreach (['body_copy' => 'Needs more body copy', 'faqs' => 'Needs FAQ support', 'headings' => 'Needs heading support', 'internal_links' => 'Needs internal link support'] as $key => $label)
                    <div class="rounded-lg bg-white p-4 ring-1 ring-slate-200">
                        <p class="font-black text-slate-950">{{ $label }}</p>
                        <p class="mt-1 leading-6 text-slate-600">{{ implode(', ', $gaps[$key] ?? []) ?: 'No major gap detected.' }}</p>
                    </div>
                @endforeach
            </div>
        </article>
    </div>

    <details class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white" open>
        <summary class="cursor-pointer bg-slate-50 px-5 py-4 text-sm font-black uppercase tracking-[0.14em] text-slate-700">Detailed keyword alignment table</summary>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Keyword</th>
                        <th class="px-5 py-4">Alignment Score</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Found In</th>
                        <th class="px-5 py-4">Missing From</th>
                        <th class="px-5 py-4">Suggested Fix</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($keywords as $row)
                        <tr>
                            <td class="px-5 py-4 align-top font-black text-slate-950">{{ $row['keyword'] ?? '' }}</td>
                            <td class="px-5 py-4 align-top font-black text-slate-950">{{ $row['alignment_score'] ?? 0 }}/100</td>
                            <td class="px-5 py-4 align-top"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ $row['status'] ?? 'Missing' }}</span></td>
                            <td class="px-5 py-4 align-top text-slate-600">{{ implode(', ', $row['found_in'] ?? []) ?: 'None' }}</td>
                            <td class="px-5 py-4 align-top text-slate-600">{{ implode(', ', $row['missing_from'] ?? []) ?: 'None' }}</td>
                            <td class="px-5 py-4 align-top text-slate-700">{{ $row['suggested_on_page_fix'] ?? 'Add clearer support for this keyword in relevant page areas.' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-6 text-center text-slate-500">No target keywords were available for this scan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </details>
</section>
