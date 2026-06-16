<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Thinkycz\LaravelCore\Support\Resolver;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * `settings` is a simple key/value store. Reads and writes go
     * through `SettingsService`. The unique index on `key` makes
     * `SettingsService::set` an idempotent upsert.
     */
    public function up(): void
    {
        Resolver::resolveSchemaBuilder()->create('settings', static function (Blueprint $table): void {
            $table->id();

            $table->string('key', 64)->unique();
            $table->text('value');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Resolver::resolveSchemaBuilder()->dropIfExists('settings');
    }
};
