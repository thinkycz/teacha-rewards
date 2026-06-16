<?php

declare(strict_types=1);

use App\Services\Reward\RewardWalletService;

\test('GET /w/{token} returns 200 and shows the wallet', function (): void {
    /** @var RewardWalletService $service */
    $service = $this->app->make(RewardWalletService::class);
    $wallet = $service->createWallet('+420601234567', '+420 601 234 567', 'Anička');

    $response = $this->get('/w/' . $wallet->getPublicToken());

    $response->assertOk();
});

\test('GET /w/{token} with a bad token returns 404', function (): void {
    $response = $this->get('/w/totally-bogus-token');

    $response->assertNotFound();
});

\test('GET /w/{token} does not require authentication', function (): void {
    /** @var RewardWalletService $service */
    $service = $this->app->make(RewardWalletService::class);
    $wallet = $service->createWallet('+420601234567', '+420 601 234 567', 'Anička');

    $response = $this->get('/w/' . $wallet->getPublicToken());

    $response->assertOk();
});
