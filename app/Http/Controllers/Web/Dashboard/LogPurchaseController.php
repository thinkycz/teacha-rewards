<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Dashboard;

use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardWallet;
use App\Models\User;
use App\Services\Reward\RewardTransactionService;
use App\Services\Settings\SettingsService;
use App\Validation\Web\Staff\LogPurchaseValidity;
use Brick\Math\BigDecimal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Thinkycz\LaravelCore\Support\Resolver;

/**
 * Log a customer purchase and credit the wallet with cashback.
 *
 * The cashback is computed in the service from the current
 * `cashback_rate` setting; the controller only validates input and
 * flashes a success message.
 *
 * Mode gate: this endpoint is only valid in `program_mode = cashback`.
 * In stamps mode the cashier should be clicking "Add stamps", not
 * logging a cashback purchase. Mixing the two would silently let
 * a cashback-mode balance grow under a stamps program, so the
 * controller refuses and flashes an error.
 */
class LogPurchaseController
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

        $validity = LogPurchaseValidity::inject();

        $validated = $this->validateRequest($request, [
            'purchase_amount' => $validity->purchaseAmount()->toArray(),
        ]);

        /** @var RewardTransactionService $service */
        $service = Resolver::resolve(RewardTransactionService::class);

        /** @var User $user */
        $user = User::mustAuth();

        $tx = $service->logPurchase(
            $wallet,
            BigDecimal::of($validated->assertString('purchase_amount')),
            $user,
        );

        Inertia::flash('success', \__('reward.purchase_logged', [
            'amount' => $tx->getAmount(),
        ]));

        return \redirect()->route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]);
    }
}
