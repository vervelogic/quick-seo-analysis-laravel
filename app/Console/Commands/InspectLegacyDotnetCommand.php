<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InspectLegacyDotnetCommand extends Command
{
    protected $signature = 'qsa:inspect-legacy-dotnet {--path=storage/app/legacy-import : Directory containing exported CSV files}';

    protected $description = 'Inspect exported legacy .NET QSA CSV files before import.';

    private const REQUIRED = [
        'app_users.csv' => ['ID', 'Name', 'Email', 'Password', 'Phone', 'AddedOn', 'LoginProvider', 'CompanyLogo', 'logopinpdf', 'CompanyDescription'],
        'analyze_urls.csv' => ['Id', 'PageUrl', 'CreatedDate', 'ClientId', 'SavedReport', 'SeoScore', 'AuditType'],
    ];

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->option('path'));

        $this->info('Inspecting legacy import folder: '.$path);

        if (! is_dir($path)) {
            $this->error('Folder not found. Create it and upload CSV files before importing.');

            return self::FAILURE;
        }

        foreach (self::REQUIRED as $file => $headers) {
            $this->inspectFile($path.'/'.$file, $headers, true);
        }

        $this->inspectFile($path.'/reports.csv', ['Id', 'Result', 'ClientId', 'SearchedUrl', 'SearchedOn', 'Active'], false);

        return self::SUCCESS;
    }

    private function inspectFile(string $file, array $requiredHeaders, bool $required): void
    {
        $name = basename($file);

        if (! is_file($file)) {
            $message = $required ? 'Missing required file' : 'Optional file not found';
            $this->line($message.': '.$name);

            return;
        }

        $handle = new \SplFileObject($file, 'r');
        $handle->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $headers = array_map(fn ($header) => trim((string) $header), $handle->fgetcsv() ?: []);
        $missing = array_values(array_diff($requiredHeaders, $headers));
        $rows = 0;

        while (! $handle->eof()) {
            $row = $handle->fgetcsv();
            if ($row && $row !== [null] && array_filter($row, fn ($value) => $value !== null && $value !== '') !== []) {
                $rows++;
            }
        }

        $this->line($name.': '.$rows.' data rows');

        if ($missing !== []) {
            $this->warn('  Missing headers: '.implode(', ', $missing));
        } else {
            $this->info('  Headers OK');
        }
    }

    private function resolvePath(string $path): string
    {
        $path = trim($path);

        if (str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return base_path($path);
        }

        return storage_path('app/'.ltrim($path, '/'));
    }
}
