<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Dashboard;

use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardWallet;
use App\Models\User;
use App\Services\Reward\RewardTransactionService;
use App\Services\Settings\SettingsService;
use App\Validation\Web\Staff\RedeemValidity;
use Brick\Math\BigDecimal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Thinkycz\LaravelCore\Support\Resolver;

/**
 * Redeem rewards as a discount.
 *
 * The "cannot exceed balance" rule is enforced inside the service so
 * we get a friendly flash message instead of a 422.
 *
 * Mode gate: this endpoint is only valid in `program_mode = cashback`.
 * In stamps mode the cashier should be clicking "Redeem reward"
 * (the stamps redeem flow) instead. Mixing the two would silently
 * subtract cashback-style amounts from a stamps card, so the
 * controller refuses and flashes an error.
 */
class RedeemController
{
    use ValidatesWebRequests;

    public function __invoke(Request $request, RewardWallet $wallet): RedirectResponse
    {
        /** @var SettingsService $settings */
        $settings = Resolver::resolve(SettingsService::class);

        if ($settings->getProgramMode() !== 'cashback') {
            Inertia::flash('error', \__('reward.action_requires_cashback_mode'));

            return \redirect()->route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]);
        }

        $validity = RedeemValidity::inject();

        $validated = $this->validateRequest($request, [
            'amount' => $validity->amount()->toArray(),
        ]);

        /** @var RewardTransactionService $service */
        $service = Resolver::resolve(RewardTransactionService::class);

        /** @var User $user */
        $user = User::mustAuth();

        $tx = $service->redeem(
            $wallet,
            BigDecimal::of($validated->assertString('amount')),
            $user,
        );

        Inertia::flash('success', \__('reward.redeemed', [
            'amount' => \str_replace('-', '', $tx->getAmount()),
        ]));

        return \redirect()->route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]);
    }
}
