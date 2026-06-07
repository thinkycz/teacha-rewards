<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Api\Auth;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Typer;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_own_resource(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'new@example.com',
            'password' => 'password1',
            'locale' => 'en',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(201);
        $response->assertJsonPath('data.attributes.email', 'new@example.com');
        $response->assertJsonPath('data.type', 'users');
    }

    public function test_registered_user_password_is_hashed_only_once(): void
    {
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
    }

    public function test_register_creates_database_token_for_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'new@example.com',
            'password' => 'password1',
            'locale' => 'en',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(201);

        $this->assertDatabaseCount('database_tokens', 1);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        Typer::assertInstance(UserFactory::new()->createOne([
            'email' => 'taken@example.com',
        ]), User::class);

        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'taken@example.com',
            'password' => 'password1',
            'locale' => 'en',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }

    public function test_register_rejects_short_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'new@example.com',
            'password' => 'short',
            'locale' => 'en',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }

    public function test_register_rejects_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'not-an-email',
            'password' => 'password1',
            'locale' => 'en',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }
}
