<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Hash;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

\test('password can be reset with valid token', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'reset@example.com',
    ]), User::class);

    $token = Resolver::resolvePasswordBroker('users')->createToken($user);

    $response = $this->postJson('/api/v1/password/reset', [
        'token' => $token,
        'email' => 'reset@example.com',
        'password' => 'new-password',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(200);
    $response->assertJsonPath('data.attributes.email', 'reset@example.com');
    $response->assertCookie(Resolver::resolveDatabaseTokenGuard($user->getTable())->cookieName());

    $user->refresh();
    static::assertTrue(Hash::check('new-password', (string) $user->getAuthPassword()));
});

\test('reset fails with invalid token', function (): void {
    Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'reset@example.com',
    ]), User::class);

    $response = $this->postJson('/api/v1/password/reset', [
        'token' => 'invalid-token',
        'email' => 'reset@example.com',
        'password' => 'new-password',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});

\test('reset fails with unknown email', function (): void {
    $response = $this->postJson('/api/v1/password/reset', [
        'token' => 'some-token',
        'email' => 'nobody@example.com',
        'password' => 'new-password',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});

\test('reset revokes existing database tokens', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'reset@example.com',
    ]), User::class);

    Resolver::resolveDatabaseTokenGuard($user->getTable())->login($user);
    $this->assertDatabaseCount('database_tokens', 1);

    $token = Resolver::resolvePasswordBroker('users')->createToken($user);

    $this->postJson('/api/v1/password/reset', [
        'token' => $token,
        'email' => 'reset@example.com',
        'password' => 'new-password',
    ], ['Accept' => 'application/vnd.api+json'])->assertStatus(200);

    $this->assertDatabaseCount('database_tokens', 1);
});
