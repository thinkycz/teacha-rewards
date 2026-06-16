<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Dashboard;

use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardWallet;
use App\Models\User;
use App\Services\Reward\RewardTransactionService;
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
 */
class LogPurchaseController
{
    use ValidatesWebRequests;

    public function __invoke(Request $request, RewardWallet $wallet): RedirectResponse
    {
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

        return \redirect()->route('dashboard.scan.show', ['token' => $wallet->getPublicToken()]);
    }
}
