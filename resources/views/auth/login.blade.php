<x-layouts.app :title="'Client Login - '.config('app.name')">
    <section class="min-h-[calc(100vh-180px)] bg-slate-950 px-5 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[1fr_0.9fr] lg:items-center">
            <div class="text-white">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-teal-300">Client workspace</p>
                <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">Manage your search and AI visibility reports.</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-300">Sign in to view company scans, reports, branding, projects and usage from one clean workspace.</p>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="rounded-3xl bg-white p-6 shadow-2xl shadow-blue-950/30 sm:p-8">
                @csrf
                <h2 class="text-2xl font-black tracking-tight">Sign in</h2>
                <p class="mt-2 text-sm text-slate-500">Use your company workspace credentials or continue with Google.</p>

                @if (session('status'))
                    <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                <a
                    href="{{ route('auth.google.redirect') }}"
                    class="mt-6 flex w-full items-center justify-center gap-3 rounded-xl border border-slate-300 px-5 py-4 text-base font-black text-slate-800 transition hover:border-slate-400 hover:bg-slate-50"
                >
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-sm font-black">G</span>
                    Continue with Google
                </a>

                <div class="mt-6 flex items-center gap-4 text-xs font-bold uppercase tracking-[0.2em] text-slate-400">
                    <span class="h-px flex-1 bg-slate-200"></span>
                    Or use email login
                    <span class="h-px flex-1 bg-slate-200"></span>
                </div>

                <div class="mt-6 space-y-5">
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-base outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </label>

                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Password</span>
                        <input type="password" name="password" required class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-base outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                    </label>

                    <label class="flex items-center gap-3 text-sm font-semibold text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-600">
                        Remember me
                    </label>
                </div>

                @if ($errors->any())
                    <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <button type="submit" class="mt-6 w-full rounded-xl bg-slate-950 px-5 py-4 text-base font-black text-white hover:bg-slate-800">Login to Dashboard</button>
            </form>
        </div>
    </section>
</x-layouts.app>
