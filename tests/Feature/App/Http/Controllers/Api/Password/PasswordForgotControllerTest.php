<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Notification;
use Thinkycz\LaravelCore\Support\Typer;

\test('known email sends password reset notification', function (): void {
    Notification::fake();

    Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'forgot@example.com',
    ]), User::class);

    $response = $this->postJson('/api/v1/password/forgot', [
        'email' => 'forgot@example.com',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(204);
});

\test('unknown email returns validation error', function (): void {
    $response = $this->postJson('/api/v1/password/forgot', [
        'email' => 'nobody@example.com',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});

\test('missing email returns validation error', function (): void {
    $response = $this->postJson('/api/v1/password/forgot', [], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});

\test('invalid email returns validation error', function (): void {
    $response = $this->postJson('/api/v1/password/forgot', [
        'email' => 'not-an-email',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});
