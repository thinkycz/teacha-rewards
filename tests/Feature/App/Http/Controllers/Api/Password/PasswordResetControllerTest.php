<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Api\Password;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

class PasswordResetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_reset_with_valid_token(): void
    {
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
    }

    public function test_reset_fails_with_invalid_token(): void
    {
        Typer::assertInstance(UserFactory::new()->createOne([
            'email' => 'reset@example.com',
        ]), User::class);

        $response = $this->postJson('/api/v1/password/reset', [
            'token' => 'invalid-token',
            'email' => 'reset@example.com',
            'password' => 'new-password',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }

    public function test_reset_fails_with_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/password/reset', [
            'token' => 'some-token',
            'email' => 'nobody@example.com',
            'password' => 'new-password',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }

    public function test_reset_revokes_existing_database_tokens(): void
    {
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
    }
}
