<?php

declare(strict_types=1);

namespace App\Validation\Web\Staff;

use Thinkycz\LaravelCore\Validation\AuthValidity;
use Thinkycz\LaravelCore\Validation\BaseValidity;
use Thinkycz\LaravelCore\Validation\Validity;

/**
 * Validity for the admin "settings" page.
 *
 * The settings page lets the admin change the cashback rate, currency,
 * program name, and store name. The currency is a 3-letter code
 * (uppercase) per ISO 4217 — the cashier scanner needs the same value
 * to render amounts in the right currency.
 */
class SettingsValidity
{
    public BaseValidity $baseValidity;

    public AuthValidity $authValidity;

    public function __construct()
    {
        $this->baseValidity = new BaseValidity();
        $this->authValidity = new AuthValidity();
    }

    public static function inject(): self
    {
        return new self();
    }

    public function cashbackRate(): Validity
    {
        return $this->baseValidity
            ->make()
            ->decimal(5, 2)
            ->required()
            ->min(0)
            ->max(100);
    }

    public function currency(): Validity
    {
        return $this->baseValidity
            ->make()
            ->varchar()
            ->required()
            ->size(3);
    }

    public function programName(): Validity
    {
        return $this->authValidity->name()->required();
    }

    public function storeName(): Validity
    {
        return $this->authValidity->name()->required();
    }
}
