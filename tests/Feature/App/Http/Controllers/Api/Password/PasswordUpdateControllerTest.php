<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Hash;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

\test('authenticated user can update password', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'update@example.com',
    ]), User::class);

    $this->be($user, 'users');

    $response = $this->postJson('/api/v1/password/update', [
        'password' => 'password',
        'new_password' => 'new-password',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(204);

    $user->refresh();
    static::assertTrue(Hash::check('new-password', (string) $user->getAuthPassword()));
});

\test('update fails with wrong current password', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'update@example.com',
    ]), User::class);

    $this->be($user, 'users');

    $response = $this->postJson('/api/v1/password/update', [
        'password' => 'wrong-password',
        'new_password' => 'new-password',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});

\test('update revokes existing database tokens', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'update@example.com',
    ]), User::class);

    Resolver::resolveDatabaseTokenGuard($user->getTable())->login($user);
    $this->assertDatabaseCount('database_tokens', 1);

    $this->be($user, 'users');

    $this->postJson('/api/v1/password/update', [
        'password' => 'password',
        'new_password' => 'new-password',
    ], ['Accept' => 'application/vnd.api+json'])->assertStatus(204);

    $this->assertDatabaseCount('database_tokens', 0);
});

\test('unauthenticated user cannot update password', function (): void {
    $response = $this->postJson('/api/v1/password/update', [
        'password' => 'password',
        'new_password' => 'new-password',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(401);
});
