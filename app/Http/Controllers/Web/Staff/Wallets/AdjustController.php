<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Staff\Wallets;

use App\Enums\ManualAdjustmentTypeEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardWallet;
use App\Models\User;
use App\Services\Reward\RewardTransactionService;
use App\Validation\Web\Staff\ManualAdjustValidity;
use Brick\Math\BigDecimal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Thinkycz\LaravelCore\Support\Resolver;

/**
 * Manual balance adjustment (add / subtract / set).
 *
 * Dispatches to the right `RewardTransactionService::manual*` method
 * based on the submitted `type`. The service refuses to underflow,
 * and the note is required (enforced here in the validity class and
 * again inside the service for defense in depth).
 */
class AdjustController
{
    use ValidatesWebRequests;

    public function __invoke(Request $request, RewardWallet $wallet): RedirectResponse
    {
        $validity = ManualAdjustValidity::inject();

        $validated = $this->validateRequest($request, [
            'type' => $validity->type()->toArray(),
            'amount' => $validity->amount()->toArray(),
            'note' => $validity->note()->toArray(),
        ]);

        /** @var RewardTransactionService $service */
        $service = Resolver::resolve(RewardTransactionService::class);

        /** @var User $user */
        $user = User::mustAuth();

        $type = ManualAdjustmentTypeEnum::from($validated->assertString('type'));
        $amount = BigDecimal::of($validated->assertString('amount'));
        $note = $validated->assertString('note');

        match ($type) {
            ManualAdjustmentTypeEnum::ADD => $service->manualAdd($wallet, $amount, $note, $user),
            ManualAdjustmentTypeEnum::SUBTRACT => $service->manualSubtract($wallet, $amount, $note, $user),
            ManualAdjustmentTypeEnum::SET => $service->manualSet($wallet, $amount, $note, $user),
        };

        Inertia::flash('success', \__('reward.adjusted'));

        return \redirect()->route('staff.scan.show', ['token' => $wallet->getPublicToken()]);
    }
}
