<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Api\Auth;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
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
    }

    public function test_login_fails_with_wrong_password(): void
    {
        Typer::assertInstance(UserFactory::new()->createOne([
            'email' => 'login@example.com',
        ]), User::class);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }

    public function test_login_creates_database_token_for_user(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne([
            'email' => 'login@example.com',
        ]), User::class);

        $this->assertDatabaseCount('database_tokens', 0);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ], ['Accept' => 'application/vnd.api+json'])->assertStatus(200);

        $this->assertDatabaseCount('database_tokens', 1);
    }
}
