<x-layouts.dashboard title="Branding">
    @unless ($company)
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-8 text-amber-900">
            <h2 class="text-2xl font-black">Company assignment needed</h2>
            <p class="mt-2">Branding can be managed after your user is assigned to a company workspace.</p>
        </section>
    @else
        <section class="grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
            <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-black uppercase tracking-[0.18em] text-blue-600">Brand Preview</p>
                <div class="mt-6 rounded-2xl border border-slate-200 p-5">
                    @if ($company->logo_path)
                        <img src="{{ asset('storage/'.$company->logo_path) }}" alt="{{ $company->name }}" class="h-20 w-20 rounded-2xl object-contain ring-1 ring-slate-200">
                    @else
                        <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-slate-950 text-2xl font-black text-white">{{ Str::of($company->name)->substr(0, 1)->upper() }}</div>
                    @endif
                    <h2 class="mt-5 text-2xl font-black">{{ $company->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $company->website_url ?? $company->domain ?? 'No website set' }}</p>
                    <div class="mt-5 flex gap-2">
                        <span class="h-8 w-8 rounded-full border border-slate-200" style="background: {{ $company->primary_color ?? '#2563eb' }}"></span>
                        <span class="h-8 w-8 rounded-full border border-slate-200" style="background: {{ $company->secondary_color ?? '#0f172a' }}"></span>
                        <span class="h-8 w-8 rounded-full border border-slate-200" style="background: {{ $company->accent_color ?? '#14b8a6' }}"></span>
                    </div>
                </div>
            </aside>

            <form method="POST" action="{{ route('dashboard.branding.update') }}" enctype="multipart/form-data" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PATCH')
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-black tracking-tight">Company Branding</h2>
                        <p class="mt-1 text-sm text-slate-500">Owners and admins can update workspace profile and report branding.</p>
                    </div>
                    @unless ($canManageBranding)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Read-only</span>
                    @endunless
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Company name</span>
                        <input name="name" value="{{ old('name', $company->name) }}" required @readonly(! $canManageBranding) class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </label>
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Website</span>
                        <input name="website_url" type="url" value="{{ old('website_url', $company->website_url) }}" @readonly(! $canManageBranding) class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </label>
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Contact email</span>
                        <input name="contact_email" type="email" value="{{ old('contact_email', $company->contact_email) }}" @readonly(! $canManageBranding) class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </label>
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Phone</span>
                        <input name="contact_phone" value="{{ old('contact_phone', $company->contact_phone) }}" @readonly(! $canManageBranding) class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </label>
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Primary brand color</span>
                        <input name="primary_color" type="color" value="{{ old('primary_color', $company->primary_color ?? '#2563eb') }}" @disabled(! $canManageBranding) class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-2 py-2">
                    </label>
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Logo</span>
                        <input name="logo" type="file" accept=".jpg,.jpeg,.png,.webp" @disabled(! $canManageBranding) class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        <span class="mt-1 block text-xs text-slate-500">Allowed: JPG, PNG, WEBP. SVG is skipped for now.</span>
                    </label>
                    <label class="md:col-span-2 block">
                        <span class="text-sm font-bold text-slate-700">Report footer text</span>
                        <textarea name="report_footer_text" rows="3" @readonly(! $canManageBranding) class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-600 focus:ring-4 focus:ring-blue-100">{{ old('report_footer_text', $company->white_label_settings['report_footer_text'] ?? '') }}</textarea>
                    </label>
                    <label class="md:col-span-2 flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <input name="white_label_enabled" type="checkbox" value="1" @checked($company->white_label_enabled) @disabled(! $canManageBranding || ! $company->featureEnabled('white_label_reports')) class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-600">
                        <span>
                            <span class="block font-black">Enable white-label reports</span>
                            <span class="block text-sm text-slate-500">Available only when the active plan allows white-label reports. Full PDF branding arrives in Phase 3.</span>
                        </span>
                    </label>
                </div>

                @if ($canManageBranding)
                    <button class="mt-6 rounded-xl bg-slate-950 px-5 py-3 font-black text-white hover:bg-slate-800">Save Branding</button>
                @endif
            </form>
        </section>
    @endunless
</x-layouts.dashboard>
