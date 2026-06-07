<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

\test('local database token cookie is not secure and uses lax same site', function (): void {
    Resolver::resolveApp()->detectEnvironment(static fn(): string => 'local');

    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $guard = Resolver::resolveDatabaseTokenGuard($user->getTable());

    $guard->login($user);

    $cookie = Resolver::resolveCookieJar()->queued($guard->cookieName(), null, '/');

    static::assertNotNull($cookie);
    static::assertFalse($cookie->isSecure());
    static::assertSame('lax', $cookie->getSameSite());
    static::assertTrue($cookie->isHttpOnly());
});

\test('non local database token cookie is secure', function (): void {
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
});
