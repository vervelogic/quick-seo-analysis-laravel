<x-layouts.app
    :title="$meta['title']"
    :meta="$meta"
    :structured-data="$structuredData"
>
    <section class="bg-slate-950 py-14 text-white">
        <div class="mx-auto max-w-5xl px-6 lg:px-8">
            <nav class="text-sm text-slate-400">
                <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                <span class="px-2">/</span>
                <a href="{{ route('blog.index') }}" class="hover:text-white">Blog</a>
                <span class="px-2">/</span>
                <span class="text-white">{{ $entry->title }}</span>
            </nav>

            <div class="mt-8">
                @if ($entry->category)
                    <span class="rounded-full bg-cyan-500/10 px-4 py-2 text-sm font-semibold text-cyan-300">{{ $entry->category->name }}</span>
                @endif
                <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">{{ $entry->title }}</h1>
                <div class="mt-5 flex flex-wrap items-center gap-4 text-sm text-slate-300">
                    <span>By {{ $entry->author_name ?: ($entry->author?->name ?: 'QSA Team') }}</span>
                    <span>{{ optional($entry->published_at)->format('d M Y') }}</span>
                    <span>{{ $entry->reading_time_minutes }} min read</span>
                    @if ($entry->modified_at)
                        <span>Updated {{ $entry->modified_at->format('d M Y') }}</span>
                    @endif
                </div>
                <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-300">{{ $entry->excerpt }}</p>
                <div class="mt-6 flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('blog.index') }}" class="rounded-full border border-white/15 px-4 py-2 font-semibold text-white hover:border-white/35">Back to blog</a>
                    @if ($entry->category)
                        <a href="{{ route('blog.category', $entry->category->slug) }}" class="rounded-full border border-cyan-400/20 bg-cyan-400/10 px-4 py-2 font-semibold text-cyan-200 hover:border-cyan-300/40">{{ $entry->category->name }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-12">
        <div class="mx-auto grid max-w-7xl gap-10 px-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:px-8">
            <article class="min-w-0">
                @if ($entry->featured_image)
                    <img src="{{ $entry->featured_image }}" alt="{{ $entry->featured_image_alt ?: $entry->title }}" class="mb-8 w-full rounded-[2rem] border border-slate-200 bg-slate-100 object-cover shadow-lg shadow-slate-200/60" />
                @endif

                <div class="prose prose-slate max-w-none prose-headings:font-black prose-a:text-blue-700 prose-img:rounded-2xl prose-img:border prose-img:border-slate-200">
                    {!! $entry->content !!}
                </div>

                @if ($entry->legacy_url)
                    <div class="mt-8 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600">
                        Originally published on
                        <a href="{{ $entry->legacy_url }}" class="font-semibold text-blue-700 hover:text-blue-800">{{ parse_url($entry->legacy_url, PHP_URL_HOST) ?: $entry->legacy_url }}</a>.
                    </div>
                @endif

                @if ($entry->tags->isNotEmpty())
                    <div class="mt-10 flex flex-wrap gap-2 border-t border-slate-200 pt-8">
                        @foreach ($entry->tags as $tag)
                            <a href="{{ route('blog.tag', $tag->slug) }}" class="rounded-full border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-blue-200 hover:text-blue-700">#{{ $tag->name }}</a>
                        @endforeach
                    </div>
                @endif

                <div class="mt-12 rounded-[2rem] bg-slate-950 p-8 text-white shadow-xl shadow-slate-950/20">
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-300">Next step</p>
                    <h2 class="mt-4 text-3xl font-black">See what your website communicates today.</h2>
                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-300">Use QSA to audit search visibility, AI visibility, technical SEO, and keyword support in one report.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('home') }}#homepage-scan" class="rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-950 hover:bg-slate-100">Run free scan</a>
                        <a href="{{ route('login') }}" class="rounded-full border border-white/20 px-5 py-3 text-sm font-semibold text-white hover:border-white/40">Create account</a>
                    </div>
                </div>
            </article>

            <aside class="space-y-6">
                <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-6 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Article details</h2>
                    <dl class="mt-4 space-y-3 text-sm text-slate-600">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="font-semibold text-slate-900">Published</dt>
                            <dd>{{ optional($entry->published_at)->format('d M Y') ?: 'Not available' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="font-semibold text-slate-900">Updated</dt>
                            <dd>{{ optional($entry->modified_at)->format('d M Y') ?: 'Not available' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="font-semibold text-slate-900">Word count</dt>
                            <dd>{{ number_format($entry->word_count) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="font-semibold text-slate-900">Reading time</dt>
                            <dd>{{ $entry->reading_time_minutes }} min</dd>
                        </div>
                    </dl>
                </div>

                @if ($relatedEntries->isNotEmpty())
                    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-black text-slate-950">Related articles</h2>
                        <div class="mt-5 space-y-4">
                            @foreach ($relatedEntries as $related)
                                <article>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ optional($related->published_at)->format('d M Y') }}</p>
                                    <h3 class="mt-2 text-base font-bold leading-6 text-slate-950">
                                        <a href="{{ route('blog.show', $related->slug) }}" class="hover:text-blue-700">{{ $related->title }}</a>
                                    </h3>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </section>
</x-layouts.app>
