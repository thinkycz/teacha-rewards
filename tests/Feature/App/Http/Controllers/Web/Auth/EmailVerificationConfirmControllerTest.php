<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Web\Auth;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Support\SessionKey;
use Tests\TestCase;
use Thinkycz\LaravelCore\Services\EmailBrokerService;
use Thinkycz\LaravelCore\Support\Typer;

class EmailVerificationConfirmControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_token_marks_user_as_verified_and_redirects_to_dashboard(): void
    {
        Event::fake();

        $user = Typer::assertInstance(UserFactory::new()->unverified()->createOne([
            'email' => 'unverified@example.com',
        ]), User::class);

        $token = EmailBrokerService::inject()->store($user->getTable(), $user->getEmailForVerification());

        $response = $this->be($user, 'users')->get('/email/verify?' . \http_build_query([
            'guard' => $user->getTable(),
            'email' => $user->getEmailForVerification(),
            'token' => $token,
        ]));

        $response->assertRedirect('/dashboard');
        $this->assertInertiaFlash('success', \__('Email verified.'));

        $user->refresh();
        static::assertNotNull($user->getEmailVerifiedAt());

        Event::assertDispatched(Verified::class);
    }

    public function test_valid_token_redirects_to_login_for_unauthenticated_visitor(): void
    {
        Event::fake();

        $user = Typer::assertInstance(UserFactory::new()->unverified()->createOne([
            'email' => 'unverified@example.com',
        ]), User::class);

        $token = EmailBrokerService::inject()->store($user->getTable(), $user->getEmailForVerification());

        $response = $this->get('/email/verify?' . \http_build_query([
            'guard' => $user->getTable(),
            'email' => $user->getEmailForVerification(),
            'token' => $token,
        ]));

        $response->assertRedirect('/login');
        $this->assertInertiaFlash('success', \__('Email verified.'));

        $user->refresh();
        static::assertNotNull($user->getEmailVerifiedAt());

        Event::assertDispatched(Verified::class);
    }

    public function test_already_verified_user_is_redirected_to_dashboard_with_idempotent_message(): void
    {
        Event::fake();

        $user = Typer::assertInstance(UserFactory::new()->createOne([
            'email' => 'verified@example.com',
        ]), User::class);

        $token = EmailBrokerService::inject()->store($user->getTable(), $user->getEmailForVerification());

        $response = $this->be($user, 'users')->get('/email/verify?' . \http_build_query([
            'guard' => $user->getTable(),
            'email' => $user->getEmailForVerification(),
            'token' => $token,
        ]));

        $response->assertRedirect('/dashboard');
        $this->assertInertiaFlash('success', \__('Email already verified.'));

        Event::assertNotDispatched(Verified::class);
    }

    public function test_invalid_token_redirects_to_login_with_error(): void
    {
        Typer::assertInstance(UserFactory::new()->unverified()->createOne([
            'email' => 'unverified@example.com',
        ]), User::class);

        $response = $this->get('/email/verify?' . \http_build_query([
            'guard' => 'users',
            'email' => 'unverified@example.com',
            'token' => 'not-a-real-token',
        ]));

        $response->assertRedirect('/login');
        $this->assertInertiaFlash('error', \__('The verification link is invalid or has expired.'));
    }

    public function test_unknown_email_redirects_to_login_with_error(): void
    {
        $response = $this->get('/email/verify?' . \http_build_query([
            'guard' => 'users',
            'email' => 'nobody@example.com',
            'token' => 'any-token',
        ]));

        $response->assertRedirect('/login');
        $this->assertInertiaFlash('error', \__('We can\'t find a user with that email address.'));
    }

    public function test_missing_parameters_return_422(): void
    {
        $response = $this->get('/email/verify');

        $response->assertStatus(422);
    }

    /**
     * Assert that the session carries an Inertia flash under the
     * dedicated `inertia.flash_data` key with the given message
     * (success or error).
     */
    protected function assertInertiaFlash(string $key, mixed $message): void
    {
        $flashed = \session(SessionKey::FLASH_DATA);

        static::assertIsArray($flashed);
        static::assertArrayHasKey($key, $flashed);
        static::assertSame($message, $flashed[$key]);
    }
}
