<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;

function fullPayload(): array
{
    return [
        'cashback_rate' => '12.5',
        'currency' => 'EUR',
        'program_name' => 'Teacha Plus',
        'store_name' => 'Test Store',
        'program_mode' => 'cashback',
        'stamps_per_reward' => '10',
        'stamps_per_reward_label' => 'Free drink',
    ];
}

\test('GET /settings shows the admin form for an admin user', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/settings');

    $response->assertOk();
});

\test('GET /settings uses the saved values when present', function (): void {
    Setting::query()->updateOrCreate(['key' => 'cashback_rate'], ['value' => '15']);
    Setting::query()->updateOrCreate(['key' => 'currency'], ['value' => 'EUR']);
    Setting::query()->updateOrCreate(['key' => 'store_name'], ['value' => 'Test Store']);

    $admin = User::factory()->admin()->create();
    $response = $this->actingAs($admin)->get('/settings');

    $response->assertOk();
    \expect(Setting::query()->where('key', 'cashback_rate')->value('value'))->toBe('15');
});

\test('GET /settings is forbidden to a regular staff user', function (): void {
    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)->get('/settings');

    $response->assertStatus(403);
});

\test('GET /settings redirects guests to login', function (): void {
    $response = $this->get('/settings');

    $response->assertRedirect();
});

\test('POST /settings updates the seven values', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(
        '/settings',
        fullPayload(),
        $this->inertiaHeaders(),
    );

    $response->assertRedirect();
    \expect(Setting::query()->where('key', 'cashback_rate')->value('value'))->toBe('12.5');
    \expect(Setting::query()->where('key', 'currency')->value('value'))->toBe('EUR');
    \expect(Setting::query()->where('key', 'program_name')->value('value'))->toBe('Teacha Plus');
    \expect(Setting::query()->where('key', 'store_name')->value('value'))->toBe('Test Store');
    \expect(Setting::query()->where('key', 'program_mode')->value('value'))->toBe('cashback');
    \expect(Setting::query()->where('key', 'stamps_per_reward')->value('value'))->toBe('10');
    \expect(Setting::query()->where('key', 'stamps_per_reward_label')->value('value'))->toBe('Free drink');
});

\test('POST /settings rejects a cashback rate above 100', function (): void {
    $admin = User::factory()->admin()->create();

    $payload = fullPayload();
    $payload['cashback_rate'] = '500';

    $response = $this->actingAs($admin)->post(
        '/settings',
        $payload,
        $this->inertiaHeaders(),
    );

    $response->assertStatus(422);
    \expect($response->json('props.errors.cashback_rate'))->toBeArray();
    \expect(\count($response->json('props.errors.cashback_rate')))->toBeGreaterThan(0);
});

\test('POST /settings rejects an invalid program_mode', function (): void {
    $admin = User::factory()->admin()->create();

    $payload = fullPayload();
    $payload['program_mode'] = 'lottery';

    $response = $this->actingAs($admin)->post(
        '/settings',
        $payload,
        $this->inertiaHeaders(),
    );

    $response->assertStatus(422);
    \expect($response->json('props.errors.program_mode'))->toBeArray();
});

\test('POST /settings is forbidden to a regular staff user', function (): void {
    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)->post(
        '/settings',
        fullPayload(),
        $this->inertiaHeaders(),
    );

    $response->assertStatus(403);
});
