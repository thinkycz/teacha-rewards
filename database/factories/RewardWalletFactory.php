<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WalletStatusEnum;
use App\Enums\WalletTypeEnum;
use App\Models\RewardWallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RewardWallet>
 */
class RewardWalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Phone is generated as a +420 9-digit number so E.164 normalization
     * round-trips through `propaganistas/laravel-phone`. `public_token`
     * and `wallet_number` are randomized per row, both unique.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'public_token' => Str::random(32),
            'wallet_number' => $this->makeWalletNumber(),
            'type' => WalletTypeEnum::CASHBACK->value,

            'first_name' => $this->faker->firstName(),

            'phone' => \sprintf(
                '+420 %s %s %s',
                $this->faker->numerify('###'),
                $this->faker->numerify('###'),
                $this->faker->numerify('###'),
            ),
            'phone_normalized' => \sprintf(
                '+420%s%s%s',
                $this->faker->unique()->numerify('###'),
                $this->faker->numerify('###'),
                $this->faker->numerify('###'),
            ),

            'rewards_balance' => '0.00',
            'stamps_count' => 0,
            'lifetime_earned' => '0.00',
            'lifetime_redeemed' => '0.00',

            'status' => WalletStatusEnum::ACTIVE->value,
            'last_used_at' => null,
        ];
    }

    /**
     * Mark the wallet as disabled.
     */
    public function disabled(): static
    {
        return $this->state([
            'status' => WalletStatusEnum::DISABLED->value,
        ]);
    }

    /**
     * Create a cashback wallet (default, but explicit for tests).
     */
    public function cashback(): static
    {
        return $this->state([
            'type' => WalletTypeEnum::CASHBACK->value,
        ]);
    }

    /**
     * Create a stamps wallet.
     */
    public function stamps(): static
    {
        return $this->state([
            'type' => WalletTypeEnum::STAMPS->value,
        ]);
    }

    /**
     * Set a non-zero starting balance.
     */
    public function withBalance(string $balance, string $lifetimeEarned = '0.00', string $lifetimeRedeemed = '0.00'): static
    {
        return $this->state([
            'rewards_balance' => $balance,
            'lifetime_earned' => $lifetimeEarned,
            'lifetime_redeemed' => $lifetimeRedeemed,
        ]);
    }

    /**
     * Generate a `T-XXXX-XXXX` style wallet number.
     */
    protected function makeWalletNumber(): string
    {
        return Str::upper(Str::random(4)) . '-' . Str::upper(Str::random(4));
    }
}
