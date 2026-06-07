<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Api\Password;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Typer;

class PasswordForgotControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_email_sends_password_reset_notification(): void
    {
        Notification::fake();

        Typer::assertInstance(UserFactory::new()->createOne([
            'email' => 'forgot@example.com',
        ]), User::class);

        $response = $this->postJson('/api/v1/password/forgot', [
            'email' => 'forgot@example.com',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(204);
    }

    public function test_unknown_email_returns_validation_error(): void
    {
        $response = $this->postJson('/api/v1/password/forgot', [
            'email' => 'nobody@example.com',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }

    public function test_missing_email_returns_validation_error(): void
    {
        $response = $this->postJson('/api/v1/password/forgot', [], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }

    public function test_invalid_email_returns_validation_error(): void
    {
        $response = $this->postJson('/api/v1/password/forgot', [
            'email' => 'not-an-email',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }
}
