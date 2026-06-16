<?php

declare(strict_types=1);

use App\Enums\UserRoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Thinkycz\LaravelCore\Support\Resolver;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Adds the `name` display field and the `role` (admin/staff) field to
     * the existing `users` table for Teacha Rewards. `role` defaults to
     * `staff` so existing registration flows (which create self-service
     * users) keep working unchanged; admins are seeded explicitly.
     */
    public function up(): void
    {
        Resolver::resolveSchemaBuilder()->table('users', static function (Blueprint $table): void {
            $table->string('name')->after('id');
            $table->string('role')->default(UserRoleEnum::STAFF->value)->after('email');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Resolver::resolveSchemaBuilder()->table('users', static function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropColumn(['name', 'role']);
        });
    }
};
