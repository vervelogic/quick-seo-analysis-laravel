<x-layouts.app
    :title="$meta['title']"
    :meta="$meta"
    :structured-data="$structuredData ?? []"
>
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-300">QSA Content Hub</p>
                <h1 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">
                    @if ($activeCategory)
                        {{ $activeCategory->name }} articles and insights.
                    @elseif ($activeTag)
                        Articles tagged #{{ $activeTag->name }}.
                    @elseif ($search)
                        Search results for “{{ $search }}”.
                    @else
                        SEO, AI visibility, and content strategy insights.
                    @endif
                </h1>
                <p class="mt-5 text-lg leading-8 text-slate-300">
                    @if ($activeCategory)
                        Browse the latest guides, frameworks, and analysis for {{ $activeCategory->name }}.
                    @elseif ($activeTag)
                        Explore every QSA article connected to {{ $activeTag->name }}.
                    @elseif ($search)
                        Review the most relevant QSA articles matching your search.
                    @else
                        Explore articles, guides, and playbooks covering technical SEO, AI visibility, GEO, AEO, content coverage, and website growth.
                    @endif
                </p>
            </div>
        </div>
    </section>

    <section class="bg-white py-12">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-8">
                    <form method="GET" action="{{ route('blog.index') }}" class="rounded-3xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
                        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_180px_180px_auto]">
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search articles" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none ring-0 placeholder:text-slate-400 focus:border-blue-500" />
                            <select name="category" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-500">
                                <option value="">All categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <select name="tag" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-500">
                                <option value="">All tags</option>
                                @foreach ($tags as $tag)
                                    <option value="{{ $tag->slug }}" @selected(request('tag') === $tag->slug)>{{ $tag->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Search</button>
                        </div>
                        @if ($search || $activeCategory || $activeTag)
                            <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-slate-600">
                                <span class="font-semibold text-slate-900">Active filters:</span>
                                @if ($search)
                                    <span class="rounded-full bg-white px-3 py-1">Search: {{ $search }}</span>
                                @endif
                                @if ($activeCategory)
                                    <span class="rounded-full bg-white px-3 py-1">Category: {{ $activeCategory->name }}</span>
                                @endif
                                @if ($activeTag)
                                    <span class="rounded-full bg-white px-3 py-1">Tag: #{{ $activeTag->name }}</span>
                                @endif
                                <a href="{{ route('blog.index') }}" class="font-semibold text-blue-700 hover:text-blue-800">Clear all</a>
                            </div>
                        @endif
                    </form>

                    @if ($featuredEntry)
                        <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-lg shadow-slate-200/60">
                            <div class="grid gap-0 lg:grid-cols-[1.25fr_minmax(0,1fr)]">
                                <div class="h-full min-h-[260px] bg-slate-100">
                                    @if ($featuredEntry->featured_image)
                                        <img src="{{ $featuredEntry->featured_image }}" alt="{{ $featuredEntry->featured_image_alt ?: $featuredEntry->title }}" class="h-full w-full object-cover" />
                                    @else
                                        <div class="flex h-full items-center justify-center bg-gradient-to-br from-blue-950 via-slate-950 to-cyan-900 p-10 text-left text-white">
                                            <div>
                                                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-300">Featured article</p>
                                                <p class="mt-4 text-3xl font-black leading-tight">{{ $featuredEntry->title }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-8">
                                    <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
                                        @if ($featuredEntry->category)
                                            <span class="rounded-full bg-blue-50 px-3 py-1 font-semibold text-blue-700">{{ $featuredEntry->category->name }}</span>
                                        @endif
                                        <span>{{ optional($featuredEntry->published_at)->format('d M Y') }}</span>
                                        <span>{{ $featuredEntry->reading_time_minutes }} min read</span>
                                    </div>
                                    <h2 class="mt-5 text-3xl font-black tracking-tight text-slate-950">
                                        <a href="{{ route('blog.show', $featuredEntry->slug) }}" class="hover:text-blue-700">{{ $featuredEntry->title }}</a>
                                    </h2>
                                    <p class="mt-4 text-base leading-7 text-slate-600">{{ $featuredEntry->excerpt }}</p>
                                    <div class="mt-6">
                                        <a href="{{ route('blog.show', $featuredEntry->slug) }}" class="inline-flex items-center rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">Read article</a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endif

                    <div class="grid gap-6 md:grid-cols-2">
                        @foreach ($entries as $entry)
                            @continue($featuredEntry && $entry->is($featuredEntry))
                            <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                                <div class="flex flex-wrap items-center gap-3 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                    @if ($entry->category)
                                        <span class="rounded-full bg-blue-50 px-3 py-1 text-blue-700">{{ $entry->category->name }}</span>
                                    @endif
                                    <span>{{ optional($entry->published_at)->format('d M Y') }}</span>
                                </div>
                                <h3 class="mt-4 text-2xl font-black leading-tight text-slate-950">
                                    <a href="{{ route('blog.show', $entry->slug) }}" class="hover:text-blue-700">{{ $entry->title }}</a>
                                </h3>
                                <p class="mt-4 text-sm leading-7 text-slate-600">{{ $entry->excerpt }}</p>
                                <div class="mt-5 flex items-center justify-between text-sm text-slate-500">
                                    <span>{{ $entry->reading_time_minutes }} min read</span>
                                    <a href="{{ route('blog.show', $entry->slug) }}" class="font-semibold text-blue-700 hover:text-blue-800">Read more</a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ($entries->isEmpty())
                        <div class="rounded-[1.75rem] border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                            <h2 class="text-2xl font-black text-slate-950">No articles matched your filters.</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600">Try a broader search, remove a category or tag filter, or return to the full blog archive.</p>
                            <a href="{{ route('blog.index') }}" class="mt-5 inline-flex rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">View all articles</a>
                        </div>
                    @endif

                    <div>
                        {{ $entries->links() }}
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        <h2 class="text-lg font-black text-slate-950">Categories</h2>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($categories as $category)
                                <a href="{{ route('blog.category', $category->slug) }}" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:border-blue-200 hover:text-blue-700">{{ $category->name }}</a>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        <h2 class="text-lg font-black text-slate-950">Tags</h2>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($tags as $tag)
                                <a href="{{ route('blog.tag', $tag->slug) }}" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:border-blue-200 hover:text-blue-700">#{{ $tag->name }}</a>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-[1.75rem] bg-slate-950 p-6 text-white shadow-xl shadow-slate-950/20">
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-300">Free Audit</p>
                        <h2 class="mt-4 text-2xl font-black">Turn content insights into action.</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-300">Run a free QSA scan to see technical SEO, AI visibility, and content gaps in one report.</p>
                        <a href="{{ route('home') }}#homepage-scan" class="mt-6 inline-flex rounded-full bg-white px-4 py-3 text-sm font-semibold text-slate-950 hover:bg-slate-100">Run free scan</a>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.app>
