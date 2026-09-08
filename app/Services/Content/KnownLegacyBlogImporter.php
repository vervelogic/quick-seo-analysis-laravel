<?php

namespace App\Services\Content;

use App\Models\ContentCategory;
use App\Models\ContentEntry;
use App\Models\ContentImportRun;
use App\Models\ContentRedirect;
use App\Models\ContentTag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class KnownLegacyBlogImporter
{
    public function __construct(
        private readonly OldBlogCrawler $crawler,
    ) {
    }

    public function import(array $options = []): array
    {
        $startedAt = microtime(true);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $timeout = max(1, (int) ($options['timeout'] ?? 10));
        $urls = $this->resolveUrls($options['urls'] ?? null, $options['urls_file'] ?? null);

        $summary = [
            'urls_configured' => count($urls),
            'expected_count' => (int) config('legacy_blog_import.expected_count', 13),
            'processed' => 0,
            'imported' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'redirects_created' => 0,
            'images_found' => 0,
            'missing_images' => 0,
            'missing_metadata' => 0,
            'categories_created' => 0,
            'tags_created' => 0,
            'execution_time_seconds' => 0,
            'failures' => [],
        ];

        $run = null;

        if (! $dryRun) {
            $run = ContentImportRun::create([
                'source' => 'quickseoanalysis_known_blog_seed',
                'type' => 'blog',
                'status' => 'running',
                'dry_run' => false,
                'started_at' => now(),
                'summary' => $summary,
            ]);
        }

        foreach ($urls as $url) {
            $summary['processed']++;

            try {
                $post = $this->crawler->fetchPost($url, $timeout);

                if (! ($post['ok'] ?? false)) {
                    throw new \RuntimeException('Fetch failed with status '.($post['status'] ?? 'n/a'));
                }

                $summary['images_found'] += (int) ($post['images_found'] ?? 0);
                $summary['missing_images'] += empty($post['featured_image']) ? 1 : 0;
                $summary['missing_metadata'] += count($post['missing_metadata'] ?? []);

                if ($dryRun) {
                    $preview = $this->previewImport($post);
                    $summary[$preview['status']]++;
                    $summary['imported']++;
                    $summary['redirects_created'] += $preview['redirects_created'];

                    continue;
                }

                $result = DB::transaction(fn () => $this->persistPost($post));

                $summary[$result['status']]++;
                if ($result['status'] !== 'skipped') {
                    $summary['imported']++;
                }
                $summary['redirects_created'] += $result['redirects_created'];
                $summary['categories_created'] += $result['categories_created'];
                $summary['tags_created'] += $result['tags_created'];
            } catch (\Throwable $exception) {
                $summary['failed']++;
                $summary['failures'][] = [
                    'url' => $url,
                    'message' => $exception->getMessage(),
                    'exception' => $exception::class,
                ];
            }
        }

        $summary['execution_time_seconds'] = round(microtime(true) - $startedAt, 2);

        if ($run) {
            $run->update([
                'status' => $summary['failed'] > 0 ? 'completed_with_errors' : 'completed',
                'summary' => $summary,
                'failures' => $summary['failures'],
                'finished_at' => now(),
            ]);
        }

        return $summary;
    }

    private function resolveUrls(null|array|string $urls, ?string $urlsFile = null): array
    {
        if (is_array($urls) && $urls !== []) {
            return $this->normalizeUrls($urls);
        }

        $file = $urlsFile ?: config('legacy_blog_import.urls_file');
        if (is_string($file) && $file !== '' && File::exists($file)) {
            return $this->normalizeUrls(preg_split('/\r\n|\r|\n/', (string) File::get($file)) ?: []);
        }

        return $this->normalizeUrls(config('legacy_blog_import.urls', []));
    }

    private function normalizeUrls(array $urls): array
    {
        return collect($urls)
            ->map(fn ($url) => trim((string) $url))
            ->filter(fn ($url) => $url !== '' && str_starts_with($url, 'http'))
            ->unique()
            ->values()
            ->all();
    }

    private function previewImport(array $post): array
    {
        $entry = $this->findExistingEntry($post);
        $payload = $this->buildEntryPayload($post, null);

        if ($entry && $this->entryMatchesPayload($entry, $payload, $post) && $this->allRedirectsExist($post, $payload['slug'])) {
            return ['status' => 'skipped', 'redirects_created' => 0];
        }

        return [
            'status' => $entry ? 'updated' : 'created',
            'redirects_created' => $this->countMissingRedirects($post, $payload['slug']),
        ];
    }

    private function persistPost(array $post): array
    {
        $entry = $this->findExistingEntry($post);

        [$categoryId, $categoriesCreated] = $this->resolveCategory($post['category'] ?? null);

        $payload = $this->buildEntryPayload($post, $categoryId);

        if ($entry && $this->entryMatchesPayload($entry, $payload, $post) && $this->allRedirectsExist($post, $payload['slug'])) {
            return [
                'status' => 'skipped',
                'redirects_created' => 0,
                'categories_created' => $categoriesCreated,
                'tags_created' => 0,
            ];
        }

        if (! $entry) {
            $payload['slug'] = $this->resolveUniqueSlug($payload['slug'], $payload['title'], $post['legacy_url']);
            $entry = new ContentEntry(['type' => 'blog']);
        }

        $isNew = ! $entry->exists;
        $entry->fill($payload);
        $entry->save();

        $tagsCreated = $this->syncTags($entry, $post['tags'] ?? []);
        $redirectsCreated = $this->syncRedirects($entry, $post);

        return [
            'status' => $isNew ? 'created' : 'updated',
            'redirects_created' => $redirectsCreated,
            'categories_created' => $categoriesCreated,
            'tags_created' => $tagsCreated,
        ];
    }

    private function findExistingEntry(array $post): ?ContentEntry
    {
        $legacyUrl = trim((string) ($post['legacy_url'] ?? ''));
        $slug = trim((string) ($post['slug'] ?? ''));

        if ($legacyUrl !== '') {
            $entry = ContentEntry::query()->where('type', 'blog')->where('legacy_url', $legacyUrl)->first();
            if ($entry) {
                return $entry;
            }
        }

        if ($slug !== '') {
            $entry = ContentEntry::query()->where('type', 'blog')->where('slug', $slug)->first();
            if ($entry && (($entry->legacy_url ?? null) === $legacyUrl || blank($entry->legacy_url))) {
                return $entry;
            }
        }

        return null;
    }

    private function buildEntryPayload(array $post, ?int $categoryId): array
    {
        $title = trim((string) ($post['title'] ?? '')) ?: Str::headline((string) ($post['slug'] ?? 'entry'));
        $slug = trim((string) ($post['slug'] ?? '')) ?: Str::slug($title);

        $legacySeoTitle = trim((string) ($post['seo_title'] ?? ''));
        $legacyOgTitle = trim((string) ($post['og_title'] ?? ''));
        $legacyTwitterTitle = trim((string) ($post['twitter_title'] ?? ''));

        $genericTitle = 'SEO Checker With Free Audit Report-Quick SEO Analysis';

        $seoTitle = $legacySeoTitle === '' || strcasecmp($legacySeoTitle, $genericTitle) === 0
            ? $title
            : $legacySeoTitle;

        $ogTitle = $legacyOgTitle === '' || strcasecmp($legacyOgTitle, $genericTitle) === 0
            ? $title
            : $legacyOgTitle;

        $twitterTitle = $legacyTwitterTitle === '' || strcasecmp($legacyTwitterTitle, $genericTitle) === 0
            ? $title
            : $legacyTwitterTitle;

        $canonicalUrl = url('/blog/'.$slug);

        return [
            'content_category_id' => $categoryId,
            'title' => $title,
            'slug' => $slug,
            'legacy_url' => $post['legacy_url'],
            'author_name' => $post['author'] ?: 'Quick SEO Analysis',
            'excerpt' => $post['excerpt'],
            'content' => trim((string) ($post['content_html'] ?? '')),
            'featured_image' => $this->stripTrackingFromUrl((string) ($post['featured_image'] ?? '')) ?: null,
            'featured_image_alt' => $post['featured_image_alt'] ?? null,
            'status' => ContentEntry::STATUS_PUBLISHED,
            'published_at' => $this->parseDate($post['published_at'] ?? null),
            'modified_at' => $this->parseDate($post['modified_at'] ?? null) ?: $this->parseDate($post['published_at'] ?? null),
            'seo_title' => $seoTitle,
            'meta_description' => $post['meta_description'] ?? null,
            'meta_keywords' => $post['meta_keywords'] ?? null,
            'canonical_url' => $canonicalUrl,
            'robots' => 'index,follow',
            'og_title' => $ogTitle,
            'og_description' => $post['og_description'] ?? ($post['meta_description'] ?? null),
            'og_image' => $this->stripTrackingFromUrl((string) ($post['og_image'] ?? $post['featured_image'] ?? '')) ?: null,
            'twitter_title' => $twitterTitle,
            'twitter_description' => $post['twitter_description'] ?? ($post['og_description'] ?? ($post['meta_description'] ?? null)),
            'twitter_image' => $this->stripTrackingFromUrl((string) ($post['twitter_image'] ?? $post['og_image'] ?? $post['featured_image'] ?? '')) ?: null,
            'schema_type' => 'BlogPosting',
            'imported_at' => now(),
            'last_crawled_at' => now(),
            'import_metadata' => [
                'source' => 'quickseoanalysis_known_blog_seed',
                'legacy_url' => $post['legacy_url'],
                'missing_metadata' => $post['missing_metadata'] ?? [],
            ],
            'legacy_metadata' => [
                'old_meta_keywords' => $post['meta_keywords'] ?? null,
            ],
        ];
    }

    private function resolveCategory(?string $name): array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return [null, 0];
        }

        $category = ContentCategory::firstOrCreate(
            ['type' => 'blog', 'slug' => Str::slug($name)],
            ['name' => $name]
        );

        return [$category->id, $category->wasRecentlyCreated ? 1 : 0];
    }

    private function syncTags(ContentEntry $entry, array $tags): int
    {
        $tagIds = [];
        $created = 0;

        foreach (collect($tags)->map(fn ($tag) => trim((string) $tag))->filter()->unique() as $tagName) {
            $tag = ContentTag::firstOrCreate(
                ['type' => 'blog', 'slug' => Str::slug($tagName)],
                ['name' => Str::title($tagName)]
            );

            if ($tag->wasRecentlyCreated) {
                $created++;
            }

            $tagIds[] = $tag->id;
        }

        $entry->tags()->sync($tagIds);

        return $created;
    }

    private function syncRedirects(ContentEntry $entry, array $post): int
    {
        $created = 0;

        foreach ($this->redirectPaths($post, $entry->slug) as $fromPath) {
            $redirect = ContentRedirect::firstOrCreate(
                ['from_path' => $fromPath],
                [
                    'content_entry_id' => $entry->id,
                    'to_path' => $entry->publicPath(),
                    'status_code' => 301,
                    'metadata' => [
                        'legacy_url' => $post['legacy_url'],
                        'new_url' => url($entry->publicPath()),
                        'imported_at' => now()->toAtomString(),
                    ],
                ]
            );

            if (! $redirect->wasRecentlyCreated && $redirect->content_entry_id !== $entry->id) {
                $redirect->update([
                    'content_entry_id' => $entry->id,
                    'to_path' => $entry->publicPath(),
                    'status_code' => 301,
                    'metadata' => [
                        'legacy_url' => $post['legacy_url'],
                        'new_url' => url($entry->publicPath()),
                        'imported_at' => now()->toAtomString(),
                    ],
                ]);
            }

            if ($redirect->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    private function allRedirectsExist(array $post, string $slug): bool
    {
        foreach ($this->redirectPaths($post, $slug) as $path) {
            if (! ContentRedirect::query()->where('from_path', $path)->exists()) {
                return false;
            }
        }

        return true;
    }

    private function countMissingRedirects(array $post, string $slug): int
    {
        $count = 0;

        foreach ($this->redirectPaths($post, $slug) as $path) {
            if (! ContentRedirect::query()->where('from_path', $path)->exists()) {
                $count++;
            }
        }

        return $count;
    }

    private function redirectPaths(array $post, string $slug): array
    {
        $legacyPath = trim((string) (parse_url((string) ($post['legacy_url'] ?? ''), PHP_URL_PATH) ?: ''));
        $canonicalPath = '/blog/'.$slug;
        $uppercasePath = '/Blog/'.$slug;

        return collect([$legacyPath !== '' ? '/'.ltrim($legacyPath, '/') : null, $canonicalPath, $uppercasePath])
            ->filter()
            ->map(fn ($path) => rtrim((string) $path, '/') ?: '/')
            ->unique()
            ->values()
            ->all();
    }

    private function resolveUniqueSlug(string $slug, string $title, string $legacyUrl): string
    {
        $slug = trim($slug) ?: Str::slug($title) ?: 'blog-post';

        $conflict = ContentEntry::query()->where('type', 'blog')->where('slug', $slug)->first();
        if (! $conflict) {
            return $slug;
        }

        if (($conflict->legacy_url ?? null) === $legacyUrl) {
            return $slug;
        }

        return ContentEntry::uniqueSlug($title ?: $slug, 'blog');
    }

    private function entryMatchesPayload(ContentEntry $entry, array $payload, array $post): bool
    {
        $fields = [
            'title', 'slug', 'legacy_url', 'author_name', 'excerpt', 'content', 'featured_image',
            'featured_image_alt', 'seo_title', 'meta_description', 'meta_keywords', 'canonical_url',
            'og_title', 'og_description', 'og_image', 'twitter_title', 'twitter_description',
            'twitter_image', 'schema_type',
        ];

        foreach ($fields as $field) {
            $existing = $entry->{$field};
            $incoming = $payload[$field] ?? null;

            if ($existing instanceof Carbon) {
                $existing = $existing->toAtomString();
            }

            if ($incoming instanceof Carbon) {
                $incoming = $incoming->toAtomString();
            }

            if ((string) $existing !== (string) $incoming) {
                return false;
            }
        }

        $existingTags = $entry->tags()->pluck('name')->map(fn ($tag) => Str::lower((string) $tag))->sort()->values()->all();
        $incomingTags = collect($post['tags'] ?? [])->map(fn ($tag) => Str::lower(trim((string) $tag)))->filter()->sort()->values()->all();

        return $existingTags === $incomingTags;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function stripTrackingFromUrl(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        $parts = parse_url($url);
        if (! $parts || (! isset($parts['scheme'], $parts['host']) && ! str_starts_with($url, '/'))) {
            return preg_replace('/([?&])(utm_[^=]+|gclid|fbclid|msclkid|gad_[^=]+)=[^&]+/i', '$1', $url) ?? $url;
        }

        $params = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $params);
            foreach (array_keys($params) as $key) {
                if (preg_match('/^(utm_|gclid|fbclid|msclkid|gad_)/i', $key)) {
                    unset($params[$key]);
                }
            }
        }

        $rebuilt = isset($parts['scheme'], $parts['host'])
            ? $parts['scheme'].'://'.$parts['host'].($parts['path'] ?? '')
            : ($parts['path'] ?? $url);

        if ($params !== []) {
            $rebuilt .= '?'.http_build_query($params);
        }

        if (! empty($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        return rtrim(preg_replace('/[?&]$/', '', $rebuilt) ?? $rebuilt, '?');
    }
}
