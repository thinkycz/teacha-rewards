<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Enums\WalletStatusEnum;
use App\Models\RewardWallet;
use App\Models\Setting;
use App\Models\User;
use App\Services\Reward\RewardTransactionService;
use App\Services\Reward\RewardWalletService;
use App\Services\Settings\SettingsService;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use libphonenumber\NumberParseException;
use Thinkycz\LaravelCore\Support\Config;
use Throwable;

/**
 * Demo data for the Teacha Rewards stack.
 *
 * Idempotent: it bails out early if a sentinel setting (or any
 * Teacha wallet) already exists, so re-running `php artisan
 * migrate:fresh --seed` is required to re-seed.
 *
 * What it creates:
 * - 1 admin user (admin@teacha.cz / password)
 * - 1 staff user (staff@teacha.cz / password)
 * - 5 customer wallets with realistic Czech names + E.164 phones
 * - 20 ledger transactions spread across the 4 ledger types
 * - the 4 default settings (cashback_rate, currency, program_name,
 *   store_name)
 */
class TeachaRewardsSeeder extends Seeder
{
    public function run(): void
    {
        if (Config::inject()->appEnvIs(['staging', 'production'])) {
            return;
        }

        if (
            Setting::query()->where('key', 'teacha_seeder_v1')->exists() ||
            RewardWallet::query()->exists()
        ) {
            return;
        }

        $settings = $this->container->make(SettingsService::class);
        $wallets = $this->container->make(RewardWalletService::class);
        $transactions = $this->container->make(RewardTransactionService::class);

        $this->seedSettings($settings);
        $admin = $this->seedAdmin();
        $staff = $this->seedStaff();
        $customerWallets = $this->seedWallets($wallets);
        $this->seedTransactions($transactions, $customerWallets, $staff);

        Setting::query()->updateOrCreate(
            ['key' => 'teacha_seeder_v1'],
            ['value' => Carbon::now()->toJSON()],
        );

        $this->command->info('Teacha Rewards demo data seeded.');
        $this->command->info('  admin: admin@teacha.cz / password');
        $this->command->info('  staff: staff@teacha.cz / password');
    }

    /**
     * @return array<string, void>
     */
    private function seedSettings(SettingsService $settings): array
    {
        $settings->set('cashback_rate', '10');
        $settings->set('currency', 'CZK');
        $settings->set('program_name', 'Teacha Rewards');
        $settings->set('store_name', 'Teacha');

        return [
            'cashback_rate' => null,
            'currency' => null,
            'program_name' => null,
            'store_name' => null,
        ];
    }

    private function seedAdmin(): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'admin@teacha.cz'],
            [
                'name' => 'Teacha Admin',
                'password' => 'password',
                'role' => UserRoleEnum::ADMIN->value,
                'email_verified_at' => Carbon::now(),
                'locale' => Config::inject()->assertString('app.locale'),
            ],
        );
    }

    private function seedStaff(): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'staff@teacha.cz'],
            [
                'name' => 'Teacha Staff',
                'password' => 'password',
                'role' => UserRoleEnum::STAFF->value,
                'email_verified_at' => Carbon::now(),
                'locale' => Config::inject()->assertString('app.locale'),
            ],
        );
    }

    /**
     * @return array<int, RewardWallet>
     */
    private function seedWallets(RewardWalletService $wallets): array
    {
        $seedPhones = [
            ['+420 601 100 001', 'Anička'],
            ['+420 602 200 002', 'Bára'],
            ['+420 603 300 003', 'Cyril'],
            ['+420 604 400 004', 'Denisa'],
            ['+420 605 500 005', 'Eliška'],
        ];

        $created = [];
        foreach ($seedPhones as [$phone, $firstName]) {
            try {
                $created[] = $wallets->findOrCreateByPhone($phone, $firstName);
            } catch (NumberParseException) {
                // Skip phones that libphonenumber rejects (shouldn't happen
                // for Czech mobile numbers, but be defensive).
                continue;
            }
        }

        // Mark one wallet as disabled to exercise that path in tests/manual.
        if (isset($created[4])) {
            $created[4]->update(['status' => WalletStatusEnum::DISABLED->value]);
        }

        return $created;
    }

    /**
     * @param array<int, RewardWallet> $customerWallets
     */
    private function seedTransactions(
        RewardTransactionService $transactions,
        array $customerWallets,
        User $staff,
    ): void {
        // 12 purchases, 5 redeems, 2 manual adds, 1 manual subtract.

        $purchases = [
            [20000, $customerWallets[0]],
            [15000, $customerWallets[0]],
            [30000, $customerWallets[1]],
            [18000, $customerWallets[1]],
            [12000, $customerWallets[2]],
            [8000, $customerWallets[2]],
            [25000, $customerWallets[0]],
            [10000, $customerWallets[3]],
            [14000, $customerWallets[1]],
            [11000, $customerWallets[3]],
            [9000, $customerWallets[2]],
            [22000, $customerWallets[0]],
        ];

        foreach ($purchases as [$amount, $wallet]) {
            try {
                $transactions->logPurchase(
                    $wallet,
                    BigDecimal::of((string) $amount),
                    $staff,
                );
            } catch (Throwable) {
                continue;
            }
        }

        $redeems = [
            [1000, $customerWallets[0]],
            [500, $customerWallets[0]],
            [2000, $customerWallets[1]],
            [1500, $customerWallets[2]],
            [1000, $customerWallets[3]],
        ];

        foreach ($redeems as [$amount, $wallet]) {
            try {
                $transactions->redeem(
                    $wallet,
                    BigDecimal::of((string) $amount),
                    $staff,
                );
            } catch (Throwable) {
                // The wallet might not have enough balance for a
                // redemption given the random purchase ordering; skip
                // silently rather than blowing up the seeder.
                continue;
            }
        }

        // 2 manual credits (goodwill) and 1 manual debit (correction).
        $manualAdds = [
            [1000, $customerWallets[2], 'Goodwill — narozeniny'],
            [2000, $customerWallets[3], 'Korekce po výpadku pokladny'],
        ];
        foreach ($manualAdds as [$amount, $wallet, $note]) {
            try {
                $transactions->manualAdd(
                    $wallet,
                    BigDecimal::of((string) $amount),
                    $note,
                    $staff,
                );
            } catch (Throwable) {
                continue;
            }
        }

        try {
            $transactions->manualSubtract(
                $customerWallets[1],
                BigDecimal::of('0.50'),
                'Korekce duplicitního nákupu',
                $staff,
            );
        } catch (Throwable) {
            // Wallet might not have enough balance; skip.
        }
    }
}
