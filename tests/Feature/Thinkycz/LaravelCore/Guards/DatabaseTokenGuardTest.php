<?php

declare(strict_types=1);

namespace Tests\Feature\Thinkycz\LaravelCore\Guards;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

class DatabaseTokenGuardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Local database token cookies work over HTTP.
     */
    public function test_local_database_token_cookie_is_not_secure_and_uses_lax_same_site(): void
    {
        Resolver::resolveApp()->detectEnvironment(static fn(): string => 'local');

        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
        $guard = Resolver::resolveDatabaseTokenGuard($user->getTable());

        $guard->login($user);

        $cookie = Resolver::resolveCookieJar()->queued($guard->cookieName(), null, '/');

        static::assertNotNull($cookie);
        static::assertFalse($cookie->isSecure());
        static::assertSame('lax', $cookie->getSameSite());
        static::assertTrue($cookie->isHttpOnly());
    }

    /**
     * Non-local database token cookies remain secure.
     */
    public function test_non_local_database_token_cookie_is_secure(): void
    {
        Resolver::resolveApp()->detectEnvironment(static fn(): string => 'staging');

        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
        $guard = Resolver::resolveDatabaseTokenGuard($user->getTable());

        $guard->login($user);

        $cookie = Resolver::resolveCookieJar()->queued($guard->cookieName(), null, '/');

        static::assertNotNull($cookie);
        static::assertStringStartsWith('__Host-', $cookie->getName());
        static::assertTrue($cookie->isSecure());
        static::assertSame('none', $cookie->getSameSite());
        static::assertTrue($cookie->isHttpOnly());
    }
}
