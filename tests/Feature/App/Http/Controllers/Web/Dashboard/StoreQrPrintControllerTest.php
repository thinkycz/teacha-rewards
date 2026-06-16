<?php

declare(strict_types=1);

\test('GET /dashboard/store-qr returns the printable store QR sheet for staff', function (): void {
    $staff = \App\Models\User::factory()->staff()->create();

    $response = $this->actingAs($staff)->get('/dashboard/store-qr');

    $response->assertOk();
});

\test('GET /dashboard/store-qr returns 200 for an admin too', function (): void {
    $admin = \App\Models\User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/dashboard/store-qr');

    $response->assertOk();
});

\test('GET /dashboard/store-qr redirects guests to login', function (): void {
    $response = $this->get('/dashboard/store-qr');

    $response->assertRedirect();
});
