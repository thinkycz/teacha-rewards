<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Middleware;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Typer;

class EnsureInertiaUserIsAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $middleware = new \App\Http\Middleware\EnsureInertiaUserIsAuthenticated();

        $request = Request::create('/dashboard', 'GET');

        $response = $middleware->handle($request, static fn(Request $r): SymfonyResponse => new \Illuminate\Http\Response('ok'));

        static::assertSame(302, $response->getStatusCode());
        static::assertStringContainsString('/login', (string) $response->headers->get('Location'));
    }

    public function test_guest_with_json_format_throws_authentication_exception(): void
    {
        $middleware = new \App\Http\Middleware\EnsureInertiaUserIsAuthenticated();

        $request = Request::create('/dashboard', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->expectException(AuthenticationException::class);

        $middleware->handle($request, static fn(Request $r): SymfonyResponse => new \Illuminate\Http\Response('ok'));
    }

    public function test_authenticated_user_passes_through(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        $this->be($user, 'users');

        $middleware = new \App\Http\Middleware\EnsureInertiaUserIsAuthenticated();

        $request = Request::create('/dashboard', 'GET');

        $response = $middleware->handle($request, static fn(Request $r): SymfonyResponse => new \Illuminate\Http\Response('ok'));

        static::assertSame(200, $response->getStatusCode());
        static::assertSame('ok', $response->getContent());
    }
}
