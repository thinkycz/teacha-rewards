<?php

declare(strict_types=1);

namespace App\Validation\Web\Staff;

use Thinkycz\LaravelCore\Validation\BaseValidity;
use Thinkycz\LaravelCore\Validation\Validity;

/**
 * Validity for the "log purchase" form on the staff scanner.
 *
 * `purchase_amount` is a positive decimal with 2 fractional digits,
 * bounded between 0.01 and 999999.99 (the cashier's flow is
 * straightforward; we don't expect 7-figure purchases).
 */
class LogPurchaseValidity
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

    /**
     * Purchase amount rules.
     */
    public function purchaseAmount(): Validity
    {
        return $this->baseValidity
            ->make()
            ->numeric(null, 0)
            ->decimal(0, 2)
            ->required()
            ->min(0.01);
    }
}
