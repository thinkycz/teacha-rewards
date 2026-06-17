<?php

declare(strict_types=1);

namespace App\Validation\Web\Staff;

use Thinkycz\LaravelCore\Validation\AuthValidity;
use Thinkycz\LaravelCore\Validation\BaseValidity;
use Thinkycz\LaravelCore\Validation\Validity;

/**
 * Validity for the admin "settings" page.
 *
 * The settings page lets the admin pick a program mode
 * (cashback | stamps), the rate/threshold for that mode, plus
 * currency, program name, and store name. The currency is a
 * 3-letter code (uppercase) per ISO 4217 — the cashier scanner
 * needs the same value to render amounts in the right currency.
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
            ->numeric(null, 0)
            ->decimal(0, 2)
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

    public function programMode(): Validity
    {
        return $this->baseValidity
            ->make()
            ->varchar()
            ->required()
            ->in(['cashback', 'stamps']);
    }

    public function stampsPerReward(): Validity
    {
        return $this->baseValidity
            ->make()
            ->numeric(null, 0)
            ->required()
            ->min(1)
            ->max(1000);
    }

    public function stampsRewardLabel(): Validity
    {
        return $this->baseValidity
            ->make()
            ->varchar()
            ->required()
            ->max(64);
    }
}
