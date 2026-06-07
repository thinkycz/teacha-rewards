<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Middleware;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Typer;

/**
 * @phpstan-type SharedProps array{
 *     app: array{name: string, locale: string},
 *     auth: array{user: callable(): (array<string, mixed>|null)},
 *     flash: array{success: callable(): (string|null), error: callable(): (string|null)},
 *     errors: array<string, mixed>
 * }
 */
class HandleInertiaRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_returns_guest_props_when_unauthenticated(): void
    {
        $middleware = new \App\Http\Middleware\HandleInertiaRequests();

        $request = Request::create('/login', 'GET');

        /** @var SharedProps $shared */
        $shared = $middleware->share($request);

        static::assertArrayHasKey('app', $shared);
        static::assertSame('Laravel Inertia Stack', $shared['app']['name']);
        static::assertArrayHasKey('auth', $shared);
        static::assertArrayHasKey('flash', $shared);
        static::assertArrayHasKey('errors', $shared);
    }

    public function test_share_resolves_user_to_null_for_guest(): void
    {
        $middleware = new \App\Http\Middleware\HandleInertiaRequests();

        $request = Request::create('/login', 'GET');

        /** @var SharedProps $shared */
        $shared = $middleware->share($request);

        $authUser = ($shared['auth']['user'])();

        static::assertNull($authUser);
    }

    public function test_share_resolves_user_to_array_for_authenticated(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        $this->be($user, 'users');

        $middleware = new \App\Http\Middleware\HandleInertiaRequests();

        $request = Request::create('/dashboard', 'GET');

        /** @var SharedProps $shared */
        $shared = $middleware->share($request);

        $authUser = ($shared['auth']['user'])();

        static::assertNotNull($authUser);
        static::assertSame($user->getEmail(), $authUser['email']);
        static::assertSame($user->getKey(), $authUser['id']);
    }

    public function test_share_resolves_flash_to_session_values(): void
    {
        $this->session(['success' => 'Welcome back', 'error' => 'Something failed']);

        $middleware = new \App\Http\Middleware\HandleInertiaRequests();

        $request = Request::create('/dashboard', 'GET');
        $request->setLaravelSession($this->app['session.store']);

        /** @var SharedProps $shared */
        $shared = $middleware->share($request);

        static::assertSame('Welcome back', ($shared['flash']['success'])());
        static::assertSame('Something failed', ($shared['flash']['error'])());
    }
}
