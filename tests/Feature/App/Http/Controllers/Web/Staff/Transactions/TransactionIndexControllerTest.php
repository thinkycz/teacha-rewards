<?php

declare(strict_types=1);

use App\Enums\TransactionTypeEnum;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Models\User;
use Carbon\Carbon;

\test('GET /staff/transactions lists transactions for staff', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();

    RewardTransaction::factory()->count(5)->create([
        'reward_wallet_id' => $wallet->getKey(),
        'user_id' => $staff->getKey(),
    ]);

    $response = $this->actingAs($staff)->get('/staff/transactions');

    $response->assertOk();
    \expect(RewardTransaction::query()->count())->toBe(5);
});

\test('GET /staff/transactions?type=redeem filters by type', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();

    RewardTransaction::factory()->create([
        'reward_wallet_id' => $wallet->getKey(),
        'user_id' => $staff->getKey(),
    ]);
    RewardTransaction::factory()->redeem()->create([
        'reward_wallet_id' => $wallet->getKey(),
        'user_id' => $staff->getKey(),
        'note' => 'coffee discount',
    ]);

    $response = $this->actingAs($staff)->get('/staff/transactions?type=redeem');

    $response->assertOk();
    \expect(RewardTransaction::query()->where('type', 'redeem')->count())->toBe(1);
});

\test('GET /staff/transactions?q= searches by note', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();

    RewardTransaction::factory()->manualAdd()->create([
        'reward_wallet_id' => $wallet->getKey(),
        'user_id' => $staff->getKey(),
        'note' => 'birthday gift',
    ]);
    RewardTransaction::factory()->create([
        'reward_wallet_id' => $wallet->getKey(),
        'user_id' => $staff->getKey(),
    ]);

    $response = $this->actingAs($staff)->get('/staff/transactions?q=birthday');

    $response->assertOk();
    \expect(RewardTransaction::query()->where('note', 'birthday gift')->count())->toBe(1);
});

\test('GET /staff/transactions redirects guests to login', function (): void {
    $response = $this->get('/staff/transactions');

    $response->assertRedirect();
});
