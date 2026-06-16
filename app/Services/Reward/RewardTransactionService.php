<?php

declare(strict_types=1);

namespace App\Services\Reward;

use App\Enums\TransactionTypeEnum;
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

            $before = BigDecimal::of($locked->getRewardsBalance());
            $after = $before->plus($amount);

            $transaction = RewardTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'reward_wallet_id' => $locked->getKey(),
                'user_id' => $user->getKey(),
                'type' => TransactionTypeEnum::MANUAL_ADD->value,
                'amount' => $amount->__toString(),
                'balance_before' => $before->__toString(),
                'balance_after' => $after->__toString(),
                'note' => $note,
            ]);

            $locked->forceFill([
                'rewards_balance' => $after->__toString(),
                'lifetime_earned' => BigDecimal::of($locked->getLifetimeEarned())->plus($amount)->__toString(),
                'last_used_at' => Carbon::now(),
            ])->save();

            return $transaction;
        });
    }

    /**
     * Manually debit a wallet. Requires a non-empty note and rejects
     * going below zero.
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

            $before = BigDecimal::of($locked->getRewardsBalance());

            if ($amount->isGreaterThan($before)) {
                Thrower::default()->message('amount', \__('reward.manual_subtract_exceeds_balance'))->throw();
            }

            $after = $before->minus($amount);

            $transaction = RewardTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'reward_wallet_id' => $locked->getKey(),
                'user_id' => $user->getKey(),
                'type' => TransactionTypeEnum::MANUAL_SUBTRACT->value,
                'amount' => $amount->negated()->__toString(),
                'balance_before' => $before->__toString(),
                'balance_after' => $after->__toString(),
                'note' => $note,
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
     * Manually set a wallet to a specific balance. Requires a
     * non-empty note. Result must be >= 0.
     */
    public function manualSet(RewardWallet $wallet, BigDecimal $newBalance, string $note, User $user): RewardTransaction
    {
        $note = \trim($note);

        if ($note === '') {
            Thrower::default()->message('note', \__('reward.note_required'))->throw();
        }

        if ($newBalance->isLessThan(BigDecimal::of('0'))) {
            Thrower::default()->message('amount', \__('reward.manual_set_negative'))->throw();
        }

        return DB::transaction(function () use ($wallet, $newBalance, $note, $user): RewardTransaction {
            $locked = $this->lockWallet($wallet);

            $before = BigDecimal::of($locked->getRewardsBalance());
            $delta = $newBalance->minus($before);

            $transaction = RewardTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'reward_wallet_id' => $locked->getKey(),
                'user_id' => $user->getKey(),
                'type' => TransactionTypeEnum::MANUAL_SET->value,
                'amount' => $delta->__toString(),
                'balance_before' => $before->__toString(),
                'balance_after' => $newBalance->__toString(),
                'note' => $note,
            ]);

            $locked->forceFill([
                'rewards_balance' => $newBalance->__toString(),
                'last_used_at' => Carbon::now(),
            ])->save();

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
}
