<?php

declare(strict_types=1);

namespace App\Services\Reward;

use App\Enums\WalletStatusEnum;
use App\Models\RewardWallet;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
     * @param SettingsService $settings Required so the service can be
     *                                   used in tests where the
     *                                   service container isn't
     *                                   resolved.
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
     * @throws NumberParseException When `$phone` is not a valid
     *                              phone number.
     */
    public function findOrCreateByPhone(string $phone, string $firstName): RewardWallet
    {
        $normalized = $this->normalizePhone($phone);

        $wallet = RewardWallet::query()
            ->where('phone_normalized', $normalized)
            ->first();

        if ($wallet !== null) {
            $existingName = \trim($wallet->getFirstName());

            if ($existingName === '' && \trim($firstName) !== '') {
                $wallet->forceFill(['first_name' => \trim($firstName)])->save();
            }

            return $wallet;
        }

        return $this->createWallet($normalized, $phone, \trim($firstName) !== '' ? \trim($firstName) : '');
    }

    /**
     * Normalize a phone number to E.164, throwing if the input is not
     * a valid number.
     *
     * The `Phone` cast from `propaganistas/laravel-phone` is the
     * canonical normalizer. We pass an explicit `+` prefix when the
     * caller omitted it, so the customer can type either
     * `+420 123 456 789` or just `123 456 789` and still succeed.
     *
     * @throws NumberParseException
     */
    public function normalizePhone(string $phone): string
    {
        $candidate = \trim($phone);

        if ($candidate === '') {
            throw new NumberParseException(NumberParseException::INVALID_COUNTRY_CODE, 'Phone number is empty.');
        }

        $instance = new PhoneNumber($candidate, 'CZ');

        if (! $instance->isValid()) {
            throw new NumberParseException(NumberParseException::NOT_A_NUMBER, $candidate . ' is not a valid phone number.');
        }

        return $instance->formatE164();
    }

    /**
     * Create a new wallet for a (normalized) phone + display name.
     *
     * Public — callers that need to upsert by phone should use
     * `findOrCreateByPhone`. `$displayPhone` is the user-entered form
     * (e.g. with spaces or formatting); `$normalized` is the E.164
     * canonical form.
     */
    public function createWallet(string $normalized, string $displayPhone, string $firstName): RewardWallet
    {
        return RewardWallet::query()->create([
            'uuid' => (string) Str::uuid(),
            'public_token' => $this->makePublicToken(),
            'wallet_number' => $this->makeWalletNumber(),
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
     *
     * @throws ModelNotFoundException
     */
    public function getByPublicToken(string $token): RewardWallet
    {
        $wallet = RewardWallet::query()
            ->where('public_token', $token)
            ->first();

        if (! $wallet instanceof RewardWallet) {
            throw (new ModelNotFoundException())->setModel(RewardWallet::class, [$token]);
        }

        return $wallet;
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
        $max = \strlen($alphabet) - 1;
        $out = '';

        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }

        return $out;
    }
}
