<?php

namespace App\Services\Content;

use App\Models\ContentCategory;
use App\Models\ContentEntry;
use App\Models\ContentImportRun;
use App\Models\ContentRedirect;
use App\Models\ContentTag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OldBlogImporter
{
    public function __construct(
        private readonly OldBlogCrawler $crawler,
    ) {
    }

    public function import(array $options = []): array
    {
        $startedAt = microtime(true);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $baseUrl = (string) ($options['base_url'] ?? 'https://www.quickseoanalysis.com');
        $limit = $options['limit'] ?? null;
        $timeout = max(1, (int) ($options['timeout'] ?? 10));
        $maxPages = max(1, (int) ($options['max_pages'] ?? (is_numeric($limit) ? (int) $limit : 25)));
        $maxDepth = max(0, (int) ($options['max_depth'] ?? 2));

        $crawl = $this->crawler->crawl($baseUrl, $maxPages, $timeout, $maxDepth);
        $urls = $crawl['blog_urls'];

        if (is_numeric($limit)) {
            $urls = array_slice($urls, 0, (int) $limit);
        }

        $summary = [
            'blogs_found' => count($urls),
            'total_discovered' => count($crawl['blog_urls']),
            'crawled' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'imported' => 0,
            'categories_created' => 0,
            'tags_created' => 0,
            'redirects_created' => 0,
            'failed' => 0,
            'images_found' => 0,
            'missing_images' => 0,
            'missing_metadata' => 0,
            'execution_time_seconds' => 0,
            'failed_urls' => [],
        ];

        $categorySlugs = [];
        $tagSlugs = [];
        $redirectCount = 0;
        $potentialRedirectCount = 0;
        $createdCategories = 0;
        $createdTags = 0;
        $run = null;

        if (! $dryRun) {
            $run = ContentImportRun::create([
                'source' => 'quickseoanalysis_old_blog',
                'type' => 'blog',
                'status' => 'running',
                'dry_run' => false,
                'started_at' => now(),
                'summary' => $summary,
            ]);
        }

        foreach ($urls as $url) {
            $post = $this->crawler->fetchPost($url, $timeout);
            $summary['crawled']++;

            if (! ($post['ok'] ?? false)) {
                $summary['failed']++;
                $summary['failed_urls'][] = $url;
                continue;
            }

            $summary['images_found'] += (int) ($post['images_found'] ?? 0);
            $summary['missing_images'] += empty($post['featured_image']) ? 1 : 0;
            $summary['missing_metadata'] += count($post['missing_metadata'] ?? []);

            if ($dryRun) {
                $entry = $this->findExistingEntry($post);
                $normalizedPayload = $this->buildEntryPayload($post, null, $baseUrl);
                [$entry, $normalizedPayload] = $this->resolveEntryAndPayload($entry, $normalizedPayload, $post);

                if ($entry && $this->entryMatchesPayload($entry, $normalizedPayload, $post)) {
                    $summary['skipped']++;
                    continue;
                }

                if ($entry) {
                    $summary['updated']++;
                    $summary['imported']++;
                } else {
                    $summary['created']++;
                    $summary['imported']++;
                }

                $potentialRedirectCount += count($this->redirectPathsToCreate($post, $normalizedPayload['slug'] ?? $post['slug']));

                continue;
            }

            try {
                $result = DB::transaction(function () use ($post, $baseUrl, &$categorySlugs, &$tagSlugs): array {
                    $entry = $this->findExistingEntry($post);

                    $category = null;
                    $categoryCreated = 0;
                    if (! empty($post['category'])) {
                        $categorySlug = Str::slug($post['category']);

                        if ($categorySlug !== '') {
                            $categorySlugs[$categorySlug] = true;
                            $category = ContentCategory::firstOrCreate(
                                ['type' => 'blog', 'slug' => $categorySlug],
                                ['name' => $post['category']]
                            );

                            if ($category->wasRecentlyCreated) {
                                $categoryCreated = 1;
                            }
                        }
                    }

                    $normalizedPayload = $this->buildEntryPayload($post, $category?->id, $baseUrl);
                    [$entry, $normalizedPayload] = $this->resolveEntryAndPayload($entry, $normalizedPayload, $post);

                    if ($entry && $this->entryMatchesPayload($entry, $normalizedPayload, $post)) {
                        return [
                            'status' => 'skipped',
                            'categories_created' => $categoryCreated,
                            'tags_created' => 0,
                            'redirect_created' => 0,
                        ];
                    }

                    $entry ??= new ContentEntry(['type' => 'blog']);
                    $isNew = ! $entry->exists;
                    $entry->fill($normalizedPayload);
                    $entry->save();

                    $tagsCreated = 0;
                    foreach ($post['tags'] ?? [] as $tagName) {
                        $tagSlug = Str::slug((string) $tagName);

                        if ($tagSlug === '') {
                            continue;
                        }

                        $tagSlugs[$tagSlug] = true;

                        $tag = ContentTag::firstOrCreate(
                            ['type' => 'blog', 'slug' => $tagSlug],
                            ['name' => $tagName]
                        );

                        if ($tag->wasRecentlyCreated) {
                            $tagsCreated++;
                        }

                        $entry->tags()->syncWithoutDetaching([$tag->id]);
                    }

                    $redirectCreated = 0;
                    foreach ($this->redirectPathsToCreate($post, $normalizedPayload['slug'] ?? $post['slug']) as $fromPath) {
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

                        if ($redirect->wasRecentlyCreated) {
                            $redirectCreated++;
                        }
                    }

                    return [
                        'status' => $isNew ? 'created' : 'updated',
                        'categories_created' => $categoryCreated,
                        'tags_created' => $tagsCreated,
                        'redirect_created' => $redirectCreated,
                    ];
                });
            } catch (\Throwable) {
                $summary['failed']++;
                $summary['failed_urls'][] = $url;
                continue;
            }

            $createdCategories += $result['categories_created'];
            $createdTags += $result['tags_created'];
            $redirectCount += $result['redirect_created'];

            if ($result['status'] === 'skipped') {
                $summary['skipped']++;
                continue;
            }

            $summary[$result['status']]++;
            $summary['imported']++;
        }

        $summary['categories_created'] = $dryRun ? count($categorySlugs) : $createdCategories;
        $summary['tags_created'] = $dryRun ? count($tagSlugs) : $createdTags;
        $summary['redirects_created'] = $dryRun ? $potentialRedirectCount : $redirectCount;
        $summary['execution_time_seconds'] = round(microtime(true) - $startedAt, 2);

        if ($run) {
            $run->update([
                'status' => $summary['failed'] > 0 ? 'completed_with_errors' : 'completed',
                'summary' => $summary,
                'failures' => $summary['failed_urls'],
                'finished_at' => now(),
            ]);
        }

        return $summary;
    }

    private function findExistingEntry(array $post): ?ContentEntry
    {
        $legacyUrl = trim((string) ($post['legacy_url'] ?? ''));
        $slug = trim((string) ($post['slug'] ?? ''));
        $normalizedTitle = $this->normalizeTitle($post['title'] ?? '');

        if ($legacyUrl !== '') {
            $exactLegacyMatch = ContentEntry::query()
                ->where('type', 'blog')
                ->where('legacy_url', $legacyUrl)
                ->first();

            if ($exactLegacyMatch) {
                return $exactLegacyMatch;
            }
        }

        if ($slug !== '') {
            $exactSlugMatch = ContentEntry::query()
                ->where('type', 'blog')
                ->where('slug', $slug)
                ->first();

            if ($exactSlugMatch) {
                return $exactSlugMatch;
            }
        }

        if (! $this->hasStrongTitle($post['title'] ?? '', $slug)) {
            return null;
        }

        return ContentEntry::query()
            ->where('type', 'blog')
            ->whereNull('legacy_url')
            ->when($slug !== '', fn ($query) => $query->where('slug', $slug))
            ->whereRaw('LOWER(REPLACE(title, " ", "")) = ?', [str_replace(' ', '', $normalizedTitle)])
            ->first();
    }

    private function buildEntryPayload(array $post, ?int $categoryId, string $baseUrl): array
    {
        return [
            'content_category_id' => $categoryId,
            'title' => $post['title'] ?: Str::headline($post['slug']),
            'slug' => $post['slug'],
            'legacy_url' => $post['legacy_url'],
            'author_name' => $post['author'] ?: 'Quick SEO Analysis',
            'excerpt' => $post['excerpt'],
            'content' => $this->normalizeImportedHtml($post['content_html'], $baseUrl),
            'featured_image' => $this->stripTrackingFromUrl((string) ($post['featured_image'] ?? '')) ?: null,
            'featured_image_alt' => $post['featured_image_alt'],
            'status' => ContentEntry::STATUS_PUBLISHED,
            'published_at' => $this->parseDate($post['published_at']),
            'modified_at' => $this->parseDate($post['modified_at']) ?: $this->parseDate($post['published_at']),
            'seo_title' => $post['seo_title'],
            'meta_description' => $post['meta_description'],
            'meta_keywords' => $post['meta_keywords'],
            'canonical_url' => $this->stripTrackingFromUrl((string) ($post['canonical'] ?? '')),
            'robots' => 'index,follow',
            'og_title' => $post['og_title'] ?: $post['title'],
            'og_description' => $post['og_description'] ?: $post['meta_description'],
            'og_image' => $this->stripTrackingFromUrl((string) ($post['og_image'] ?: $post['featured_image'] ?? '')) ?: null,
            'twitter_title' => $post['twitter_title'] ?: $post['og_title'] ?: $post['title'],
            'twitter_description' => $post['twitter_description'] ?: $post['og_description'] ?: $post['meta_description'],
            'twitter_image' => $this->stripTrackingFromUrl((string) ($post['twitter_image'] ?: $post['og_image'] ?: $post['featured_image'] ?? '')) ?: null,
            'schema_type' => $this->inferSchemaType($post['content_html']),
            'imported_at' => now(),
            'last_crawled_at' => now(),
            'import_metadata' => [
                'source' => 'quickseoanalysis_old_blog',
                'legacy_url' => $post['legacy_url'],
                'missing_metadata' => $post['missing_metadata'] ?? [],
            ],
            'legacy_metadata' => [
                'old_meta_keywords' => $post['meta_keywords'],
            ],
        ];
    }

    private function preparePayloadForPersistence(array $payload, ?ContentEntry $entry, array $post): array
    {
        if ($entry) {
            return $payload;
        }

        $legacyUrl = trim((string) ($post['legacy_url'] ?? ''));
        $slug = trim((string) ($payload['slug'] ?? ''));

        if ($slug === '') {
            $payload['slug'] = ContentEntry::uniqueSlug((string) ($payload['title'] ?? 'Entry'), 'blog');

            return $payload;
        }

        $slugConflict = ContentEntry::query()
            ->where('type', 'blog')
            ->where('slug', $slug)
            ->first();

        if (! $slugConflict) {
            return $payload;
        }

        if ($legacyUrl !== '' && $slugConflict->legacy_url === $legacyUrl) {
            return $payload;
        }

        $payload['slug'] = ContentEntry::uniqueSlug((string) ($payload['title'] ?? $slug), 'blog');

        return $payload;
    }

    private function resolveEntryAndPayload(?ContentEntry $entry, array $payload, array $post): array
    {
        if (! $entry) {
            $slug = trim((string) ($payload['slug'] ?? ''));

            if ($slug !== '') {
                $entry = ContentEntry::query()
                    ->where('type', 'blog')
                    ->where('slug', $slug)
                    ->first();
            }
        }

        $payload = $this->preparePayloadForPersistence($payload, $entry, $post);

        return [$entry, $payload];
    }

    private function entryMatchesPayload(ContentEntry $entry, array $payload, array $post): bool
    {
        $compareFields = [
            'title',
            'slug',
            'legacy_url',
            'author_name',
            'excerpt',
            'content',
            'featured_image',
            'featured_image_alt',
            'seo_title',
            'meta_description',
            'meta_keywords',
            'canonical_url',
            'og_title',
            'og_description',
            'og_image',
            'twitter_title',
            'twitter_description',
            'twitter_image',
            'schema_type',
        ];

        foreach ($compareFields as $field) {
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

        $existingTags = $entry->exists
            ? $entry->tags()->pluck('name')->map(fn ($tag) => Str::title((string) $tag))->sort()->values()->all()
            : [];
        $incomingTags = collect($post['tags'] ?? [])->map(fn ($tag) => Str::title((string) $tag))->sort()->values()->all();

        return $existingTags === $incomingTags;
    }

    private function redirectWouldBeCreated(array $post, string $slug): bool
    {
        foreach ($this->redirectPathsToCreate($post, $slug) as $path) {
            if (! ContentRedirect::query()->where('from_path', $path)->exists()) {
                return true;
            }
        }

        return false;
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

    private function normalizeImportedHtml(?string $html, string $baseUrl): string
    {
        if (blank($html)) {
            return '';
        }

        $internalErrors = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $xpath = new \DOMXPath($document);

        foreach ($xpath->query('//*[@style]') ?: [] as $node) {
            if ($node instanceof \DOMElement) {
                $node->removeAttribute('style');
            }
        }

        foreach ($xpath->query('//script|//noscript|//iframe') ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach ($xpath->query('//header|//footer|//aside|//nav|//form') ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach ($xpath->query('//*[contains(@class,"sidebar") or contains(@class,"comment") or contains(@class,"comments") or contains(@class,"recent") or contains(@class,"related") or contains(@class,"widget") or contains(@class,"share") or contains(@class,"social") or contains(@class,"author-box") or contains(@class,"reply") or contains(@class,"respond")]') ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach ($xpath->query('//a[@href]') ?: [] as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $href = $node->getAttribute('href');
            $clean = $this->stripTrackingFromUrl($href);
            $path = parse_url($clean, PHP_URL_PATH) ?: '';

            if (preg_match('~^/(Blog|blog)/([^/?#]+)$~', $path, $match)
                && ! str_contains($path, '/blog/category/')
                && ! str_contains($path, '/blog/tag/')) {
                $node->setAttribute('href', url('/blog/'.trim($match[2], '/')));
            } else {
                $node->setAttribute('href', $clean);
            }
        }

        foreach ($xpath->query('//img[@src]') ?: [] as $node) {
            if ($node instanceof \DOMElement) {
                $node->setAttribute('src', $this->stripTrackingFromUrl($node->getAttribute('src')));
                $node->removeAttribute('srcset');
                $node->removeAttribute('sizes');
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if (! $body instanceof \DOMElement) {
            return str_replace(
                [rtrim($baseUrl, '/').'/blog/', rtrim($baseUrl, '/').'/Blog/'],
                [url('/blog/').'/', url('/blog/').'/'],
                $html
            );
        }

        $clean = '';
        foreach ($body->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
    }

    private function inferSchemaType(?string $content): string
    {
        $content = (string) $content;
        $normalized = strtolower(strip_tags($content));

        if ($this->containsStrongFaqMarkup($content)) {
            return 'FAQPage';
        }

        if (str_contains($normalized, 'step 1') || str_contains($normalized, 'how to')) {
            return 'HowTo';
        }

        return 'BlogPosting';
    }

    private function containsStrongFaqMarkup(string $content): bool
    {
        if (blank($content)) {
            return false;
        }

        if (preg_match_all('/<h[2-4][^>]*>(.*?)<\/h[2-4]>\s*<p[^>]*>(.*?)<\/p>/is', $content, $matches, PREG_SET_ORDER) === false) {
            return false;
        }

        $questions = 0;
        foreach ($matches as $match) {
            $question = trim(strip_tags($match[1] ?? ''));
            $answer = trim(strip_tags($match[2] ?? ''));

            if ($question !== '' && $answer !== '' && str_contains($question, '?')) {
                $questions++;
            }
        }

        return $questions >= 2;
    }

    private function redirectPathsToCreate(array $post, string $slug): array
    {
        $legacyPath = trim((string) (parse_url((string) ($post['legacy_url'] ?? ''), PHP_URL_PATH) ?: ''));
        $fallbackPath = '/blog/'.$slug;

        $paths = [];
        foreach ([$legacyPath !== '' ? $legacyPath : $fallbackPath] as $path) {
            $normalized = '/'.ltrim($path, '/');
            $paths[] = $normalized;

            if (preg_match('~^/blog/(.+)$~', $normalized, $match)) {
                $paths[] = '/Blog/'.$match[1];
            } elseif (preg_match('~^/Blog/(.+)$~', $normalized, $match)) {
                $paths[] = '/blog/'.$match[1];
            }
        }

        return collect($paths)
            ->map(fn (string $path) => rtrim($path, '/') ?: '/')
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeTitle(string $title): string
    {
        return Str::lower(Str::squish($title));
    }

    private function hasStrongTitle(?string $title, ?string $slug = null): bool
    {
        $title = trim((string) $title);

        if ($title === '') {
            return false;
        }

        $normalizedTitle = $this->normalizeTitle($title);
        $normalizedSlug = Str::of((string) $slug)->replace('-', ' ')->lower()->squish()->toString();

        if ($normalizedTitle === '') {
            return false;
        }

        if (in_array($normalizedTitle, ['quick seo analysis', 'blog', 'entry'], true)) {
            return false;
        }

        if ($normalizedSlug !== '' && $normalizedTitle === $normalizedSlug) {
            return str_word_count($title) >= 3;
        }

        return str_word_count($title) >= 3;
    }

    private function stripTrackingFromUrl(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        $parts = parse_url($url);
        if (! $parts || ! isset($parts['scheme'], $parts['host']) && ! str_starts_with($url, '/')) {
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

        if (isset($parts['scheme'], $parts['host'])) {
            $rebuilt = $parts['scheme'].'://'.$parts['host'].($parts['path'] ?? '');
        } else {
            $rebuilt = $parts['path'] ?? $url;
        }

        if ($params !== []) {
            $rebuilt .= '?'.http_build_query($params);
        }

        if (! empty($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        return rtrim(preg_replace('/[?&]$/', '', $rebuilt) ?? $rebuilt, '?');
    }
}
