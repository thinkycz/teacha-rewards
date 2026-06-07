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

class PasswordUpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_password(): void
    {
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
    }

    public function test_update_fails_with_wrong_current_password(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne([
            'email' => 'update@example.com',
        ]), User::class);

        $this->be($user, 'users');

        $response = $this->postJson('/api/v1/password/update', [
            'password' => 'wrong-password',
            'new_password' => 'new-password',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(422);
    }

    public function test_update_revokes_existing_database_tokens(): void
    {
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
    }

    public function test_unauthenticated_user_cannot_update_password(): void
    {
        $response = $this->postJson('/api/v1/password/update', [
            'password' => 'password',
            'new_password' => 'new-password',
        ], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(401);
    }
}
