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
use App\Http\Controllers\Web\Scan\ScanShowController as PublicScanShowController;
use App\Http\Controllers\Web\Settings\SettingsController;
use App\Http\Controllers\Web\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Web\Staff\Scan\ScanIndexController as StaffScanIndexController;
use App\Http\Controllers\Web\Staff\Scan\ScanShowController as StaffScanShowController;
use App\Http\Controllers\Web\Staff\Settings\SettingsEditController;
use App\Http\Controllers\Web\Staff\Settings\SettingsUpdateController;
use App\Http\Controllers\Web\Staff\Transactions\TransactionIndexController;
use App\Http\Controllers\Web\Staff\Wallets\AdjustController as StaffAdjustController;
use App\Http\Controllers\Web\Staff\Wallets\DisableController;
use App\Http\Controllers\Web\Staff\Wallets\EnableController;
use App\Http\Controllers\Web\Staff\Wallets\LogPurchaseController;
use App\Http\Controllers\Web\Staff\Wallets\RedeemController;
use App\Http\Controllers\Web\Staff\Wallets\WalletIndexController as StaffWalletIndexController;
use App\Http\Controllers\Web\Staff\Wallets\WalletShowController as StaffWalletShowController;
use App\Http\Controllers\Web\Wallet\WalletActivityController;
use App\Http\Controllers\Web\Wallet\WalletCreateController;
use App\Http\Controllers\Web\Wallet\WalletShowController as PublicWalletShowController;
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
    ->get('w/{token}', PublicWalletShowController::class)
    ->name('wallet.show');
Resolver::resolveRouteRegistrar()
    ->get('w/{token}/activity', WalletActivityController::class)
    ->name('wallet.activity');

// PWA offline fallback (also serves as a soft-404 when the network
// is down for navigations).
Resolver::resolveRouteRegistrar()->get('offline', OfflineController::class);

// PWA install guide (iOS-friendly step-by-step screen).
Resolver::resolveRouteRegistrar()
    ->get('install', \App\Http\Controllers\Web\Pwa\InstallGuideController::class)
    ->name('pwa.install');

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

// Staff surface: requires authenticated user with role in
// {admin, staff}. The `staff` middleware alias is registered in
// bootstrap/app.php and gates the whole /staff/* subtree.
Resolver::resolveRouteRegistrar()
    ->middleware(['web', \App\Http\Middleware\HandleInertiaRequests::class, EnsureInertiaUserIsAuthenticated::class, 'staff'])
    ->prefix('staff')
    ->name('staff.')
    ->group(static function (Router $router): void {
        $router->get('/', StaffDashboardController::class)->name('dashboard');
        $router->get('scan', StaffScanIndexController::class)->name('scan.index');
        $router->get('scan/{token}', StaffScanShowController::class)->name('scan.show');
        $router->get('wallets', StaffWalletIndexController::class)->name('wallets.index');
        $router->get('wallets/{wallet}', StaffWalletShowController::class)->name('wallets.show');
        $router->post('wallets/{wallet}/purchase', LogPurchaseController::class)->name('wallets.purchase');
        $router->post('wallets/{wallet}/redeem', RedeemController::class)->name('wallets.redeem');
        $router->post('wallets/{wallet}/adjust', StaffAdjustController::class)->name('wallets.adjust');
        $router->post('wallets/{wallet}/disable', DisableController::class)->name('wallets.disable');
        $router->post('wallets/{wallet}/enable', EnableController::class)->name('wallets.enable');
        $router->get('transactions', TransactionIndexController::class)->name('transactions.index');
    });

// Admin-only settings surface.
Resolver::resolveRouteRegistrar()
    ->middleware(['web', \App\Http\Middleware\HandleInertiaRequests::class, EnsureInertiaUserIsAuthenticated::class, 'admin'])
    ->prefix('staff/settings')
    ->name('staff.settings.')
    ->group(static function (Router $router): void {
        $router->get('/', SettingsEditController::class)->name('edit');
        $router->post('/', SettingsUpdateController::class)->name('update');
    });

// Printable store QR sheet — staff (any role) can use it; the page
// is a clean print-friendly render pointing customers at /wallet.
Resolver::resolveRouteRegistrar()
    ->middleware(['web', \App\Http\Middleware\HandleInertiaRequests::class, EnsureInertiaUserIsAuthenticated::class, 'staff'])
    ->get('staff/store-qr', \App\Http\Controllers\Web\Staff\StoreQrPrintController::class)
    ->name('staff.store-qr');

