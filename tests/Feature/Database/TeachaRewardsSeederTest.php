<?php

declare(strict_types=1);

use App\Enums\UserRoleEnum;
use App\Enums\WalletStatusEnum;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\TeachaRewardsSeeder;

\test('TeachaRewardsSeeder creates the expected demo data', function (): void {
    $this->seed(TeachaRewardsSeeder::class);

    // 1 admin + 1 staff
    \expect(User::query()->where('role', UserRoleEnum::ADMIN->value)->count())->toBe(1);
    \expect(User::query()->where('role', UserRoleEnum::STAFF->value)->count())->toBe(1);

    // The admin + staff rows should be the seeded ones.
    \expect(User::query()->where('email', 'admin@teacha.cz')->exists())->toBeTrue();
    \expect(User::query()->where('email', 'staff@teacha.cz')->exists())->toBeTrue();

    // 5 customer wallets (4 active + 1 disabled for path coverage).
    \expect(RewardWallet::query()->count())->toBe(5);
    \expect(RewardWallet::query()->where('status', WalletStatusEnum::ACTIVE->value)->count())->toBe(4);
    \expect(RewardWallet::query()->where('status', WalletStatusEnum::DISABLED->value)->count())->toBe(1);

    // At least 12 purchases and 5 redeems — manual adds/subtracts may
    // not always succeed against this random ordering, so we just
    // assert the upper bound of ledger rows.
    \expect(RewardTransaction::query()->where('type', 'purchase_cashback')->count())->toBe(12);
    \expect(RewardTransaction::query()->count())->toBeGreaterThanOrEqual(17);
    \expect(RewardTransaction::query()->count())->toBeLessThanOrEqual(20);

    // The 4 settings are present.
    \expect(Setting::query()->where('key', 'cashback_rate')->value('value'))->toBe('10');
    \expect(Setting::query()->where('key', 'currency')->value('value'))->toBe('CZK');
    \expect(Setting::query()->where('key', 'program_name')->value('value'))->toBe('Teacha Rewards');
    \expect(Setting::query()->where('key', 'store_name')->value('value'))->toBe('Teacha');

    // The sentinel setting is set so a re-run is a no-op.
    \expect(Setting::query()->where('key', 'teacha_seeder_v1')->exists())->toBeTrue();
});

\test('TeachaRewardsSeeder is idempotent — re-running does not duplicate data', function (): void {
    $this->seed(TeachaRewardsSeeder::class);
    $firstWallets = RewardWallet::query()->count();
    $firstTransactions = RewardTransaction::query()->count();
    $firstUsers = User::query()->count();

    $this->seed(TeachaRewardsSeeder::class);

    \expect(RewardWallet::query()->count())->toBe($firstWallets);
    \expect(RewardTransaction::query()->count())->toBe($firstTransactions);
    \expect(User::query()->count())->toBe($firstUsers);
});
