<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Reward\RewardTransactionService;
use App\Services\Reward\RewardWalletService;
use App\Services\Settings\SettingsService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Thinkycz\LaravelCore\Support\Resolver;

/**
 * Wires the three reward-domain services as singletons.
 *
 * Services are resolved through `Resolver::resolve(...)` at runtime,
 * so they all need to be registered here AND this provider has to be
 * listed in `bootstrap/providers.php`.
 */
class RewardServiceProvider extends ServiceProvider
{
    /**
     * Register the reward services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class, static function (Application $app): SettingsService {
            return new SettingsService();
        });

        $this->app->singleton(RewardWalletService::class, static function (Application $app): RewardWalletService {
            return new RewardWalletService(
                Resolver::resolve(SettingsService::class),
            );
        });

        $this->app->singleton(RewardTransactionService::class, static function (Application $app): RewardTransactionService {
            return new RewardTransactionService(
                Resolver::resolve(SettingsService::class),
            );
        });
    }
}
