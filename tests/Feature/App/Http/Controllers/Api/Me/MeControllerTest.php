<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Api\Me;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Typer;

class MeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_authenticated_user(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne([
            'email' => 'me@example.com',
        ]), User::class);

        $this->be($user, 'users');

        $response = $this->getJson('/api/v1/me/show', ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(200);
        $response->assertJsonPath('data.attributes.email', 'me@example.com');
        $response->assertJsonPath('data.type', 'users');
    }

    public function test_show_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/me/show', ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(401);
    }

    public function test_update_can_change_user_email(): void
    {
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
    }

    public function test_update_can_change_user_locale(): void
    {
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
    }

    public function test_update_rejects_duplicate_email(): void
    {
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
    }

    public function test_destroy_deletes_user_and_logs_out(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne([
            'email' => 'doomed@example.com',
        ]), User::class);

        $this->be($user, 'users');

        $this->assertDatabaseHas('users', ['email' => 'doomed@example.com']);

        $response = $this->postJson('/api/v1/me/destroy', [], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['email' => 'doomed@example.com']);
    }

    public function test_destroy_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/me/destroy', [], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(401);
    }
}
