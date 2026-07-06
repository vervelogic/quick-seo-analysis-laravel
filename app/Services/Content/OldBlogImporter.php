<?php

namespace App\Services\Content;

use App\Models\ContentCategory;
use App\Models\ContentEntry;
use App\Models\ContentImportRun;
use App\Models\ContentRedirect;
use App\Models\ContentTag;
use Illuminate\Support\Carbon;
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

            $entry = $this->findExistingEntry($post);

            $category = null;
            if (! empty($post['category'])) {
                $categorySlug = Str::slug($post['category']);

                if ($categorySlug !== '') {
                    $categorySlugs[$categorySlug] = true;

                    if (! $dryRun) {
                        $category = ContentCategory::firstOrCreate(
                            ['type' => 'blog', 'slug' => $categorySlug],
                            ['name' => $post['category']]
                        );

                        if ($category->wasRecentlyCreated) {
                            $createdCategories++;
                        }
                    }
                }
            }

            $normalizedPayload = $this->buildEntryPayload($post, $category?->id, $baseUrl);

            if ($entry && $this->entryMatchesPayload($entry, $normalizedPayload, $post)) {
                $summary['skipped']++;
                continue;
            }

            if ($dryRun) {
                if ($entry) {
                    $summary['updated']++;
                    $summary['imported']++;
                } else {
                    $summary['created']++;
                    $summary['imported']++;
                }

                if ($this->redirectWouldBeCreated($post, $normalizedPayload['slug'] ?? $post['slug'])) {
                    $potentialRedirectCount++;
                }

                continue;
            }

            $entry ??= new ContentEntry(['type' => 'blog']);
            $isNew = ! $entry->exists;
            $entry->fill($normalizedPayload);
            $entry->save();

            if ($isNew) {
                $summary['created']++;
                $summary['imported']++;
            } else {
                $summary['updated']++;
                $summary['imported']++;
            }

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
                    $createdTags++;
                }

                $entry->tags()->syncWithoutDetaching([$tag->id]);
            }

            $redirect = ContentRedirect::firstOrCreate(
                ['from_path' => parse_url($post['legacy_url'], PHP_URL_PATH) ?: '/blog/'.$post['slug']],
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
                $redirectCount++;
            }
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
        $normalizedTitle = $this->normalizeTitle($post['title'] ?? '');

        return ContentEntry::query()
            ->where('type', 'blog')
            ->where(function ($query) use ($post, $normalizedTitle): void {
                $query
                    ->where('legacy_url', $post['legacy_url'])
                    ->orWhere('slug', $post['slug']);

                if ($normalizedTitle !== '') {
                    $query->orWhereRaw('LOWER(REPLACE(title, " ", "")) = ?', [str_replace(' ', '', $normalizedTitle)]);
                }
            })
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

    private function entryMatchesPayload(ContentEntry $entry, array $payload, array $post): bool
    {