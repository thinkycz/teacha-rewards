<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Api\Auth;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

class LogoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne([
            'email' => 'logout@example.com',
        ]), User::class);

        $guard = Resolver::resolveDatabaseTokenGuard($user->getTable());
        $guard->login($user);
        $this->assertDatabaseCount('database_tokens', 1);

        $cookie = $guard->cookieName();
        $tokenModel = $user->databaseTokens()->getQuery()->first();

        $this->withCookie($cookie, (string) ($tokenModel?->getKey() ?? ''));

        $this->be($user, 'users');

        $response = $this->postJson('/api/v1/auth/logout', [], ['Accept' => 'application/vnd.api+json']);

        $response->assertStatus(204);
        $response->assertCookieExpired($cookie);
    }

    public function test_logout_fails_for_guest(): void
    {
        $response = $this->postJson('/api/v1/auth/logout', [], ['Accept' => 'application/vnd.api+json']);

        $status = (int) $response->baseResponse->getStatusCode();
        static::assertContains($status, [403, 401, 427]);
    }
}
