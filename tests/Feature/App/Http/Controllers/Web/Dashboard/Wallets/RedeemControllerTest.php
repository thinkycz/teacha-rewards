<?php

declare(strict_types=1);

use App\Enums\TransactionTypeEnum;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Models\User;

\test('POST /dashboard/wallets/{wallet}/redeem debits the wallet balance', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '50.00',
        'lifetime_redeemed' => '0.00',
    ]);

    $response = $this->actingAs($staff)->post(
        "/dashboard/wallets/{$wallet->getKey()}/redeem",
        ['amount' => '20.00'],
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();

    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('30.00');
    \expect($wallet->getLifetimeRedeemed())->toBe('20.00');

    $tx = RewardTransaction::query()
        ->where('reward_wallet_id', $wallet->getKey())
        ->where('type', TransactionTypeEnum::REDEEM->value)
        ->first();
    \expect($tx)->not->toBeNull();
    \expect($tx?->getAmount())->toBe('-20.00');
    \expect($tx?->getBalanceBefore())->toBe('50.00');
    \expect($tx?->getBalanceAfter())->toBe('30.00');
});

\test('POST /dashboard/wallets/{wallet}/redeem rejects an amount larger than the balance with a friendly error', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '10.00',
    ]);

    $response = $this->actingAs($staff)->post(
        "/dashboard/wallets/{$wallet->getKey()}/redeem",
        ['amount' => '50.00'],
        $this->inertiaHeaders(),
    );

    // The service rejects with a Thrower (ValidationException), which
    // the Inertia exception handler renders as 422 + the translated
    // `reward.redeem_amount_exceeds_balance` message.
    $response->assertStatus(422);
    \expect($response->json('props.errors'))->toBeArray();
    \expect(\count($response->json('props.errors')))->toBeGreaterThan(0);
    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('10.00');
});

\test('POST /dashboard/wallets/{wallet}/redeem rejects a non-positive amount', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '50.00',
    ]);

    $response = $this->actingAs($staff)->post(
        "/dashboard/wallets/{$wallet->getKey()}/redeem",
        ['amount' => '0'],
        $this->inertiaHeaders(),
    );

    $response->assertStatus(422);
    \expect($response->json('props.errors.amount'))->toBeArray();
    \expect(\count($response->json('props.errors.amount')))->toBeGreaterThan(0);
});

\test('POST /dashboard/wallets/{wallet}/redeem is forbidden to a guest', function (): void {
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '50.00',
    ]);

    $response = $this->post(
        "/dashboard/wallets/{$wallet->getKey()}/redeem",
        ['amount' => '10.00'],
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();
});
