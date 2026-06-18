<?php

declare(strict_types=1);

\test('GET /offline returns the offline fallback page for any user', function (): void {
    $response = $this->get('/offline');

    $response->assertOk();
});

\test('GET /install returns the PWA install guide', function (): void {
    $response = $this->get('/install');

    $response->assertOk();
});

\test('GET /offline returns 200 for guests and authenticated users alike', function (): void {
    $user = App\Models\User::factory()->staff()->create();

    $guest = $this->get('/offline');
    $guest->assertOk();

    $auth = $this->actingAs($user)->get('/offline');
    $auth->assertOk();
});

\test('GET /install returns 200 for guests and authenticated users alike', function (): void {
    $user = App\Models\User::factory()->staff()->create();

    $guest = $this->get('/install');
    $guest->assertOk();

    $auth = $this->actingAs($user)->get('/install');
    $auth->assertOk();
});
