<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Thinkycz\LaravelCore\Support\Config;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate([
            'email' => 'matcha@teacha.cz',
        ], [
            'name' => 'Teacha Staff',
            'password' => \bcrypt('password'),
            'role' => UserRoleEnum::ADMIN->value,
            'email_verified_at' => Carbon::now(),
            'locale' => Config::inject()->assertString('app.locale'),
        ]);
    }
}
