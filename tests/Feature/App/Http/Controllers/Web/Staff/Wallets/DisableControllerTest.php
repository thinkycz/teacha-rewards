<?php

declare(strict_types=1);

use App\Enums\WalletStatusEnum;
use App\Models\RewardWallet;
use App\Models\User;

\test('POST /staff/wallets/{wallet}/disable flips status to disabled', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'status' => WalletStatusEnum::ACTIVE->value,
    ]);

    $response = $this->actingAs($staff)->post(
        "/staff/wallets/{$wallet->getKey()}/disable",
        [],
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();
    $wallet->refresh();
    \expect($wallet->getStatus())->toBe(WalletStatusEnum::DISABLED);
});

\test('POST /staff/wallets/{wallet}/enable flips status back to active', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->disabled()->create();

    $response = $this->actingAs($staff)->post(
        "/staff/wallets/{$wallet->getKey()}/enable",
        [],
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();
    $wallet->refresh();
    \expect($wallet->getStatus())->toBe(WalletStatusEnum::ACTIVE);
});

\test('POST /staff/wallets/{wallet}/disable is forbidden to a guest', function (): void {
    $wallet = RewardWallet::factory()->create();

    $response = $this->post(
        "/staff/wallets/{$wallet->getKey()}/disable",
        [],
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();
});
