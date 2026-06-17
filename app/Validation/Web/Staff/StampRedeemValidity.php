<?php

declare(strict_types=1);

namespace App\Validation\Web\Staff;

use Thinkycz\LaravelCore\Validation\BaseValidity;
use Thinkycz\LaravelCore\Validation\Validity;

/**
 * Validity for the cashier "Redeem free reward" action (stamps mode).
 *
 * The cashier chooses how many rewards to redeem (1 up to
 * `floor(stamps_count / stamps_per_reward)`). The service-layer
 * check is authoritative; this validator just bounds the input.
 */
class StampRedeemValidity
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

    public function rewards(): Validity
    {
        return $this->baseValidity
            ->make()
            ->numeric(null, 0)
            ->required()
            ->min(1)
            ->max(100);
    }
}
