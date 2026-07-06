<?php

namespace App\Http\Controllers;

use App\Models\ContentCategory;
use App\Models\ContentEntry;
use App\Models\ContentRedirect;
use App\Models\ContentTag;
use App\Services\Content\ContentSchemaBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

class BlogController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $categorySlug = trim((string) $request->string('category'));
        $tagSlug = trim((string) $request->string('tag'));

        $entries = ContentEntry::query()
            ->blogs()
            ->published()
            ->with(['category', 'tags'])
            ->when($categorySlug !== '', function ($query) use ($categorySlug): void {
                $query->whereHas('category', fn ($builder) => $builder->where('slug', $categorySlug));
            })
            ->when($tagSlug !== '', function ($query) use ($tagSlug): void {
                $query->whereHas('tags', fn ($builder) => $builder->where('slug', $tagSlug));
            })
            ->search($search)
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        $featuredEntry = $entries->firstWhere('is_featured', true) ?? $entries->first();
        $category = $categorySlug !== ''
            ? ContentCategory::query()->where('type', 'blog')->where('slug', $categorySlug)->first()
            : null;
        $tag = $tagSlug !== ''
            ? ContentTag::query()->where('type', 'blog')->where('slug', $tagSlug)->first()
            : null;
        $meta = $this->buildIndexMeta($search, $category, $tag);
        $structuredData = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Blog',
                'name' => 'Quick SEO Analysis Blog',
                'description' => $meta['description'],
                'url' => $meta['canonical'],
                'inLanguage' => 'en-US',
                'blogPost' => $entries->take(10)->map(fn (ContentEntry $entry) => [
                    '@type' => 'BlogPosting',
                    'headline' => $entry->title,
                    'url' => url($entry->publicPath()),
                    'datePublished' => optional($entry->published_at)->toAtomString(),
                    'description' => $entry->seoDescription(),
                ])->values()->all(),
            ],
        ];

        if ($category || $tag) {
            $structuredData[] = [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $category?->name ? $category->name.' Articles' : '#'.$tag?->name.' Articles',
                'url' => $meta['canonical'],
                'description' => $meta['description'],
            ];
        }

        return view('blog.index', [
            'entries' => $entries,
            'featuredEntry' => $featuredEntry,
            'activeCategory' => $category,
            'activeTag' => $tag,
            'search' => $search,
            'categories' => ContentCategory::query()->where('type', 'blog')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'tags' => ContentTag::query()->where('type', 'blog')->where('is_active', true)->orderBy('name')->get(),
            'meta' => $meta,
            'structuredData' => $structuredData,
        ]);
    }

    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $entry = ContentEntry::query()
            ->blogs()
            ->where('slug', $slug)
            ->with(['category', 'tags'])
            ->first();

        if (! $entry) {
            $requestedPath = '/'.ltrim((string) $request->path(), '/');
            $redirect = ContentRedirect::query()
                ->whereIn('from_path', array_values(array_unique([
                    '/blog/'.$slug,
                    '/Blog/'.$slug,
                    '/'.$slug,
                    $requestedPath !== '/' ? $requestedPath : null,
                ])))
                ->where('is_active', true)
                ->first();

            if ($redirect) {
                return redirect($redirect->to_path, $redirect->status_code);
            }

            abort(404);
        }

        $isPreview = $request->boolean('preview') && URL::hasValidSignature($request);

        abort_unless($entry->isPubliclyVisible() || $isPreview, 404);

        if ($request->routeIs('blog.legacy-show') && ! $isPreview) {
            return redirect($entry->publicPath(), 301);
        }

        $builder = app(ContentSchemaBuilder::class);

        return view('blog.show', [
            'entry' => $entry,
            'relatedEntries' => ContentEntry::query()
                ->blogs()
                ->published()
                ->whereKeyNot($entry->id)
                ->when($entry->content_category_id, fn ($query) => $query->where('content_category_id', $entry->content_category_id))
                ->latest('published_at')
                ->limit(3)
                ->get(),
            'meta' => [
                'title' => $entry->seoTitle(),
                'description' => $entry->seoDescription(),
                'keywords' => $entry->meta_keywords,
                'robots' => $entry->robots ?: 'index,follow',
                'canonical' => $entry->resolvedCanonicalUrl(),
                'open_graph' => [
                    'title' => $entry->og_title ?: $entry->seoTitle(),
                    'description' => $entry->og_description ?: $entry->seoDescription(),
                    'url' => url($entry->publicPath()),
                    'type' => 'article',
                    'image' => $entry->og_image ?: $entry->featured_image,
                    'image_alt' => $entry->featured_image_alt,
                ],
                'twitter' => [
                    'title' => $entry->twitter_title ?: $entry->og_title ?: $entry->seoTitle(),
                    'description' => $entry->twitter_description ?: $entry->og_description ?: $entry->seoDescription(),
                    'image' => $entry->twitter_image ?: $entry->og_image ?: $entry->featured_image,
                ],
            ],
            'structuredData' => array_values(array_filter([
                $builder->build($entry),
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Home',
                            'item' => route('home'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Blog',
                            'item' => route('blog.index'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $entry->title,
                            'item' => url($entry->publicPath()),
                        ],
                    ],
                ],
                $entry->custom_json_ld ? json_decode($entry->custom_json_ld, true) : null,
            ])),
        ]);
    }

    public function category(Request $request, string $slug): View
    {
        $request->merge(['category' => $slug]);

        return $this->index($request);
    }

    public function tag(Request $request, string $slug): View
    {
        $request->merge(['tag' => $slug]);

        return $this->index($request);
    }

    public function rss(): Response
    {
        $entries = ContentEntry::query()
            ->blogs()
            ->published()
            ->with('category')
            ->latest('published_at')
            ->limit(25)
            ->get();

        $xml = view('blog.feed', ['entries' => $entries])->render();

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    private function buildIndexMeta(?string $search, ?ContentCategory $category, ?ContentTag $tag): array
    {
        $title = 'QSA Blog | SEO, AI Visibility, GEO & AEO Insights';
        $description = 'Read SEO, AI visibility, GEO, AEO, and content strategy articles from Quick SEO Analysis.';
        $canonical = route('blog.index');

        if ($category) {
            $title = $category->name.' Articles | QSA Blog';
            $description = 'Browse '.$category->name.' articles, guides, and SEO visibility insights from Quick SEO Analysis.';
            $canonical = route('blog.category', $category->slug);
        } elseif ($tag) {
            $title = '#'.$tag->name.' Articles | QSA Blog';
            $description = 'Browse articles tagged '.$tag->name.' from Quick SEO Analysis.';
            $canonical = route('blog.tag', $tag->slug);
        } elseif (filled($search)) {
            $title = 'Search results for "'.$search.'" | QSA Blog';
            $description = 'Search the Quick SEO Analysis blog for '.$search.', including SEO, AI visibility, GEO, and AEO topics.';
            $canonical = route('blog.index', ['q' => $search]);
        }

        return [
            'title' => $title,
            'description' => $description,
            'robots' => 'index,follow',
            'canonical' => $canonical,
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => $title,
                'description' => $description,
            ],
            'open_graph' => [
                'title' => $title,
                'description' => $description,
                'url' => $canonical,
                'type' => 'website',
            ],
            'extra_meta' => [
                ['name' => 'url', 'content' => $canonical],
            ],
        ];
    }
}
