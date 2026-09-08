<?php

namespace App\Console\Commands;

use App\Services\Content\KnownLegacyBlogImporter;
use Illuminate\Console\Command;

class ImportOldBlogsCommand extends Command
{
    protected $signature = 'qsa:import-old-blogs {--dry-run} {--urls-file=} {--timeout=10}';

    protected $description = 'Import the fixed legacy quickseoanalysis.com blog URL list into the QSA content module.';

    public function handle(KnownLegacyBlogImporter $importer): int
    {
        $summary = $importer->import([
            'dry_run' => (bool) $this->option('dry-run'),
            'urls_file' => $this->option('urls-file'),
            'timeout' => (int) $this->option('timeout'),
        ]);

        $this->table(['Metric', 'Count'], [
            ['URLs configured', $summary['urls_configured']],
            ['Expected URLs', $summary['expected_count']],
            ['Processed', $summary['processed']],
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

        if (($summary['urls_configured'] ?? 0) !== ($summary['expected_count'] ?? 13)) {
            $this->warn('Configured URL count does not yet match the expected legacy total.');
        }

        if (! empty($summary['failures'])) {
            $this->warn('Failures:');
            foreach ($summary['failures'] as $failure) {
                $this->line('- '.$failure['url'].' => '.$failure['message']);
            }
        }

        $this->info($this->option('dry-run')
            ? 'Dry-run only. No blog rows were created or updated.'
            : 'Controlled legacy blog import finished.');

        return self::SUCCESS;
    }
}
