<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('scan', function (Request $request) {
            return Limit::perMinute((int) config('qsa.scan_rate_limit_per_minute', 6))
                ->by($request->ip());
        });

        RateLimiter::for('lead-capture', function (Request $request) {
            return Limit::perMinute((int) config('qsa.lead_rate_limit_per_minute', 10))
                ->by($request->ip());
        });
    }
}
