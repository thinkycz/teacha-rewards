<?php

declare(strict_types=1);

use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Models\User;

\test('GET /transactions lists transactions for staff', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();

    RewardTransaction::factory()->count(5)->create([
        'reward_wallet_id' => $wallet->getKey(),
        'user_id' => $staff->getKey(),
    ]);

    $response = $this->actingAs($staff)->get('/transactions');

    $response->assertOk();
    \expect(RewardTransaction::query()->count())->toBe(5);
});

\test('GET /transactions?type=redeem filters by type', function (): void {
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

    $response = $this->actingAs($staff)->get('/transactions?type=redeem');

    $response->assertOk();
    \expect(RewardTransaction::query()->where('type', 'redeem')->count())->toBe(1);
});

\test('GET /transactions?q= searches by note', function (): void {
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

    $response = $this->actingAs($staff)->get('/transactions?q=birthday');

    $response->assertOk();
    \expect(RewardTransaction::query()->where('note', 'birthday gift')->count())->toBe(1);
});

\test('GET /transactions redirects guests to login', function (): void {
    $response = $this->get('/transactions');

    $response->assertRedirect();
});

\test('GET /transactions paginates 25 transactions per page', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();
    RewardTransaction::factory()->count(30)->create([
        'reward_wallet_id' => $wallet->getKey(),
        'user_id' => $staff->getKey(),
    ]);

    $response = $this->actingAs($staff)->get('/transactions', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('props.transactions.per_page', 25);
    $response->assertJsonPath('props.transactions.total', 30);
    $response->assertJsonPath('props.transactions.last_page', 2);
    \expect($response->json('props.transactions.data'))->toHaveCount(25);
});

\test('GET /transactions?page=2 returns the second page', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();
    RewardTransaction::factory()->count(30)->create([
        'reward_wallet_id' => $wallet->getKey(),
        'user_id' => $staff->getKey(),
    ]);

    $response = $this->actingAs($staff)->get('/transactions?page=2', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('props.transactions.current_page', 2);
    \expect($response->json('props.transactions.data'))->toHaveCount(5);
});

\test('GET /transactions?type=…&page=2 filters and paginates together', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();
    RewardTransaction::factory()->count(30)->create([
        'reward_wallet_id' => $wallet->getKey(),
        'user_id' => $staff->getKey(),
    ]);
    RewardTransaction::factory()->redeem()->count(30)->create([
        'reward_wallet_id' => $wallet->getKey(),
        'user_id' => $staff->getKey(),
    ]);

    $response = $this->actingAs($staff)->get('/transactions?type=redeem&page=2', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('props.transactions.current_page', 2);
    $response->assertJsonPath('props.transactions.total', 30);
    \expect($response->json('props.transactions.data'))->toHaveCount(5);
});

\test('GET /transactions paginator preserves the current q in prev_page_url', function (): void {
    $staff = User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();
    RewardTransaction::factory()->count(30)->create([
        'reward_wallet_id' => $wallet->getKey(),
        'user_id' => $staff->getKey(),
        'note' => 'birthday gift',
    ]);

    $response = $this->actingAs($staff)->get('/transactions?q=birthday&page=2', $this->inertiaHeaders());

    $response->assertOk();
    \expect($response->json('props.transactions.prev_page_url'))->toContain('q=birthday');
    \expect($response->json('props.transactions.prev_page_url'))->toContain('page=1');
});
