<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Per-wallet program type.
 *
 * Set at creation from the current `program_mode` setting; immutable
 * afterwards. A cashback wallet stays a cashback wallet even if the
 * shop later flips the default to stamps, and vice versa — see the
 * `reward_wallets.type` column and the wallet-type controller gates.
 */
enum WalletTypeEnum: string
{
    case CASHBACK = 'cashback';

    case STAMPS = 'stamps';

    /**
     * Get possible values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return \array_column(self::cases(), 'value');
    }
}
