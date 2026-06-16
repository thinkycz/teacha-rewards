<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Agent\AgentRunCancelController;
use App\Http\Controllers\Web\Agent\AgentRunStartController;
use App\Http\Controllers\Web\Agent\AgentRunStreamController;
use App\Http\Controllers\Web\Auth\EmailVerificationConfirmController;
use App\Http\Controllers\Web\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\LogoutController;
use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\Auth\ResetPasswordController;
use App\Http\Controllers\Web\Auth\VerifyEmailController;
use App\Http\Controllers\Web\ConversationController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\Marketing\MarketingIndexController;
use App\Http\Controllers\Web\Pwa\OfflineController;
use App\Http\Controllers\Web\Settings\SettingsController;
use App\Http\Controllers\Web\Wallet\WalletActivityController;
use App\Http\Controllers\Web\Wallet\WalletCreateController;
use App\Http\Controllers\Web\Wallet\WalletShowController;
use App\Http\Controllers\Web\Wallet\WalletStoreController;
use App\Http\Middleware\EnsureInertiaUserIsAuthenticated;
use App\Models\User;
use Illuminate\Routing\Router;
use Thinkycz\LaravelCore\Support\Resolver;

// `GET /` is always the customer marketing landing, even for
// authenticated staff. Staff navigate to `/staff` for their dashboard.
Resolver::resolveRouteRegistrar()->get('/', MarketingIndexController::class);

// Public customer flow: no auth, the `public_token` in the URL is
// the only identifier for the wallet.
Resolver::resolveRouteRegistrar()
    ->get('wallet', WalletCreateController::class)
    ->name('wallet.create');
Resolver::resolveRouteRegistrar()
    ->post('wallet', WalletStoreController::class)
    ->name('wallet.store');
Resolver::resolveRouteRegistrar()
    ->get('w/{token}', WalletShowController::class)
    ->name('wallet.show');
Resolver::resolveRouteRegistrar()
    ->get('w/{token}/activity', WalletActivityController::class)
    ->name('wallet.activity');

// PWA offline fallback (also serves as a soft-404 when the network
// is down for navigations).
Resolver::resolveRouteRegistrar()->get('offline', OfflineController::class);

Resolver::resolveRouteRegistrar()
    ->middleware('guest:users')
    ->group(static function (Router $router): void {
        $router->get('login', [LoginController::class, 'create']);
        $router->post('login', [LoginController::class, 'store']);
        $router->get('register', [RegisterController::class, 'create']);
        $router->post('register', [RegisterController::class, 'store']);
        $router->get('forgot-password', [ForgotPasswordController::class, 'create']);
        $router->post('forgot-password', [ForgotPasswordController::class, 'store']);
        $router->get('reset-password', [ResetPasswordController::class, 'create']);
        $router->post('reset-password', [ResetPasswordController::class, 'store']);
    });

Resolver::resolveRouteRegistrar()->get('email/verify', EmailVerificationConfirmController::class);

Resolver::resolveRouteRegistrar()
    ->middleware(EnsureInertiaUserIsAuthenticated::class)
    ->group(static function (Router $router): void {
        $router->post('logout', LogoutController::class);
        $router->get('dashboard', DashboardController::class);
        $router->get('verify-email', [VerifyEmailController::class, 'create']);
        $router->post('verify-email', [VerifyEmailController::class, 'store']);
        $router->get('settings', [SettingsController::class, 'edit']);
        $router->post('settings/profile', [SettingsController::class, 'updateProfile']);
        $router->post('settings/password', [SettingsController::class, 'updatePassword']);

        $router->get('conversations/{id}', [ConversationController::class, 'show']);
        $router->delete('conversations/{id}', [ConversationController::class, 'destroy']);

        $router->post('agent/runs', AgentRunStartController::class);
        $router->post('agent/runs/cancel', AgentRunCancelController::class);
        $router->get('agent/runs/stream', AgentRunStreamController::class);
    });

