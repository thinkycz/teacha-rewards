<?php

declare(strict_types=1);

namespace App\Enums;

enum ManualAdjustmentTypeEnum: string
{
    case ADD = 'add';

    case SUBTRACT = 'subtract';

    case SET = 'set';

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
