<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionTypeEnum;
use App\Models\RewardTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RewardTransaction>
 */
class RewardTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * The default type is `purchase_cashback` so the factory produces a
     * cashback ledger row by default. Use the `redeem()`, `manualAdd()`,
     * `manualSubtract()`, or `manualSet()` states to override.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'type' => TransactionTypeEnum::PURCHASE_CASHBACK->value,
            'amount' => '1.00',
            'purchase_amount' => '10.00',
            'cashback_rate' => '10.00',
            'balance_before' => '0.00',
            'balance_after' => '1.00',
            'note' => null,
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the transaction is a redemption.
     */
    public function redeem(): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionTypeEnum::REDEEM->value,
            'amount' => '-1.00',
            'purchase_amount' => null,
            'cashback_rate' => null,
        ]);
    }

    /**
     * Indicate that the transaction is a manual add.
     */
    public function manualAdd(): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionTypeEnum::MANUAL_ADD->value,
            'amount' => '5.00',
            'purchase_amount' => null,
            'cashback_rate' => null,
        ]);
    }
}
