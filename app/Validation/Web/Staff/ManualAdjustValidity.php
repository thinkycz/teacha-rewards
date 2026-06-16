<?php

declare(strict_types=1);

namespace App\Validation\Web\Staff;

use App\Enums\ManualAdjustmentTypeEnum;
use Thinkycz\LaravelCore\Validation\BaseValidity;
use Thinkycz\LaravelCore\Validation\Validity;

/**
 * Validity for the "manual adjustment" form.
 *
 * `type` is one of the three `ManualAdjustmentTypeEnum` cases.
 * `amount` is a positive decimal; for `set` the service applies it as
 * the new balance, for `add` and `subtract` it's the delta.
 * `note` is required (the service throws a `Thrower` on empty note too,
 * but we also want a 422 to redirect the cashier back to the form).
 */
class ManualAdjustValidity
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

    public function type(): Validity
    {
        return $this->baseValidity
            ->make()
            ->varchar()
            ->required()
            ->in(ManualAdjustmentTypeEnum::values());
    }

    public function amount(): Validity
    {
        return $this->baseValidity
            ->make()
            ->decimal(8, 2)
            ->required()
            ->min(0.01);
    }

    public function note(): Validity
    {
        return $this->baseValidity
            ->make()
            ->varchar()
            ->required()
            ->min(2)
            ->max(255);
    }
}
