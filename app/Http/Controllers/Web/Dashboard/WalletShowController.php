<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Dashboard;

use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Services\Settings\SettingsService;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;

/**
 * Staff wallet detail.
 *
 * The cashier-facing full wallet view: customer info, recent
 * transactions, plus the four action buttons (log purchase / redeem /
 * manual adjust / toggle status in cashback mode; add stamps / redeem
 * free reward / manual adjust / toggle status in stamps mode). The
 * mode + stamps config is shared with the customer-facing surface so
 * the same rendering rules apply on both sides.
 */
class WalletShowController
{
    use ValidatesWebRequests;

    public function __invoke(RewardWallet $wallet): Response
    {
        /** @var SettingsService $settings */
        $settings = Resolver::resolve(SettingsService::class);

        $recent = $wallet->transactions()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return Inertia::render('Dashboard/Wallets/Show', [
            'wallet' => [
                'id' => $wallet->getKey(),
                'public_token' => $wallet->getPublicToken(),
                'wallet_number' => $wallet->getWalletNumber(),
                'first_name' => $wallet->getFirstName(),
                'phone' => $wallet->getPhone(),
                'phone_normalized' => $wallet->getPhoneNormalized(),
                'rewards_balance' => $wallet->getRewardsBalance(),
                'stamps_count' => $wallet->getStampsCount(),
                'lifetime_earned' => $wallet->getLifetimeEarned(),
                'lifetime_redeemed' => $wallet->getLifetimeRedeemed(),
                'status' => $wallet->getStatus()->value,
                'last_used_at' => $wallet->getLastUsedAt()?->format(\DateTimeInterface::ATOM),
            ],
            'transactions' => $recent->map(static function (RewardTransaction $tx): array {
                $createdAt = $tx->getAttribute('created_at');
                return [
                    'id' => $tx->getKey(),
                    'type' => $tx->getType()->value,
                    'amount' => $tx->getAmount(),
                    'purchase_amount' => $tx->getPurchaseAmount(),
                    'cashback_rate' => $tx->getCashbackRate(),
                    'balance_before' => $tx->getBalanceBefore(),
                    'balance_after' => $tx->getBalanceAfter(),
                    'note' => $tx->getNote(),
                    'staff_name' => $tx->user?->getName(),
                    'created_at' => $createdAt instanceof \DateTimeInterface ? $createdAt->format(\DateTimeInterface::ATOM) : null,
                ];
            })->all(),
            'program' => [
                'mode' => $settings->getProgramMode(),
                'stamps_per_reward' => $settings->getStampsPerReward(),
                'stamps_per_reward_label' => $settings->getStampsRewardLabel(),
            ],
        ]);
    }
}
