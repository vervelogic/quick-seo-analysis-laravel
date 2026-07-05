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
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $baseUrl = (string) ($options['base_url'] ?? 'https://www.quickseoanalysis.com');
        $limit = $options['limit'] ?? null;

        $crawl = $this->crawler->crawl($baseUrl);
        $urls = $crawl['blog_urls'];

        if (is_numeric($limit)) {
            $urls = array_slice($urls, 0, (int) $limit);
        }

        $summary = [
            'blogs_found' => count($urls),
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'categories_created' => 0,
            'tags_created' => 0,
            'redirects_created' => 0,
            'failed' => 0,
            'failed_urls' => [],
        ];

        $categorySlugs = [];
        $tagSlugs = [];
        $redirectCount = 0;

        $run = ContentImportRun::create([
            'source' => 'quickseoanalysis_old_blog',
            'type' => 'blog',
            'status' => 'running',
            'dry_run' => $dryRun,
            'started_at' => now(),
            'summary' => $summary,
        ]);

        foreach ($urls as $url) {
            $post = $this->crawler->fetchPost($url);

            if (! ($post['ok'] ?? false)) {
                $summary['failed']++;
                $summary['failed_urls'][] = $url;
                continue;
            }

            $entry = ContentEntry::query()
                ->where('type', 'blog')
                ->where(function ($query) use ($post): void {
                    $query
                        ->where('legacy_url', $post['legacy_url'])
                        ->orWhere('slug', $post['slug']);
                })
                ->first();

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
                    }
                }
            }

            if ($entry) {
                $summary['updated']++;
            } else {
                $summary['imported']++;
            }

            if ($dryRun) {
                continue;
            }

            $entry ??= new ContentEntry(['type' => 'blog']);
            $entry->fill([
                'content_category_id' => $category?->id,
                'title' => $post['title'] ?: Str::headline($post['slug']),
                'slug' => $post['slug'],
                'legacy_url' => $post['legacy_url'],
                'author_name' => $post['author'] ?: 'Quick SEO Analysis',
                'excerpt' => $post['excerpt'],
                'content' => $this->normalizeImportedHtml($post['content_html'], $baseUrl),
                'featured_image' => $post['featured_image'],
                'featured_image_alt' => $post['featured_image_alt'],
                'status' => ContentEntry::STATUS_PUBLISHED,
                'published_at' => $this->parseDate($post['published_at']),
                'modified_at' => $this->parseDate($post['modified_at']) ?: $this->parseDate($post['published_at']),
                'seo_title' => $post['seo_title'],
                'meta_description' => $post['meta_description'],
                'meta_keywords' => $post['meta_keywords'],
                'canonical_url' => $post['canonical'],
                'robots' => 'index,follow',
                'og_title' => $post['og_title'] ?: $post['title'],
                'og_description' => $post['og_description'] ?: $post['meta_description'],
                'og_image' => $post['og_image'] ?: $post['featured_image'],
                'twitter_title' => $post['twitter_title'] ?: $post['og_title'] ?: $post['title'],
                'twitter_description' => $post['twitter_description'] ?: $post['og_description'] ?: $post['meta_description'],
                'twitter_image' => $post['twitter_image'] ?: $post['og_image'] ?: $post['featured_image'],
                'schema_type' => $this->inferSchemaType($post['content_html']),
                'imported_at' => now(),
                'last_crawled_at' => now(),
                'import_metadata' => [
                    'source' => 'quickseoanalysis_old_blog',
                    'legacy_url' => $post['legacy_url'],
                ],
                'legacy_metadata' => [
                    'old_meta_keywords' => $post['meta_keywords'],
                ],
            ]);
            $entry->save();

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

                $entry->tags()->syncWithoutDetaching([$tag->id]);
            }

            $redirect = ContentRedirect::firstOrCreate(
                ['from_path' => parse_url($post['legacy_url'], PHP_URL_PATH) ?: '/blog/'.$post['slug']],
                [
                    'content_entry_id' => $entry->id,
                    'to_path' => $entry->publicPath(),
                    'status_code' => 301,
                ]
            );

            if ($redirect->wasRecentlyCreated) {
                $redirectCount++;
            }
        }

        $summary['categories_created'] = $dryRun
            ? count($categorySlugs)
            : ContentCategory::query()->where('type', 'blog')->count();
        $summary['tags_created'] = $dryRun
            ? count($tagSlugs)
            : ContentTag::query()->where('type', 'blog')->count();
        $summary['redirects_created'] = $dryRun ? count($urls) - $summary['failed'] : $redirectCount;
        $run->update([
            'status' => $summary['failed'] > 0 ? 'completed_with_errors' : 'completed',
            'summary' => $summary,
            'failures' => $summary['failed_urls'],
            'finished_at' => now(),
        ]);

        return $summary;
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

        return str_replace(
            [rtrim($baseUrl, '/').'/blog/', rtrim($baseUrl, '/').'/Blog/'],
            [url('/blog/').'/', url('/blog/').'/'],
            $html
        );
    }

    private function inferSchemaType(?string $content): string
    {
        $content = strtolower(strip_tags((string) $content));

        if (str_contains($content, 'faq') || substr_count($content, '?') >= 3) {
            return 'FAQPage';
        }

        if (str_contains($content, 'step 1') || str_contains($content, 'how to')) {
            return 'HowTo';
        }

        return 'BlogPosting';
    }
}
