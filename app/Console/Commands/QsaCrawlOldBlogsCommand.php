<?php

namespace App\Console\Commands;

use App\Services\Content\OldBlogCrawler;
use Illuminate\Console\Command;

class QsaCrawlOldBlogsCommand extends Command
{
    protected $signature = 'qsa:crawl-old-blogs
        {--dry-run}
        {--base-url=https://www.quickseoanalysis.com}
        {--limit=25}
        {--timeout=10}
        {--max-pages=25}
        {--max-depth=2}';

    protected $description = 'Crawl the old quickseoanalysis.com blog and report discovered blog URLs, categories, tags, and failures.';

    public function handle(OldBlogCrawler $crawler): int
    {
        $baseUrl = (string) $this->option('base-url');
        $timeout = max(1, (int) $this->option('timeout'));
        $maxPages = max(1, (int) $this->option('max-pages'));
        $maxDepth = max(0, (int) $this->option('max-depth'));
        $limit = max(1, (int) $this->option('limit'));

        $this->components->info(sprintf(
            'Starting old blog crawl: base=%s, limit=%d, timeout=%ds, max-pages=%d, max-depth=%d',
            $baseUrl,
            $limit,
            $timeout,
            $maxPages,
            $maxDepth
        ));

        $lastProgressAt = microtime(true);
        $result = $crawler->crawl(
            $baseUrl,
            $maxPages,
            $timeout,
            $maxDepth,
            function (string $event, array $payload) use (&$lastProgressAt): void {
                $now = microtime(true);

                if ($event === 'failed') {
                    $this->warn(sprintf(
                        'Failed: %s [%s]',
                        $payload['url'] ?? 'unknown',
                        $payload['status'] ?? 'n/a'
                    ));
                    return;
                }

                if ($event === 'discovered_post') {
                    $this->line(sprintf(
                        'Discovered post %d: %s',
                        $payload['posts'] ?? 0,
                        $payload['url'] ?? 'unknown'
                    ));
                    return;
                }

                if ($event === 'visiting' && ($now - $lastProgressAt) >= 0.5) {
                    $lastProgressAt = $now;
                    $this->line(sprintf(
                        'Visiting #%d (depth %d, queue %d, posts %d): %s',
                        $payload['visited'] ?? 0,
                        $payload['depth'] ?? 0,
                        $payload['queue'] ?? 0,
                        $payload['posts'] ?? 0,
                        $payload['url'] ?? 'unknown'
                    ));
                }
            }
        );

        if (count($result['blog_urls']) > $limit) {
            $result['blog_urls'] = array_slice($result['blog_urls'], 0, $limit);
            $result['total_blogs_found'] = count($result['blog_urls']);
        }

        $this->info('Old blog crawl complete.');
        $this->table(['Metric', 'Count'], [
            ['Visited URLs', count($result['visited_urls'])],
            ['Total blogs found', $result['total_blogs_found']],
            ['Categories found', count($result['categories'])],
            ['Tags found', count($result['tags'])],
            ['Broken/failed URLs', count($result['failed_urls'])],
            ['Duplicate URLs', count($result['duplicate_urls'])],
            ['Depth-skipped URLs', count($result['skipped_depth_urls'] ?? [])],
        ]);

        $this->line('Blog URLs:');
        foreach ($result['blog_urls'] as $url) {
            $this->line('- '.$url);
        }

        if ($result['categories'] !== []) {
            $this->newLine();
            $this->line('Categories: '.implode(', ', $result['categories']));
        }

        if ($result['tags'] !== []) {
            $this->newLine();
            $this->line('Tags: '.implode(', ', $result['tags']));
        }

        if ($result['failed_urls'] !== []) {
            $this->newLine();
            $this->warn('Failed URLs:');
            foreach ($result['failed_urls'] as $failed) {
                $this->line('- '.$failed['url'].' ['.($failed['status'] ?? 'n/a').']');
            }
        }

        return self::SUCCESS;
    }
}
