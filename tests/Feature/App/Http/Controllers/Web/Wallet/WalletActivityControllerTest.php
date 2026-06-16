<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Reward\RewardTransactionService;
use App\Services\Reward\RewardWalletService;
use Brick\Math\BigDecimal;

\test('GET /w/{token}/activity returns 200 and renders the activity page', function (): void {
    $staff = User::factory()->staff()->create();

    /** @var RewardWalletService $service */
    $service = $this->app->make(RewardWalletService::class);
    $wallet = $service->createWallet('+420601234567', '+420 601 234 567', 'Anička');

    /** @var RewardTransactionService $txService */
    $txService = $this->app->make(RewardTransactionService::class);
    $txService->logPurchase($wallet, BigDecimal::of('100'), $staff);
    $txService->logPurchase($wallet, BigDecimal::of('50'), $staff);

    $response = $this->get('/w/' . $wallet->getPublicToken() . '/activity');

    $response->assertOk();
});

\test('GET /w/{token}/activity with bad token returns 404', function (): void {
    $response = $this->get('/w/totally-bogus-token/activity');

    $response->assertNotFound();
});
