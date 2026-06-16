<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Wallet;

use App\Models\RewardWallet;
use App\Services\Reward\RewardWalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Thrower;

/**
 * Public wallet page.
 *
 * No auth: the `public_token` in the URL is the only identifier and
 * the page is strictly read-only. The token resolves to a wallet via
 * `RewardWalletService::getByPublicToken`, which throws
 * `ModelNotFoundException` on a miss — Inertia's exception handler
 * turns that into a 404.
 */
class WalletShowController
{
    /**
     * Show the wallet.
     */
    public function __invoke(Request $request, string $token): Response
    {
        /** @var RewardWalletService $service */
        $service = Resolver::resolve(RewardWalletService::class);

        $wallet = $service->getByPublicToken($token);

        $recent = $wallet->transactions()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $items = $recent->map(static function ($tx): array {
            /** @var \App\Models\RewardTransaction $tx */
            $createdAt = $tx->getAttribute('created_at');
            return [
                'id' => $tx->getKey(),
                'type' => $tx->getType()->value,
                'amount' => $tx->getAmount(),
                'balance_after' => $tx->getBalanceAfter(),
                'note' => $tx->getNote(),
                'created_at' => $createdAt instanceof \DateTimeInterface ? $createdAt->format(\DateTimeInterface::ATOM) : null,
                'staff_name' => $tx->user?->getName(),
            ];
        })->all();

        return Inertia::render('Wallet/Show', [
            'wallet' => [
                'public_token' => $wallet->getPublicToken(),
                'wallet_number' => $wallet->getWalletNumber(),
                'first_name' => $wallet->getFirstName(),
                'rewards_balance' => $wallet->getRewardsBalance(),
                'lifetime_earned' => $wallet->getLifetimeEarned(),
                'lifetime_redeemed' => $wallet->getLifetimeRedeemed(),
                'status' => $wallet->getStatus()->value,
            ],
            'recent_transactions' => $items,
            'wallet_url' => $request->url(),
        ]);
    }
}
