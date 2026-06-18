<?php

declare(strict_types=1);

namespace App\Services\Reward;

use App\Enums\WalletStatusEnum;
use App\Enums\WalletTypeEnum;
use App\Models\RewardWallet;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

/**
 * Customer-wallet management.
 *
 * Phone is the natural key (E.164 in `phone_normalized`). `public_token`
 * is the unguessable 32-char URL-safe identifier that goes in the QR
 * code and the `/w/{token}` URL. `wallet_number` is the short printed
 * code on the card.
 */
class RewardWalletService
{
    /**
     * @param SettingsService $settings required so the service can be
     *                                  used in tests where the
     *                                  service container isn't
     *                                  resolved
     */
    public function __construct(
        protected SettingsService $settings,
    ) {
    }

    /**
     * Look up an existing wallet for the phone, or create one.
     *
     * If a wallet exists, `first_name` is updated only when currently
     * empty (plan §5.1 rule).
     *
     * The lookup and create run inside a single `DB::transaction` with
     * `lockForUpdate()` so two concurrent requests for the same number
     * can't both decide to create. The DB-level `unique` index on
     * `phone_normalized` is the final safety net; the controller turns
     * any leaked `QueryException` into a friendly error.
     */
    public function findOrCreateByPhone(string $phone, string $firstName): RewardWallet
    {
        return DB::transaction(function () use ($phone, $firstName): RewardWallet {
            $normalized = $this->normalizePhone($phone);
            $display = $this->formatDisplayPhone($phone);

            $wallet = RewardWallet::query()
                ->where('phone_normalized', $normalized)
                ->lockForUpdate()
                ->first();

            if ($wallet !== null) {
                $existingName = \trim($wallet->getFirstName());

                if ($existingName === '' && \trim($firstName) !== '') {
                    $wallet->forceFill(['first_name' => \trim($firstName)])->save();
                }

                return $wallet;
            }

            return $this->createWallet($normalized, $display, \trim($firstName) !== '' ? \trim($firstName) : '');
        });
    }

    /**
     * Normalize a phone number to E.164, throwing if the input is not
     * a valid number.
     *
     * The `Phone` cast from `propaganistas/laravel-phone` is the
     * canonical normalizer. The default region is `'CZ'`: a number
     * without a `+` prefix is treated as Czech; a number with an
     * explicit `+XXX` prefix is parsed under that country.
     */
    public function normalizePhone(string $phone): string
    {
        return $this->parsePhone($phone)->formatE164();
    }

    /**
     * Format a phone number for display (international, with
     * spaces — e.g. `+420 730 969 399`).
     *
     * Always derives from a fresh parse so the form is stable
     * regardless of how the user typed the input.
     */
    public function formatDisplayPhone(string $phone): string
    {
        return $this->parsePhone($phone)->formatInternational();
    }

    /**
     * Create a new wallet for a (normalized) phone + display name.
     *
     * The wallet's `type` is captured here, once, from the current
     * `program_mode` setting — and never changes again. Later flips
     * of the global program_mode affect only wallets created from
     * that point on.
     *
     * Public — callers that need to upsert by phone should use
     * `findOrCreateByPhone`. `$displayPhone` is the standardized
     * international form (e.g. `+420 730 969 399`, produced by
     * `formatDisplayPhone`); `$normalized` is the E.164 canonical
     * form.
     */
    public function createWallet(string $normalized, string $displayPhone, string $firstName): RewardWallet
    {
        return RewardWallet::query()->create([
            'uuid' => (string) Str::uuid(),
            'public_token' => $this->makePublicToken(),
            'wallet_number' => $this->makeWalletNumber(),
            'type' => $this->resolveInitialType()->value,
            'first_name' => $firstName,
            'phone' => \trim($displayPhone),
            'phone_normalized' => $normalized,
            'rewards_balance' => '0.00',
            'lifetime_earned' => '0.00',
            'lifetime_redeemed' => '0.00',
            'status' => WalletStatusEnum::ACTIVE->value,
        ]);
    }

    /**
     * Look up a wallet by its public token, or throw.
     */
    public function getByPublicToken(string $token): RewardWallet
    {
        $wallet = RewardWallet::query()
            ->where('public_token', $token)
            ->first();

        if (!$wallet instanceof RewardWallet) {
            throw (new ModelNotFoundException())->setModel(RewardWallet::class, [$token]);
        }

        return $wallet;
    }

    /**
     * Parse `$phone` against the `'CZ'` default region, throwing if
     * the result is empty or not a valid number.
     *
     * Centralised so `normalizePhone` and `formatDisplayPhone` share
     * a single source of truth for the parse step.
     */
    protected function parsePhone(string $phone): PhoneNumber
    {
        $candidate = \trim($phone);

        if ($candidate === '') {
            throw new NumberParseException(NumberParseException::INVALID_COUNTRY_CODE, 'Phone number is empty.');
        }

        $instance = new PhoneNumber($candidate, 'CZ');

        if (!$instance->isValid()) {
            throw new NumberParseException(NumberParseException::NOT_A_NUMBER, $candidate . ' is not a valid phone number.');
        }

        return $instance;
    }

    /**
     * Resolve the type a brand-new wallet should be created with.
     *
     * The global `program_mode` setting is the only signal we have
     * at create time. If the setting holds a value we don't
     * recognize (a legacy DB row, a manual SQL edit) we fall back
     * to cashback rather than refuse to create the wallet.
     */
    protected function resolveInitialType(): WalletTypeEnum
    {
        $raw = $this->settings->getProgramMode();

        return WalletTypeEnum::tryFrom($raw) ?? WalletTypeEnum::CASHBACK;
    }

    /**
     * Generate a 32-char URL-safe public token.
     *
     * `Str::random(32)` draws from `[A-Za-z0-9]`, which is fully
     * URL-safe and gives 192 bits of entropy. No further filter step
     * is needed.
     */
    protected function makePublicToken(): string
    {
        try {
            return Str::random(32);
        } catch (Throwable) {
            // Fallback for tests where the random generator is mocked.
            return \bin2hex(\random_bytes(16));
        }
    }

    /**
     * Generate a short human-readable wallet number, `T-XXXX-XXXX`.
     *
     * The two 4-char blocks are uppercase letters + digits for an
     * easily-pronounced code that's also quick to type.
     */
    protected function makeWalletNumber(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return 'T-' . $this->randomFrom($alphabet, 4) . '-' . $this->randomFrom($alphabet, 4);
    }

    /**
     * Pick `$length` characters from `$alphabet` using
     * `random_int` (CSPRNG).
     */
    protected function randomFrom(string $alphabet, int $length): string
    {
        $max = \mb_strlen($alphabet) - 1;
        $out = '';

        for ($i = 0; $i < $length; ++$i) {
            $out .= $alphabet[\random_int(0, $max)];
        }

        return $out;
    }
}
