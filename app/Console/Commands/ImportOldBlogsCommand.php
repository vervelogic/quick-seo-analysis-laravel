<?php

namespace App\Console\Commands;

use App\Services\Content\OldBlogImporter;
use Illuminate\Console\Command;

class ImportOldBlogsCommand extends Command
{
    protected $signature = 'qsa:import-old-blogs {--dry-run} {--base-url=https://www.quickseoanalysis.com} {--limit=}';

    protected $description = 'Import old quickseoanalysis.com blog content into the QSA content module.';

    public function handle(OldBlogImporter $importer): int
    {
        $summary = $importer->import([
            'dry_run' => (bool) $this->option('dry-run'),
            'base_url' => (string) $this->option('base-url'),
            'limit' => $this->option('limit'),
        ]);

        $this->table(['Metric', 'Count'], [
            ['Blogs found', $summary['blogs_found']],
            ['Imported', $summary['imported']],
            ['Updated', $summary['updated']],
            ['Skipped', $summary['skipped']],
            ['Categories created', $summary['categories_created']],
            ['Tags created', $summary['tags_created']],
            ['Redirects created', $summary['redirects_created']],
            ['Failed', $summary['failed']],
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
