<?php

declare(strict_types=1);

namespace App\Validation\Web\Staff;

use Thinkycz\LaravelCore\Validation\BaseValidity;
use Thinkycz\LaravelCore\Validation\Validity;

/**
 * Validity for the cashier "Add stamps" action (stamps mode only).
 *
 * The cashier clicks the "Add stamps" tile N times for N drinks
 * paid at full price. A single submission also accepts an explicit
 * `count` so the batch form (1 / 5 / 10 / custom) can submit
 * without N clicks.
 */
class StampEarnValidity
{
    public BaseValidity $baseValidity;

    public function __construct()
    {
        $this->baseValidity = new BaseValidity();
    }

    public static function inject(): self
    {
        return new self();
    }

    public function count(): Validity
    {
        return $this->baseValidity
            ->make()
            ->numeric(null, 0)
            ->required()
            ->min(1)
            ->max(100);
    }
}
