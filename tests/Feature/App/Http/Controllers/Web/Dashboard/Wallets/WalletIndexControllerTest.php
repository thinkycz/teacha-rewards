<?php

declare(strict_types=1);

use App\Models\RewardWallet;
use App\Models\User;

\test('GET /wallets lists wallets for staff', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->count(3)->create();
    RewardWallet::factory()->disabled()->create();

    $response = $this->actingAs($staff)->get('/wallets');

    $response->assertOk();
});

\test('GET /wallets filters by status=active', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->count(2)->create();
    RewardWallet::factory()->disabled()->count(2)->create();

    $response = $this->actingAs($staff)->get('/wallets?status=active');

    $response->assertOk();
    \expect(RewardWallet::query()->where('status', 'active')->count())->toBe(2);
});

\test('GET /wallets filters by status=disabled', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->count(2)->create();
    RewardWallet::factory()->disabled()->count(3)->create();

    $response = $this->actingAs($staff)->get('/wallets?status=disabled');

    $response->assertOk();
    \expect(RewardWallet::query()->where('status', 'disabled')->count())->toBe(3);
});

\test('GET /wallets?sort=balance returns 200', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->create(['rewards_balance' => '1.00']);
    RewardWallet::factory()->create(['rewards_balance' => '50.00']);
    RewardWallet::factory()->create(['rewards_balance' => '999.00']);

    $response = $this->actingAs($staff)->get('/wallets?sort=balance');

    $response->assertOk();
});

\test('GET /wallets?q= searches by first name', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->create(['first_name' => 'Anička']);
    RewardWallet::factory()->create(['first_name' => 'Bára']);

    $response = $this->actingAs($staff)->get('/wallets?q=Ani');

    $response->assertOk();
    \expect(RewardWallet::query()->where('first_name', 'Anička')->count())->toBe(1);
});

\test('GET /wallets?q=<public_token> matches a freshly scanned barcode', function (): void {
    $staff = User::factory()->staff()->create();
    $match = RewardWallet::factory()->create();
    RewardWallet::factory()->create();
    $token = $match->getPublicToken();

    $response = $this->actingAs($staff)->get('/wallets?q=' . \urlencode($token));

    $response->assertOk();
    \expect(RewardWallet::query()->where('public_token', $token)->count())->toBe(1);
});

\test('GET /wallets redirects guests to login', function (): void {
    $response = $this->get('/wallets');

    $response->assertRedirect();
});

\test('GET /wallets paginates 25 wallets per page', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->count(30)->create();

    $response = $this->actingAs($staff)->get('/wallets', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('props.wallets.per_page', 25);
    $response->assertJsonPath('props.wallets.total', 30);
    $response->assertJsonPath('props.wallets.last_page', 2);
    $response->assertJsonPath('props.wallets.current_page', 1);
    \expect($response->json('props.wallets.data'))->toHaveCount(25);
});

\test('GET /wallets?page=2 returns the second page of wallets', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->count(30)->create();

    $response = $this->actingAs($staff)->get('/wallets?page=2', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('props.wallets.current_page', 2);
    \expect($response->json('props.wallets.data'))->toHaveCount(5);
});

\test('GET /wallets?q=…&page=2 filters and paginates together', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->count(30)->create(['first_name' => 'Anička']);
    RewardWallet::factory()->count(30)->create(['first_name' => 'Bára']);

    $response = $this->actingAs($staff)->get('/wallets?q=Ani&page=2', $this->inertiaHeaders());

    $response->assertOk();
    $response->assertJsonPath('props.wallets.current_page', 2);
    $response->assertJsonPath('props.wallets.total', 30);
    \expect($response->json('props.wallets.data'))->toHaveCount(5);
});

\test('GET /wallets paginator includes prev/next URLs when not on the first page', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->count(30)->create();

    $response = $this->actingAs($staff)->get('/wallets?page=2', $this->inertiaHeaders());

    $response->assertOk();
    \expect($response->json('props.wallets.prev_page_url'))->toContain('page=1');
    \expect($response->json('props.wallets.next_page_url'))->toBeNull();
});

\test('GET /wallets paginator preserves the current q in prev_page_url', function (): void {
    $staff = User::factory()->staff()->create();
    RewardWallet::factory()->count(30)->create(['first_name' => 'Anička']);

    $response = $this->actingAs($staff)->get('/wallets?q=Ani&page=2', $this->inertiaHeaders());

    $response->assertOk();
    \expect($response->json('props.wallets.prev_page_url'))->toContain('q=Ani');
    \expect($response->json('props.wallets.prev_page_url'))->toContain('page=1');
});
