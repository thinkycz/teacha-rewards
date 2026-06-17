<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Dashboard;

use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardWallet;
use App\Models\User;
use App\Services\Reward\RewardTransactionService;
use App\Validation\Web\Staff\StampEarnValidity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Thinkycz\LaravelCore\Support\Resolver;

/**
 * Add stamps to a wallet (cashier clicked "Add stamps" N times for
 * N drinks paid at full price).
 *
 * The amount field is the number of stamps to credit. The cashier
 * tile defaults to 1 and uses +/- buttons for batch adds (so the
 * single submit covers N drinks in one click).
 */
class StampEarnController
{
    use ValidatesWebRequests;

    public function __invoke(Request $request, RewardWallet $wallet): RedirectResponse
    {
        $validity = StampEarnValidity::inject();

        $validated = $this->validateRequest($request, [
            'count' => $validity->count()->toArray(),
        ]);

        /** @var RewardTransactionService $service */
        $service = Resolver::resolve(RewardTransactionService::class);

        /** @var User $user */
        $user = User::mustAuth();

        $service->stampEarn(
            $wallet,
            (int) $validated->assertString('count'),
            $user,
        );

        Inertia::flash('success', \__('reward.stamps_added', [
            'count' => $validated->assertString('count'),
        ]));

        return \redirect()->route('dashboard.wallets.show', ['wallet' => $wallet->getKey()]);
    }
}
