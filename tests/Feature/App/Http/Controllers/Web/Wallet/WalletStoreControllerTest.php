<?php

declare(strict_types=1);

use App\Models\RewardWallet;
use App\Services\Reward\RewardWalletService;

\test('POST /wallet creates a new wallet and redirects to the public page', function (): void {
    $response = $this->post('/wallet', [
        'phone' => '+420 601 234 567',
        'first_name' => 'Anička',
    ]);

    $response->assertRedirect();
    $wallet = RewardWallet::query()->where('first_name', 'Anička')->first();
    \expect($wallet)->not->toBeNull();
    \expect($response->headers->get('Location'))->toContain('/w/' . $wallet->getPublicToken());
});

\test('POST /wallet with an existing phone opens the existing wallet', function (): void {
    $service = $this->app->make(RewardWalletService::class);
    $existing = $service->createWallet('+420601234567', '+420 601 234 567', 'Existing Name');

    $response = $this->post('/wallet', [
        'phone' => '+420 601 234 567',
        'first_name' => 'New Name',
    ]);

    $response->assertRedirect();
    \expect(RewardWallet::query()->count())->toBe(1);
    \expect($existing->fresh()->getFirstName())->toBe('Existing Name'); // unchanged
});

\test('POST /wallet with invalid phone returns validation errors', function (): void {
    $response = $this->post('/wallet', [
        'phone' => 'not-a-phone',
        'first_name' => 'Anička',
    ], $this->inertiaHeaders());

    $response->assertStatus(422);
    $response->assertJsonPath('props.errors.phone.0', 'validation.phone');
});

\test('POST /wallet with missing first_name returns validation errors', function (): void {
    $response = $this->post('/wallet', [
        'phone' => '+420 601 234 567',
        'first_name' => '',
    ], $this->inertiaHeaders());

    $response->assertStatus(422);
    $response->assertJsonPath('props.errors.first_name.0', 'The first name field is required.');
});

\test('POST /wallet stores the phone in the standardized international form (no prefix)', function (): void {
    $this->post('/wallet', [
        'phone' => '730969399',
        'first_name' => 'Anička',
    ]);

    $wallet = RewardWallet::query()->where('first_name', 'Anička')->first();
    \expect($wallet)->not->toBeNull();
    \expect($wallet->getPhone())->toBe('+420 730 969 399');
    \expect($wallet->getPhoneNormalized())->toBe('+420730969399');
});

\test('POST /wallet stores the phone in the standardized international form (with prefix, no spaces)', function (): void {
    $this->post('/wallet', [
        'phone' => '+420730969399',
        'first_name' => 'Anička',
    ]);

    $wallet = RewardWallet::query()->where('first_name', 'Anička')->first();
    \expect($wallet)->not->toBeNull();
    \expect($wallet->getPhone())->toBe('+420 730 969 399');
    \expect($wallet->getPhoneNormalized())->toBe('+420730969399');
});

\test('POST /wallet with a Slovak number creates a wallet under the +421 country code', function (): void {
    $this->post('/wallet', [
        'phone' => '+421 911 123 456',
        'first_name' => 'Slovák',
    ]);

    $wallet = RewardWallet::query()->where('first_name', 'Slovák')->first();
    \expect($wallet)->not->toBeNull();
    \expect($wallet->getPhone())->toBe('+421 911 123 456');
    \expect($wallet->getPhoneNormalized())->toBe('+421911123456');
});

\test('POST /wallet with varied input formats for the same number reuses the same wallet', function (): void {
    $this->post('/wallet', ['phone' => '730 969 399', 'first_name' => 'Anička']);
    $this->post('/wallet', ['phone' => '+420730969399', 'first_name' => 'Anička']);
    $this->post('/wallet', ['phone' => '00420 730 969 399', 'first_name' => 'Anička']);

    \expect(RewardWallet::query()->count())->toBe(1);
    \expect(RewardWallet::query()->first()->getPhone())->toBe('+420 730 969 399');
});
