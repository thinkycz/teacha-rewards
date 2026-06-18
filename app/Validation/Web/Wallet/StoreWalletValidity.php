<?php

declare(strict_types=1);

namespace App\Validation\Web\Wallet;

use Thinkycz\LaravelCore\Validation\AuthValidity;
use Thinkycz\LaravelCore\Validation\BaseValidity;
use Thinkycz\LaravelCore\Validation\Validity;

/**
 * Validity for the public "create or open my wallet" form.
 *
 * The `phone` rule is the custom `MobilePhoneRule`: a number without
 * a `+` prefix is treated as a Czech mobile, and a number with an
 * explicit `+XXX` prefix is parsed under that country. Normalization
 * to E.164 happens in `RewardWalletService::parsePhone` after
 * validation, using the same `'CZ'` default.
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
            ->addRule(new MobilePhoneRule());
    }

    /**
     * First name rules.
     */
    public function firstName(): Validity
    {
        return (new AuthValidity())->name()->required();
    }
}
