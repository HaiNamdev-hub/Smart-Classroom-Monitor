<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Keep the account-specific limit effective while allowing a classroom
        // behind one NAT address to authenticate concurrently.
        RateLimiter::for('login', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(120)->by('login-ip:'.$request->ip()),
                Limit::perMinute(5)->by('login-email:'.hash('sha256', $email)),
            ];
        });

        // Authenticated per-account limits avoid throttling an entire classroom
        // whose students share one campus NAT address.
        RateLimiter::for('exam-start', fn (Request $request) => [
            Limit::perMinute(120)->by('exam-start-ip:'.$request->ip()),
            Limit::perMinute(10)->by('exam-start-user:'.($request->user()?->getAuthIdentifier() ?? 'guest')),
        ]);
        RateLimiter::for('exam-alert', fn (Request $request) => [
            Limit::perMinute(1200)->by('exam-alert-ip:'.$request->ip()),
            Limit::perMinute(60)->by('exam-alert-user:'.($request->user()?->getAuthIdentifier() ?? 'guest')),
        ]);
        RateLimiter::for('exam-evidence', fn (Request $request) => [
            Limit::perMinute(1200)->by('exam-evidence-ip:'.$request->ip()),
            Limit::perMinute(20)->by('exam-evidence-user:'.($request->user()?->getAuthIdentifier() ?? 'guest')),
        ]);
        RateLimiter::for('exam-event', fn (Request $request) => [
            Limit::perMinute(1200)->by('exam-event-ip:'.$request->ip()),
            Limit::perMinute(60)->by('exam-event-user:'.($request->user()?->getAuthIdentifier() ?? 'guest')),
        ]);
        RateLimiter::for('ice-config', fn (Request $request) => [
            Limit::perMinute(600)->by('ice-config-ip:'.$request->ip()),
            Limit::perMinute(30)->by('ice-config-user:'.($request->user()?->getAuthIdentifier() ?? 'guest')),
        ]);
    }
}
