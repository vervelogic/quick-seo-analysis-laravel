<x-layouts.dashboard title="Reports">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-black tracking-tight">Generated Reports</h2>
                <p class="mt-1 text-sm text-slate-500">Open public reports or generate a company-branded print-ready PDF view.</p>
            </div>
            <a href="{{ route('home') }}#scan" class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white hover:bg-blue-700">Run New Scan</a>
        </div>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                    <tr>
                        <th class="px-4 py-4">Report</th>
                        <th class="px-4 py-4">Score</th>
                        <th class="px-4 py-4">Generated</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">View</th>
                        <th class="px-4 py-4">White-Label PDF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($reports as $scan)
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-black">{{ parse_url($scan->normalized_url, PHP_URL_HOST) ?? $scan->url }}</p>
                                <p class="mt-1 max-w-md truncate text-xs text-slate-500">{{ $scan->normalized_url ?? $scan->url }}</p>
                            </td>
                            <td class="px-4 py-4 font-black">{{ $scan->result?->score ?? 'N/A' }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ ($scan->completed_at ?? $scan->created_at)?->timezone(config('app.timezone'))->format('d M Y, h:i A') }} IST</td>
                            <td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black capitalize text-slate-700">{{ $scan->status }}</span></td>
                            <td class="px-4 py-4"><a href="{{ route('report.show', $scan->uuid) }}" class="font-bold text-blue-700">Open</a></td>
                            <td class="px-4 py-4"><a href="{{ route('dashboard.reports.white-label-pdf', $scan->uuid) }}" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">Download</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center font-semibold text-slate-500">No reports yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $reports?->links() }}
        </div>
    </section>
</x-layouts.dashboard>
