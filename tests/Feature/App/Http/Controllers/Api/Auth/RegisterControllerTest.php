<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Hash;
use Thinkycz\LaravelCore\Support\Typer;

\test('user can register and receive own resource', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'email' => 'new@example.com',
        'password' => 'password1',
        'locale' => 'en',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(201);
    $response->assertJsonPath('data.attributes.email', 'new@example.com');
    $response->assertJsonPath('data.type', 'users');
});

\test('registered user password is hashed only once', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'email' => 'new@example.com',
        'password' => 'password1',
        'locale' => 'en',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(201);

    $user = User::query()->where('email', 'new@example.com')->first();
    static::assertNotNull($user);
    static::assertNotSame('password1', $user->getAuthPassword());
    static::assertTrue(Hash::check('password1', (string) $user->getAuthPassword()));
});

\test('register creates database token for user', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'email' => 'new@example.com',
        'password' => 'password1',
        'locale' => 'en',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(201);

    $this->assertDatabaseCount('database_tokens', 1);
});

\test('register rejects duplicate email', function (): void {
    Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'taken@example.com',
    ]), User::class);

    $response = $this->postJson('/api/v1/auth/register', [
        'email' => 'taken@example.com',
        'password' => 'password1',
        'locale' => 'en',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});

\test('register rejects short password', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'email' => 'new@example.com',
        'password' => 'short',
        'locale' => 'en',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});

\test('register rejects invalid email', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'email' => 'not-an-email',
        'password' => 'password1',
        'locale' => 'en',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});
