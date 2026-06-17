<?php

declare(strict_types=1);

use App\Enums\ManualAdjustmentTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Models\User;

\test('POST /wallets/{wallet}/adjust type=add credits the wallet and records a note', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '10.00',
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/adjust",
        [
            'type' => ManualAdjustmentTypeEnum::ADD->value,
            'amount' => '15.00',
            'note' => 'Goodwill credit',
        ],
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();
    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('25.00');

    $tx = RewardTransaction::query()
        ->where('reward_wallet_id', $wallet->getKey())
        ->where('type', TransactionTypeEnum::MANUAL_ADD->value)
        ->first();
    \expect($tx)->not->toBeNull();
    \expect($tx?->getNote())->toBe('Goodwill credit');
    \expect($tx?->getUserId())->toBe($staff->getKey());
});

\test('POST /wallets/{wallet}/adjust type=subtract debits the wallet', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '50.00',
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/adjust",
        [
            'type' => ManualAdjustmentTypeEnum::SUBTRACT->value,
            'amount' => '20.00',
            'note' => 'Refund reversal',
        ],
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();
    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('30.00');
});

/*
 * Manual adjust on stamps wallets must write to `stamps_count`,
 * not `rewards_balance`. The audit found the service was always
 * touching `rewards_balance` regardless of wallet type — a cashier
 * adjusting a stamps wallet was silently mutating the wrong column.
 */

\test('POST /wallets/{wallet}/adjust type=add on a stamps wallet credits stamps_count, not rewards_balance', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->stamps()->create([
        'stamps_count' => 4,
        'rewards_balance' => '0.00',
    ]);

    $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/adjust",
        [
            'type' => ManualAdjustmentTypeEnum::ADD->value,
            'amount' => '3',
            'note' => 'Missed stamp',
        ],
        $this->inertiaHeaders(),
    )->assertRedirect();

    $wallet->refresh();
    \expect($wallet->getStampsCount())->toBe(7);
    // rewards_balance must stay untouched on a stamps wallet.
    \expect($wallet->getRewardsBalance())->toBe('0.00');

    $tx = RewardTransaction::query()
        ->where('reward_wallet_id', $wallet->getKey())
        ->where('type', TransactionTypeEnum::MANUAL_ADD->value)
        ->first();
    \expect($tx)->not->toBeNull();
    \expect((int) $tx->getAmount())->toBe(3);
    // The `balance_before` / `balance_after` columns are `decimal(10,2)`
    // so they always read back with two decimal places (e.g. "4.00").
    \expect($tx->getBalanceBefore())->toBe('4.00');
    \expect($tx->getBalanceAfter())->toBe('7.00');
});

\test('POST /wallets/{wallet}/adjust type=subtract on a stamps wallet debits stamps_count', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->stamps()->create([
        'stamps_count' => 10,
    ]);

    $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/adjust",
        [
            'type' => ManualAdjustmentTypeEnum::SUBTRACT->value,
            'amount' => '3',
            'note' => 'Counted wrong',
        ],
        $this->inertiaHeaders(),
    )->assertRedirect();

    $wallet->refresh();
    \expect($wallet->getStampsCount())->toBe(7);
});

\test('POST /wallets/{wallet}/adjust type=set on a stamps wallet overwrites stamps_count', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->stamps()->create([
        'stamps_count' => 8,
    ]);

    $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/adjust",
        [
            'type' => ManualAdjustmentTypeEnum::SET->value,
            'amount' => '5',
            'note' => 'Reconcile after audit',
        ],
        $this->inertiaHeaders(),
    )->assertRedirect();

    $wallet->refresh();
    \expect($wallet->getStampsCount())->toBe(5);
});

\test('POST /wallets/{wallet}/adjust type=subtract on a stamps wallet refuses to underflow', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->stamps()->create([
        'stamps_count' => 2,
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/adjust",
        [
            'type' => ManualAdjustmentTypeEnum::SUBTRACT->value,
            'amount' => '5',
            'note' => 'Should fail',
        ],
        $this->inertiaHeaders(),
    );

    $response->assertStatus(422);
    \expect($response->json('props.errors'))->toBeArray();

    $wallet->refresh();
    \expect($wallet->getStampsCount())->toBe(2);
});


\test('POST /wallets/{wallet}/adjust type=set overwrites the balance', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '99.00',
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/adjust",
        [
            'type' => ManualAdjustmentTypeEnum::SET->value,
            'amount' => '0.00',
            'note' => 'Reset to zero',
        ],
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();
    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('0.00');
});

\test('POST /wallets/{wallet}/adjust rejects a note that is too short', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/adjust",
        [
            'type' => ManualAdjustmentTypeEnum::ADD->value,
            'amount' => '5.00',
            'note' => 'x',
        ],
        $this->inertiaHeaders(),
    );

    $response->assertStatus(422);
    \expect($response->json('props.errors.note'))->toBeArray();
    \expect(\count($response->json('props.errors.note')))->toBeGreaterThan(0);
});

\test('POST /wallets/{wallet}/adjust rejects an unknown type', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/adjust",
        [
            'type' => 'bogus',
            'amount' => '5.00',
            'note' => 'whatever',
        ],
        $this->inertiaHeaders(),
    );

    $response->assertStatus(422);
});

\test('POST /wallets/{wallet}/adjust rejects a subtract larger than the balance', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '5.00',
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/adjust",
        [
            'type' => ManualAdjustmentTypeEnum::SUBTRACT->value,
            'amount' => '99.00',
            'note' => 'typo test',
        ],
        $this->inertiaHeaders(),
    );

    // The service rejects with a Thrower (ValidationException), which
    // the Inertia exception handler renders as 422.
    $response->assertStatus(422);
    \expect($response->json('props.errors'))->toBeArray();
    \expect(\count($response->json('props.errors')))->toBeGreaterThan(0);
    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('5.00');
});
