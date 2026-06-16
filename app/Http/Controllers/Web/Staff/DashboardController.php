<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Staff;

use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

/**
 * Staff dashboard.
 *
 * Top-of-page summary: counts of active vs disabled wallets, today's
 * purchase count and cashback, lifetime totals. Pulls numbers in a
 * single query per metric so the page renders in < 100ms for a shop
 * with thousands of wallets.
 */
class DashboardController
{
    use ValidatesWebRequests;

    public function __invoke(): Response
    {
        $today = \now()->toDateString();

        $activeWallets = RewardWallet::query()
            ->where('status', \App\Enums\WalletStatusEnum::ACTIVE->value)
            ->count();

        $disabledWallets = RewardWallet::query()
            ->where('status', \App\Enums\WalletStatusEnum::DISABLED->value)
            ->count();

        $todayRow = RewardTransaction::query()
            ->where('type', TransactionTypeEnum::PURCHASE_CASHBACK->value)
            ->whereDate('created_at', $today)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total_cashback')
            ->first();

        $todayCount = $todayRow === null ? 0 : Typer::assertInt($todayRow->getAttribute('cnt'));
        $todayCashback = '0.00';
        if ($todayRow !== null) {
            $raw = $todayRow->getAttribute('total_cashback');
            if (\is_string($raw) || \is_numeric($raw)) {
                $todayCashback = Typer::assertString((string) $raw);
            }
        }

        $recentTransactions = RewardTransaction::query()
            ->with(['wallet:id,first_name,wallet_number,public_token', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return Inertia::render('Staff/Dashboard', [
            'stats' => [
                'active_wallets' => $activeWallets,
                'disabled_wallets' => $disabledWallets,
                'today_purchase_count' => $todayCount,
                'today_cashback' => $todayCashback,
            ],
            'recent_transactions' => $recentTransactions->map(static function (RewardTransaction $tx): array {
                $createdAt = $tx->getAttribute('created_at');
                $wallet = $tx->wallet;
                $user = $tx->user;
                $createdAtStr = $createdAt instanceof \DateTimeInterface ? $createdAt->format(\DateTimeInterface::ATOM) : null;
                return [
                    'id' => $tx->getKey(),
                    'type' => $tx->getType()->value,
                    'amount' => $tx->getAmount(),
                    'wallet_first_name' => $wallet === null ? null : $wallet->getFirstName(),
                    'wallet_number' => $wallet === null ? null : $wallet->getWalletNumber(),
                    'wallet_public_token' => $wallet === null ? null : $wallet->getPublicToken(),
                    'staff_name' => $user === null ? null : $user->getName(),
                    'created_at' => $createdAtStr,
                ];
            })->all(),
        ]);
    }
}
