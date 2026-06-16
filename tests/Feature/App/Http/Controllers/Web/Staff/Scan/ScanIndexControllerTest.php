<?php

declare(strict_types=1);

use App\Models\RewardWallet;

\test('GET /staff/scan returns 200 for staff', function (): void {
    $user = \App\Models\User::factory()->staff()->create();

    $response = $this->actingAs($user)->get('/staff/scan');

    $response->assertOk();
});

\test('GET /staff/scan redirects guests to login', function (): void {
    $response = $this->get('/staff/scan');

    $response->assertRedirect();
});

\test('GET /staff/scan/{token} resolves the wallet for staff', function (): void {
    $user = \App\Models\User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();

    $response = $this->actingAs($user)->get('/staff/scan/' . $wallet->getPublicToken());

    $response->assertOk();
});

\test('GET /staff/scan/{token} with an unknown token returns 404', function (): void {
    $user = \App\Models\User::factory()->staff()->create();

    $response = $this->actingAs($user)->get('/staff/scan/this-token-does-not-exist');

    $response->assertStatus(404);
});
