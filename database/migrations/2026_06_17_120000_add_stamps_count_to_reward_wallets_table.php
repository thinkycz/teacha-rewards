<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Thinkycz\LaravelCore\Support\Resolver;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Adds `stamps_count` to `reward_wallets` so the program can run
     * either in cashback mode (using `rewards_balance`) or stamps mode
     * (using `stamps_count`). The mode is a single store-wide setting,
     * not a per-wallet column; both columns stay live so switching the
     * mode is non-destructive and demo data survives.
     */
    public function up(): void
    {
        Resolver::resolveSchemaBuilder()->table('reward_wallets', static function (Blueprint $table): void {
            $table->unsignedInteger('stamps_count')->default(0)->after('rewards_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Resolver::resolveSchemaBuilder()->table('reward_wallets', static function (Blueprint $table): void {
            $table->dropColumn('stamps_count');
        });
    }
};
