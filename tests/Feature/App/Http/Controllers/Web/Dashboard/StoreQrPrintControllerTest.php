<?php

declare(strict_types=1);

\test('GET /store-qr returns the printable store QR sheet for staff', function (): void {
    $staff = App\Models\User::factory()->staff()->create();

    $response = $this->actingAs($staff)->get('/store-qr');

    $response->assertOk();
});

\test('GET /store-qr returns 200 for an admin too', function (): void {
    $admin = App\Models\User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/store-qr');

    $response->assertOk();
});

\test('GET /store-qr redirects guests to login', function (): void {
    $response = $this->get('/store-qr');

    $response->assertRedirect();
});
