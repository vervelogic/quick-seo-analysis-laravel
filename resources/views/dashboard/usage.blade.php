<x-layouts.dashboard title="Usage">
    @unless ($company)
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-8 text-amber-900">
            <h2 class="text-2xl font-black">Company assignment needed</h2>
            <p class="mt-2">Usage appears after your user is assigned to a company workspace.</p>
        </section>
    @else
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Plan</p>
                <p class="mt-2 text-2xl font-black">{{ $plan?->name ?? 'No plan' }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Monthly Limit</p>
                <p class="mt-2 text-2xl font-black">{{ $monthlyScanLimit ?? 'Unlimited' }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Scans Used</p>
                <p class="mt-2 text-2xl font-black">{{ $scansUsed }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Reports Generated</p>
                <p class="mt-2 text-2xl font-black">{{ $reportsGenerated }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">PDF Downloads</p>
                <p class="mt-2 text-2xl font-black">{{ $pdfDownloads }}</p>
            </article>
        </section>

        <section class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-black tracking-tight">Usage History</h2>
            <p class="mt-1 text-sm text-slate-500">Report generation and download events will appear here as usage tracking is expanded.</p>

            <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                        <tr>
                            <th class="px-4 py-4">Action</th>
                            <th class="px-4 py-4">Channel</th>
                            <th class="px-4 py-4">Credits</th>
                            <th class="px-4 py-4">User</th>
                            <th class="px-4 py-4">Scan</th>
                            <th class="px-4 py-4">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($usageHistory ?? [] as $usage)
                            <tr>
                                <td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black capitalize text-slate-700">{{ $usage->action }}</span></td>
                                <td class="px-4 py-4 text-slate-600">{{ $usage->channel ?? 'N/A' }}</td>
                                <td class="px-4 py-4 font-black">{{ $usage->credits_used }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $usage->user?->name ?? 'System' }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $usage->scan?->normalized_url ? parse_url($usage->scan->normalized_url, PHP_URL_HOST) : 'N/A' }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $usage->created_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') }} IST</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center font-semibold text-slate-500">No usage events yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $usageHistory?->links() }}
            </div>
        </section>
    @endunless
</x-layouts.dashboard>
