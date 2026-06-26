<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\LegacyAccount;
use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use App\Services\Legacy\LegacyWorkspaceBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DashboardController
{
    public function index(Request $request): View
    {
        $company = $this->company($request);

        return view('dashboard.index', array_merge(
            $this->workspaceData($company),
            [
                'pendingLegacyAccounts' => $this->pendingLegacyAccounts($request->user()),
                'recentScans' => $this->companyScans($company)->latest()->limit(6)->get(),
                'recentReports' => $this->companyScans($company)->whereHas('result')->latest()->limit(6)->get(),
            ]
        ));
    }

    public function scans(Request $request): View
    {
        $company = $this->company($request);

        return view('dashboard.scans', array_merge(
            $this->workspaceData($company),
            ['scans' => $this->companyScans($company)->latest()->paginate(20)]
        ));
    }

    public function reports(Request $request): View
    {
        $company = $this->company($request);

        return view('dashboard.reports', array_merge(
            $this->workspaceData($company),
            ['reports' => $this->companyScans($company)->whereHas('result')->latest()->paginate(20)]
        ));
    }

    public function projects(Request $request): View
    {
        $company = $this->company($request);

        return view('dashboard.projects', array_merge(
            $this->workspaceData($company),
            ['projects' => $company?->projects()->withCount('scans')->latest()->paginate(20)]
        ));
    }

    public function storeProject(Request $request, LegacyWorkspaceBuilder $workspaceBuilder): RedirectResponse
    {
        $company = $this->requireCompany($request);
        $this->authorizeManager($request->user());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
        ]);

        $domain = isset($data['website_url']) ? parse_url($data['website_url'], PHP_URL_HOST) : null;
        $workspace = $workspaceBuilder->ensureWorkspaceForCompany($company);

        $company->projects()->create([
            'workspace_id' => $workspace->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'website_url' => $data['website_url'] ?? null,
            'normalized_domain' => $domain ? strtolower($domain) : null,
            'status' => $data['status'],
        ]);

        return back()->with('status', 'Project created.');
    }

    public function updateProject(Request $request, Project $project): RedirectResponse
    {
        $company = $this->requireCompany($request);
        $this->authorizeManager($request->user());

        abort_unless($project->company_id === $company->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
        ]);

        $data['normalized_domain'] = isset($data['website_url']) ? strtolower((string) parse_url($data['website_url'], PHP_URL_HOST)) : null;

        $project->update($data);

        return back()->with('status', 'Project updated.');
    }

    public function branding(Request $request): View
    {
        $company = $this->company($request);

        return view('dashboard.branding', $this->workspaceData($company));
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $company = $this->requireCompany($request);
        $this->authorizeOwnerAdmin($request->user());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'report_footer_text' => ['nullable', 'string', 'max:500'],
            'white_label_enabled' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $whiteLabelEnabled = $request->boolean('white_label_enabled') && $company->featureEnabled('white_label_reports');
        $whiteLabelSettings = $company->white_label_settings ?? [];
        $whiteLabelSettings['report_footer_text'] = $data['report_footer_text'] ?? null;

        $updates = [
            'name' => $data['name'],
            'website_url' => $data['website_url'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'primary_color' => $data['primary_color'] ?? null,
            'white_label_enabled' => $whiteLabelEnabled,
            'white_label_settings' => $whiteLabelSettings,
        ];

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $updates['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company->update($updates);

        return back()->with('status', 'Branding updated.');
    }

    public function usage(Request $request): View
    {
        $company = $this->company($request);

        return view('dashboard.usage', array_merge(
            $this->workspaceData($company),
            ['usageHistory' => $company?->reportUsages()->with(['user', 'scan'])->latest()->paginate(20)]
        ));
    }

    private function workspaceData(?Company $company): array
    {
        $monthlyScanLimit = $company?->planLimit('monthly_scans');
        $scansUsed = $this->liveCompanyScans($company)->where('created_at', '>=', now()->startOfMonth())->count();
        $reportsGenerated = $company?->reportUsages()->where('action', 'generated')->count() ?? 0;
        $pdfDownloads = $company?->reportUsages()->where('action', 'downloaded')->count() ?? 0;
        $remainingCredits = is_numeric($monthlyScanLimit) ? max(0, (int) $monthlyScanLimit - $scansUsed) : null;

        return [
            'company' => $company,
            'plan' => $company?->plan,
            'monthlyScanLimit' => $monthlyScanLimit,
            'scansUsed' => $scansUsed,
            'reportsGenerated' => $reportsGenerated,
            'pdfDownloads' => $pdfDownloads,
            'remainingCredits' => $remainingCredits,
            'canManageBranding' => auth()->user()?->canManageCompany() ?? false,
            'canManageProjects' => auth()->user()?->canManageScans() ?? false,
        ];
    }

    private function companyScans(?Company $company): Builder
    {
        $query = Scan::query()->with(['result', 'project']);

        if (! $company) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('company_id', $company->id);
    }

    private function liveCompanyScans(?Company $company): Builder
    {
        return $this->companyScans($company)->whereNull('legacy_source');
    }

    private function pendingLegacyAccounts(?User $user)
    {
        if (! $user) {
            return collect();
        }

        return LegacyAccount::query()
            ->where('status', LegacyAccount::STATUS_PENDING_CLAIM)
            ->where('email', strtolower($user->email))
            ->latest('last_activity_at')
            ->get();
    }

    private function company(Request $request): ?Company
    {
        return $request->user()?->company()->with('plan')->first();
    }

    private function requireCompany(Request $request): Company
    {
        $company = $this->company($request);

        abort_unless($company, 403, 'Your account is not assigned to a company workspace yet.');

        return $company;
    }

    private function authorizeOwnerAdmin(?User $user): void
    {
        abort_unless($user?->canManageCompany(), 403);
    }

    private function authorizeManager(?User $user): void
    {
        abort_unless($user?->canManageScans(), 403);
    }
}
