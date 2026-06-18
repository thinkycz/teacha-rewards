<?php

declare(strict_types=1);

namespace App\Validation\Web\Wallet;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberType;
use Propaganistas\LaravelPhone\PhoneNumber;

/**
 * Validates that a phone number is a valid mobile number.
 *
 * The default region is `'CZ'`: a number without a `+` prefix is
 * treated as a Czech mobile. A number with an explicit `+XXX`
 * prefix is parsed under that country.
 *
 * Used by `StoreWalletValidity` to replace propaganistas's
 * `phone:mobile`, which cannot express "CZ default + any country
 * with a prefix" in its rule grammar (the country parameter both
 * sets the parsing default AND enforces a strict country check,
 * which would reject `+49…` and similar).
 */
class MobilePhoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!\is_string($value)) {
            $fail('validation.phone')->translate();

            return;
        }

        if (!$this->isValidMobile($value)) {
            $fail('validation.phone')->translate();
        }
    }

    /**
     * Parse the candidate as a mobile number, defaulting to `'CZ'`
     * when no `+` prefix is present.
     */
    protected function isValidMobile(string $candidate): bool
    {
        foreach (['CZ', null] as $default) {
            try {
                $instance = new PhoneNumber($candidate, $default);
            } catch (NumberParseException) {
                continue;
            }

            if ($instance->isValid() && $instance->isOfType(PhoneNumberType::MOBILE)) {
                return true;
            }
        }

        return false;
    }
}
