<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Typer;

\test('show returns authenticated user', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'me@example.com',
    ]), User::class);

    $this->be($user, 'users');

    $response = $this->getJson('/api/v1/me/show', ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(200);
    $response->assertJsonPath('data.attributes.email', 'me@example.com');
    $response->assertJsonPath('data.type', 'users');
});

\test('show requires authentication', function (): void {
    $response = $this->getJson('/api/v1/me/show', ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(401);
});

\test('update can change user email', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'before@example.com',
    ]), User::class);

    $this->be($user, 'users');

    $response = $this->postJson('/api/v1/me/update', [
        'email' => 'after@example.com',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(200);
    $response->assertJsonPath('data.attributes.email', 'after@example.com');

    $user->refresh();
    static::assertSame('after@example.com', $user->getEmail());
});

\test('update can change user locale', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'me@example.com',
        'locale' => 'en',
    ]), User::class);

    $this->be($user, 'users');

    $response = $this->postJson('/api/v1/me/update', [
        'locale' => 'cs',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(200);
    $response->assertJsonPath('data.attributes.locale', 'cs');
});

\test('update rejects duplicate email', function (): void {
    Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'taken@example.com',
    ]), User::class);

    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'me@example.com',
    ]), User::class);

    $this->be($user, 'users');

    $response = $this->postJson('/api/v1/me/update', [
        'email' => 'taken@example.com',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});

\test('destroy deletes user and logs out', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'doomed@example.com',
    ]), User::class);

    $this->be($user, 'users');

    $this->assertDatabaseHas('users', ['email' => 'doomed@example.com']);

    $response = $this->postJson('/api/v1/me/destroy', [], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(204);
    $this->assertDatabaseMissing('users', ['email' => 'doomed@example.com']);
});

\test('destroy requires authentication', function (): void {
    $response = $this->postJson('/api/v1/me/destroy', [], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(401);
});
