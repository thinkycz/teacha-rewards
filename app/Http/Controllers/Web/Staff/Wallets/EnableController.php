<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Staff\Wallets;

use App\Enums\WalletStatusEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardWallet;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class EnableController
{
    use ValidatesWebRequests;

    public function __invoke(RewardWallet $wallet): RedirectResponse
    {
        if ($wallet->getStatus() === WalletStatusEnum::ACTIVE) {
            Inertia::flash('success', \__('Wallet is already active.'));

            return \redirect()->route('staff.wallets.show', ['wallet' => $wallet->getKey()]);
        }

        $wallet->forceFill(['status' => WalletStatusEnum::ACTIVE->value])->save();

        Inertia::flash('success', \__('Wallet re-enabled.'));

        return \redirect()->route('staff.wallets.show', ['wallet' => $wallet->getKey()]);
    }
}
