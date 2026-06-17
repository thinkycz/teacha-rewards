<?php

declare(strict_types=1);

use App\Enums\TransactionTypeEnum;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Models\Setting;
use App\Models\User;

\test('POST /wallets/{id}/stamps/earn increments stamps_count and writes a stamp_earn row', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->stamps()->create(['stamps_count' => 2]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/stamps/earn",
        ['count' => '3'],
    );

    $response->assertRedirect();
    $wallet->refresh();
    \expect($wallet->getStampsCount())->toBe(5);

    $tx = RewardTransaction::query()->latest('id')->first();
    \expect($tx)->not->toBeNull();
    \expect($tx->getType())->toBe(TransactionTypeEnum::STAMP_EARN);
    \expect((int) $tx->getAmount())->toBe(3);
    \expect($tx->getBalanceBefore())->toBe('2.00');
    \expect($tx->getBalanceAfter())->toBe('5.00');
});

\test('POST /wallets/{id}/stamps/redeem deducts stamps_per_reward * rewards and writes a stamp_redeem row', function (): void {
    Setting::query()->updateOrCreate(['key' => 'stamps_per_reward'], ['value' => '10']);
    Setting::query()->updateOrCreate(['key' => 'stamps_per_reward_label'], ['value' => 'Free drink']);

    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->stamps()->create(['stamps_count' => 25]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/stamps/redeem",
        ['rewards' => '2'],
    );

    $response->assertRedirect();
    $wallet->refresh();
    // 2 free rewards * 10 stamps/reward = 20 deducted, 5 left.
    \expect($wallet->getStampsCount())->toBe(5);

    $tx = RewardTransaction::query()->latest('id')->first();
    \expect($tx)->not->toBeNull();
    \expect($tx->getType())->toBe(TransactionTypeEnum::STAMP_REDEEM);
    \expect((int) $tx->getAmount())->toBe(-2);
    \expect($tx->getBalanceBefore())->toBe('25.00');
    \expect($tx->getBalanceAfter())->toBe('5.00');
});

\test('stamps-mode actions leave rewards_balance untouched (wallet type is fixed at creation)', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->stamps()->create([
        'rewards_balance' => '50.00',
        'stamps_count' => 3,
    ]);

    $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/stamps/earn",
        ['count' => '2'],
    )->assertRedirect();

    $wallet->refresh();
    // Even if the rewards_balance column is non-zero on a stamps
    // wallet (legacy data), the stamps path must not touch it.
    \expect($wallet->getRewardsBalance())->toBe('50.00');
    \expect($wallet->getStampsCount())->toBe(5);
});

\test('SettingsService::getStampsPerReward reads from the settings table', function (): void {
    Setting::query()->updateOrCreate(['key' => 'stamps_per_reward'], ['value' => '12']);
    Setting::query()->updateOrCreate(['key' => 'stamps_per_reward_label'], ['value' => 'Free matcha']);

    $service = app(\App\Services\Settings\SettingsService::class);
    \expect($service->getStampsPerReward())->toBe(12);
    \expect($service->getStampsRewardLabel())->toBe('Free matcha');
});

\test('SettingsService::getProgramMode defaults to cashback and reads stamps', function (): void {
    Setting::query()->where('key', 'program_mode')->delete();
    $service = app(\App\Services\Settings\SettingsService::class);
    \expect($service->getProgramMode())->toBe('cashback');

    Setting::query()->updateOrCreate(['key' => 'program_mode'], ['value' => 'stamps']);
    \expect($service->getProgramMode())->toBe('stamps');
});

/*
 * Type-mismatch gates.
 *
 * A wallet's type is set at creation (from the current program_mode
 * default) and is immutable. The four reward endpoints must each
 * refuse to act on the wrong type, regardless of the global
 * program_mode setting — a cashback action on a stamps wallet (or
 * vice versa) would silently corrupt the balance.
 *
 * Crucially, these tests do not set program_mode. The gate is on
 * wallet.type, not on the global setting.
 */

\test('POST /wallets/{id}/purchase is refused on a stamps wallet regardless of program_mode', function (): void {
    // program_mode is intentionally left at its default (cashback).
    // A stamps wallet still can't be credited via the purchase
    // endpoint, because wallet.type wins.
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->stamps()->create([
        'rewards_balance' => '12.00',
        'stamps_count' => 4,
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/purchase",
        ['purchase_amount' => '100.00'],
    );

    $response->assertRedirect(\route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]));
    $response->assertSessionHas('inertia.flash_data.error', \__('reward.action_unavailable_for_wallet_type'));

    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('12.00');
    \expect($wallet->getStampsCount())->toBe(4);

    // No transaction row should be written for the refused action.
    \expect(RewardTransaction::query()->count())->toBe(0);
});

\test('POST /wallets/{id}/redeem is refused on a stamps wallet regardless of program_mode', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->stamps()->create([
        'rewards_balance' => '12.00',
        'stamps_count' => 4,
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/redeem",
        ['amount' => '5.00'],
    );

    $response->assertRedirect(\route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]));
    $response->assertSessionHas('inertia.flash_data.error', \__('reward.action_unavailable_for_wallet_type'));

    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('12.00');
    \expect($wallet->getStampsCount())->toBe(4);
    \expect(RewardTransaction::query()->count())->toBe(0);
});

\test('POST /wallets/{id}/stamps/earn is refused on a cashback wallet regardless of program_mode', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->cashback()->create([
        'rewards_balance' => '12.00',
        'stamps_count' => 4,
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/stamps/earn",
        ['count' => '3'],
    );

    $response->assertRedirect(\route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]));
    $response->assertSessionHas('inertia.flash_data.error', \__('reward.action_unavailable_for_wallet_type'));

    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('12.00');
    \expect($wallet->getStampsCount())->toBe(4);
    \expect(RewardTransaction::query()->count())->toBe(0);
});

\test('POST /wallets/{id}/stamps/redeem is refused on a cashback wallet regardless of program_mode', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->cashback()->create([
        'rewards_balance' => '12.00',
        'stamps_count' => 25,
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/stamps/redeem",
        ['rewards' => '1'],
    );

    $response->assertRedirect(\route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]));
    $response->assertSessionHas('inertia.flash_data.error', \__('reward.action_unavailable_for_wallet_type'));

    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('12.00');
    \expect($wallet->getStampsCount())->toBe(25);
    \expect(RewardTransaction::query()->count())->toBe(0);
});

/*
 * Wallet creation: the wallet's type follows the program_mode
 * default at the moment of creation, and a later change to
 * program_mode must not affect already-created wallets.
 */

\test('new wallet created in stamps mode carries type=stamps, even after program_mode flips to cashback', function (): void {
    Setting::query()->updateOrCreate(['key' => 'program_mode'], ['value' => 'stamps']);

    $service = app(\App\Services\Reward\RewardWalletService::class);
    $wallet = $service->findOrCreateByPhone('+420 601 111 222', 'Anička');

    \expect($wallet->getType()->value)->toBe('stamps');

    // Admin later flips the default to cashback. The existing
    // wallet's type must not change.
    Setting::query()->updateOrCreate(['key' => 'program_mode'], ['value' => 'cashback']);

    $wallet->refresh();
    \expect($wallet->getType()->value)->toBe('stamps');
});
