<?php

namespace App\Providers;

use App\Models\ApiClient;
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
        RateLimiter::for('open-api', function (Request $request) {
            $client = $request->attributes->get('api_client');
            $limit = $client instanceof ApiClient ? $client->rate_limit : 60;
            $key = $client instanceof ApiClient ? (string) $client->id : $request->ip();

            return Limit::perMinute($limit)->by($key);
        });
    }
}
