<?php

namespace App\Console\Commands;

use App\Services\Content\OldBlogCrawler;
use Illuminate\Console\Command;

class QsaCrawlOldBlogsCommand extends Command
{
    protected $signature = 'qsa:crawl-old-blogs {--dry-run} {--base-url=https://www.quickseoanalysis.com} {--max-pages=250}';

    protected $description = 'Crawl the old quickseoanalysis.com blog and report discovered blog URLs, categories, tags, and failures.';

    public function handle(OldBlogCrawler $crawler): int
    {
        $result = $crawler->crawl(
            (string) $this->option('base-url'),
            (int) $this->option('max-pages')
        );

        $this->info('Old blog crawl complete.');
        $this->table(['Metric', 'Count'], [
            ['Visited URLs', count($result['visited_urls'])],
            ['Total blogs found', $result['total_blogs_found']],
            ['Categories found', count($result['categories'])],
            ['Tags found', count($result['tags'])],
            ['Broken/failed URLs', count($result['failed_urls'])],
            ['Duplicate URLs', count($result['duplicate_urls'])],
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
