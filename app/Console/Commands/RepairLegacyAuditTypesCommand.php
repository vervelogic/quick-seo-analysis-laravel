<?php

namespace App\Console\Commands;

use App\Models\Scan;
use Illuminate\Console\Command;

class RepairLegacyAuditTypesCommand extends Command
{
    protected $signature = 'qsa:repair-legacy-audit-types {--dry-run : Preview repairs without updating scans}';

    protected $description = 'Repair corrupted legacy Desktop/Mobile audit type values imported from CSV archives.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $checked = 0;
        $wouldRepair = 0;
        $repaired = 0;
        $alreadyClean = 0;

        $rows = [];

        Scan::query()
            ->whereNotNull('legacy_source')
            ->with('legacySnapshot')
            ->orderBy('id')
            ->chunkById(200, function ($scans) use ($dryRun, &$checked, &$wouldRepair, &$repaired, &$alreadyClean, &$rows): void {
                foreach ($scans as $scan) {
                    $checked++;
                    $current = $this->cleanAuditType($scan->legacy_audit_type);

                    if ($this->isValidAuditType($current)) {
                        $alreadyClean++;
                        continue;
                    }

                    $newValue = $this->inferAuditType($scan);
                    $wouldRepair++;

                    if (! $dryRun) {
                        $scan->forceFill(['legacy_audit_type' => $newValue])->save();
                        $repaired++;
                    }

                    if (count($rows) < 25) {
                        $rows[] = [
                            $scan->id,
                            $scan->legacy_id,
                            $scan->normalized_domain ?: parse_url((string) $scan->normalized_url, PHP_URL_HOST),
                            $scan->legacy_audit_type ?: 'NULL',
                            $newValue ?: 'Unknown',
                        ];
                    }
                }
            });

        $this->info(($dryRun ? 'Dry-run: ' : '').'Legacy audit type repair scan complete.');

        if ($rows !== []) {
            $this->table(['Scan ID', 'Legacy ID', 'Domain', 'Current', 'Repair To'], $rows);
        }

        $this->table(['Metric', 'Count'], [
            ['legacy scans checked', $checked],
            ['already Desktop/Mobile', $alreadyClean],
            [$dryRun ? 'would repair' : 'repaired', $dryRun ? $wouldRepair : $repaired],
            ['set to Unknown/null', $wouldRepair],
        ]);

        if ($dryRun) {
            $this->warn('Dry-run only. No legacy scan rows were updated.');
        }

        return self::SUCCESS;
    }

    private function inferAuditType(Scan $scan): ?string
    {
        $candidates = [
            $scan->legacy_audit_type,
            $scan->legacySnapshot?->metadata['audit_type'] ?? null,
            $scan->legacySnapshot?->payload ?? null,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->cleanAuditType((string) $candidate);

            if ($this->isValidAuditType($normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    private function cleanAuditType(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/\bdesktop\b/i', $value)) {
            return 'Desktop';
        }

        if (preg_match('/\bmobile\b/i', $value)) {
            return 'Mobile';
        }

        return $value;
    }

    private function isValidAuditType(?string $value): bool
    {
        return in_array($value, ['Desktop', 'Mobile'], true);
    }
}
