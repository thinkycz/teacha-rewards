<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Setting;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\RoundingMode;
use Thinkycz\LaravelCore\Support\Typer;
use Throwable;

/**
 * Simple key/value settings service.
 *
 * `get` / `set` are deliberately minimal. The store-of-record is the
 * `settings` table; reads default to the caller-supplied value when the
 * key is missing, so callers can read with confidence without first
 * having to seed the table.
 */
class SettingsService
{
    /**
     * Read a setting by key, returning the default if the row is
     * missing.
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $row = Setting::query()->where('key', $key)->first();

        if ($row === null) {
            return $default;
        }

        $value = $row->getValue();

        // The column is a text type; callers that want a non-string
        // value go through a typed helper (see `getCashbackRate`).
        // We still try to coerce `null`/`true`/`false`/numeric literals
        // so the JSON returned to Inertia matches what was written.
        if ($value === '') {
            return $default;
        }

        if ($value === 'null') {
            return null;
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if (\is_numeric($value)) {
            return \str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }

    /**
     * Write a setting, upserting on `key`.
     */
    public function set(string $key, mixed $value): void
    {
        $serialized = $this->serialize($value);

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $serialized],
        );
    }

    /**
     * Read the cashback rate as a `BigDecimal` percentage.
     *
     * The value is always rounded to 2 decimal places so that a stored
     * `10` becomes `10.00` and a stored `10.555` becomes `10.56` (banker
     * rounding would be wrong for a money context).
     */
    public function getCashbackRate(): BigDecimal
    {
        $raw = $this->get('cashback_rate', '10');

        if (! \is_scalar($raw)) {
            return BigDecimal::of('10.00');
        }

        // After the type guard above, $raw is int|float|string. The
        // cast is the documented runtime step; PHPStan's bleedingEdge
        // rules will not narrow mixed through the guard alone.
        try {
            return BigDecimal::of(Typer::assertString((string) $raw))->toScale(2, RoundingMode::HalfUp);
        } catch (NumberFormatException) {
            return BigDecimal::of('10.00');
        } catch (Throwable) {
            return BigDecimal::of('10.00');
        }
    }

    /**
     * Coerce a value to its stored `text` representation.
     */
    protected function serialize(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        if (\is_array($value) || \is_object($value)) {
            return Typer::assertString(\json_encode($value, \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR));
        }

        return Typer::assertString($value);
    }
}
