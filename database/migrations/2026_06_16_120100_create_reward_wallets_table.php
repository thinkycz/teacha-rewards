<?php

declare(strict_types=1);

use App\Enums\WalletStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Thinkycz\LaravelCore\Support\Resolver;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * `reward_wallets` is the customer record. Phone is the natural key
     * (stored E.164 in `phone_normalized`), but the public identifier is
     * the unguessable `public_token` that goes in the QR code and the
     * `/w/{token}` URL. `wallet_number` is a short human-readable code
     * printed on the card.
     */
    public function up(): void
    {
        Resolver::resolveSchemaBuilder()->create('reward_wallets', static function (Blueprint $table): void {
            $table->id();

            $table->uuid('uuid')->unique();
            $table->string('public_token', 48)->unique();
            $table->string('wallet_number', 16)->unique();

            $table->string('first_name', 64);
            $table->string('phone', 32);
            $table->string('phone_normalized', 32)->unique();

            $table->decimal('rewards_balance', 10, 2)->default('0.00');
            $table->decimal('lifetime_earned', 10, 2)->default('0.00');
            $table->decimal('lifetime_redeemed', 10, 2)->default('0.00');

            $table->string('status')->default(WalletStatusEnum::ACTIVE->value);

            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Resolver::resolveSchemaBuilder()->dropIfExists('reward_wallets');
    }
};
