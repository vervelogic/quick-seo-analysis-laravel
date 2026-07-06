<?php

namespace App\Console\Commands;

use App\Services\Content\OldBlogImporter;
use Illuminate\Console\Command;

class ImportOldBlogsCommand extends Command
{
    protected $signature = 'qsa:import-old-blogs
        {--dry-run}
        {--base-url=https://www.quickseoanalysis.com}
        {--limit=25}
        {--timeout=10}
        {--max-pages=25}
        {--max-depth=2}';

    protected $description = 'Import old quickseoanalysis.com blog content into the QSA content module.';

    public function handle(OldBlogImporter $importer): int
    {
        $summary = $importer->import([
            'dry_run' => (bool) $this->option('dry-run'),
            'base_url' => (string) $this->option('base-url'),
            'limit' => $this->option('limit'),
            'timeout' => (int) $this->option('timeout'),
            'max_pages' => (int) $this->option('max-pages'),
            'max_depth' => (int) $this->option('max-depth'),
        ]);

        $this->table(['Metric', 'Count'], [
            ['Total discovered', $summary['total_discovered']],
            ['Blogs selected', $summary['blogs_found']],
            ['Crawled', $summary['crawled']],
            ['Imported', $summary['imported']],
            ['Created', $summary['created']],
            ['Updated', $summary['updated']],
            ['Skipped', $summary['skipped']],
            ['Failed', $summary['failed']],
            ['Redirects created', $summary['redirects_created']],
            ['Categories created', $summary['categories_created']],
            ['Tags created', $summary['tags_created']],
            ['Images found', $summary['images_found']],
            ['Missing images', $summary['missing_images']],
            ['Missing metadata', $summary['missing_metadata']],
            ['Execution time (s)', $summary['execution_time_seconds']],
        ]);

        if (! empty($summary['failed_urls'])) {
            $this->warn('Failed URLs:');
            foreach ($summary['failed_urls'] as $url) {
                $this->line('- '.$url);
            }
        }

        $this->info($this->option('dry-run')
            ? 'Dry-run only. No blog rows were created or updated.'
            : 'Blog import finished.');

        return self::SUCCESS;
    }
}
