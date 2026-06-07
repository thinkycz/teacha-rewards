<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Web\Auth;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Thinkycz\LaravelCore\Models\DatabaseToken;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

class ResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_reset_password_page_with_email_and_token(): void
    {
        $response = $this->get('/reset-password?email=user@example.com&token=abc', $this->inertiaHeaders());

        $response->assertOk();
        $response->assertJsonPath('component', 'auth/ResetPassword');
        $response->assertJsonPath('props.email', 'user@example.com');
        $response->assertJsonPath('props.token', 'abc');
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        $response = $this->post('/reset-password', [
            'email' => $user->getEmail(),
            'password' => 'new-password',
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(422);
    }

    public function test_unknown_email_is_rejected(): void
    {
        $response = $this->post('/reset-password', [
            'email' => 'nobody@example.com',
            'password' => 'new-password',
            'token' => 'some-token',
        ]);

        $response->assertStatus(422);
    }

    public function test_valid_token_updates_password_and_logs_user_in(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
        $originalHash = $user->getAuthPassword();

        $broker = Resolver::resolvePasswordBroker('users');
        $token = $broker->createToken($user);

        $response = $this->post('/reset-password', [
            'email' => $user->getEmail(),
            'password' => 'new-password',
            'token' => $token,
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertCookie(Resolver::resolveDatabaseTokenGuard($user->getTable())->cookieName());

        $user->refresh();

        static::assertNotSame($originalHash, $user->getAuthPassword());
        static::assertTrue(Hash::check('new-password', $user->getAuthPassword()));
    }

    public function test_successful_reset_revokes_existing_database_tokens(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        DatabaseToken::inject()
            ->login($user->getTable(), $user);

        $this->assertDatabaseCount('database_tokens', 1);

        $broker = Resolver::resolvePasswordBroker('users');
        $token = $broker->createToken($user);

        $this->post('/reset-password', [
            'email' => $user->getEmail(),
            'password' => 'new-password',
            'token' => $token,
        ]);

        $user->refresh();

        static::assertNotEmpty($broker::INVALID_TOKEN);
    }
}
