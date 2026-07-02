<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Services\Scanner\SeoScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class PublicScanAbuseProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config()->set('qsa.scan_rate_limit_per_minute', 100);
        config()->set('qsa.authenticated_scan_rate_limit_per_minute', 100);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_duplicate_scan_cooldown_blocks_repeat_anonymous_scan_for_same_url(): void
    {
        config()->set('qsa.public_scan_abuse.daily_anonymous_quota', 10);
        config()->set('qsa.public_scan_abuse.duplicate_scan_cooldown_minutes', 30);
        config()->set('qsa.public_scan_abuse.verification_trigger_after_abuse_events', 99);

        $scanner = Mockery::mock(SeoScanner::class);
        $scanner->shouldReceive('scan')->once();
        $this->app->instance(SeoScanner::class, $scanner);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->from('/')
            ->post('/scan', ['url' => 'https://example.com'])
            ->assertRedirect();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->from('/')
            ->post('/scan', ['url' => 'https://example.com'])
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'url' => 'This URL was already scanned recently from this connection. Please wait before scanning it again.',
            ]);

        $this->assertDatabaseCount('scans', 1);
    }

    public function test_daily_anonymous_quota_blocks_additional_keyword_focus_attempts(): void
    {
        config()->set('qsa.public_scan_abuse.daily_anonymous_quota', 1);
        config()->set('qsa.public_scan_abuse.duplicate_scan_cooldown_minutes', 0);
        config()->set('qsa.public_scan_abuse.verification_trigger_after_abuse_events', 99);

        $scanner = Mockery::mock(SeoScanner::class);
        $scanner->shouldReceive('scan')->once();
        $this->app->instance(SeoScanner::class, $scanner);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->from('/keyword-focus-audit')
            ->post('/keyword-focus-audit', [
                'url' => 'https://example.com/services',
                'target_keywords' => "seo services\nlocal seo company",
            ])
            ->assertRedirect();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->from('/keyword-focus-audit')
            ->post('/keyword-focus-audit', [
                'url' => 'https://example.org/about',
                'target_keywords' => "ai seo audit",
            ])
            ->assertRedirect('/keyword-focus-audit')
            ->assertSessionHasErrors([
                'url' => 'You have reached the free daily scan limit for this connection. Please sign in to continue.',
            ]);

        $this->assertSame(1, Scan::query()->count());
        $this->assertSame('keyword_focus', (string) Scan::query()->first()->scan_mode);
    }

    public function test_repeated_blocked_anonymous_attempts_trigger_sign_in_lock(): void
    {
        config()->set('qsa.public_scan_abuse.daily_anonymous_quota', 1);
        config()->set('qsa.public_scan_abuse.duplicate_scan_cooldown_minutes', 0);
        config()->set('qsa.public_scan_abuse.verification_trigger_after_abuse_events', 2);
        config()->set('qsa.public_scan_abuse.verification_lock_hours', 12);

        $scanner = Mockery::mock(SeoScanner::class);
        $scanner->shouldReceive('scan')->once();
        $this->app->instance(SeoScanner::class, $scanner);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.30'])
            ->from('/')
            ->post('/scan', ['url' => 'https://example.com'])
            ->assertRedirect();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.30'])
            ->from('/')
            ->post('/scan', ['url' => 'https://example.org'])
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'url' => 'You have reached the free daily scan limit for this connection. Please sign in to continue.',
            ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.30'])
            ->from('/')
            ->post('/scan', ['url' => 'https://example.net'])
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'url' => 'Too many anonymous scan attempts were detected. Please sign in with Google or use your client login to continue.',
            ]);

        $this->assertDatabaseCount('scans', 1);
    }
}
