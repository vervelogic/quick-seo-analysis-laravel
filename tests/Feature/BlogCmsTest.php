<?php

namespace Tests\Feature;

use App\Http\Controllers\BlogController;
use App\Models\ContentCategory;
use App\Models\ContentEntry;
use App\Models\ContentRedirect;
use App\Models\ContentTag;
use App\Services\Content\OldBlogCrawler;
use App\Services\Content\OldBlogImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BlogCmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Route::middleware('web')->group(function (): void {
            Route::get('/', fn () => 'home')->name('home');
            Route::get('/login', fn () => 'login')->name('login');
            Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
            Route::get('/blog/feed', [BlogController::class, 'rss'])->name('blog.feed');
            Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
            Route::get('/blog/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');
            Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
            Route::get('/Blog/{slug}', [BlogController::class, 'show'])->name('blog.legacy-show');
        });
    }

    public function test_blog_index_renders_only_published_posts(): void
    {
        $category = $this->createCategory('SEO');

        $published = ContentEntry::create([
            'type' => 'blog',
            'title' => 'How to audit your website',
            'slug' => 'how-to-audit-your-website',
            'content_category_id' => $category->id,
            'content' => '<p>Useful content for an audit.</p>',
            'excerpt' => 'Useful content for an audit.',
            'status' => ContentEntry::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'schema_type' => 'BlogPosting',
            'is_featured' => true,
        ]);

        ContentEntry::create([
            'type' => 'blog',
            'title' => 'Draft article',
            'slug' => 'draft-article',
            'content_category_id' => $category->id,
            'content' => '<p>Draft only.</p>',
            'excerpt' => 'Draft only.',
            'status' => ContentEntry::STATUS_DRAFT,
            'schema_type' => 'BlogPosting',
        ]);

        $response = $this->get(route('blog.index'));

        $response->assertOk()
            ->assertSee($published->title)
            ->assertDontSee('Draft article');
    }

    public function test_article_detail_page_renders_by_slug(): void
    {
        $entry = ContentEntry::create([
            'type' => 'blog',
            'title' => 'Visibility guide',
            'slug' => 'visibility-guide',
            'content' => '<h2>Section</h2><p>Readable article body.</p>',
            'excerpt' => 'Readable article body.',
            'status' => ContentEntry::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
            'schema_type' => 'BlogPosting',
        ]);

        $this->get(route('blog.show', $entry->slug))
            ->assertOk()
            ->assertSee('Visibility guide')
            ->assertSee('Readable article body.', false);
    }

    public function test_category_listing_works(): void
    {
        $seo = $this->createCategory('SEO');
        $geo = $this->createCategory('GEO');

        ContentEntry::create([
            'type' => 'blog',
            'title' => 'SEO article',
            'slug' => 'seo-article',
            'content_category_id' => $seo->id,
            'content' => '<p>SEO body.</p>',
            'excerpt' => 'SEO body.',
            'status' => ContentEntry::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'schema_type' => 'BlogPosting',
        ]);

        ContentEntry::create([
            'type' => 'blog',
            'title' => 'GEO article',
            'slug' => 'geo-article',
            'content_category_id' => $geo->id,
            'content' => '<p>GEO body.</p>',
            'excerpt' => 'GEO body.',
            'status' => ContentEntry::STATUS_PUBLISHED,
            'published_at' => now()->subHours(2),
            'schema_type' => 'BlogPosting',
        ]);

        $this->get(route('blog.category', $seo->slug))
            ->assertOk()
            ->assertSee('SEO article')
            ->assertDontSee('GEO article');
    }

    public function test_tag_listing_works(): void
    {
        $tagAeo = $this->createTag('AEO');
        $tagGeo = $this->createTag('GEO');

        $entryAeo = ContentEntry::create([
            'type' => 'blog',
            'title' => 'AEO article',
            'slug' => 'aeo-article',
            'content' => '<p>AEO body.</p>',
            'excerpt' => 'AEO body.',
            'status' => ContentEntry::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'schema_type' => 'BlogPosting',
        ]);
        $entryAeo->tags()->attach($tagAeo->id);

        $entryGeo = ContentEntry::create([
            'type' => 'blog',
            'title' => 'GEO article',
            'slug' => 'geo-article',
            'content' => '<p>GEO body.</p>',
            'excerpt' => 'GEO body.',
            'status' => ContentEntry::STATUS_PUBLISHED,
            'published_at' => now()->subHours(2),
            'schema_type' => 'BlogPosting',
        ]);
        $entryGeo->tags()->attach($tagGeo->id);

        $this->get(route('blog.tag', $tagAeo->slug))
            ->assertOk()
            ->assertSee('AEO article')
            ->assertDontSee('GEO article');
    }

    public function test_rss_feed_returns_expected_content(): void
    {
        $category = $this->createCategory('SEO');

        ContentEntry::create([
            'type' => 'blog',
            'title' => 'Feed article',
            'slug' => 'feed-article',
            'content_category_id' => $category->id,
            'content' => '<p>Feed content.</p>',
            'excerpt' => 'Feed content.',
            'status' => ContentEntry::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'schema_type' => 'BlogPosting',
        ]);

        $response = $this->get(route('blog.feed'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->assertSee('<rss version="2.0"', false)
            ->assertSee('<title><![CDATA[Feed article]]></title>', false)
            ->assertSee('<category><![CDATA[SEO]]></category>', false);
    }

    public function test_legacy_redirect_lookup_works(): void
    {
        $entry = ContentEntry::create([
            'type' => 'blog',
            'title' => 'Fresh article',
            'slug' => 'fresh-article',
            'content' => '<p>Fresh article body.</p>',
            'excerpt' => 'Fresh article body.',
            'status' => ContentEntry::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
            'schema_type' => 'BlogPosting',
        ]);

        ContentRedirect::create([
            'content_entry_id' => $entry->id,
            'from_path' => '/Blog/old-article',
            'to_path' => $entry->publicPath(),
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/Blog/old-article')
            ->assertRedirect($entry->publicPath());
    }

    public function test_importer_dry_run_does_not_persist_content(): void
    {
        $crawler = new class extends OldBlogCrawler
        {
            public function crawl(string $baseUrl = 'https://www.quickseoanalysis.com', int $maxPages = 250): array
            {
                return [
                    'total_blogs_found' => 1,
                    'blog_urls' => ['https://www.quickseoanalysis.com/blog/test-post'],
                    'categories' => ['SEO'],
                    'tags' => ['Audit'],
                    'broken_urls' => [],
                    'duplicate_urls' => [],
                    'failed_urls' => [],
                    'visited_urls' => [$baseUrl, 'https://www.quickseoanalysis.com/blog/test-post'],
                ];
            }

            public function fetchPost(string $url): array
            {
                return [
                    'ok' => true,
                    'url' => $url,
                    'title' => 'Test post',
                    'slug' => 'test-post',
                    'legacy_url' => $url,
                    'author' => 'QSA',
                    'category' => 'SEO',
                    'tags' => ['Audit', 'SEO'],
                    'published_at' => now()->subDay()->toAtomString(),
                    'modified_at' => now()->subHours(12)->toAtomString(),
                    'excerpt' => 'Importer excerpt.',
                    'content_html' => '<article><h2>Intro</h2><p>Body</p></article>',
                    'featured_image' => 'https://www.quickseoanalysis.com/uploads/test.jpg?utm_source=test',
                    'featured_image_alt' => 'Test image',
                    'seo_title' => 'Test SEO Title',
                    'meta_description' => 'Test description',
                    'meta_keywords' => 'audit, seo',
                    'canonical' => $url.'?utm_source=test',
                    'og_title' => 'Test SEO Title',
                    'og_description' => 'Test description',
                    'og_image' => 'https://www.quickseoanalysis.com/uploads/test.jpg?gclid=123',
                    'twitter_title' => 'Test SEO Title',
                    'twitter_description' => 'Test description',
                    'twitter_image' => 'https://www.quickseoanalysis.com/uploads/test.jpg?fbclid=123',
                    'images_found' => 1,
                    'missing_metadata' => [],
                    'raw_html' => '<html><body><article><p>Body</p></article></body></html>',
                ];
            }
        };

        $importer = new OldBlogImporter($crawler);
        $summary = $importer->import(['dry_run' => true]);

        $this->assertSame(1, $summary['blogs_found']);
        $this->assertSame(1, $summary['imported']);
        $this->assertSame(0, ContentEntry::query()->count());
        $this->assertSame(0, ContentCategory::query()->count());
        $this->assertSame(0, ContentTag::query()->count());
        $this->assertSame(0, ContentRedirect::query()->count());
    }

    public function test_importer_is_idempotent_for_existing_content(): void
    {
        $crawler = new class extends OldBlogCrawler
        {
            public function crawl(string $baseUrl = 'https://www.quickseoanalysis.com', int $maxPages = 250): array
            {
                return [
                    'total_blogs_found' => 1,
                    'blog_urls' => ['https://www.quickseoanalysis.com/blog/idempotent-post'],
                    'categories' => ['SEO'],
                    'tags' => ['Audit'],
                    'broken_urls' => [],
                    'duplicate_urls' => [],
                    'failed_urls' => [],
                    'visited_urls' => [$baseUrl, 'https://www.quickseoanalysis.com/blog/idempotent-post'],
                ];
            }

            public function fetchPost(string $url): array
            {
                return [
                    'ok' => true,
                    'url' => $url,
                    'title' => 'Idempotent post',
                    'slug' => 'idempotent-post',
                    'legacy_url' => $url,
                    'author' => 'QSA',
                    'category' => 'SEO',
                    'tags' => ['Audit'],
                    'published_at' => '2026-01-01T10:00:00+00:00',
                    'modified_at' => '2026-01-01T10:00:00+00:00',
                    'excerpt' => 'Repeatable excerpt.',
                    'content_html' => '<article><h2>Intro</h2><p>Repeatable body</p></article>',
                    'featured_image' => 'https://www.quickseoanalysis.com/uploads/test.jpg',
                    'featured_image_alt' => 'Test image',
                    'seo_title' => 'Idempotent SEO Title',
                    'meta_description' => 'Repeatable description',
                    'meta_keywords' => 'audit',
                    'canonical' => $url,
                    'og_title' => 'Idempotent SEO Title',
                    'og_description' => 'Repeatable description',
                    'og_image' => 'https://www.quickseoanalysis.com/uploads/test.jpg',
                    'twitter_title' => 'Idempotent SEO Title',
                    'twitter_description' => 'Repeatable description',
                    'twitter_image' => 'https://www.quickseoanalysis.com/uploads/test.jpg',
                    'images_found' => 1,
                    'missing_metadata' => [],
                    'raw_html' => '<html><body><article><p>Repeatable body</p></article></body></html>',
                ];
            }
        };

        $importer = new OldBlogImporter($crawler);

        $first = $importer->import(['dry_run' => false]);
        $second = $importer->import(['dry_run' => false]);

        $this->assertSame(1, $first['created']);
        $this->assertSame(1, ContentEntry::query()->count());
        $this->assertSame(1, ContentCategory::query()->count());
        $this->assertSame(1, ContentTag::query()->count());
        $this->assertSame(1, ContentRedirect::query()->count());

        $this->assertSame(1, $second['skipped']);
        $this->assertSame(1, ContentEntry::query()->count());
        $this->assertSame(1, ContentCategory::query()->count());
        $this->assertSame(1, ContentTag::query()->count());
        $this->assertSame(1, ContentRedirect::query()->count());
    }

    private function createCategory(string $name): ContentCategory
    {
        return ContentCategory::create([
            'type' => 'blog',
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'is_active' => true,
        ]);
    }

    private function createTag(string $name): ContentTag
    {
        return ContentTag::create([
            'type' => 'blog',
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'is_active' => true,
        ]);
    }
}
