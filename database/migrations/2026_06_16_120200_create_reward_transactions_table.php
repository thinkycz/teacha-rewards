<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Thinkycz\LaravelCore\Support\Resolver;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * `reward_transactions` is the append-only ledger. Every balance
     * change writes a row. `purchase_amount` and `cashback_rate` are
     * only set for `purchase_cashback` rows. `note` is required for
     * `manual_*` types (enforced at the service layer).
     */
    public function up(): void
    {
        Resolver::resolveSchemaBuilder()->create('reward_transactions', static function (Blueprint $table): void {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignId('reward_wallet_id')
                ->constrained('reward_wallets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('type');

            $table->decimal('purchase_amount', 10, 2)->nullable();
            $table->decimal('cashback_rate', 5, 2)->nullable();

            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);

            $table->string('note')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Resolver::resolveSchemaBuilder()->dropIfExists('reward_transactions');
    }
};
