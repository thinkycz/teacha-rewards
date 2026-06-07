<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Notification;
use Thinkycz\LaravelCore\Notifications\EmailVerificationNotification;
use Thinkycz\LaravelCore\Services\EmailBrokerService;
use Thinkycz\LaravelCore\Support\Typer;

\test('resend sends verification email to unverified user', function (): void {
    Notification::fake();

    $user = Typer::assertInstance(UserFactory::new()->unverified()->createOne([
        'email' => 'unverified@example.com',
    ]), User::class);

    $response = $this->postJson('/api/v1/email_verification/resend', [
        'email' => 'unverified@example.com',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(204);
    Notification::assertSentTo($user, EmailVerificationNotification::class);
});

\test('resend returns 204 for already verified user', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'verified@example.com',
    ]), User::class);

    static::assertNotNull($user->getEmailVerifiedAt());

    $response = $this->postJson('/api/v1/email_verification/resend', [
        'email' => 'verified@example.com',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(204);
});

\test('resend returns 422 for unknown email', function (): void {
    $response = $this->postJson('/api/v1/email_verification/resend', [
        'email' => 'nobody@example.com',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});

\test('verify marks user as verified with valid token', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->unverified()->createOne([
        'email' => 'unverified@example.com',
    ]), User::class);

    $token = EmailBrokerService::inject()->store($user->getTable(), $user->getEmailForVerification());

    $response = $this->postJson('/api/v1/email_verification/verify', [
        'email' => 'unverified@example.com',
        'token' => $token,
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(204);

    $user->refresh();
    static::assertNotNull($user->getEmailVerifiedAt());
});

\test('verify returns 422 for invalid token', function (): void {
    Typer::assertInstance(UserFactory::new()->unverified()->createOne([
        'email' => 'unverified@example.com',
    ]), User::class);

    $response = $this->postJson('/api/v1/email_verification/verify', [
        'email' => 'unverified@example.com',
        'token' => 'invalid-token',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});

\test('verify returns 422 for unknown email', function (): void {
    $response = $this->postJson('/api/v1/email_verification/verify', [
        'email' => 'nobody@example.com',
        'token' => 'any-token',
    ], ['Accept' => 'application/vnd.api+json']);

    $response->assertStatus(422);
});
