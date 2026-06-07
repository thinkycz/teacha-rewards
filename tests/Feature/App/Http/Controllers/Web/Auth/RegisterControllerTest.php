<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Web\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Resolver;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_register_page(): void
    {
        $response = $this->get('/register', $this->inertiaHeaders());

        $response->assertOk();
        $response->assertJsonPath('component', 'auth/Register');
    }

    public function test_user_can_register_with_database_token_cookie(): void
    {
        $response = $this->post('/register', [
            'email' => 'new-user@example.com',
            'password' => 'password',
            'locale' => 'en',
        ]);

        $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();

        $response->assertRedirect('/dashboard');
        $response->assertCookie(Resolver::resolveDatabaseTokenGuard($user->getTable())->cookieName());
        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.com',
            'locale' => 'en',
        ]);
    }

    public function test_registered_user_password_is_hashed_only_once(): void
    {
        $this->post('/register', [
            'email' => 'new-user@example.com',
            'password' => 'password',
            'locale' => 'en',
        ]);

        $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();

        static::assertTrue(Resolver::resolveHasher()->check('password', $user->getAuthPassword()));
    }
}
