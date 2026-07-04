<x-layouts.app :title="config('app.name').' - Contact'" :meta="[
    'title' => 'Contact Quick SEO Analysis',
    'description' => 'Contact Quick SEO Analysis for help with visibility audits, SEO reviews, and AI readiness reporting.',
    'canonical' => route('contact'),
]">
    <section class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-4xl px-5 sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Contact</p>
            <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950 sm:text-5xl">Need help reviewing a report or planning the next fix?</h1>
            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="text-xl font-bold text-slate-950">Email</h2>
                    <p class="mt-3 text-base text-slate-600">hello@quickseoanalysis.com</p>
                </article>
                <article class="rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="text-xl font-bold text-slate-950">What we help with</h2>
                    <p class="mt-3 text-base leading-7 text-slate-600">SEO audits, AI visibility reviews, white-label reporting, and search-focused action plans.</p>
                </article>
            </div>
        </div>
    </section>
</x-layouts.app>
