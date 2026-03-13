<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        Gate::before(function (User $user) {
            if ($user->hasRole('SuperAdmin')) {
                return true;
            }
        });

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('mobile-api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user('mobile')?->id ?: $request->ip());
        });

        RateLimiter::for('mobile-auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('mobile-otp', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('mobile-transaction', function (Request $request) {
            return Limit::perMinute(10)->by($request->user('mobile')?->id ?: $request->ip());
        });
    }
}
