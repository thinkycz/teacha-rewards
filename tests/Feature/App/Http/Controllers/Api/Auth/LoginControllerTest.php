<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

\test('user can login with valid credentials', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'login@example.com',
    ]), User::class);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'password',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(200);
    $response->assertJsonPath('data.attributes.email', 'login@example.com');
    $response->assertCookie(Resolver::resolveDatabaseTokenGuard($user->getTable())->cookieName());
});

\test('login fails with wrong password', function (): void {
    Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'login@example.com',
    ]), User::class);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'wrong-password',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});

\test('login fails with unknown email', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'password',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});

\test('login creates database token for user', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'login@example.com',
    ]), User::class);

    $this->assertDatabaseCount('database_tokens', 0);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'password',
    ], ['Accept' => 'application/vnd.api+json'])->assertStatus(200);

    $this->assertDatabaseCount('database_tokens', 1);
});
