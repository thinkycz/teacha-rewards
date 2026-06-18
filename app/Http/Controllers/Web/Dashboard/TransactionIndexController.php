<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Dashboard;

use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\RewardTransaction;
use App\Services\Settings\SettingsService;
use DateTimeInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;

/**
 * Staff-wide transaction log.
 *
 * Filterable by type and wallet_id, with a `q` search over the note
 * and the related wallet's first name / phone. Default order is most
 * recent first.
 */
class TransactionIndexController
{
    use ValidatesWebRequests;

    public function __invoke(Request $request): Response
    {
        /** @var SettingsService $settings */
        $settings = Resolver::resolve(SettingsService::class);

        $query = RewardTransaction::query()
            ->with(['wallet:id,first_name,wallet_number,public_token,type', 'user:id,name']);

        $type = $request->str('type')->toString();
        if ($type !== '' && \in_array($type, TransactionTypeEnum::values(), true)) {
            $query->where('type', $type);
        }

        $walletId = $request->integer('wallet_id');
        if ($walletId > 0) {
            $query->where('reward_wallet_id', $walletId);
        }

        $search = $request->str('q')->toString();
        if ($search !== '') {
            RewardTransaction::scopeSearch($query, $search);
        }

        $transactions = $query
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(static function (RewardTransaction $tx): array {
                $createdAt = $tx->getAttribute('created_at');

                return [
                    'id' => $tx->getKey(),
                    'type' => $tx->getType()->value,
                    'amount' => $tx->getAmount(),
                    'purchase_amount' => $tx->getPurchaseAmount(),
                    'cashback_rate' => $tx->getCashbackRate(),
                    'balance_before' => $tx->getBalanceBefore(),
                    'balance_after' => $tx->getBalanceAfter(),
                    'note' => $tx->getNote(),
                    'wallet_id' => $tx->reward_wallet_id,
                    'wallet_type' => $tx->wallet?->getType()->value,
                    'wallet_first_name' => $tx->wallet?->getFirstName(),
                    'wallet_number' => $tx->wallet?->getWalletNumber(),
                    'wallet_public_token' => $tx->wallet?->getPublicToken(),
                    'staff_name' => $tx->user?->getName(),
                    'created_at' => $createdAt instanceof DateTimeInterface ? $createdAt->format(DateTimeInterface::ATOM) : null,
                ];
            });

        return Inertia::render('Dashboard/Transactions/Index', [
            'transactions' => $transactions,
            'filters' => [
                'q' => $search,
                'type' => $type,
                'wallet_id' => $walletId,
            ],
            'type_options' => TransactionTypeEnum::values(),
            'program' => [
                'stamps_per_reward' => $settings->getStampsPerReward(),
                'stamps_per_reward_label' => $settings->getStampsRewardLabel(),
            ],
        ]);
    }
}
