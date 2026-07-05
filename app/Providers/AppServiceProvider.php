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
        date_default_timezone_set(config('app.timezone', 'Asia/Kolkata'));

        RateLimiter::for('scan', function (Request $request) {
            $isAuthenticated = $request->user() !== null;
            $limit = $isAuthenticated
                ? (int) config('qsa.authenticated_scan_rate_limit_per_minute', 20)
                : (int) config('qsa.scan_rate_limit_per_minute', 6);

            return Limit::perMinute($limit)
                ->by($isAuthenticated ? 'user:'.$request->user()->id : 'ip:'.$request->ip())
                ->response(function () {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'url' => 'Too many scan attempts were received from this connection. Please wait a minute and try again, or sign in to continue.',
                        ]);
                });
        });

        RateLimiter::for('lead-capture', function (Request $request) {
            return Limit::perMinute((int) config('qsa.lead_rate_limit_per_minute', 10))
                ->by($request->ip());
        });
    }
}
