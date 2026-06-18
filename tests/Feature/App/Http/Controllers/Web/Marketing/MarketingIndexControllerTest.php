<?php

declare(strict_types=1);

\test('GET / returns 200 for guests', function (): void {
    $response = $this->get('/');

    $response->assertOk();
});

\test('GET / returns 200 for authenticated users too', function (): void {
    $user = App\Models\User::factory()->staff()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertOk();
});
