<?php

declare(strict_types=1);

use App\Enums\TransactionTypeEnum;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Models\Setting;
use App\Models\User;

\test('POST /wallets/{id}/stamps/earn increments stamps_count and writes a stamp_earn row', function (): void {
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
