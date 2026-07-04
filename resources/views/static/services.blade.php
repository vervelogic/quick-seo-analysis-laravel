<x-layouts.app :title="config('app.name').' - Services'" :meta="[
    'title' => 'Services | Quick SEO Analysis',
    'description' => 'Explore Quick SEO Analysis services for SEO audits, AI visibility reviews, keyword alignment, and reporting workflows.',
    'canonical' => route('services'),
]">
    <section class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-5xl px-5 sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Services</p>
            <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950 sm:text-5xl">Services built around visibility, alignment, and follow-through.</h1>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['title' => 'SEO Health Audits', 'copy' => 'Review technical SEO, metadata, structure, indexing signals, and core page quality.'],
                    ['title' => 'AI Visibility Reviews', 'copy' => 'Understand how AI systems interpret your content, trust signals, and answer readiness.'],
                    ['title' => 'Keyword Alignment Audits', 'copy' => 'Check whether your page actually supports the keywords and commercial intent you are targeting.'],
                ] as $service)
                    <article class="rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-950">{{ $service['title'] }}</h2>
                        <p class="mt-3 text-base leading-7 text-slate-600">{{ $service['copy'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.app>
