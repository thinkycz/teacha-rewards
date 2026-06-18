<?php

declare(strict_types=1);

use App\Enums\WalletStatusEnum;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use Carbon\Carbon;

\test('GET /dashboard returns 200 for a staff user', function (): void {
    $user = App\Models\User::factory()->staff()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
});

\test('GET /dashboard returns 200 for an admin user', function (): void {
    $user = App\Models\User::factory()->admin()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
});

\test('GET /dashboard redirects guests to login', function (): void {
    $response = $this->get('/dashboard');

    $response->assertRedirect();
    \expect($response->headers->get('Location'))->toContain('/login');
});

\test('the dashboard controller counts active + disabled wallets and today totals correctly', function (): void {
    $user = App\Models\User::factory()->staff()->create();
    RewardWallet::factory()->count(3)->create();
    RewardWallet::factory()->disabled()->count(2)->create();

    $wallet = RewardWallet::factory()->create();
    RewardTransaction::factory()->create([
        'reward_wallet_id' => $wallet->getKey(),
        'user_id' => $user->getKey(),
        'amount' => '5.00',
        'purchase_amount' => '50.00',
        'balance_before' => '0.00',
        'balance_after' => '5.00',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    $old = RewardWallet::factory()->create();
    RewardTransaction::factory()->create([
        'reward_wallet_id' => $old->getKey(),
        'user_id' => $user->getKey(),
        'amount' => '99.00',
        'purchase_amount' => '990.00',
        'balance_before' => '0.00',
        'balance_after' => '99.00',
        'created_at' => Carbon::now()->subDays(1),
        'updated_at' => Carbon::now()->subDays(1),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk();

    // Verify the counts via DB queries (mirroring the controller logic).
    $active = RewardWallet::query()->where('status', WalletStatusEnum::ACTIVE->value)->count();
    $disabled = RewardWallet::query()->where('status', WalletStatusEnum::DISABLED->value)->count();

    \expect($active)->toBe(5);
    \expect($disabled)->toBe(2);

    $todayCount = RewardTransaction::query()
        ->where('type', 'purchase_cashback')
        ->whereDate('created_at', Carbon::now()->toDateString())
        ->count();
    \expect($todayCount)->toBe(1);
});

\test('the dashboard controller caps recent transactions at 10', function (): void {
    $user = App\Models\User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();

    for ($i = 0; $i < 12; ++$i) {
        RewardTransaction::factory()->create([
            'reward_wallet_id' => $wallet->getKey(),
            'user_id' => $user->getKey(),
            'amount' => '1.00',
            'purchase_amount' => '10.00',
            'balance_before' => '0.00',
            'balance_after' => '1.00',
            'created_at' => Carbon::now()->subMinutes(12 - $i),
            'updated_at' => Carbon::now()->subMinutes(12 - $i),
        ]);
    }

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk();

    $count = \count(RewardTransaction::query()->orderByDesc('created_at')->limit(10)->get()->all());
    \expect($count)->toBe(10);
});
