<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\LegacyAccount;
use App\Models\Scan;
use App\Models\User;
use App\Services\Legacy\LegacyWorkspaceBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PrepareLegacyAccountsCommand extends Command
{
    protected $signature = 'qsa:prepare-legacy-accounts
        {--path=storage/app/legacy-import : Directory containing exported CSV files}
        {--dry-run : Preview without writing data}
        {--limit=0 : Optional maximum number of accounts to process}';

    protected $description = 'Create pending-claim SaaS users, companies, workspaces and domain projects for legitimate legacy QSA accounts.';

    private const SOURCE = 'dotnet_qsa';

    private array $summary = [
        'users_found' => 0,
        'scan_linked_users' => 0,
        'skipped_invalid_or_spam' => 0,
        'pending_accounts_created' => 0,
        'existing_accounts_skipped' => 0,
        'companies_created' => 0,
        'workspaces_created_or_matched' => 0,
        'scans_attached' => 0,
        'projects_created_or_matched' => 0,
    ];

    public function __construct(private readonly LegacyWorkspaceBuilder $workspaceBuilder)
    {
        parent::__construct();
    }

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
        $processed = 0;
        $previewRows = [];

        $this->summary['users_found'] = count($users);
        $this->summary['scan_linked_users'] = count($scanStats);

        foreach ($users as $row) {
            $legacyId = $this->value($row, 'ID');

            if ($legacyId === '' || ! isset($scanStats[$legacyId])) {
                continue;
            }

            $email = strtolower($this->value($row, 'Email'));
            $name = $this->legacyName($row, $legacyId);
            $skipReason = $this->skipReason($name, $email);

            if ($skipReason !== null) {
                $this->summary['skipped_invalid_or_spam']++;
                continue;
            }

            if (LegacyAccount::query()->where('legacy_source', self::SOURCE)->where('legacy_id', $legacyId)->exists()) {
                $this->summary['existing_accounts_skipped']++;
                continue;
            }

            $processed++;

            $previewRows[] = [
                $legacyId,
                $name,
                $email,
                $scanStats[$legacyId]['scan_count'],
                $scanStats[$legacyId]['report_count'],
                $scanStats[$legacyId]['last_activity_at']?->timezone(config('app.timezone'))->format('d M Y, h:i A').' IST',
                'pending_claim',
            ];

            if (! $dryRun) {
                $result = $this->createPendingAccount($row, $legacyId, $name, $email, $scanStats[$legacyId]);
                $this->summary['companies_created'] += $result['company_created'] ? 1 : 0;
                $this->summary['workspaces_created_or_matched']++;
                $this->summary['scans_attached'] += $result['scans_attached'];
                $this->summary['projects_created_or_matched'] += $result['projects_created_or_matched'];
                $this->summary['pending_accounts_created']++;
            }

            if ($limit > 0 && $processed >= $limit) {
                break;
            }
        }

        if ($dryRun) {
            $this->warn('Dry-run only. No users, companies, workspaces, projects or scan links were changed.');
        }

        if ($previewRows !== []) {
            $this->table(['Legacy ID', 'Name', 'Email', 'Scans', 'Reports', 'Last Activity', 'Claim Status'], array_slice($previewRows, 0, 30));
        }

        if ($dryRun) {
            $this->summary['pending_accounts_created'] = count($previewRows);
        }

        $this->table(['Metric', 'Count'], collect($this->summary)->map(fn ($count, $metric) => [str_replace('_', ' ', $metric), $count])->values()->all());

        return self::SUCCESS;
    }

    private function createPendingAccount(array $row, string $legacyId, string $name, string $email, array $scanStats): array
    {
        return DB::transaction(function () use ($row, $legacyId, $name, $email, $scanStats): array {
            $companyCreated = false;
            $company = Company::query()->where('domain', $this->domainFromEmail($email))->first();

            if (! $company) {
                $company = Company::create([
                    'name' => $this->companyName($name, $email),
                    'slug' => 'legacy-dotnet-'.$legacyId,
                    'domain' => $this->domainFromEmail($email),
                    'contact_name' => $name,
                    'contact_email' => $email,
                    'legacy_company_logo' => $this->safeLegacyFilename($this->value($row, 'CompanyLogo')),
                    'legacy_pdf_logo' => $this->safeLegacyFilename($this->value($row, 'logopinpdf')),
                    'legacy_company_description' => $this->cleanNullableText($this->value($row, 'CompanyDescription')),
                    'legacy_metadata' => [
                        'legacy_user_id' => $legacyId,
                        'phone' => $this->value($row, 'Phone'),
                        'login_provider' => $this->value($row, 'LoginProvider'),
                    ],
                    'created_at' => $this->dateValue($row, 'AddedOn') ?: now(),
                    'updated_at' => now(),
                ]);
                $companyCreated = true;
            }

            $workspace = $this->workspaceBuilder->ensureWorkspaceForCompany($company);
            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $user = User::create([
                    'company_id' => $company->id,
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(80)),
                    'role' => 'client',
                    'company_role' => User::COMPANY_ROLE_OWNER,
                    'is_admin' => false,
                    'email_verified_at' => null,
                    'legacy_id' => $legacyId,
                    'legacy_source' => self::SOURCE,
                    'legacy_imported_at' => now(),
                    'legacy_login_provider' => $this->cleanNullableText($this->value($row, 'LoginProvider')),
                    'invite_required' => true,
                    'legacy_metadata' => [
                        'pending_claim' => true,
                        'legacy_registration_date' => $this->value($row, 'AddedOn'),
                    ],
                    'created_at' => $this->dateValue($row, 'AddedOn') ?: now(),
                    'updated_at' => now(),
                ]);
            }

            $legacyAccount = LegacyAccount::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'workspace_id' => $workspace->id,
                'legacy_source' => self::SOURCE,
                'legacy_id' => $legacyId,
                'name' => $name,
                'email' => $email,
                'status' => LegacyAccount::STATUS_PENDING_CLAIM,
                'scan_count' => $scanStats['scan_count'],
                'report_count' => $scanStats['report_count'],
                'registered_at' => $this->dateValue($row, 'AddedOn'),
                'last_activity_at' => $scanStats['last_activity_at'],
                'metadata' => [
                    'source' => 'csv_upgrade_preparation',
                    'claim_message' => 'We found your previous Quick SEO Analysis account.',
                ],
            ]);

            $attached = $this->workspaceBuilder->attachHistoricalAssets($legacyAccount->fresh(['company', 'workspace']));

            return [
                'company_created' => $companyCreated,
                'scans_attached' => $attached['scans_attached'],
                'projects_created_or_matched' => $attached['projects_created_or_matched'],
            ];
        });
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

    private function legacyName(array $row, string $legacyId): string
    {
        $name = $this->value($row, 'Name') ?: $this->value($row, 'UserName');

        return in_array(strtolower($name), ['', 'null', 'n/a', 'na'], true) ? 'Legacy User '.$legacyId : $name;
    }

    private function value(array $row, string $key): string
    {
        return trim((string) ($row[$key] ?? ''));
    }

    private function companyName(string $name, string $email): string
    {
        $domain = $this->domainFromEmail($email);

        return $domain ? Str::headline(Str::beforeLast($domain, '.')) : $name;
    }

    private function domainFromEmail(string $email): ?string
    {
        return str_contains($email, '@') ? strtolower(Str::after($email, '@')) : null;
    }

    private function safeLegacyFilename(string $filename): ?string
    {
        $basename = basename(str_replace('\\', '/', trim($filename)));
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $basename : null;
    }

    private function cleanNullableText(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
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
