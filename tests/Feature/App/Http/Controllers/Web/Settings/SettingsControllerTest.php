<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\UserFactory;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

\test('authenticated user can view settings page', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $response = $this->be($user, 'users')->get('/profile', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('component', 'settings/Index');
});

\test('guest is redirected from settings to login', function (): void {
    $response = $this->get('/profile');

    $response->assertRedirect('/login');
});

\test('user can update profile email and locale', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $response = $this->be($user, 'users')->from('/profile')->post('/profile', [
        'email' => 'new-email@example.com',
        'locale' => 'cs',
    ], $this->inertiaHeaders());

    // PRG: mutating POST redirects back; the success flash survives the
    // 302 → render chain via Inertia::flash().
    $response->assertRedirect('/profile');
    \assertInertiaFlash($response, 'success', \__('Profile updated.'));

    $user->refresh();
    static::assertSame('new-email@example.com', $user->getEmail());
    static::assertSame('cs', $user->getLocale());
});

\test('profile update rejects invalid email', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $originalEmail = $user->getEmail();

    $response = $this->be($user, 'users')->post('/profile', [
        'email' => 'not-an-email',
        'locale' => 'en',
    ]);

    $response->assertStatus(422);

    $user->refresh();
    static::assertSame($originalEmail, $user->getEmail());
});

\test('profile update rejects email already in use', function (): void {
    $existing = Typer::assertInstance(UserFactory::new()->createOne([
        'email' => 'taken@example.com',
    ]), User::class);

    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

    $response = $this->be($user, 'users')->post('/profile', [
        'email' => $existing->getEmail(),
        'locale' => 'en',
    ]);

    $response->assertStatus(422);
});

\test('user can update their password', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $originalHash = $user->getAuthPassword();

    $response = $this->be($user, 'users')->from('/profile')->post('/profile/password', [
        'password' => UserFactory::$password,
        'new_password' => 'new-password-123',
    ], $this->inertiaHeaders());

    // PRG: mutating POST redirects back; the success flash survives the
    // 302 → render chain via Inertia::flash().
    $response->assertRedirect('/profile');
    \assertInertiaFlash($response, 'success', \__('Password updated.'));

    $user->refresh();
    static::assertNotSame($originalHash, $user->getAuthPassword());
    static::assertTrue(Resolver::resolveHasher()->check('new-password-123', $user->getAuthPassword()));
});

\test('password update rejects wrong current password', function (): void {
    $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);
    $originalHash = $user->getAuthPassword();

    $response = $this->be($user, 'users')->post('/profile/password', [
        'password' => 'not-the-current-password',
        'new_password' => 'new-password-123',
    ]);

    $response->assertStatus(422);

    $user->refresh();
    static::assertSame($originalHash, $user->getAuthPassword());
});
