<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Staff\Scan;

use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardWallet;
use App\Services\Reward\RewardWalletService;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Thrower;

/**
 * Scanner result page.
 *
 * Resolves the `public_token` from the scanned QR (or the manual
 * input fallback) to a wallet and renders a sticky, mobile-first
 * card with the customer's balance + the three action buttons
 * (Log purchase, Redeem, Manual adjust).
 */
class ScanShowController
{
    use ValidatesWebRequests;

    public function __invoke(string $token): Response
    {
        /** @var RewardWalletService $service */
        $service = Resolver::resolve(RewardWalletService::class);

        $wallet = $service->getByPublicToken($token);

        return Inertia::render('Staff/Scan/Show', [
            'wallet' => [
                'id' => $wallet->getKey(),
                'public_token' => $wallet->getPublicToken(),
                'wallet_number' => $wallet->getWalletNumber(),
                'first_name' => $wallet->getFirstName(),
                'rewards_balance' => $wallet->getRewardsBalance(),
                'lifetime_earned' => $wallet->getLifetimeEarned(),
                'status' => $wallet->getStatus()->value,
            ],
        ]);
    }
}
