<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // Public auth endpoints: per-IP.
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by('ip:'.$request->ip());
        });

        // Login additionally keys on email+IP so a single account cannot be
        // credential-stuffed from one source while other users stay unaffected.
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(10)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('email:'.Str::lower((string) $request->string('email')).'|'.$request->ip()),
            ];
        });

        // Authenticated traffic: per-user; unauthenticated fallthrough: per-IP.
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            return $user !== null
                ? Limit::perMinute(120)->by('user:'.$user->getAuthIdentifier())
                : Limit::perMinute(30)->by('ip:'.$request->ip());
        });
    }
}
