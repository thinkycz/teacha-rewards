<?php

declare(strict_types=1);

use App\Models\RewardWallet;
use App\Services\Reward\RewardWalletService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use libphonenumber\NumberParseException;
use Thinkycz\LaravelCore\Support\Resolver;

/*
 * Re-format every `reward_wallets.phone` value through
 * `RewardWalletService::formatDisplayPhone` so the column holds the
 * standardized international form (`+420 730 969 399`).
 *
 * `phone_normalized` is already canonical (E.164) and unique, so
 * uniqueness is not at risk. We only need to align the display
 * column. Rows whose `phone` value is no longer parseable are left
 * alone and logged — they are typically test fixtures with
 * hand-crafted strings and don't represent real customers.
 *
 * The reverse migration is a no-op: the original user-entered
 * string is not recoverable once we've overwritten it.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /** @var RewardWalletService $service */
        $service = Resolver::resolve(RewardWalletService::class);

        DB::transaction(function () use ($service): void {
            /** @var RewardWallet $wallet */
            foreach (RewardWallet::query()->cursor() as $wallet) {
                try {
                    $standardized = $service->formatDisplayPhone($wallet->phone);

                    if ($standardized !== $wallet->phone) {
                        $wallet->phone = $standardized;
                        $wallet->save();
                    }
                } catch (NumberParseException) {
                    \error_log(\sprintf(
                        'standardize_reward_wallets_phone_display: skipping reward_wallet #%d, un-parseable phone "%s".',
                        $wallet->getKey(),
                        $wallet->phone,
                    ));
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // no-op: the original raw input is not recoverable.
    }
};
