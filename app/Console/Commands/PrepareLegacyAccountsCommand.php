<?php

namespace App\Console\Commands;

use App\Models\LegacyAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PrepareLegacyAccountsCommand extends Command
{
    protected $signature = 'qsa:prepare-legacy-accounts
        {--path=storage/app/legacy-import : Directory containing exported CSV files}
        {--dry-run : Preview without writing data}
        {--limit=0 : Optional maximum number of accounts to process}';

    protected $description = 'Create or refresh pending legacy account records without creating active SaaS users, companies, workspaces or projects.';

    private const SOURCE = 'dotnet_qsa';

    private array $summary = [
        'legacy_users_found' => 0,
        'scan_linked_legacy_accounts' => 0,
        'invalid_or_spam_skipped' => 0,
        'duplicate_email_rows' => 0,
        'duplicate_client_ids' => 0,
        'legacy_accounts_to_create' => 0,
        'legacy_accounts_to_update' => 0,
        'legacy_accounts_created' => 0,
        'legacy_accounts_updated' => 0,
        'existing_prepared_users_detected' => 0,
        'legacy_text_sanitized' => 0,
        'fallback_names_used' => 0,
    ];

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->option('path'));
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        if (! is_dir($path)) {
            $this->error('Legacy import folder not found: '.$path);

            return self::FAILURE;
        }

        $usersFile = $path.'/app_users.csv';
        $scansFile = $path.'/analyze_urls.csv';

        if (! is_file($usersFile) || ! is_file($scansFile)) {
            $this->error('Expected app_users.csv and analyze_urls.csv in '.$path);

            return self::FAILURE;
        }

        $users = $this->readCsv($usersFile);
        $scans = $this->readCsv($scansFile);
        $scanStats = $this->scanStats($scans);
        $legacyIdCounts = $this->countBy($users, 'ID');
        $scanLinkedRows = array_values(array_filter($users, function (array $row) use ($scanStats): bool {
            $legacyId = $this->value($row, 'ID');

            return $legacyId !== '' && isset($scanStats[$legacyId]);
        }));
        $emailCounts = $this->countBy($scanLinkedRows, 'Email', true);
        $previewRows = [];
        $processed = 0;

        $this->summary['legacy_users_found'] = count($users);
        $this->summary['scan_linked_legacy_accounts'] = count($scanStats);
        $this->summary['duplicate_client_ids'] = count(array_filter($legacyIdCounts, fn (int $count): bool => $count > 1));
        $this->summary['duplicate_email_rows'] = count(array_filter($scanLinkedRows, function (array $row) use ($emailCounts): bool {
            $email = strtolower($this->normalizedEmail($this->value($row, 'Email')));

            return $email !== '' && (($emailCounts[$email] ?? 0) > 1);
        }));
        $this->summary['existing_prepared_users_detected'] = User::query()->where('legacy_source', self::SOURCE)->count();

        foreach ($scanLinkedRows as $row) {
            $legacyId = $this->value($row, 'ID');
            $email = $this->normalizedEmail($this->value($row, 'Email'));
            $fallbackNameUsed = false;
            $name = $this->legacyName($row, $legacyId, $fallbackNameUsed);
            $skipReason = $this->skipReason($name, $email);

            if ($skipReason !== null) {
                $this->summary['invalid_or_spam_skipped']++;
                continue;
            }

            if ($fallbackNameUsed) {
                $this->summary['fallback_names_used']++;
            }

            $existing = LegacyAccount::query()
                ->where('legacy_source', self::SOURCE)
                ->where('legacy_id', $legacyId)
                ->first();

            $action = $existing ? 'update' : 'create';
            $reasonIncluded = 'Scan-linked legacy account ready for claim.';
            $loginProvider = $this->cleanNullableText($this->value($row, 'LoginProvider')) ?: 'N/A';

            $previewRows[] = [
                $legacyId,
                $name,
                $email,
                $loginProvider,
                $scanStats[$legacyId]['scan_count'],
                $scanStats[$legacyId]['report_count'],
                $scanStats[$legacyId]['last_activity_at']?->timezone(config('app.timezone'))->format('d M Y, h:i A').' IST',
                $this->safeLegacyFilename($this->value($row, 'CompanyLogo')) ? 'Yes' : 'No',
                $action,
                $reasonIncluded,
            ];

            if ($existing) {
                $this->summary['legacy_accounts_to_update']++;
            } else {
                $this->summary['legacy_accounts_to_create']++;
            }

            if (! $dryRun) {
                $this->syncLegacyAccount($row, $legacyId, $name, $email, $scanStats[$legacyId], $existing, ($emailCounts[$email] ?? 0) > 1);

                if ($existing) {
                    $this->summary['legacy_accounts_updated']++;
                } else {
                    $this->summary['legacy_accounts_created']++;
                }
            }

            $processed++;

            if ($limit > 0 && $processed >= $limit) {
                break;
            }
        }

        if ($dryRun) {
            $this->warn('Dry-run only. No users, companies, workspaces, projects or scan ownership links were changed.');
        }

        if ($previewRows !== []) {
            $this->table(
                ['Legacy ID', 'Name', 'Email', 'Login Provider', 'Scans', 'Reports', 'Last Activity', 'Company Logo', 'Action', 'Reason Included'],
                array_slice($previewRows, 0, 30)
            );
        }

        if ($this->summary['existing_prepared_users_detected'] > 0) {
            $this->warn('Previously prepared active SaaS users were detected from an earlier run. This command does not delete them. Review them manually before any cleanup.');
        }

        $this->table(
            ['Metric', 'Count'],
            collect($this->summary)->map(fn ($count, $metric) => [str_replace('_', ' ', $metric), $count])->values()->all()
        );

        return self::SUCCESS;
    }

    private function syncLegacyAccount(
        array $row,
        string $legacyId,
        string $name,
        string $email,
        array $scanStats,
        ?LegacyAccount $existing,
        bool $duplicateEmail
    ): void {
        $registeredAt = $this->dateValue($row, 'AddedOn');
        $metadata = [
            'source' => 'csv_upgrade_preparation',
            'claim_message' => 'We found your previous Quick SEO Analysis account.',
            'legacy_login_provider' => $this->cleanNullableText($this->value($row, 'LoginProvider')),
            'legacy_phone' => $this->cleanNullableText($this->value($row, 'Phone')),
            'legacy_username' => $this->cleanNullableText($this->value($row, 'UserName')),
            'legacy_company_logo' => $this->safeLegacyFilename($this->value($row, 'CompanyLogo')),
            'legacy_pdf_logo' => $this->safeLegacyFilename($this->value($row, 'logopinpdf')),
            'legacy_company_description' => $this->cleanNullableText($this->value($row, 'CompanyDescription')),
            'email_domain' => $this->domainFromEmail($email),
            'duplicate_email_in_source' => $duplicateEmail,
            'source_added_on' => $this->cleanNullableText($this->value($row, 'AddedOn')),
        ];

        $payload = [
            'legacy_source' => self::SOURCE,
            'legacy_id' => $legacyId,
            'name' => $name,
            'email' => $email,
            'status' => $existing?->isClaimed() ? LegacyAccount::STATUS_CLAIMED : LegacyAccount::STATUS_PENDING_CLAIM,
            'scan_count' => $scanStats['scan_count'],
            'report_count' => $scanStats['report_count'],
            'registered_at' => $registeredAt,
            'last_activity_at' => $scanStats['last_activity_at'],
            'metadata' => $metadata,
        ];

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return;
        }

        LegacyAccount::create($payload);
    }

    private function scanStats(array $scans): array
    {
        $stats = [];

        foreach ($scans as $row) {
            $clientId = $this->value($row, 'ClientId');

            if ($clientId === '') {
                continue;
            }

            $createdAt = $this->dateValue($row, 'CreatedDate');
            $hasReport = $this->value($row, 'SavedReport') !== '';

            $stats[$clientId] ??= ['scan_count' => 0, 'report_count' => 0, 'last_activity_at' => null];
            $stats[$clientId]['scan_count']++;
            $stats[$clientId]['report_count'] += $hasReport ? 1 : 0;

            if ($createdAt && (! $stats[$clientId]['last_activity_at'] || $createdAt->greaterThan($stats[$clientId]['last_activity_at']))) {
                $stats[$clientId]['last_activity_at'] = $createdAt;
            }
        }

        return $stats;
    }

    private function readCsv(string $file): array
    {
        $handle = new \SplFileObject($file, 'r');
        $handle->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $headers = array_map(fn ($header) => trim((string) $header), $handle->fgetcsv() ?: []);
        $rows = [];

        while (! $handle->eof()) {
            $values = $handle->fgetcsv();

            if (! $values || $values === [null] || array_filter($values, fn ($value) => $value !== null && $value !== '') === []) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = isset($values[$index]) ? trim((string) $values[$index]) : '';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function countBy(array $rows, string $key, bool $lowercase = false): array
    {
        $counts = [];

        foreach ($rows as $row) {
            $value = $this->value($row, $key);

            if ($lowercase) {
                $value = $this->normalizedEmail($value);
            }

            if ($value === '') {
                continue;
            }

            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        return $counts;
    }

    private function skipReason(string $name, string $email): ?string
    {
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'invalid email';
        }

        if (trim($name) === '') {
            return 'missing name';
        }

        $combined = strtolower($name.' '.$email);

        foreach (['http://', 'https://', 'www.', '.php', '<script'] as $needle) {
            if (str_contains($combined, $needle)) {
                return 'spam-like account';
            }
        }

        if (preg_match('/\b(test|testuser|testaccount|mailinator|10minutemail)\b/', $combined)) {
            return 'test or disposable account';
        }

        return null;
    }

    private function legacyName(array $row, string $legacyId, bool &$fallbackUsed = false): string
    {
        $candidate = $this->cleanNullableText($this->value($row, 'Name'))
            ?? $this->cleanNullableText($this->value($row, 'UserName'));

        if ($candidate === null || in_array(strtolower($candidate), ['null', 'n/a', 'na'], true)) {
            $fallbackUsed = true;

            return 'Legacy User '.$legacyId;
        }

        return $candidate;
    }

    private function value(array $row, string $key): string
    {
        return trim((string) ($row[$key] ?? ''));
    }

    private function domainFromEmail(string $email): ?string
    {
        return str_contains($email, '@') ? strtolower(substr(strrchr($email, '@'), 1)) : null;
    }

    private function safeLegacyFilename(string $filename): ?string
    {
        $filename = $this->cleanNullableText($filename);

        if ($filename === null) {
            return null;
        }

        $basename = basename(str_replace('\\', '/', $filename));
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $basename : null;
    }

    private function normalizedEmail(string $value): string
    {
        $cleaned = $this->cleanNullableText($value);

        return $cleaned === null ? '' : strtolower($cleaned);
    }

    private function cleanNullableText(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $original = $value;

        if (function_exists('mb_check_encoding') && ! mb_check_encoding($value, 'UTF-8')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1, ASCII');
            if (is_string($converted) && $converted !== '') {
                $value = $converted;
            }
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if (is_string($converted) && $converted !== '') {
                $value = $converted;
            }
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $value) ?? '';
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        if ($value === '') {
            return null;
        }

        if ($value !== $original) {
            $this->summary['legacy_text_sanitized']++;
        }

        return $value;
    }

    private function dateValue(array $row, string $key): ?Carbon
    {
        $value = $this->cleanNullableText($this->value($row, $key));

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
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
