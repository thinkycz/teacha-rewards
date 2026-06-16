<?php

declare(strict_types=1);

use App\Models\RewardWallet;
use App\Models\User;

\test('GET /dashboard/wallets/{wallet} shows the full wallet view for staff', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '42.50',
        'lifetime_earned' => '100.00',
        'lifetime_redeemed' => '57.50',
    ]);

    $response = $this->actingAs($staff)->get('/dashboard/wallets/' . $wallet->getKey());

    $response->assertOk();
});

\test('GET /dashboard/wallets/{wallet} with an unknown id returns 404', function (): void {
    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)->get('/dashboard/wallets/999999');

    $response->assertStatus(404);
});

\test('GET /dashboard/wallets/{wallet} redirects guests to login', function (): void {
    $wallet = RewardWallet::factory()->create();

    $response = $this->get('/dashboard/wallets/' . $wallet->getKey());

    $response->assertRedirect();
});
