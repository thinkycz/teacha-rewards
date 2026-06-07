<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Api\EmailVerification;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Thinkycz\LaravelCore\Notifications\EmailVerificationNotification;
use Thinkycz\LaravelCore\Services\EmailBrokerService;
use Thinkycz\LaravelCore\Support\Typer;

class EmailVerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_sends_verification_email_to_unverified_user(): void
    {
        Notification::fake();

        $user = Typer::assertInstance(UserFactory::new()->unverified()->createOne([
            'email' => 'unverified@example.com',
        ]), User::class);

        $response = $this->postJson('/api/v1/email_verification/resend', [
            'email' => 'unverified@example.com',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(204);
        Notification::assertSentTo($user, EmailVerificationNotification::class);
    }

    public function test_resend_returns_204_for_already_verified_user(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne([
            'email' => 'verified@example.com',
        ]), User::class);

        static::assertNotNull($user->getEmailVerifiedAt());

        $response = $this->postJson('/api/v1/email_verification/resend', [
            'email' => 'verified@example.com',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(204);
    }

    public function test_resend_returns_422_for_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/email_verification/resend', [
            'email' => 'nobody@example.com',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }

    public function test_verify_marks_user_as_verified_with_valid_token(): void
    {
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
    }

    public function test_verify_returns_422_for_invalid_token(): void
    {
        Typer::assertInstance(UserFactory::new()->unverified()->createOne([
            'email' => 'unverified@example.com',
        ]), User::class);

        $response = $this->postJson('/api/v1/email_verification/verify', [
            'email' => 'unverified@example.com',
            'token' => 'invalid-token',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }

    public function test_verify_returns_422_for_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/email_verification/verify', [
            'email' => 'nobody@example.com',
            'token' => 'any-token',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }
}
