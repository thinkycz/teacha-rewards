<?php

declare(strict_types=1);

use App\Models\RewardWallet;

\test('GET /dashboard/scan returns 200 for staff', function (): void {
    $user = \App\Models\User::factory()->staff()->create();

    $response = $this->actingAs($user)->get('/dashboard/scan');

    $response->assertOk();
});

\test('GET /dashboard/scan redirects guests to login', function (): void {
    $response = $this->get('/dashboard/scan');

    $response->assertRedirect();
});

\test('GET /dashboard/scan/{token} resolves the wallet for staff', function (): void {
    $user = \App\Models\User::factory()->staff()->create();
    $wallet = RewardWallet::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard/scan/' . $wallet->getPublicToken());

    $response->assertOk();
});

\test('GET /dashboard/scan/{token} with an unknown token returns 404', function (): void {
    $user = \App\Models\User::factory()->staff()->create();

    $response = $this->actingAs($user)->get('/dashboard/scan/this-token-does-not-exist');

    $response->assertStatus(404);
});
