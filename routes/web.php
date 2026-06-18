<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Auth\EmailVerificationConfirmController;
use App\Http\Controllers\Web\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\LogoutController;
use App\Http\Controllers\Web\Auth\ResetPasswordController;
use App\Http\Controllers\Web\Auth\VerifyEmailController;
use App\Http\Controllers\Web\Dashboard\AdjustController;
use App\Http\Controllers\Web\Dashboard\DashboardController;
use App\Http\Controllers\Web\Dashboard\DisableController;
use App\Http\Controllers\Web\Dashboard\EnableController;
use App\Http\Controllers\Web\Dashboard\LogPurchaseController;
use App\Http\Controllers\Web\Dashboard\RedeemController;
use App\Http\Controllers\Web\Dashboard\Settings\SettingsEditController;
use App\Http\Controllers\Web\Dashboard\Settings\SettingsUpdateController;
use App\Http\Controllers\Web\Dashboard\StampEarnController;
use App\Http\Controllers\Web\Dashboard\StampRedeemController;
use App\Http\Controllers\Web\Dashboard\StoreQrPrintController;
use App\Http\Controllers\Web\Dashboard\TransactionIndexController;
use App\Http\Controllers\Web\Dashboard\WalletIndexController;
use App\Http\Controllers\Web\Dashboard\WalletShowController;
use App\Http\Controllers\Web\Marketing\MarketingIndexController;
use App\Http\Controllers\Web\Pwa\InstallGuideController;
use App\Http\Controllers\Web\Pwa\OfflineController;
use App\Http\Controllers\Web\Settings\SettingsController;
use App\Http\Controllers\Web\Wallet\WalletActivityController;
use App\Http\Controllers\Web\Wallet\WalletCreateController;
use App\Http\Controllers\Web\Wallet\WalletShowController as PublicWalletShowController;
use App\Http\Controllers\Web\Wallet\WalletStoreController;
use App\Http\Middleware\EnsureInertiaUserIsAuthenticated;
use Illuminate\Routing\Router;
use Thinkycz\LaravelCore\Support\Resolver;

// Public customer landing. Always the marketing page — even for
// authenticated staff, who then click "Admin" to reach /dashboard.
Resolver::resolveRouteRegistrar()->get('/', MarketingIndexController::class);

// Public customer wallet flow: no auth required, the `public_token`
// in the URL is the only identifier for the wallet.
Resolver::resolveRouteRegistrar()
    ->get('wallet', WalletCreateController::class)
    ->name('wallet.create');
Resolver::resolveRouteRegistrar()
    ->post('wallet', WalletStoreController::class)
    ->name('wallet.store');
Resolver::resolveRouteRegistrar()
    ->get('w/{token}', PublicWalletShowController::class)
    ->name('wallet.show');
Resolver::resolveRouteRegistrar()
    ->get('w/{token}/activity', WalletActivityController::class)
    ->name('wallet.activity');

// PWA offline fallback.
Resolver::resolveRouteRegistrar()->get('offline', OfflineController::class);

// PWA install guide for iOS users.
Resolver::resolveRouteRegistrar()
    ->get('install', InstallGuideController::class)
    ->name('pwa.install');

// Guest-only auth surface.
Resolver::resolveRouteRegistrar()
    ->middleware('guest:users')
    ->group(static function (Router $router): void {
        $router->get('login', [LoginController::class, 'create']);
        $router->post('login', [LoginController::class, 'store']);
        $router->get('forgot-password', [ForgotPasswordController::class, 'create']);
        $router->post('forgot-password', [ForgotPasswordController::class, 'store']);
        $router->get('reset-password', [ResetPasswordController::class, 'create']);
        $router->post('reset-password', [ResetPasswordController::class, 'store']);
    });

// Email verification link landing.
Resolver::resolveRouteRegistrar()->get('email/verify', EmailVerificationConfirmController::class);

// Authenticated-but-not-staff surface: logout, email verification,
// and the per-user profile + password settings pages.
Resolver::resolveRouteRegistrar()
    ->middleware(EnsureInertiaUserIsAuthenticated::class)
    ->group(static function (Router $router): void {
        $router->post('logout', LogoutController::class);
        $router->get('verify-email', [VerifyEmailController::class, 'create']);
        $router->post('verify-email', [VerifyEmailController::class, 'store']);
        $router->get('profile', [SettingsController::class, 'edit'])->name('profile.edit');
        $router->post('profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        $router->post('profile/password', [SettingsController::class, 'updatePassword'])->name('profile.password');
    });

// Admin / dashboard surface. The /dashboard home is the only route
// keeping the `dashboard.` prefix — every other admin endpoint is
// flat at the root. The middleware chain gates the surface behind
// the `staff` role guard (admin or staff), with `admin` re-applied
// to /settings for the program + store settings form.
Resolver::resolveRouteRegistrar()
    ->middleware(['web', App\Http\Middleware\HandleInertiaRequests::class, EnsureInertiaUserIsAuthenticated::class, 'staff'])
    ->name('dashboard.')
    ->group(static function (Router $router): void {
        $router->get('dashboard', DashboardController::class)->name('index');
        $router->get('wallets', WalletIndexController::class)->name('wallets.index');
        $router->get('wallets/{wallet}', WalletShowController::class)->name('wallets.show');
        $router->post('wallets/{wallet}/purchase', LogPurchaseController::class)->name('wallets.purchase');
        $router->post('wallets/{wallet}/stamps/redeem', StampRedeemController::class)->name('wallets.stamps.redeem');
        $router->post('wallets/{wallet}/redeem', RedeemController::class)->name('wallets.redeem');
        $router->post('wallets/{wallet}/stamps/earn', StampEarnController::class)->name('wallets.stamps.earn');
        $router->post('wallets/{wallet}/adjust', AdjustController::class)->name('wallets.adjust');
        $router->post('wallets/{wallet}/disable', DisableController::class)->name('wallets.disable');
        $router->post('wallets/{wallet}/enable', EnableController::class)->name('wallets.enable');
        $router->get('transactions', TransactionIndexController::class)->name('transactions.index');
        $router->get('store-qr', StoreQrPrintController::class)->name('store-qr');
    });

// Admin-only sub-tree: program + store settings.
Resolver::resolveRouteRegistrar()
    ->middleware(['web', App\Http\Middleware\HandleInertiaRequests::class, EnsureInertiaUserIsAuthenticated::class, 'admin'])
    ->name('dashboard.settings.')
    ->group(static function (Router $router): void {
        $router->get('settings', SettingsEditController::class)->name('edit');
        $router->post('settings', SettingsUpdateController::class)->name('update');
    });
