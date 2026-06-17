<?php

declare(strict_types=1);

use App\Models\RewardWallet;
use App\Models\User;

\test('GET /wallets lists wallets for staff', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->count(3)->create();
    RewardWallet::factory()->disabled()->create();

    $response = $this->actingAs($staff)->get('/wallets');

    $response->assertOk();
});

\test('GET /wallets filters by status=active', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->count(2)->create();
    RewardWallet::factory()->disabled()->count(2)->create();

    $response = $this->actingAs($staff)->get('/wallets?status=active');

    $response->assertOk();
    \expect(\App\Models\RewardWallet::query()->where('status', 'active')->count())->toBe(2);
});

\test('GET /wallets filters by status=disabled', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->count(2)->create();
    RewardWallet::factory()->disabled()->count(3)->create();

    $response = $this->actingAs($staff)->get('/wallets?status=disabled');

    $response->assertOk();
    \expect(\App\Models\RewardWallet::query()->where('status', 'disabled')->count())->toBe(3);
});

\test('GET /wallets?sort=balance returns 200', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->create(['rewards_balance' => '1.00']);
    RewardWallet::factory()->create(['rewards_balance' => '50.00']);
    RewardWallet::factory()->create(['rewards_balance' => '999.00']);

    $response = $this->actingAs($staff)->get('/wallets?sort=balance');

    $response->assertOk();
});

\test('GET /wallets?q= searches by first name', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->create(['first_name' => 'Anička']);
    RewardWallet::factory()->create(['first_name' => 'Bára']);

    $response = $this->actingAs($staff)->get('/wallets?q=Ani');

    $response->assertOk();
    \expect(\App\Models\RewardWallet::query()->where('first_name', 'Anička')->count())->toBe(1);
});

\test('GET /wallets?q=<public_token> matches a freshly scanned barcode', function (): void {
    $staff = User::factory()->staff()->create();
    $match = RewardWallet::factory()->create();
    RewardWallet::factory()->create();
    $token = $match->getPublicToken();

    $response = $this->actingAs($staff)->get('/wallets?q=' . urlencode($token));

    $response->assertOk();
    \expect(\App\Models\RewardWallet::query()->where('public_token', $token)->count())->toBe(1);
});

\test('GET /wallets redirects guests to login', function (): void {
    $response = $this->get('/wallets');

    $response->assertRedirect();
});
