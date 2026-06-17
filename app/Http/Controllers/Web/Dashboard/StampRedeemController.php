<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Dashboard;

use App\Enums\WalletTypeEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardWallet;
use App\Models\User;
use App\Services\Reward\RewardTransactionService;
use App\Validation\Web\Staff\StampRedeemValidity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Thinkycz\LaravelCore\Support\Resolver;

/**
 * Redeem N free rewards from a wallet (stamps mode).
 *
 * The cashier picks the number of free rewards to redeem; the
 * service enforces the `floor(stamps_count / stamps_per_reward)`
 * ceiling and writes a `STAMP_REDEEM` row carrying the negative
 * rewards count. Leftover stamps stay on the card.
 *
 * Type gate: this endpoint is only valid for stamps wallets. A
 * cashback wallet was created under a different program and must be
 * redeemed via `RedeemController`; mixing the two would silently
 * subtract stamp-redemptions from a cashback-mode balance.
 */
class StampRedeemController
{
    use ValidatesWebRequests;

    public function __invoke(Request $request, RewardWallet $wallet): RedirectResponse
    {
        if ($wallet->getType() !== WalletTypeEnum::STAMPS) {
            Inertia::flash('error', \__('reward.action_unavailable_for_wallet_type'));

            return \redirect()->route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]);
        }

        $validity = StampRedeemValidity::inject();

        $validated = $this->validateRequest($request, [
            'rewards' => $validity->rewards()->toArray(),
        ]);

        /** @var RewardTransactionService $service */
        $service = Resolver::resolve(RewardTransactionService::class);

        /** @var User $user */
        $user = User::mustAuth();

        $service->stampRedeem(
            $wallet,
            (int) $validated->assertString('rewards'),
            $user,
        );

        Inertia::flash('success', \__('reward.stamps_redeemed', [
            'count' => $validated->assertString('rewards'),
        ]));

        return \redirect()->route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]);
    }
}
