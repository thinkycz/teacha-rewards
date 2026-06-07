<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

\test('authenticated user can logout', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'logout@example.com',
    ]), User::class);

    $guard = Resolver::resolveDatabaseTokenGuard($user->getTable());
    $guard->login($user);
    $this->assertDatabaseCount('database_tokens', 1);

    $cookie = $guard->cookieName();
    $tokenModel = $user->databaseTokens()->getQuery()->first();

    $this->withCookie($cookie, (string) ($tokenModel?->getKey() ?? ''));

    $this->be($user, 'users');

    $response = $this->postJson('/api/v1/auth/logout', [], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(204);
    $response->assertCookieExpired($cookie);
});

\test('logout fails for guest', function (): void {
    $response = $this->postJson('/api/v1/auth/logout', [], ['Accept' => 'application/vnd.api+json']);

    $status = (int) $response->baseResponse->getStatusCode();
    static::assertContains($status, [403, 401, 427]);
});
