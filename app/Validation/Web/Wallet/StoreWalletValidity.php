<?php

declare(strict_types=1);

namespace App\Validation\Web\Wallet;

use Propaganistas\LaravelPhone\Rules\Phone;
use Thinkycz\LaravelCore\Validation\AuthValidity;
use Thinkycz\LaravelCore\Validation\BaseValidity;
use Thinkycz\LaravelCore\Validation\Validity;

/**
 * Validity for the public "create or open my wallet" form.
 *
 * The `phone` rule is `propaganistas/laravel-phone`'s `Phone` rule
 * with the `cz,mobile` country-and-type constraint, so a customer can
 * type any of `+420 601 234 567`, `601234567`, or `00420 601 234 567`
 * and pass validation. Normalization to E.164 happens in
 * `RewardWalletService::normalizePhone` after validation.
 *
 * `first_name` is a required varchar (re-uses `AuthValidity::name`).
 */
class StoreWalletValidity
{
    /**
     * Base validity.
     */
    public BaseValidity $baseValidity;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->baseValidity = new BaseValidity();
    }

    /**
     * Inject.
     */
    public static function inject(): self
    {
        return new self();
    }

    /**
     * Phone number rules.
     */
    public function phone(): Validity
    {
        return $this->baseValidity
            ->make()
            ->varchar()
            ->required()
            ->customRule(new Phone('cz', 'mobile'));
    }

    /**
     * First name rules.
     */
    public function firstName(): Validity
    {
        return (new AuthValidity())->name()->required();
    }
}
