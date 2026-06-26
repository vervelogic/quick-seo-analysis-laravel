<?php

namespace App\Services\Legacy;

use App\Models\Company;
use App\Models\LegacyAccount;
use App\Models\LegacyReportSnapshot;
use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LegacyWorkspaceBuilder
{
    public const SOURCE = 'dotnet_qsa';

    public function ensureWorkspaceForCompany(Company $company): Workspace
    {
        return $company->workspaces()->firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default Workspace',
                'status' => 'active',
                'settings' => [
                    'created_for' => 'legacy_account_upgrade',
                ],
            ]
        );
    }

    public function ensureProjectForDomain(Company $company, Workspace $workspace, string $domain): Project
    {
        $domain = strtolower(trim($domain));
        $name = $domain ?: 'Legacy Project';
        $slug = Str::slug($domain ?: 'legacy-project') ?: 'legacy-project';

        return Project::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'normalized_domain' => $domain ?: null,
            ],
            [
                'workspace_id' => $workspace->id,
                'name' => $name,
                'slug' => $slug.'-'.Str::lower(Str::random(5)),
                'website_url' => $domain ? 'https://'.$domain : null,
                'status' => 'active',
                'legacy_source' => self::SOURCE,
                'settings' => [
                    'created_from' => 'legacy_scan_domain_grouping',
                ],
            ]
        );
    }

    public function attachHistoricalAssets(LegacyAccount $legacyAccount): array
    {
        if (! $legacyAccount->company || ! $legacyAccount->workspace) {
            return ['scans_attached' => 0, 'projects_created_or_matched' => 0, 'snapshots_attached' => 0];
        }

        $scansAttached = 0;
        $projectIds = [];

        Scan::query()
            ->where('legacy_source', self::SOURCE)
            ->where('legacy_client_id', $legacyAccount->legacy_id)
            ->orderBy('legacy_created_at')
            ->chunkById(100, function ($scans) use ($legacyAccount, &$scansAttached, &$projectIds): void {
                foreach ($scans as $scan) {
                    $domain = $scan->normalized_domain ?: $this->domainFromUrl($scan->normalized_url ?: $scan->url);
                    $project = $this->ensureProjectForDomain($legacyAccount->company, $legacyAccount->workspace, $domain ?: 'unknown-domain');
                    $projectIds[$project->id] = true;

                    $scan->forceFill([
                        'company_id' => $legacyAccount->company_id,
                        'user_id' => $legacyAccount->user_id,
                        'workspace_id' => $legacyAccount->workspace_id,
                        'project_id' => $project->id,
                    ])->save();

                    $scansAttached++;
                }
            });

        $snapshotsAttached = LegacyReportSnapshot::query()
            ->where('legacy_source', self::SOURCE)
            ->where('legacy_client_id', $legacyAccount->legacy_id)
            ->update(['user_id' => $legacyAccount->user_id]);

        $legacyAccount->forceFill([
            'scan_count' => Scan::query()->where('legacy_source', self::SOURCE)->where('legacy_client_id', $legacyAccount->legacy_id)->count(),
            'report_count' => LegacyReportSnapshot::query()->where('legacy_source', self::SOURCE)->where('legacy_client_id', $legacyAccount->legacy_id)->count(),
            'last_activity_at' => Scan::query()->where('legacy_source', self::SOURCE)->where('legacy_client_id', $legacyAccount->legacy_id)->max('legacy_created_at'),
        ])->save();

        return [
            'scans_attached' => $scansAttached,
            'projects_created_or_matched' => count($projectIds),
            'snapshots_attached' => $snapshotsAttached,
        ];
    }

    public function claim(LegacyAccount $legacyAccount, User $user): array
    {
        return DB::transaction(function () use ($legacyAccount, $user): array {
            $company = $user->company ?: $legacyAccount->company;

            if (! $company) {
                $company = Company::create([
                    'name' => $legacyAccount->name ? $legacyAccount->name.' Workspace' : $this->companyNameFromEmail((string) $legacyAccount->email),
                    'slug' => 'claimed-legacy-'.$legacyAccount->legacy_id,
                    'domain' => $this->domainFromEmail((string) $legacyAccount->email),
                    'legacy_metadata' => ['claimed_from_legacy_account_id' => $legacyAccount->id],
                ]);
            }

            if (! $user->company_id) {
                $user->forceFill([
                    'company_id' => $company->id,
                    'company_role' => User::COMPANY_ROLE_OWNER,
                ])->save();
            }

            $workspace = $this->ensureWorkspaceForCompany($company);

            $legacyAccount->forceFill([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'workspace_id' => $workspace->id,
                'status' => LegacyAccount::STATUS_CLAIMED,
                'claimed_at' => now(),
            ])->save();

            return $this->attachHistoricalAssets($legacyAccount->fresh(['company', 'workspace']));
        });
    }

    private function domainFromUrl(?string $url): ?string
    {
        return $url ? parse_url($url, PHP_URL_HOST) ?: null : null;
    }

    private function domainFromEmail(string $email): ?string
    {
        return str_contains($email, '@') ? strtolower(Str::after($email, '@')) : null;
    }

    private function companyNameFromEmail(string $email): string
    {
        $domain = $this->domainFromEmail($email);

        return $domain ? Str::headline(Str::beforeLast($domain, '.')) : 'Legacy QSA Workspace';
    }
}
