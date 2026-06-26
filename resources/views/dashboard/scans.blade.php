<x-layouts.dashboard title="Scans">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-black tracking-tight">Company Scans</h2>
                <p class="mt-1 text-sm text-slate-500">Only scans attached to your company workspace are shown here.</p>
            </div>
            <a href="{{ route('home') }}#scan" class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white hover:bg-blue-700">Run New Scan</a>
        </div>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                    <tr>
                        <th class="px-4 py-4">Scanned URL</th>
                        <th class="px-4 py-4">Domain</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Score</th>
                        <th class="px-4 py-4">Scan Type</th>
                        <th class="px-4 py-4">Created</th>
                        <th class="px-4 py-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($scans as $scan)
                        <tr>
                            <td class="max-w-sm px-4 py-4 font-semibold text-slate-900">
                                <span class="block truncate">{{ $scan->normalized_url ?? $scan->url }}</span>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ parse_url($scan->normalized_url, PHP_URL_HOST) ?? 'N/A' }}</td>
                            <td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black capitalize text-slate-700">{{ $scan->status }}</span></td>
                            <td class="px-4 py-4 font-black">{{ $scan->result?->score ?? 'N/A' }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ str_replace('_', ' ', $scan->scan_mode ?? 'current_visibility') }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $scan->created_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') }} IST</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('report.show', $scan->uuid) }}" class="font-bold text-blue-700">Open</a>
                                    @if ($scan->result)
                                        <a href="{{ route('dashboard.reports.white-label-pdf', $scan->uuid) }}" class="font-bold text-slate-900">White-label PDF</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center font-semibold text-slate-500">No company scans yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $scans?->links() }}
        </div>
    </section>
</x-layouts.dashboard>
