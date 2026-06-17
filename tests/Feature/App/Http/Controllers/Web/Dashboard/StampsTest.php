<?php

declare(strict_types=1);

use App\Enums\TransactionTypeEnum;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Models\Setting;
use App\Models\User;

\test('POST /wallets/{id}/stamps/earn increments stamps_count and writes a stamp_earn row', function (): void {
    Setting::query()->updateOrCreate(['key' => 'program_mode'], ['value' => 'stamps']);

    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create(['stamps_count' => 2]);

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
    Setting::query()->updateOrCreate(['key' => 'program_mode'], ['value' => 'stamps']);
    Setting::query()->updateOrCreate(['key' => 'stamps_per_reward'], ['value' => '10']);
    Setting::query()->updateOrCreate(['key' => 'stamps_per_reward_label'], ['value' => 'Free drink']);

    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create(['stamps_count' => 25]);

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

\test('cashier stamps-mode actions leave rewards_balance untouched (mode flip is non-destructive)', function (): void {
    Setting::query()->updateOrCreate(['key' => 'program_mode'], ['value' => 'stamps']);

    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '50.00',
        'stamps_count' => 3,
    ]);

    $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/stamps/earn",
        ['count' => '2'],
    )->assertRedirect();

    $wallet->refresh();
    // Cashback balance survives the mode toggle.
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
 * Mode-mismatch gates.
 *
 * The two program modes (cashback / stamps) must never bleed into
 * each other: a cashier logging a cashback-style purchase while the
 * shop is in stamps mode (or vice versa) would silently corrupt the
 * balance. Each of the four reward endpoints refuses with a
 * translated error flash when the active mode doesn't match.
 */

\test('POST /wallets/{id}/purchase is refused in stamps mode and does not move the balance', function (): void {
    Setting::query()->updateOrCreate(['key' => 'program_mode'], ['value' => 'stamps']);

    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '12.00',
        'stamps_count' => 4,
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/purchase",
        ['purchase_amount' => '100.00'],
    );

    $response->assertRedirect(\route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]));
    $response->assertSessionHas('inertia.flash_data.error', \__('reward.action_requires_cashback_mode'));

    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('12.00');
    \expect($wallet->getStampsCount())->toBe(4);

    // No transaction row should be written for the refused action.
    \expect(RewardTransaction::query()->count())->toBe(0);
});

\test('POST /wallets/{id}/redeem is refused in stamps mode and does not move the balance', function (): void {
    Setting::query()->updateOrCreate(['key' => 'program_mode'], ['value' => 'stamps']);

    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '12.00',
        'stamps_count' => 4,
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/redeem",
        ['amount' => '5.00'],
    );

    $response->assertRedirect(\route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]));
    $response->assertSessionHas('inertia.flash_data.error', \__('reward.action_requires_cashback_mode'));

    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('12.00');
    \expect($wallet->getStampsCount())->toBe(4);
    \expect(RewardTransaction::query()->count())->toBe(0);
});

\test('POST /wallets/{id}/stamps/earn is refused in cashback mode and does not move the balance', function (): void {
    Setting::query()->updateOrCreate(['key' => 'program_mode'], ['value' => 'cashback']);

    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '12.00',
        'stamps_count' => 4,
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/stamps/earn",
        ['count' => '3'],
    );

    $response->assertRedirect(\route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]));
    $response->assertSessionHas('inertia.flash_data.error', \__('reward.action_requires_stamps_mode'));

    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('12.00');
    \expect($wallet->getStampsCount())->toBe(4);
    \expect(RewardTransaction::query()->count())->toBe(0);
});

\test('POST /wallets/{id}/stamps/redeem is refused in cashback mode and does not move the balance', function (): void {
    Setting::query()->updateOrCreate(['key' => 'program_mode'], ['value' => 'cashback']);

    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create([
        'rewards_balance' => '12.00',
        'stamps_count' => 25,
    ]);

    $response = $this->actingAs($staff)->post(
        "/wallets/{$wallet->getKey()}/stamps/redeem",
        ['rewards' => '1'],
    );

    $response->assertRedirect(\route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]));
    $response->assertSessionHas('inertia.flash_data.error', \__('reward.action_requires_stamps_mode'));

    $wallet->refresh();
    \expect($wallet->getRewardsBalance())->toBe('12.00');
    \expect($wallet->getStampsCount())->toBe(25);
    \expect(RewardTransaction::query()->count())->toBe(0);
});
