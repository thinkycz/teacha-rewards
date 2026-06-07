<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Web\Auth;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/login', $this->inertiaHeaders());

        $response->assertOk();
        $response->assertJsonPath('component', 'auth/Login');
    }

    public function test_user_can_login_with_database_token_cookie(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        $response = $this->post('/login', [
            'email' => $user->getEmail(),
            'password' => UserFactory::$password,
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertCookie(Resolver::resolveDatabaseTokenGuard($user->getTable())->cookieName());
    }
}
