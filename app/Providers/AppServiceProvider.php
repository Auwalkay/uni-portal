<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Contracts\PaymentGatewayInterface::class, function ($app) {
            $gateway = \App\Models\SystemSetting::get('payment_gateway', env('PAYMENT_GATEWAY', 'squadco'));
            
            if ($gateway === 'squadco') {
                return new \App\Services\SquadcoService();
            }
            
            return new \App\Services\PaystackService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Register Login Event Listener
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\LoginListener::class
        );

        // Implicitly grant "Super Admin" role all permissions
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        $this->configureRateLimiting();
    }

    /**
     * Configure IP Rate Limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Global IP Rate Limiter (Default: 120 requests/minute per IP)
        \Illuminate\Support\Facades\RateLimiter::for('global-ip', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute((int) env('THROTTLE_GLOBAL_LIMIT', 120))
                ->by($request->ip());
        });

        // API Rate Limiter (Default: 60 requests/minute per IP)
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute((int) env('THROTTLE_API_LIMIT', 60))
                ->by($request->ip());
        });

        // Webhooks Rate Limiter (Default: 30 requests/minute per IP with Whitelist support)
        \Illuminate\Support\Facades\RateLimiter::for('webhooks', function (\Illuminate\Http\Request $request) {
            $whitelist = array_filter(array_map('trim', explode(',', (string) env('THROTTLE_WHITELIST_IPS', ''))));
            if (in_array($request->ip(), $whitelist, true)) {
                return \Illuminate\Cache\RateLimiting\Limit::none();
            }

            return \Illuminate\Cache\RateLimiting\Limit::perMinute((int) env('THROTTLE_WEBHOOKS_LIMIT', 30))
                ->by($request->ip());
        });

        // Public Verification Rate Limiter (Default: 20 requests/minute per IP)
        \Illuminate\Support\Facades\RateLimiter::for('public-verification', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute((int) env('THROTTLE_PUBLIC_VERIFICATION_LIMIT', 20))
                ->by($request->ip());
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
