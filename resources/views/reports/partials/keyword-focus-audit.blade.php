@php
    $summary = $alignment['summary'] ?? [];
    $keywords = $alignment['keywords'] ?? [];
    $intent = $alignment['intent_summary'] ?? [];
    $gaps = $alignment['content_gaps'] ?? [];
    $overall = (int) ($alignment['overall_score'] ?? 0);
@endphp

<section data-keyword-focus-audit class="mt-8 rounded-xl border border-blue-100 bg-white p-6 shadow-sm sm:p-8">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-3xl">
            <p class="text-sm font-black uppercase tracking-[0.18em] text-blue-700">Keyword Focus Audit</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Your page alignment with selected keywords</h2>
            <p class="mt-4 text-base leading-7 text-slate-600">This section compares your selected target keywords with visible page signals. It does not estimate rankings, search volume, keyword difficulty or traffic.</p>
        </div>
        <div class="rounded-xl bg-slate-950 p-5 text-white shadow-sm lg:min-w-60">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-teal-200">Overall Alignment</p>
            <div class="mt-3 flex items-end gap-2">
                <span class="text-5xl font-black">{{ $overall }}</span>
                <span class="pb-2 text-sm font-bold text-slate-300">/ 100</span>
            </div>
            <div class="mt-4 h-2 rounded-full bg-white/10"><div class="h-2 rounded-full bg-teal-300" style="width: {{ min(100, max(0, $overall)) }}%"></div></div>
        </div>
    </div>

    <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg bg-blue-50 p-4 ring-1 ring-blue-100"><p class="text-xs font-black uppercase tracking-[0.14em] text-blue-700">Total Checked</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $summary['total'] ?? count($keywords) }}</p></div>
        <div class="rounded-lg bg-emerald-50 p-4 ring-1 ring-emerald-100"><p class="text-xs font-black uppercase tracking-[0.14em] text-emerald-700">Strong</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $summary['strongly_supported'] ?? 0 }}</p></div>
        <div class="rounded-lg bg-amber-50 p-4 ring-1 ring-amber-100"><p class="text-xs font-black uppercase tracking-[0.14em] text-amber-700">Partial</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $summary['partially_supported'] ?? 0 }}</p></div>
        <div class="rounded-lg bg-red-50 p-4 ring-1 ring-red-100"><p class="text-xs font-black uppercase tracking-[0.14em] text-red-700">Weak / Missing</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $summary['weak_or_missing'] ?? 0 }}</p></div>
    </div>

    <div class="mt-8 overflow-hidden rounded-xl border border-slate-200">
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
    </div>

    <div class="mt-8 grid gap-5 lg:grid-cols-2">
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-xl font-black tracking-tight text-slate-950">Search Intent Match Summary</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">How clearly selected keywords are represented by high-priority page signals.</p>
            <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg bg-white p-4 ring-1 ring-slate-200"><p class="text-xs font-black uppercase tracking-[0.12em] text-emerald-700">Strong</p><p class="mt-2 text-2xl font-black">{{ $intent['strong'] ?? 0 }}</p></div>
                <div class="rounded-lg bg-white p-4 ring-1 ring-slate-200"><p class="text-xs font-black uppercase tracking-[0.12em] text-amber-700">Partial</p><p class="mt-2 text-2xl font-black">{{ $intent['partial'] ?? 0 }}</p></div>
                <div class="rounded-lg bg-white p-4 ring-1 ring-slate-200"><p class="text-xs font-black uppercase tracking-[0.12em] text-red-700">Weak</p><p class="mt-2 text-2xl font-black">{{ $intent['weak'] ?? 0 }}</p></div>
            </div>
        </article>

        <article class="rounded-xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-xl font-black tracking-tight text-slate-950">Content Gaps</h3>
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
</section>
