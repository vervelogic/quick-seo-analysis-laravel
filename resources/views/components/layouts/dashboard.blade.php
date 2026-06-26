@props(['title' => 'Dashboard'])

@php
    $user = auth()->user();
    $company = $user?->company;
    $nav = [
        ['label' => 'Overview', 'route' => 'dashboard.index'],
        ['label' => 'Scans', 'route' => 'dashboard.scans'],
        ['label' => 'Reports', 'route' => 'dashboard.reports'],
        ['label' => 'Projects', 'route' => 'dashboard.projects'],
        ['label' => 'Branding', 'route' => 'dashboard.branding'],
        ['label' => 'Usage', 'route' => 'dashboard.usage'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-950 antialiased">
    <div class="min-h-screen lg:flex">
        <aside class="border-b border-slate-200 bg-white lg:fixed lg:inset-y-0 lg:left-0 lg:w-72 lg:border-b-0 lg:border-r">
            <div class="flex items-center justify-between px-5 py-5 lg:block">
                <a href="{{ route('dashboard.index') }}" class="flex items-center gap-3">
                    @if ($company?->logo_path)
                        <img src="{{ asset('storage/'.$company->logo_path) }}" alt="{{ $company->name }}" class="h-11 w-11 rounded-xl object-contain ring-1 ring-slate-200">
                    @else
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-sm font-black text-white">Q</span>
                    @endif
                    <span>
                        <span class="block text-base font-black">{{ $company?->name ?? config('app.name') }}</span>
                        <span class="block text-xs font-semibold text-slate-500">Workspace Dashboard</span>
                    </span>
                </a>
            </div>

            <nav class="flex gap-2 overflow-x-auto px-5 pb-5 lg:block lg:space-y-1 lg:overflow-visible">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" class="whitespace-nowrap rounded-xl px-4 py-3 text-sm font-bold transition lg:block {{ request()->routeIs($item['route']) ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="hidden border-t border-slate-200 p-5 lg:block">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Signed in</p>
                <p class="mt-2 text-sm font-black text-slate-900">{{ $user?->name }}</p>
                <p class="text-xs text-slate-500">{{ $user?->email }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Logout</button>
                </form>
            </div>
        </aside>

        <div class="flex min-h-screen flex-1 flex-col lg:pl-72">
            <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
                <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-600">{{ config('app.name') }}</p>
                        <h1 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">{{ $title }}</h1>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('home') }}#scan" class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-blue-700">Run New Scan</a>
                        <a href="{{ route('home') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Public Site</a>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-5 py-6 lg:px-8 lg:py-8">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-800">
                        {{ $errors->first() }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
