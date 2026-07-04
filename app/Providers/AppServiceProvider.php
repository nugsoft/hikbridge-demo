<?php

namespace App\Providers;

use App\Models\Business;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Nugsoft\HikBridge\HikBridgeClient;
use Nugsoft\HikBridge\HikBridgeManager;

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
        $this->configureDefaults();
        $this->bindTenantHikBridge();
    }

    /**
     * Make the HikBridge SDK multi-tenant.
     *
     * The package binds its client as a singleton using the global config key.
     * Here we override it with a per-request (scoped) binding that swaps in the
     * currently authenticated user's business key. Because each HikBridge key
     * is scoped to one business server-side, this guarantees a user can only
     * ever reach their own business's devices — never another tenant's.
     */
    protected function bindTenantHikBridge(): void
    {
        $this->app->scoped(HikBridgeClient::class, function ($app) {
            $config = $app['config']['hikbridge'];

            if ($business = $this->currentBusiness()) {
                $config['api_key'] = $business->hik_api_key;

                if (! empty($business->hik_base_url)) {
                    $config['base_url'] = $business->hik_base_url;
                }
            }

            return new HikBridgeClient($config);
        });

        $this->app->scoped(
            HikBridgeManager::class,
            fn ($app) => new HikBridgeManager($app->make(HikBridgeClient::class)),
        );
    }

    /**
     * Resolve the business (tenant) for the current request, if any.
     */
    protected function currentBusiness(): ?Business
    {
        return auth()->user()?->business;
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
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
            : null,
        );
    }
}
