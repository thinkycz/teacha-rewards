<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;

\test('GET /staff/settings shows the admin form for an admin user', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/staff/settings');

    $response->assertOk();
});

\test('GET /staff/settings uses the saved values when present', function (): void {
    Setting::query()->updateOrCreate(['key' => 'cashback_rate'], ['value' => '15']);
    Setting::query()->updateOrCreate(['key' => 'currency'], ['value' => 'EUR']);
    Setting::query()->updateOrCreate(['key' => 'store_name'], ['value' => 'Test Store']);

    $admin = User::factory()->admin()->create();
    $response = $this->actingAs($admin)->get('/staff/settings');

    $response->assertOk();
    \expect(Setting::query()->where('key', 'cashback_rate')->value('value'))->toBe('15');
});

\test('GET /staff/settings is forbidden to a regular staff user', function (): void {
    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)->get('/staff/settings');

    $response->assertStatus(403);
});

\test('GET /staff/settings redirects guests to login', function (): void {
    $response = $this->get('/staff/settings');

    $response->assertRedirect();
});

\test('POST /staff/settings updates the four values', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(
        '/staff/settings',
        [
            'cashback_rate' => '12.5',
            'currency' => 'EUR',
            'program_name' => 'Teacha Plus',
            'store_name' => 'Test Store',
        ],
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();
    \expect(Setting::query()->where('key', 'cashback_rate')->value('value'))->toBe('12.5');
    \expect(Setting::query()->where('key', 'currency')->value('value'))->toBe('EUR');
    \expect(Setting::query()->where('key', 'program_name')->value('value'))->toBe('Teacha Plus');
    \expect(Setting::query()->where('key', 'store_name')->value('value'))->toBe('Test Store');
});

\test('POST /staff/settings rejects a cashback rate above 100', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(
        '/staff/settings',
        [
            'cashback_rate' => '500',
            'currency' => 'EUR',
            'program_name' => 'Teacha Plus',
            'store_name' => 'Test Store',
        ],
        $this->inertiaHeaders(),
    );

    $response->assertStatus(422);
    \expect($response->json('props.errors.cashback_rate'))->toBeArray();
    \expect(\count($response->json('props.errors.cashback_rate')))->toBeGreaterThan(0);
});

\test('POST /staff/settings is forbidden to a regular staff user', function (): void {
    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)->post(
        '/staff/settings',
        [
            'cashback_rate' => '10',
            'currency' => 'CZK',
            'program_name' => 'Teacha',
            'store_name' => 'Teacha',
        ],
        $this->inertiaHeaders(),
    );

    $response->assertStatus(403);
});
