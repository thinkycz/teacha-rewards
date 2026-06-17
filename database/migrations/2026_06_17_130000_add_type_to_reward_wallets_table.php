<?php

declare(strict_types=1);

use App\Enums\WalletTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Thinkycz\LaravelCore\Support\Resolver;

/**
 * Add the per-wallet `type` column.
 *
 * Each wallet is created under exactly one program (cashback or
 * stamps) and stays that way forever — even if the admin later
 * changes the `program_mode` setting (which now only sets the
 * default for new wallets). Mixing the two programs on a single
 * wallet is a real bug (a cashback balance silently growing under
 * a stamps program), so the column is NOT NULL.
 *
 * The `cashback` default is safe in this dev environment where the
 * seeded data all predates the column.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Resolver::resolveSchemaBuilder()->table('reward_wallets', static function (Blueprint $table): void {
            $table->string('type', 16)->default(WalletTypeEnum::CASHBACK->value)->after('wallet_number');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Resolver::resolveSchemaBuilder()->table('reward_wallets', static function (Blueprint $table): void {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
