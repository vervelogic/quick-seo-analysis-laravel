<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\LegacyReportSnapshot;
use App\Models\Scan;
use App\Models\ScanResult;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportLegacyDotnetCommand extends Command
{
    protected $signature = 'qsa:import-legacy-dotnet
        {--path=storage/app/legacy-import : Directory containing exported CSV files}
        {--dry-run : Preview import without writing data}
        {--users : Import eligible users linked to AnalyzeUrls}
        {--scans : Import AnalyzeUrls scan history and archive SavedReport payloads}';

    protected $description = 'Import legacy .NET QSA users and scan history from CSV exports.';

    private const SOURCE = 'dotnet_qsa';

    private const REQUIRED_USER_HEADERS = ['ID', 'Name', 'Email', 'Password', 'Phone', 'AddedOn', 'LoginProvider', 'CompanyLogo', 'logopinpdf', 'CompanyDescription'];

    private const REQUIRED_SCAN_HEADERS = ['Id', 'PageUrl', 'CreatedDate', 'ClientId', 'SavedReport', 'SeoScore', 'AuditType'];

    private array $summary = [
        'users_found' => 0,
        'users_linked_to_scans' => 0,
        'users_skipped_invalid_spam' => 0,
        'users_created' => 0,
        'scans_found' => 0,
        'scans_imported' => 0,
        'scans_skipped' => 0,
        'snapshots_archived' => 0,
        'duplicate_rows_skipped' => 0,
    ];

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->option('path'));
        $dryRun = (bool) $this->option('dry-run');
        $importUsers = (bool) $this->option('users');
        $importScans = (bool) $this->option('scans');

        if (! $importUsers && ! $importScans) {
            $importUsers = true;
            $importScans = true;
        }

        if (! is_dir($path)) {
            $this->error('Legacy import folder not found: '.$path);

            return self::FAILURE;
        }

        $usersFile = $path.'/app_users.csv';
        $scansFile = $path.'/analyze_urls.csv';

        if (! $this->validateCsv($usersFile, self::REQUIRED_USER_HEADERS) || ! $this->validateCsv($scansFile, self::REQUIRED_SCAN_HEADERS)) {
            return self::FAILURE;
        }

        $scanRows = $this->readCsv($scansFile);
        $linkedClientIds = $this->linkedClientIds($scanRows);
        $this->summary['scans_found'] = count($scanRows);
        $this->summary['users_linked_to_scans'] = count($linkedClientIds);

        $this->info(($dryRun ? 'Dry-run: ' : '').'Legacy import path: '.$path);

        if ($importUsers) {
            $this->importUsers($this->readCsv($usersFile), $linkedClientIds, $dryRun);
        }

        if ($importScans) {
            $this->importScans($scanRows, $dryRun);
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], collect($this->summary)->map(fn ($count, $metric) => [str_replace('_', ' ', $metric), $count])->values()->all());

        if ($dryRun) {
            $this->warn('Dry-run only. No database rows were created or updated.');
        }

        return self::SUCCESS;
    }

    private function importUsers(array $rows, array $linkedClientIds, bool $dryRun): void
    {
        foreach ($rows as $row) {
            $this->summary['users_found']++;
            $legacyId = $this->value($row, 'ID');

            if ($legacyId === '' || ! in_array($legacyId, $linkedClientIds, true)) {
                continue;
            }

            $email = strtolower($this->value($row, 'Email'));
            $name = trim($this->value($row, 'Name')) ?: trim($this->value($row, 'UserName')) ?: 'Legacy User '.$legacyId;

            if (! $this->validEmail($email) || $this->looksSpammy($name, $email)) {
                $this->summary['users_skipped_invalid_spam']++;
                $this->warn('Skipping legacy user '.$legacyId.': invalid or spam-like email/name.');
                continue;
            }

            if (User::where('legacy_source', self::SOURCE)->where('legacy_id', $legacyId)->exists()) {
                $this->summary['duplicate_rows_skipped']++;
                continue;
            }

            if (User::where('email', $email)->exists()) {
                $this->summary['duplicate_rows_skipped']++;
                $this->warn('Skipping legacy user '.$legacyId.': email already exists.');
                continue;
            }

            if ($dryRun) {
                $this->summary['users_created']++;
                continue;
            }

            DB::transaction(function () use ($row, $legacyId, $email, $name): void {
                $company = Company::create([
                    'name' => $this->companyName($name, $email),
                    'slug' => 'legacy-dotnet-'.$legacyId,
                    'domain' => $this->domainFromEmail($email),
                    'legacy_company_logo' => $this->safeLegacyFilename($this->value($row, 'CompanyLogo')),
                    'legacy_pdf_logo' => $this->safeLegacyFilename($this->value($row, 'logopinpdf')),
                    'legacy_company_description' => $this->cleanNullableText($this->value($row, 'CompanyDescription')),
                    'legacy_metadata' => [
                        'phone' => $this->value($row, 'Phone'),
                        'contact_no' => $this->value($row, 'ContactNo'),
                        'city' => $this->value($row, 'City'),
                        'state' => $this->value($row, 'State'),
                        'country' => $this->value($row, 'Country'),
                        'country_code' => $this->value($row, 'countrycode'),
                    ],
                    'created_at' => $this->dateValue($row, 'AddedOn') ?: now(),
                    'updated_at' => now(),
                ]);

                User::create([
                    'company_id' => $company->id,
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(80)),
                    'role' => 'legacy',
                    'is_admin' => false,
                    'email_verified_at' => null,
                    'legacy_id' => $legacyId,
                    'legacy_source' => self::SOURCE,
                    'legacy_imported_at' => now(),
                    'legacy_login_provider' => $this->cleanNullableText($this->value($row, 'LoginProvider')),
                    'invite_required' => true,
                    'legacy_metadata' => [
                        'login_provider_id' => $this->value($row, 'LoginProviderId'),
                        'device' => $this->value($row, 'Device'),
                        'email_confirmed' => $this->value($row, 'EmailConfirmed'),
                        'valid_till' => $this->value($row, 'ValidTill'),
                        'token_id_present' => $this->value($row, 'TokenId') !== '',
                        'old_username' => $this->value($row, 'UserName'),
                        'is_system_account' => $this->value($row, 'IsSystemAccount'),
                    ],
                    'created_at' => $this->dateValue($row, 'AddedOn') ?: now(),
                    'updated_at' => now(),
                ]);
            });

            $this->summary['users_created']++;
        }
    }

    private function importScans(array $rows, bool $dryRun): void
    {
        foreach ($rows as $row) {
            $legacyId = $this->value($row, 'Id');
            $pageUrl = $this->value($row, 'PageUrl');
            $normalizedUrl = $this->normalizeUrl($pageUrl);

            if ($legacyId === '' || $normalizedUrl === null) {
                $this->summary['scans_skipped']++;
                $this->warn('Skipping scan row: missing legacy Id or invalid PageUrl.');
                continue;
            }

            if (Scan::where('legacy_source', self::SOURCE)->where('legacy_id', $legacyId)->exists()) {
                $this->summary['duplicate_rows_skipped']++;
                continue;
            }

            $legacyClientId = $this->value($row, 'ClientId');
            $user = $legacyClientId !== ''
                ? User::where('legacy_source', self::SOURCE)->where('legacy_id', $legacyClientId)->first()
                : null;
            $createdAt = $this->dateValue($row, 'CreatedDate') ?: now();
            $legacyScore = $this->scoreValue($this->value($row, 'SeoScore'));
            $payload = $this->value($row, 'SavedReport');

            if ($dryRun) {
                $this->summary['scans_imported']++;
                if ($payload !== '') {
                    $this->summary['snapshots_archived']++;
                }
                continue;
            }

            DB::transaction(function () use ($row, $legacyId, $pageUrl, $normalizedUrl, $legacyClientId, $user, $createdAt, $legacyScore, $payload): void {
                $scan = Scan::create([
                    'company_id' => $user?->company_id,
                    'url' => $pageUrl,
                    'normalized_url' => $normalizedUrl,
                    'scan_mode' => 'legacy_archive',
                    'status' => 'legacy_archived',
                    'started_at' => $createdAt,
                    'completed_at' => $createdAt,
                    'legacy_id' => $legacyId,
                    'legacy_source' => self::SOURCE,
                    'legacy_client_id' => $legacyClientId ?: null,
                    'legacy_audit_type' => $this->cleanNullableText($this->value($row, 'AuditType')),
                    'legacy_score' => $legacyScore,
                    'legacy_created_at' => $createdAt,
                    'normalized_domain' => $this->domainFromUrl($normalizedUrl),
                    'created_at' => $createdAt,
                    'updated_at' => now(),
                ]);

                ScanResult::updateOrCreate(
                    ['scan_id' => $scan->id],
                    [
                        'score' => $legacyScore ?? 0,
                        'is_reachable' => false,
                        'uses_https' => parse_url($normalizedUrl, PHP_URL_SCHEME) === 'https',
                        'recommendations' => [],
                        'checks' => [],
                        'raw' => [
                            'legacy_source' => self::SOURCE,
                            'legacy_id' => $legacyId,
                            'legacy_client_id' => $legacyClientId ?: null,
                            'legacy_audit_type' => $this->value($row, 'AuditType'),
                            'legacy_score' => $legacyScore,
                            'archived_payload_stored_separately' => $payload !== '',
                        ],
                        'score_breakdown' => [
                            'legacy_score' => $legacyScore,
                        ],
                        'created_at' => $createdAt,
                        'updated_at' => now(),
                    ]
                );

                if ($payload !== '') {
                    $this->archivePayload($scan, $user, $row, $payload, $createdAt);
                }
            });

            $this->summary['scans_imported']++;
        }
    }

    private function archivePayload(Scan $scan, ?User $user, array $row, string $payload, Carbon $createdAt): void
    {
        $hash = hash('sha256', $payload);

        if (LegacyReportSnapshot::where('legacy_source', self::SOURCE)->where('payload_hash', $hash)->exists()) {
            $this->summary['duplicate_rows_skipped']++;
            return;
        }

        LegacyReportSnapshot::create([
            'scan_id' => $scan->id,
            'user_id' => $user?->id,
            'legacy_source' => self::SOURCE,
            'legacy_table' => 'AnalyzeUrls',
            'legacy_id' => $this->value($row, 'Id'),
            'legacy_client_id' => $this->value($row, 'ClientId') ?: null,
            'source_url' => $this->value($row, 'PageUrl') ?: null,
            'payload' => $payload,
            'payload_hash' => $hash,
            'metadata' => [
                'audit_type' => $this->value($row, 'AuditType'),
                'seo_score' => $this->value($row, 'SeoScore'),
                'saved_report_length' => strlen($payload),
            ],
            'legacy_created_at' => $createdAt,
        ]);

        $this->summary['snapshots_archived']++;
    }

    private function validateCsv(string $file, array $requiredHeaders): bool
    {
        if (! is_file($file)) {
            $this->error('Missing required CSV: '.basename($file));
            return false;
        }

        $handle = new \SplFileObject($file, 'r');
        $handle->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $headers = array_map(fn ($header) => trim((string) $header), $handle->fgetcsv() ?: []);
        $missing = array_values(array_diff($requiredHeaders, $headers));

        if ($missing !== []) {
            $this->error(basename($file).' is missing headers: '.implode(', ', $missing));
            return false;
        }

        return true;
    }

    private function readCsv(string $file): array
    {
        $handle = new \SplFileObject($file, 'r');
        $handle->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $headers = array_map(fn ($header) => trim((string) $header), $handle->fgetcsv() ?: []);
        $rows = [];
        $seen = [];

        while (! $handle->eof()) {
            $values = $handle->fgetcsv();

            if (! $values || $values === [null] || array_filter($values, fn ($value) => $value !== null && $value !== '') === []) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = isset($values[$index]) ? trim((string) $values[$index]) : '';
            }

            $legacyId = $row['Id'] ?? $row['ID'] ?? null;
            $dedupeKey = basename($file).':'.($legacyId ?: md5(json_encode($row)));

            if (isset($seen[$dedupeKey])) {
                $this->summary['duplicate_rows_skipped']++;
                continue;
            }

            $seen[$dedupeKey] = true;
            $rows[] = $row;
        }

        return $rows;
    }

    private function linkedClientIds(array $scanRows): array
    {
        return array_values(array_unique(array_filter(array_map(fn ($row) => $this->value($row, 'ClientId'), $scanRows))));
    }

    private function value(array $row, string $key): string
    {
        return trim((string) ($row[$key] ?? ''));
    }

    private function validEmail(string $email): bool
    {
        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function looksSpammy(string $name, string $email): bool
    {
        $value = strtolower($name.' '.$email);

        foreach (['http://', 'https://', 'www.', '.php', '.gif', '.jpg', '.jpeg', '.png', '<script'] as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function safeLegacyFilename(string $filename): ?string
    {
        $filename = trim($filename);

        if ($filename === '') {
            return null;
        }

        $basename = basename(str_replace('\\', '/', $filename));
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $basename : null;
    }

    private function cleanNullableText(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function companyName(string $name, string $email): string
    {
        $domain = $this->domainFromEmail($email);

        return $domain ? str($domain)->beforeLast('.')->headline()->toString() : $name;
    }

    private function domainFromEmail(string $email): ?string
    {
        if (! str_contains($email, '@')) {
            return null;
        }

        return strtolower(str($email)->after('@')->toString());
    }

    private function normalizeUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$path.$query;
    }

    private function domainFromUrl(string $url): ?string
    {
        return parse_url($url, PHP_URL_HOST) ?: null;
    }

    private function scoreValue(string $score): ?int
    {
        if ($score === '' || ! is_numeric($score)) {
            return null;
        }

        return max(0, min(100, (int) round((float) $score)));
    }

    private function dateValue(array $row, string $key): ?Carbon
    {
        $value = $this->value($row, $key);

        if ($value === '') {
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
