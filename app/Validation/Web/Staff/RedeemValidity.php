<?php

declare(strict_types=1);

namespace App\Validation\Web\Staff;

use Thinkycz\LaravelCore\Validation\BaseValidity;
use Thinkycz\LaravelCore\Validation\Validity;

/**
 * Validity for the "redeem rewards" form.
 *
 * `amount` is a positive decimal with 2 fractional digits, at least
 * 0.01. The "cannot exceed balance" rule is enforced in the service
 * layer (it depends on the current wallet row), not here.
 */
class RedeemValidity
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

    public function amount(): Validity
    {
        return $this->baseValidity
            ->make()
            ->decimal(8, 2)
            ->required()
            ->min(0.01);
    }
}
