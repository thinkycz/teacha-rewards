<?php

declare(strict_types=1);

namespace App\Services\Reward;

use App\Enums\TransactionTypeEnum;
use App\Enums\WalletTypeEnum;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Thinkycz\LaravelCore\Support\Thrower;

/**
 * Ledger of every balance change.
 *
 * All methods are wrapped in `DB::transaction(...)` and take a
 * `lockForUpdate()` row lock on the wallet so two concurrent staff
 * actions on the same wallet cannot race past each other.
 *
 * `logPurchase` is the only positive-balance entry from a customer
 * purchase. `redeem` and `manualSubtract` are the only negative paths.
 * `manualAdd` and `manualSet` are the manual override paths and
 * require a non-empty `note` (enforced here, not in the validity
 * class — a missing note is a business rule, not a typing rule).
 *
 * All methods reject the operation if the resulting balance would be
 * negative, and all transactions write a row in `reward_transactions`
 * with `balance_before` and `balance_after` set.
 */
class RewardTransactionService
{
    public function __construct(
        protected SettingsService $settings,
    ) {
    }

    /**
     * Log a customer purchase and credit the wallet with cashback.
     *
     * `cashback = purchaseAmount * cashbackRate / 100`, rounded to 2
     * decimal places using banker's-rounding's evil twin HalfUp
     * (which is the conventional rounding for money). 120 Kč at 10%
     * = 12.00 Kč, 105 Kč at 7.5% = 7.88 Kč, etc.
     */
    public function logPurchase(RewardWallet $wallet, BigDecimal $purchaseAmount, User $user): RewardTransaction
    {
        if ($purchaseAmount->isLessThanOrEqualTo(BigDecimal::of('0'))) {
            Thrower::default()->message('purchase_amount', \__('reward.purchase_amount_positive'))->throw();
        }

        $rate = $this->settings->getCashbackRate();

        $cashback = $purchaseAmount
            ->multipliedBy($rate)
            ->dividedBy(BigDecimal::of('100'), 4, RoundingMode::HalfUp)
            ->toScale(2, RoundingMode::HalfUp);

        return DB::transaction(function () use ($wallet, $purchaseAmount, $rate, $cashback, $user): RewardTransaction {
            $locked = $this->lockWallet($wallet);

            $before = BigDecimal::of($locked->getRewardsBalance());
            $after = $before->plus($cashback);

            $transaction = RewardTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'reward_wallet_id' => $locked->getKey(),
                'user_id' => $user->getKey(),
                'type' => TransactionTypeEnum::PURCHASE_CASHBACK->value,
                'purchase_amount' => $purchaseAmount->toScale(2, RoundingMode::HalfUp)->__toString(),
                'cashback_rate' => $rate->toScale(2, RoundingMode::HalfUp)->__toString(),
                'amount' => $cashback->__toString(),
                'balance_before' => $before->__toString(),
                'balance_after' => $after->__toString(),
            ]);

            $locked->forceFill([
                'rewards_balance' => $after->__toString(),
                'lifetime_earned' => BigDecimal::of($locked->getLifetimeEarned())->plus($cashback)->__toString(),
                'last_used_at' => Carbon::now(),
            ])->save();

            return $transaction;
        });
    }

    /**
     * Redeem rewards as a discount.
     *
     * Rejects when `$amount` is not strictly positive or exceeds the
     * current balance.
     */
    public function redeem(RewardWallet $wallet, BigDecimal $amount, User $user): RewardTransaction
    {
        if ($amount->isLessThanOrEqualTo(BigDecimal::of('0'))) {
            Thrower::default()->message('amount', \__('reward.redeem_amount_positive'))->throw();
        }

        return DB::transaction(function () use ($wallet, $amount, $user): RewardTransaction {
            $locked = $this->lockWallet($wallet);

            $before = BigDecimal::of($locked->getRewardsBalance());

            if ($amount->isGreaterThan($before)) {
                Thrower::default()->message('amount', \__('reward.redeem_amount_exceeds_balance'))->throw();
            }

            $after = $before->minus($amount);

            $transaction = RewardTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'reward_wallet_id' => $locked->getKey(),
                'user_id' => $user->getKey(),
                'type' => TransactionTypeEnum::REDEEM->value,
                'amount' => $amount->negated()->__toString(),
                'balance_before' => $before->__toString(),
                'balance_after' => $after->__toString(),
            ]);

            $locked->forceFill([
                'rewards_balance' => $after->__toString(),
                'lifetime_redeemed' => BigDecimal::of($locked->getLifetimeRedeemed())->plus($amount)->__toString(),
                'last_used_at' => Carbon::now(),
            ])->save();

            return $transaction;
        });
    }

    /**
     * Manually credit a wallet. Requires a non-empty note.
     *
     * Branches on `wallet.type`: cashback wallets get a `BigDecimal`
     * credit on `rewards_balance` and a bump to `lifetime_earned`;
     * stamps wallets get an integer credit on `stamps_count`.
     */
    public function manualAdd(RewardWallet $wallet, BigDecimal $amount, string $note, User $user): RewardTransaction
    {
        $note = \trim($note);

        if ($note === '') {
            Thrower::default()->message('note', \__('reward.note_required'))->throw();
        }

        if ($amount->isLessThanOrEqualTo(BigDecimal::of('0'))) {
            Thrower::default()->message('amount', \__('reward.manual_amount_positive'))->throw();
        }

        return DB::transaction(function () use ($wallet, $amount, $note, $user): RewardTransaction {
            $locked = $this->lockWallet($wallet);

            if ($locked->getType() === WalletTypeEnum::STAMPS) {
                $delta = (int) $amount->__toString();
                $before = $locked->getStampsCount();
                $after = $before + $delta;
                $amountStr = (string) $delta;
                $update = ['stamps_count' => $after, 'last_used_at' => Carbon::now()];
            } else {
                $before = BigDecimal::of($locked->getRewardsBalance());
                $after = $before->plus($amount);
                $amountStr = $amount->__toString();
                $update = [
                    'rewards_balance' => $after->__toString(),
                    'lifetime_earned' => BigDecimal::of($locked->getLifetimeEarned())->plus($amount)->__toString(),
                    'last_used_at' => Carbon::now(),
                ];
            }

            $transaction = RewardTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'reward_wallet_id' => $locked->getKey(),
                'user_id' => $user->getKey(),
                'type' => TransactionTypeEnum::MANUAL_ADD->value,
                'amount' => $amountStr,
                'balance_before' => (string) $before,
                'balance_after' => (string) $after,
                'note' => $note,
            ]);

            $locked->forceFill($update)->save();

            return $transaction;
        });
    }

    /**
     * Manually debit a wallet. Requires a non-empty note and rejects
     * going below zero.
     *
     * Branches on `wallet.type` — see `manualAdd` for the column split.
     */
    public function manualSubtract(RewardWallet $wallet, BigDecimal $amount, string $note, User $user): RewardTransaction
    {
        $note = \trim($note);

        if ($note === '') {
            Thrower::default()->message('note', \__('reward.note_required'))->throw();
        }

        if ($amount->isLessThanOrEqualTo(BigDecimal::of('0'))) {
            Thrower::default()->message('amount', \__('reward.manual_amount_positive'))->throw();
        }

        return DB::transaction(function () use ($wallet, $amount, $note, $user): RewardTransaction {
            $locked = $this->lockWallet($wallet);

            if ($locked->getType() === WalletTypeEnum::STAMPS) {
                $delta = (int) $amount->__toString();
                $before = $locked->getStampsCount();
                if ($delta > $before) {
                    Thrower::default()->message('amount', \__('reward.manual_subtract_exceeds_balance'))->throw();
                }
                $after = $before - $delta;
                $amountStr = (string) (-$delta);
                $update = ['stamps_count' => $after, 'last_used_at' => Carbon::now()];
            } else {
                $before = BigDecimal::of($locked->getRewardsBalance());
                if ($amount->isGreaterThan($before)) {
                    Thrower::default()->message('amount', \__('reward.manual_subtract_exceeds_balance'))->throw();
                }
                $after = $before->minus($amount);
                $amountStr = $amount->negated()->__toString();
                $update = [
                    'rewards_balance' => $after->__toString(),
                    'lifetime_redeemed' => BigDecimal::of($locked->getLifetimeRedeemed())->plus($amount)->__toString(),
                    'last_used_at' => Carbon::now(),
                ];
            }

            $transaction = RewardTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'reward_wallet_id' => $locked->getKey(),
                'user_id' => $user->getKey(),
                'type' => TransactionTypeEnum::MANUAL_SUBTRACT->value,
                'amount' => $amountStr,
                'balance_before' => (string) $before,
                'balance_after' => (string) $after,
                'note' => $note,
            ]);

            $locked->forceFill($update)->save();

            return $transaction;
        });
    }

    /**
     * Manually set a wallet to a specific value. Requires a
     * non-empty note. Result must be >= 0.
     *
     * Branches on `wallet.type` — see `manualAdd` for the column split.
     */
    public function manualSet(RewardWallet $wallet, BigDecimal $newValue, string $note, User $user): RewardTransaction
    {
        $note = \trim($note);

        if ($note === '') {
            Thrower::default()->message('note', \__('reward.note_required'))->throw();
        }

        if ($newValue->isLessThan(BigDecimal::of('0'))) {
            Thrower::default()->message('amount', \__('reward.manual_set_negative'))->throw();
        }

        return DB::transaction(function () use ($wallet, $newValue, $note, $user): RewardTransaction {
            $locked = $this->lockWallet($wallet);

            if ($locked->getType() === WalletTypeEnum::STAMPS) {
                $after = (int) $newValue->__toString();
                $before = $locked->getStampsCount();
                $delta = $after - $before;
                $amountStr = (string) $delta;
                $update = ['stamps_count' => $after, 'last_used_at' => Carbon::now()];
            } else {
                $before = BigDecimal::of($locked->getRewardsBalance());
                $after = $newValue;
                $delta = $after->minus($before);
                $amountStr = $delta->__toString();
                $update = ['rewards_balance' => $after->__toString(), 'last_used_at' => Carbon::now()];
            }

            $transaction = RewardTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'reward_wallet_id' => $locked->getKey(),
                'user_id' => $user->getKey(),
                'type' => TransactionTypeEnum::MANUAL_SET->value,
                'amount' => $amountStr,
                'balance_before' => (string) $before,
                'balance_after' => (string) $after,
                'note' => $note,
            ]);

            $locked->forceFill($update)->save();

            return $transaction;
        });
    }

    /**
     * Acquire a `SELECT ... FOR UPDATE` lock on the wallet row and
     * return a fresh instance. Throws if the row no longer exists
     * (concurrent delete edge case).
     *
     * @throws ModelNotFoundException
     */
    protected function lockWallet(RewardWallet $wallet): RewardWallet
    {
        $locked = RewardWallet::query()
            ->whereKey($wallet->getKey())
            ->lockForUpdate()
            ->first();

        if (! $locked instanceof RewardWallet) {
            throw (new ModelNotFoundException())->setModel(RewardWallet::class, [$wallet->getKey()]);
        }

        return $locked;
    }

    /**
     * Award stamps to a wallet (cashier clicked "Add stamps" N
     * times for N drinks paid at full price).
     *
     * Writes a `STAMP_EARN` row with `amount = N` (stamps credited)
     * and increments `stamps_count`. Cashback-mode balances are
     * untouched: both columns stay live so the mode toggle is
     * non-destructive.
     */
    public function stampEarn(RewardWallet $wallet, int $count, User $user): RewardTransaction
    {
        if ($count < 1) {
            Thrower::default()->message('count', \__('reward.stamp_count_positive'))->throw();
        }

        return DB::transaction(function () use ($wallet, $count, $user): RewardTransaction {
            $locked = $this->lockWallet($wallet);

            $before = $locked->getStampsCount();
            $after = $before + $count;

            $transaction = RewardTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'reward_wallet_id' => $locked->getKey(),
                'user_id' => $user->getKey(),
                'type' => TransactionTypeEnum::STAMP_EARN->value,
                'amount' => number_format($count, 2, '.', ''),
                'balance_before' => number_format($before, 2, '.', ''),
                'balance_after' => number_format($after, 2, '.', ''),
            ]);

            $locked->forceFill([
                'stamps_count' => $after,
                'last_used_at' => Carbon::now(),
            ])->save();

            return $transaction;
        });
    }

    /**
     * Redeem $rewards free rewards from the wallet.
     *
     * The cashier can redeem any number from 1 up to
     * `floor(stamps_count / stamps_per_reward)`. The leftover
     * stamps stay (21 stamps, threshold 10, redeem 2 -> 1 stamp
     * left). The transaction row carries `amount = -rewards` so
     * the history log shows how many were redeemed at once.
     */
    public function stampRedeem(RewardWallet $wallet, int $rewards, User $user): RewardTransaction
    {
        if ($rewards < 1) {
            Thrower::default()->message('rewards', \__('reward.stamp_rewards_positive'))->throw();
        }

        $perReward = $this->settings->getStampsPerReward();

        return DB::transaction(function () use ($wallet, $rewards, $perReward, $user): RewardTransaction {
            $locked = $this->lockWallet($wallet);

            $before = $locked->getStampsCount();
            $cost = $rewards * $perReward;

            if ($before < $cost) {
                Thrower::default()->message('rewards', \__('reward.stamp_rewards_exceed_card'))->throw();
            }

            $after = $before - $cost;

            $transaction = RewardTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'reward_wallet_id' => $locked->getKey(),
                'user_id' => $user->getKey(),
                'type' => TransactionTypeEnum::STAMP_REDEEM->value,
                'amount' => number_format(-$rewards, 2, '.', ''),
                'balance_before' => number_format($before, 2, '.', ''),
                'balance_after' => number_format($after, 2, '.', ''),
            ]);

            $locked->forceFill([
                'stamps_count' => $after,
                'last_used_at' => Carbon::now(),
            ])->save();

            return $transaction;
        });
    }
}
