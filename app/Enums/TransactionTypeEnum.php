<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case PURCHASE_CASHBACK = 'purchase_cashback';

    case REDEEM = 'redeem';

    case STAMP_EARN = 'stamp_earn';

    case STAMP_REDEEM = 'stamp_redeem';

    case MANUAL_ADD = 'manual_add';

    case MANUAL_SUBTRACT = 'manual_subtract';

    case MANUAL_SET = 'manual_set';

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
