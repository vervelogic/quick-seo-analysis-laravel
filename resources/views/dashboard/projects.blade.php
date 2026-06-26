<x-layouts.dashboard title="Projects">
    <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-black tracking-tight">Create Project</h2>
            <p class="mt-1 text-sm text-slate-500">Projects group scans and reports by website or client.</p>

            @if ($canManageProjects && $company)
                <form method="POST" action="{{ route('dashboard.projects.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Project name</span>
                        <input name="name" required class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </label>
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Website URL</span>
                        <input name="website_url" type="url" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </label>
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Status</span>
                        <select name="status" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                    <button class="w-full rounded-xl bg-slate-950 px-5 py-3 font-black text-white hover:bg-slate-800">Create Project</button>
                </form>
            @else
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm font-semibold text-slate-600">
                    Project creation is available to owners, admins and managers.
                </div>
            @endif
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-black tracking-tight">Company Projects</h2>
            <div class="mt-6 space-y-4">
                @forelse ($projects ?? [] as $project)
                    <article class="rounded-2xl border border-slate-200 p-5">
                        <form method="POST" action="{{ route('dashboard.projects.update', $project) }}" class="grid gap-4 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                            @csrf
                            @method('PATCH')
                            <label>
                                <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Project</span>
                                <input name="name" value="{{ $project->name }}" @readonly(! $canManageProjects) class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-bold">
                            </label>
                            <label>
                                <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Website</span>
                                <input name="website_url" type="url" value="{{ $project->website_url }}" @readonly(! $canManageProjects) class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                            </label>
                            <label>
                                <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Status</span>
                                <select name="status" @disabled(! $canManageProjects) class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                                    @foreach (['active' => 'Active', 'paused' => 'Paused', 'archived' => 'Archived'] as $value => $label)
                                        <option value="{{ $value }}" @selected($project->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <div class="lg:col-span-3 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
                                <span>{{ $project->scans_count ?? 0 }} scan{{ ($project->scans_count ?? 0) === 1 ? '' : 's' }}</span>
                                <span>{{ $project->normalized_domain ?: parse_url((string) $project->website_url, PHP_URL_HOST) }}</span>
                                <span>Created {{ $project->created_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') }} IST</span>
                                @if ($canManageProjects)
                                    <button class="rounded-xl bg-blue-600 px-4 py-2 font-black text-white hover:bg-blue-700">Save</button>
                                @endif
                            </div>
                        </form>
                    </article>
                @empty
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center font-semibold text-slate-500">No projects yet.</div>
                @endforelse
            </div>

            <div class="mt-5">
                {{ $projects?->links() }}
            </div>
        </section>
    </div>
</x-layouts.dashboard>
