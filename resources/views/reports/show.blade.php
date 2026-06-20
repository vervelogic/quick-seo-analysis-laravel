<x-layouts.app :title="'SEO Report - '.$scan->normalized_url">
    <section class="bg-slate-950 py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-300">SEO report</p>
            <div class="mt-4 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="break-words text-3xl font-black tracking-tight text-white sm:text-5xl">{{ $scan->normalized_url }}</h1>
                    <p class="mt-3 text-slate-300">Status: <span class="font-semibold capitalize">{{ $scan->status }}</span></p>
                </div>
                <div class="rounded-xl bg-white p-6 text-center shadow-xl">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-slate-500">SEO score</p>
                    <p class="mt-2 text-6xl font-black {{ ($result?->score ?? 0) >= 80 ? 'text-teal-600' : (($result?->score ?? 0) >= 55 ? 'text-amber-600' : 'text-red-600') }}">{{ $result?->score ?? 0 }}</p>
                    <p class="text-sm text-slate-500">out of 100</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-12 sm:py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 sm:px-6 lg:grid-cols-[1fr_380px] lg:px-8">
            <div class="space-y-8">
                @if (session('status'))
                    <div class="rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm font-medium text-teal-800">{{ session('status') }}</div>
                @endif

                @if ($scan->error_message)
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">{{ $scan->error_message }}</div>
                @endif

                <div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Scan summary</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            'HTTP status' => $result?->http_status ?? 'N/A',
                            'Response time' => $result ? $result->response_time_ms.' ms' : 'N/A',
                            'Page size' => $result ? number_format($result->page_size_bytes / 1024, 1).' KB' : 'N/A',
                            'Title length' => $result?->title_length ?? 0,
                            'Meta length' => $result?->meta_description_length ?? 0,
                            'H1 count' => $result?->h1_count ?? 0,
                            'Internal links' => $result?->internal_links_count ?? 0,
                            'External links' => $result?->external_links_count ?? 0,
                            'Images missing alt' => $result?->images_missing_alt_count ?? 0,
                        ] as $label => $value)
                            <div class="rounded-lg border border-slate-200 p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $label }}</p>
                                <p class="mt-2 text-2xl font-black text-slate-950">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Checks</h2>
                    <div class="mt-5 divide-y divide-slate-200 rounded-lg border border-slate-200">
                        @foreach (($result?->checks ?? []) as $check)
                            <div class="flex items-center justify-between gap-4 p-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $check['label'] }}</p>
                                    <p class="text-sm text-slate-500">Weight: {{ $check['weight'] }} points</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-sm font-bold {{ $check['passed'] ? 'bg-teal-100 text-teal-800' : 'bg-red-100 text-red-700' }}">{{ $check['passed'] ? 'Passed' : 'Needs work' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-950">Recommendations</h2>
                    <div class="mt-5 space-y-3">
                        @forelse (($result?->recommendations ?? []) as $recommendation)
                            <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm font-medium leading-6 text-blue-950">{{ $recommendation }}</div>
                        @empty
                            <div class="rounded-lg border border-teal-100 bg-teal-50 p-4 text-sm font-medium text-teal-900">Strong baseline. Keep monitoring content quality, backlinks, and technical SEO depth.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <aside class="h-fit rounded-xl border border-slate-200 bg-slate-50 p-6">
                <h2 class="text-xl font-black tracking-tight text-slate-950">Send me the full report</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Save contact details for follow-up and future email report delivery.</p>
                <form method="POST" action="{{ route('lead.capture') }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="scan_uuid" value="{{ $scan->uuid }}">
                    <div>
                        <label class="text-sm font-semibold text-slate-800" for="name">Name</label>
                        <input id="name" name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-800" for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600" required>
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-800" for="phone">Phone</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-800" for="company_name">Company</label>
                        <input id="company_name" name="company_name" value="{{ old('company_name') }}" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                    </div>
                    <button class="w-full rounded-lg bg-slate-950 px-5 py-3 font-bold text-white hover:bg-slate-800" type="submit">Save details</button>
                </form>
            </aside>
        </div>
    </section>
</x-layouts.app>
