<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Thinkycz\LaravelCore\Support\Resolver;

Resolver::resolveRouteRegistrar()->get('/', static function () {
    if (App\Models\User::auth() instanceof App\Models\User) {
        return Resolver::resolveRedirector()->to('/dashboard');
    }

    return Resolver::resolveRedirector()->to('/login');
});

Resolver::resolveRouteRegistrar()
    ->middleware('guest:users')
    ->group(static function (Router $router): void {
        $router->get('login', [App\Http\Controllers\Web\Auth\LoginController::class, 'create']);
        $router->post('login', [App\Http\Controllers\Web\Auth\LoginController::class, 'store']);
        $router->get('register', [App\Http\Controllers\Web\Auth\RegisterController::class, 'create']);
        $router->post('register', [App\Http\Controllers\Web\Auth\RegisterController::class, 'store']);
        $router->get('forgot-password', [App\Http\Controllers\Web\Auth\ForgotPasswordController::class, 'create']);
        $router->post('forgot-password', [App\Http\Controllers\Web\Auth\ForgotPasswordController::class, 'store']);
        $router->get('reset-password', [App\Http\Controllers\Web\Auth\ResetPasswordController::class, 'create']);
        $router->post('reset-password', [App\Http\Controllers\Web\Auth\ResetPasswordController::class, 'store']);
    });

Resolver::resolveRouteRegistrar()
    ->middleware(App\Http\Middleware\EnsureInertiaUserIsAuthenticated::class)
    ->group(static function (Router $router): void {
        $router->post('logout', App\Http\Controllers\Web\Auth\LogoutController::class);
        $router->get('dashboard', App\Http\Controllers\Web\DashboardController::class);
        $router->get('verify-email', [App\Http\Controllers\Web\Auth\VerifyEmailController::class, 'create']);
        $router->post('verify-email', [App\Http\Controllers\Web\Auth\VerifyEmailController::class, 'store']);
        $router->get('settings/profile', [App\Http\Controllers\Web\Settings\ProfileController::class, 'edit']);
        $router->post('settings/profile', [App\Http\Controllers\Web\Settings\ProfileController::class, 'update']);
        $router->get('settings/password', [App\Http\Controllers\Web\Settings\PasswordController::class, 'edit']);
        $router->post('settings/password', [App\Http\Controllers\Web\Settings\PasswordController::class, 'update']);
    });
