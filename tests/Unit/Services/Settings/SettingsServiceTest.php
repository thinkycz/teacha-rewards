<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\Settings\SettingsService;
use Brick\Math\BigDecimal;

\beforeEach(function (): void {
    $this->service = $this->app->make(SettingsService::class);
});

\test('get returns the supplied default when the key is missing', function (): void {
    \expect($this->service->get('does_not_exist', 'fallback'))->toBe('fallback');
});

\test('get returns null when the key is missing and no default is given', function (): void {
    \expect($this->service->get('does_not_exist'))->toBeNull();
});

\test('set then get round-trips a string', function (): void {
    $this->service->set('program_name', 'Teacha Rewards');

    \expect($this->service->get('program_name'))->toBe('Teacha Rewards');
});

\test('set then get round-trips an integer', function (): void {
    $this->service->set('cashback_rate', '10');

    \expect($this->service->get('cashback_rate'))->toBe(10);
});

\test('set then get round-trips a float', function (): void {
    $this->service->set('cashback_rate', '12.5');

    \expect($this->service->get('cashback_rate'))->toBe(12.5);
});

\test('set then get round-trips a bool', function (): void {
    $this->service->set('flag', true);

    \expect($this->service->get('flag'))->toBeTrue();
});

\test('set then get round-trips null', function (): void {
    $this->service->set('nullable', null);

    \expect($this->service->get('nullable'))->toBeNull();
});

\test('set updates an existing key instead of inserting a duplicate', function (): void {
    $this->service->set('cashback_rate', '10');
    $this->service->set('cashback_rate', '20');

    \expect(Setting::query()->where('key', 'cashback_rate')->count())->toBe(1);
    \expect($this->service->get('cashback_rate'))->toBe(20);
});

\test('getCashbackRate returns 10.00 by default when no row is set', function (): void {
    \expect((string) $this->service->getCashbackRate())->toBe('10.00');
});

\test('getCashbackRate reads the stored value rounded to 2 decimals', function (): void {
    $this->service->set('cashback_rate', '12.555');

    \expect((string) $this->service->getCashbackRate())->toBe('12.56');
});

\test('getCashbackRate falls back to 10.00 when the stored value is unparseable', function (): void {
    Setting::query()->updateOrCreate(['key' => 'cashback_rate'], ['value' => 'not-a-number']);

    \expect((string) $this->service->getCashbackRate())->toBe('10.00');
});

\test('getCashbackRate returns a BigDecimal instance', function (): void {
    \expect($this->service->getCashbackRate())->toBeInstanceOf(BigDecimal::class);
});
