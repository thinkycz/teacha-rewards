<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Wallet;

use App\Models\RewardWallet;
use App\Services\Reward\RewardWalletService;
use App\Services\Settings\SettingsService;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Thrower;

/**
 * Full activity history for one wallet.
 *
 * Public, like `WalletShowController`. Returns paginated transactions
 * (latest first) plus the same wallet summary the show page renders,
 * so the activity page can be deep-linked.
 */
class WalletActivityController
{
    /**
     * Show the full activity history.
     */
    public function __invoke(string $token): Response
    {
        /** @var RewardWalletService $service */
        $service = Resolver::resolve(RewardWalletService::class);

        /** @var SettingsService $settings */
        $settings = Resolver::resolve(SettingsService::class);

        $wallet = $service->getByPublicToken($token);

        $transactions = $wallet->transactions()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $walletType = $wallet->getType()->value;
        $items = $transactions->map(static function ($tx) use ($walletType): array {
            /** @var \App\Models\RewardTransaction $tx */
            $createdAt = $tx->getAttribute('created_at');
            return [
                'id' => $tx->getKey(),
                'type' => $tx->getType()->value,
                'amount' => $tx->getAmount(),
                'purchase_amount' => $tx->getPurchaseAmount(),
                'balance_after' => $tx->getBalanceAfter(),
                'note' => $tx->getNote(),
                'wallet_type' => $walletType,
                'created_at' => $createdAt instanceof \DateTimeInterface ? $createdAt->format(\DateTimeInterface::ATOM) : null,
                'staff_name' => $tx->user?->getName(),
            ];
        })->all();

        return Inertia::render('Wallet/Activity', [
            'wallet' => [
                'public_token' => $wallet->getPublicToken(),
                'wallet_number' => $wallet->getWalletNumber(),
                'type' => $wallet->getType()->value,
                'first_name' => $wallet->getFirstName(),
                'rewards_balance' => $wallet->getRewardsBalance(),
                'stamps_count' => $wallet->getStampsCount(),
                'lifetime_earned' => $wallet->getLifetimeEarned(),
                'lifetime_redeemed' => $wallet->getLifetimeRedeemed(),
            ],
            'transactions' => $items,
            'program' => [
                'stamps_per_reward' => $settings->getStampsPerReward(),
                'stamps_per_reward_label' => $settings->getStampsRewardLabel(),
                'stamp_icon' => $settings->getStampIcon(),
            ],
        ]);
    }
}
