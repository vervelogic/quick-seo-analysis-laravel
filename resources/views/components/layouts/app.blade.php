<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-950 antialiased">
    <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-lg bg-blue-600 text-sm font-black text-white">Q</span>
                <span class="text-base font-bold tracking-tight">{{ config('app.name') }}</span>
            </a>
            <nav class="hidden items-center gap-8 text-sm font-medium text-slate-600 sm:flex">
                <a class="hover:text-slate-950" href="{{ route('home') }}#benefits">Benefits</a>
                <a class="hover:text-slate-950" href="{{ route('home') }}#checks">Checks</a>
                <a class="rounded-lg bg-slate-950 px-4 py-2 text-white hover:bg-slate-800" href="{{ route('home') }}#scan">Free report</a>
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Built for fast SEO discovery.</p>
            <a class="font-medium text-slate-700 hover:text-blue-700" href="/admin">Admin</a>
        </div>
    </footer>
</body>
</html>
