<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Dashboard;

use App\Enums\WalletStatusEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardWallet;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;

/**
 * Staff wallet list.
 *
 * Searchable by first name, phone, wallet number, or scanned
 * barcode (public_token). Filter by `status` (`active` | `disabled` |
 * all). Sort by recent activity, lifetime earned, lifetime redeemed,
 * or balance (cashback-mode) / stamps count (stamps-mode).
 *
 * `program.mode` is shared so the list page can render the right
 * column (balance or stamp count) per the current mode.
 */
class WalletIndexController
{
    use ValidatesWebRequests;

    public function __invoke(Request $request): Response
    {
        /** @var SettingsService $settings */
        $settings = Resolver::resolve(SettingsService::class);

        $query = RewardWallet::query();

        $search = $request->str('q')->toString();
        if ($search !== '') {
            RewardWallet::scopeSearch($query, $search);
        }

        $status = $request->str('status')->toString() === '' ? 'all' : $request->str('status')->toString();
        if ($status === 'active') {
            $query->where('status', WalletStatusEnum::ACTIVE->value);
        } elseif ($status === 'disabled') {
            $query->where('status', WalletStatusEnum::DISABLED->value);
        }

        $mode = $settings->getProgramMode();

        $sort = $request->str('sort')->toString();
        match ($sort) {
            'earned' => $query->orderByDesc('lifetime_earned'),
            'redeemed' => $query->orderByDesc('lifetime_redeemed'),
            'balance' => $query->orderByDesc($mode === 'stamps' ? 'stamps_count' : 'rewards_balance'),
            default => $query->orderByDesc('last_used_at')->orderByDesc('id'),
        };

        $wallets = $query->limit(100)->get();

        return Inertia::render('Dashboard/Wallets/Index', [
            'wallets' => $wallets->map(static fn (RewardWallet $w): array => [
                'id' => $w->getKey(),
                'public_token' => $w->getPublicToken(),
                'wallet_number' => $w->getWalletNumber(),
                'first_name' => $w->getFirstName(),
                'phone' => $w->getPhone(),
                'rewards_balance' => $w->getRewardsBalance(),
                'stamps_count' => $w->getStampsCount(),
                'lifetime_earned' => $w->getLifetimeEarned(),
                'lifetime_redeemed' => $w->getLifetimeRedeemed(),
                'status' => $w->getStatus()->value,
                'last_used_at' => $w->getLastUsedAt()?->format(\DateTimeInterface::ATOM),
            ])->all(),
            'filters' => [
                'q' => $search,
                'status' => $status,
                'sort' => $sort,
            ],
            'program' => [
                'mode' => $mode,
                'stamps_per_reward' => $settings->getStampsPerReward(),
                'stamps_per_reward_label' => $settings->getStampsRewardLabel(),
            ],
        ]);
    }
}
