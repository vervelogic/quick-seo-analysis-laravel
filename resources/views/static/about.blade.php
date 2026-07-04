<x-layouts.app :title="config('app.name').' - About'" :meta="[
    'title' => 'About Quick SEO Analysis',
    'description' => 'Learn how Quick SEO Analysis helps teams understand SEO, AI visibility, and search readiness with practical audits.',
    'canonical' => route('about'),
]">
    <section class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-4xl px-5 sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">About</p>
            <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950 sm:text-5xl">Built to turn search and AI visibility into something teams can act on.</h1>
            <div class="mt-8 space-y-6 text-base leading-8 text-slate-600">
                <p>Quick SEO Analysis helps teams understand what a page communicates to search engines, answer engines, and AI systems without drowning them in technical noise.</p>
                <p>We focus on practical visibility signals: crawlability, structured data, content depth, AI readiness, citation readiness, and the next actions that matter most.</p>
                <p>QSA is designed for marketing teams, agencies, consultants, and business owners who need a fast way to review SEO health and explain opportunities clearly to stakeholders.</p>
            </div>
        </div>
    </section>
</x-layouts.app>
