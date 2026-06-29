<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\LegacyAccount;
use App\Models\LegacyReportSnapshot;
use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleLoginAndLegacyClaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google.client_id', 'test-google-client-id');
        config()->set('services.google.client_secret', 'test-google-client-secret');
        config()->set('services.google.redirect', 'https://qsa.test/auth/google/callback');
    }

    public function test_existing_user_can_log_in_with_google_and_is_linked(): void
    {
        $user = User::query()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => 'password',
            'role' => 'client',
            'company_role' => User::COMPANY_ROLE_OWNER,
            'invite_required' => false,
        ]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->fakeGoogleUser(
                id: 'google-existing-1',
                email: 'existing@example.com',
                name: 'Existing User',
            ));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('google-existing-1', $user->fresh()->google_id);
        $this->assertSame('google', $user->fresh()->auth_provider);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_google_login_creates_user_for_pending_legacy_account_without_creating_workspace_structures(): void
    {
        LegacyAccount::query()->create([
            'legacy_source' => 'dotnet_qsa',
            'legacy_id' => 'legacy-client-101',
            'name' => 'Legacy User',
            'email' => 'legacy@example.com',
            'status' => LegacyAccount::STATUS_PENDING_CLAIM,
            'scan_count' => 4,
            'report_count' => 2,
        ]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->fakeGoogleUser(
                id: 'google-legacy-1',
                email: 'legacy@example.com',
                name: 'Legacy User',
            ));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard.index'));

        $user = User::query()->where('email', 'legacy@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-legacy-1', $user->google_id);
        $this->assertNull($user->company_id);
        $this->assertSame(0, Company::query()->count());
        $this->assertSame(0, Workspace::query()->count());
        $this->assertSame(0, Project::query()->count());
        $this->assertSame(LegacyAccount::STATUS_PENDING_CLAIM, LegacyAccount::query()->firstOrFail()->status);
    }

    public function test_claim_requires_matching_email(): void
    {
        $legacyAccount = LegacyAccount::query()->create([
            'legacy_source' => 'dotnet_qsa',
            'legacy_id' => 'legacy-client-202',
            'name' => 'Legacy Owner',
            'email' => 'legacy-owner@example.com',
            'status' => LegacyAccount::STATUS_PENDING_CLAIM,
            'scan_count' => 1,
            'report_count' => 1,
        ]);

        $user = User::query()->create([
            'name' => 'Different User',
            'email' => 'different@example.com',
            'password' => 'password',
            'role' => 'client',
            'company_role' => User::COMPANY_ROLE_OWNER,
            'invite_required' => false,
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.legacy-accounts.claim', $legacyAccount))
            ->assertForbidden();
    }

    public function test_claim_attaches_legacy_scans_to_company_workspace_and_projects(): void
    {
        $legacyAccount = LegacyAccount::query()->create([
            'legacy_source' => 'dotnet_qsa',
            'legacy_id' => 'legacy-client-303',
            'name' => 'Legacy Claimant',
            'email' => 'claimant@example.com',
            'status' => LegacyAccount::STATUS_PENDING_CLAIM,
            'scan_count' => 2,
            'report_count' => 1,
        ]);

        $legacyScan = Scan::query()->create([
            'url' => 'https://example.com/landing',
            'normalized_url' => 'https://example.com/landing',
            'normalized_domain' => 'example.com',
            'status' => 'legacy_archived',
            'legacy_source' => 'dotnet_qsa',
            'legacy_id' => 'legacy-scan-1',
            'legacy_client_id' => 'legacy-client-303',
            'legacy_created_at' => now()->subYears(2),
        ]);

        LegacyReportSnapshot::query()->create([
            'scan_id' => $legacyScan->id,
            'legacy_source' => 'dotnet_qsa',
            'legacy_table' => 'AnalyzeUrls',
            'legacy_id' => 'legacy-snapshot-1',
            'legacy_client_id' => 'legacy-client-303',
            'source_url' => 'https://example.com/landing',
            'payload' => '{"report":"legacy"}',
            'payload_hash' => sha1('{"report":"legacy"}'),
            'metadata' => ['source' => 'test'],
            'legacy_created_at' => now()->subYears(2),
        ]);

        $user = User::query()->create([
            'name' => 'Legacy Claimant',
            'email' => 'claimant@example.com',
            'password' => 'password',
            'role' => 'client',
            'company_role' => User::COMPANY_ROLE_OWNER,
            'invite_required' => false,
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.legacy-accounts.claim', $legacyAccount))
            ->assertRedirect(route('dashboard.index'));

        $legacyAccount->refresh();
        $legacyScan->refresh();
        $snapshot = LegacyReportSnapshot::query()->firstOrFail();
        $user->refresh();

        $this->assertSame(LegacyAccount::STATUS_CLAIMED, $legacyAccount->status);
        $this->assertSame($user->id, $legacyAccount->claimed_by_user_id);
        $this->assertNotNull($legacyAccount->company_id);
        $this->assertNotNull($legacyAccount->workspace_id);
        $this->assertSame($legacyAccount->company_id, $user->company_id);
        $this->assertSame($legacyAccount->company_id, $legacyScan->company_id);
        $this->assertSame($legacyAccount->workspace_id, $legacyScan->workspace_id);
        $this->assertSame($user->id, $legacyScan->user_id);
        $this->assertNotNull($legacyScan->project_id);
        $this->assertSame($user->id, $snapshot->user_id);
        $this->assertSame('example.com', Project::query()->firstOrFail()->normalized_domain);
    }

    private function fakeGoogleUser(string $id, string $email, string $name): SocialiteUser
    {
        $user = new SocialiteUser();
        $user->id = $id;
        $user->name = $name;
        $user->email = $email;
        $user->avatar = 'https://example.com/avatar.png';
        $user->user = [
            'verified_email' => true,
            'email_verified' => true,
        ];

        return $user;
    }
}
