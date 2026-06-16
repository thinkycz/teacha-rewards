<?php

declare(strict_types=1);

use App\Enums\TransactionTypeEnum;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Models\User;

\test('POST /staff/wallets/{wallet}/purchase credits cashback based on the current rate', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();

    $response = $this->actingAs($staff)->post(
        "/staff/wallets/{$wallet->getKey()}/purchase",
        ['purchase_amount' => '200.00'],
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();

    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('20.00');
    \expect($wallet->getLifetimeEarned())->toBe('20.00');

    $tx = RewardTransaction::query()
        ->where('reward_wallet_id', $wallet->getKey())
        ->where('type', TransactionTypeEnum::PURCHASE_CASHBACK->value)
        ->first();
    \expect($tx)->not->toBeNull();
    \expect($tx?->getAmount())->toBe('20.00');
    \expect($tx?->getPurchaseAmount())->toBe('200.00');
    \expect($tx?->getCashbackRate())->toBe('10.00');
    \expect($tx?->getBalanceBefore())->toBe('0.00');
    \expect($tx?->getBalanceAfter())->toBe('20.00');
});

\test('POST /staff/wallets/{wallet}/purchase applies a custom cashback rate from settings', function (): void {
    \App\Models\Setting::query()->updateOrCreate(['key' => 'cashback_rate'], ['value' => '25']);

    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();

    $response = $this->actingAs($staff)->post(
        "/staff/wallets/{$wallet->getKey()}/purchase",
        ['purchase_amount' => '80.00'],
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();
    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('20.00');
});

\test('POST /staff/wallets/{wallet}/purchase rejects a non-positive amount', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();

    $response = $this->actingAs($staff)->post(
        "/staff/wallets/{$wallet->getKey()}/purchase",
        ['purchase_amount' => '0'],
        $this->inertiaHeaders(),
    );

    $response->assertStatus(422);
    \expect($response->json('props.errors.purchase_amount'))->toBeArray();
    \expect(\count($response->json('props.errors.purchase_amount')))->toBeGreaterThan(0);
});

\test('POST /staff/wallets/{wallet}/purchase rejects an unknown wallet', function (): void {
    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)->post(
        '/staff/wallets/999999/purchase',
        ['purchase_amount' => '50.00'],
        $this->inertiaHeaders(),
    );

    $response->assertStatus(404);
});

\test('POST /staff/wallets/{wallet}/purchase is forbidden to a guest', function (): void {
    $wallet = RewardWallet::factory()->create();

    $response = $this->post(
        "/staff/wallets/{$wallet->getKey()}/purchase",
        ['purchase_amount' => '50.00'],
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();
});
