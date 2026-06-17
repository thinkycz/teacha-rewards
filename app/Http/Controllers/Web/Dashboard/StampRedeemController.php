<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Dashboard;

use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardWallet;
use App\Models\User;
use App\Services\Reward\RewardTransactionService;
use App\Services\Settings\SettingsService;
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
 * Mode gate: this endpoint is only valid in `program_mode = stamps`.
 * In cashback mode the cashier should be clicking "Redeem rewards"
 * (the cashback redeem flow) instead. Mixing the two would silently
 * subtract stamp-redemptions from a cashback-mode balance, so the
 * controller refuses and flashes an error.
 */
class StampRedeemController
{
    use ValidatesWebRequests;

    public function __invoke(Request $request, RewardWallet $wallet): RedirectResponse
    {
        /** @var SettingsService $settings */
        $settings = Resolver::resolve(SettingsService::class);

        if ($settings->getProgramMode() !== 'stamps') {
            Inertia::flash('error', \__('reward.action_requires_stamps_mode'));

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
