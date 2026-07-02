<?php

namespace App\Services\Scans;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ScanAbuseProtector
{
    public function enforce(Request $request, string $normalizedUrl): void
    {
        if ($request->user()) {
            return;
        }

        $ip = $request->ip() ?: 'unknown';
        $settings = config('qsa.public_scan_abuse', []);

        if ($this->isVerificationRequired($ip)) {
            $this->reject('url', (string) ($settings['messages']['verification_required'] ?? 'Too many anonymous scan attempts were detected. Please sign in to continue.'));
        }

        $dailyQuota = (int) ($settings['daily_anonymous_quota'] ?? 0);
        if ($dailyQuota > 0 && $this->dailyAcceptedCount($ip) >= $dailyQuota) {
            $this->registerAbuseEvent($ip);
            $this->reject('url', (string) ($settings['messages']['daily_quota'] ?? 'You have reached the free daily scan limit for this connection. Please sign in to continue.'));
        }

        $cooldownMinutes = (int) ($settings['duplicate_scan_cooldown_minutes'] ?? 0);
        if ($cooldownMinutes > 0 && $this->duplicateCooldownActive($ip, $normalizedUrl)) {
            $this->registerAbuseEvent($ip);
            $this->reject('url', (string) ($settings['messages']['duplicate_cooldown'] ?? 'This URL was already scanned recently from this connection. Please wait before scanning it again.'));
        }

        $this->recordAcceptedAnonymousScan($ip, $normalizedUrl, $cooldownMinutes);
    }

    private function recordAcceptedAnonymousScan(string $ip, string $normalizedUrl, int $cooldownMinutes): void
    {
        $acceptedKey = $this->dailyAcceptedKey($ip);
        $ttl = now()->endOfDay()->diffInSeconds(now()) + 60;

        Cache::add($acceptedKey, 0, $ttl);
        Cache::increment($acceptedKey);

        if ($cooldownMinutes > 0) {
            Cache::put($this->duplicateCooldownKey($ip, $normalizedUrl), now()->timestamp, now()->addMinutes($cooldownMinutes));
        }
    }

    private function registerAbuseEvent(string $ip): void
    {
        $settings = config('qsa.public_scan_abuse', []);
        $eventKey = $this->dailyAbuseEventsKey($ip);
        $ttl = now()->endOfDay()->diffInSeconds(now()) + 60;

        Cache::add($eventKey, 0, $ttl);
        $count = Cache::increment($eventKey);

        $threshold = (int) ($settings['verification_trigger_after_abuse_events'] ?? 0);
        if ($threshold > 0 && $count >= $threshold) {
            $hours = max(1, (int) ($settings['verification_lock_hours'] ?? 12));
            Cache::put($this->verificationLockKey($ip), now()->timestamp, now()->addHours($hours));
        }
    }

    private function isVerificationRequired(string $ip): bool
    {
        return Cache::has($this->verificationLockKey($ip));
    }

    private function dailyAcceptedCount(string $ip): int
    {
        return (int) Cache::get($this->dailyAcceptedKey($ip), 0);
    }

    private function duplicateCooldownActive(string $ip, string $normalizedUrl): bool
    {
        return Cache::has($this->duplicateCooldownKey($ip, $normalizedUrl));
    }

    private function dailyAcceptedKey(string $ip): string
    {
        return 'qsa:public-scan:accepted:'.md5($ip).':'.now()->format('Y-m-d');
    }

    private function dailyAbuseEventsKey(string $ip): string
    {
        return 'qsa:public-scan:abuse-events:'.md5($ip).':'.now()->format('Y-m-d');
    }

    private function verificationLockKey(string $ip): string
    {
        return 'qsa:public-scan:verification-lock:'.md5($ip);
    }

    private function duplicateCooldownKey(string $ip, string $normalizedUrl): string
    {
        return 'qsa:public-scan:cooldown:'.md5($ip.':'.strtolower($normalizedUrl));
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
