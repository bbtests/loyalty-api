<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\PaymentService;
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
        // Bind PaymentService to 'payment' alias for easier access
        $this->app->alias(PaymentService::class, 'payment');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return [Limit::perMinute(config('constants.rate_limiting.api'))->by($request->ip())];
        });
        RateLimiter::for('short', function (Request $request) {
            return [Limit::perMinute(config('constants.rate_limiting.short'))->by($request->user()?->id ?: $request->ip())];
        });
        RateLimiter::for('medium', function (Request $request) {
            return [Limit::perMinute(config('constants.rate_limiting.medium'))->by($request->user()?->id ?: $request->ip())];
        });
        RateLimiter::for('long', function (Request $request) {
            return [Limit::perMinute(config('constants.rate_limiting.long'))->by($request->user()?->id ?: $request->ip())];
        });

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super admin') ? true : null;
        });
    }
}
