<x-layouts.dashboard title="Dashboard">
    @unless ($company)
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-8">
            <p class="text-sm font-black uppercase tracking-[0.18em] text-amber-700">Workspace setup needed</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-amber-950">Your account is not assigned to a company yet.</h2>
            <p class="mt-3 max-w-2xl text-amber-800">Ask a QSA admin to assign your user to a company workspace before scans, reports and usage can appear here.</p>
        </section>
    @else
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Company</p>
                <p class="mt-2 text-2xl font-black">{{ $company->name }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Current Plan</p>
                <p class="mt-2 text-2xl font-black">{{ $plan?->name ?? 'No plan' }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Monthly Scans</p>
                <p class="mt-2 text-2xl font-black">{{ $scansUsed }} / {{ $monthlyScanLimit ?? 'Unlimited' }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Remaining Credits</p>
                <p class="mt-2 text-2xl font-black">{{ $remainingCredits ?? 'Unlimited' }}</p>
            </article>
        </section>

        <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('home') }}#scan" class="rounded-2xl bg-blue-600 p-5 text-white shadow-sm hover:bg-blue-700">
                <p class="text-lg font-black">Run New Scan</p>
                <p class="mt-2 text-sm text-blue-100">Start a current visibility audit.</p>
            </a>
            <a href="{{ route('dashboard.reports') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-200">
                <p class="text-lg font-black">View Reports</p>
                <p class="mt-2 text-sm text-slate-500">Open scan-based report links.</p>
            </a>
            <a href="{{ route('dashboard.branding') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-200">
                <p class="text-lg font-black">Manage Branding</p>
                <p class="mt-2 text-sm text-slate-500">Logo, color and report footer.</p>
            </a>
            <a href="{{ route('dashboard.usage') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-200">
                <p class="text-lg font-black">View Usage</p>
                <p class="mt-2 text-sm text-slate-500">Credits, scans and downloads.</p>
            </a>
        </section>

        <section class="mt-8 grid gap-6 xl:grid-cols-2">
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-black">Recent Scans</h2>
                    <a href="{{ route('dashboard.scans') }}" class="text-sm font-bold text-blue-700">View all</a>
                </div>
                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                    @forelse ($recentScans as $scan)
                        <div class="grid gap-2 border-b border-slate-100 p-4 last:border-b-0 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <p class="font-black">{{ parse_url($scan->normalized_url, PHP_URL_HOST) ?? $scan->url }}</p>
                                <p class="mt-1 truncate text-sm text-slate-500">{{ $scan->normalized_url ?? $scan->url }}</p>
                            </div>
                            <a href="{{ route('report.show', $scan->uuid) }}" class="text-sm font-bold text-blue-700">Open report</a>
                        </div>
                    @empty
                        <p class="p-5 text-sm font-semibold text-slate-500">No scans yet.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-black">Recent Reports</h2>
                    <a href="{{ route('dashboard.reports') }}" class="text-sm font-bold text-blue-700">View all</a>
                </div>
                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                    @forelse ($recentReports as $scan)
                        <div class="grid gap-2 border-b border-slate-100 p-4 last:border-b-0 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <p class="font-black">{{ parse_url($scan->normalized_url, PHP_URL_HOST) ?? $scan->url }}</p>
                                <p class="mt-1 text-sm text-slate-500">Score: {{ $scan->result?->score ?? 'N/A' }}</p>
                            </div>
                            <a href="{{ route('report.show', $scan->uuid) }}" class="text-sm font-bold text-blue-700">View</a>
                        </div>
                    @empty
                        <p class="p-5 text-sm font-semibold text-slate-500">No generated reports yet.</p>
                    @endforelse
                </div>
            </article>
        </section>
    @endunless
</x-layouts.dashboard>
