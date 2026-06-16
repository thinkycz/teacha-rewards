<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Staff\Wallets;

use App\Enums\TransactionTypeEnum;
use App\Enums\WalletStatusEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Thinkycz\LaravelCore\Support\Thrower;

/**
 * Disable a wallet.
 *
 * Writes a manual-set transaction of the current balance (delta=0)
 * with a system note. The reason note is hardcoded because the
 * cashier might be acting in a hurry; admins can re-enable later
 * via `EnableController`.
 */
class DisableController
{
    use ValidatesWebRequests;

    public function __invoke(RewardWallet $wallet): RedirectResponse
    {
        /** @var User $user */
        $user = User::mustAuth();

        if ($wallet->getStatus() === WalletStatusEnum::DISABLED) {
            Inertia::flash('success', \__('Wallet is already disabled.'));

            return \redirect()->route('staff.wallets.show', ['wallet' => $wallet->getKey()]);
        }

        DB::transaction(function () use ($wallet, $user): void {
            $locked = RewardWallet::query()
                ->whereKey($wallet->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                Thrower::default()->message('wallet', \__('Wallet not found.'))->throw();
            }

            $currentBalance = $locked->getRewardsBalance();

            RewardTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'reward_wallet_id' => $locked->getKey(),
                'user_id' => $user->getKey(),
                'type' => TransactionTypeEnum::MANUAL_SET->value,
                'amount' => '0.00',
                'balance_before' => $currentBalance,
                'balance_after' => $currentBalance,
                'note' => 'Disabled by staff',
            ]);

            $locked->forceFill([
                'status' => WalletStatusEnum::DISABLED->value,
                'last_used_at' => Carbon::now(),
            ])->save();
        });

        Inertia::flash('success', \__('Wallet disabled.'));

        return \redirect()->route('staff.wallets.show', ['wallet' => $wallet->getKey()]);
    }
}
