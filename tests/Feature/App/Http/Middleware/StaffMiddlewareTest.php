<?php

declare(strict_types=1);

use App\Models\User;

\test('EnsureStaffRole allows admin users through', function (): void {
    $admin = User::factory()->admin()->create();
    $response = $this->actingAs($admin)->get('/staff');
    $response->assertOk();
});

\test('EnsureStaffRole allows staff users through', function (): void {
    $staff = User::factory()->staff()->create();
    $response = $this->actingAs($staff)->get('/staff');
    $response->assertOk();
});

\test('EnsureStaffRole blocks guests with a redirect', function (): void {
    $response = $this->get('/staff');
    $response->assertRedirect();
});

\test('EnsureAdminRole allows admin users through', function (): void {
    $admin = User::factory()->admin()->create();
    $response = $this->actingAs($admin)->get('/staff/settings');
    $response->assertOk();
});

\test('EnsureAdminRole blocks regular staff with a 403', function (): void {
    $staff = User::factory()->staff()->create();
    $response = $this->actingAs($staff)->get('/staff/settings');
    $response->assertStatus(403);
});
