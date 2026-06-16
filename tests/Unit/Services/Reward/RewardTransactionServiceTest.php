<?php

declare(strict_types=1);

use App\Enums\TransactionTypeEnum;
use App\Models\RewardTransaction;
use App\Models\RewardWallet;
use App\Models\User;
use App\Services\Reward\RewardTransactionService;
use App\Services\Settings\SettingsService;
use Brick\Math\BigDecimal;

\beforeEach(function (): void {
    $this->settings = $this->app->make(SettingsService::class);
    $this->settings->set('cashback_rate', '10');
    $this->service = $this->app->make(RewardTransactionService::class);

    $this->staff = User::factory()->staff()->create();
    $this->wallet = RewardWallet::factory()->create();
});

\test('logPurchase at 10% credits the wallet with 10% of the purchase amount', function (): void {
    $tx = $this->service->logPurchase($this->wallet, BigDecimal::of('100'), $this->staff);

    \expect($tx->getType())->toBe(TransactionTypeEnum::PURCHASE_CASHBACK);
    \expect($tx->getAmount())->toBe('10.00');
    \expect($tx->getBalanceBefore())->toBe('0.00');
    \expect($tx->getBalanceAfter())->toBe('10.00');
    \expect($this->wallet->fresh()->getRewardsBalance())->toBe('10.00');
    \expect($this->wallet->fresh()->getLifetimeEarned())->toBe('10.00');
});

\test('logPurchase respects the configured cashback rate', function (): void {
    $this->settings->set('cashback_rate', '20');

    $tx = $this->service->logPurchase($this->wallet, BigDecimal::of('100'), $this->staff);

    \expect($tx->getAmount())->toBe('20.00');
    \expect($this->wallet->fresh()->getRewardsBalance())->toBe('20.00');
});

\test('logPurchase accumulates into the existing balance', function (): void {
    $this->service->logPurchase($this->wallet, BigDecimal::of('100'), $this->staff);
    $this->service->logPurchase($this->wallet, BigDecimal::of('50'), $this->staff);

    \expect($this->wallet->fresh()->getRewardsBalance())->toBe('15.00');
    \expect($this->wallet->fresh()->getLifetimeEarned())->toBe('15.00');
});

\test('logPurchase rounds cashback to 2 decimals using HALF_UP', function (): void {
    $this->settings->set('cashback_rate', '7.5');
    // 100 * 7.5 / 100 = 7.5 -> 7.50

    $tx = $this->service->logPurchase($this->wallet, BigDecimal::of('100'), $this->staff);

    \expect($tx->getAmount())->toBe('7.50');
});

\test('logPurchase rejects a zero purchase', function (): void {
    $this->service->logPurchase($this->wallet, BigDecimal::of('0'), $this->staff);
})->throws(\Illuminate\Validation\ValidationException::class);

\test('logPurchase rejects a negative purchase', function (): void {
    $this->service->logPurchase($this->wallet, BigDecimal::of('-1'), $this->staff);
})->throws(\Illuminate\Validation\ValidationException::class);

\test('logPurchase updates last_used_at', function (): void {
    \expect($this->wallet->getLastUsedAt())->toBeNull();

    $this->service->logPurchase($this->wallet, BigDecimal::of('100'), $this->staff);

    \expect($this->wallet->fresh()->getLastUsedAt())->not->toBeNull();
});

\test('redeem decrements the wallet and records a negative transaction', function (): void {
    $this->service->logPurchase($this->wallet, BigDecimal::of('100'), $this->staff);

    $tx = $this->service->redeem($this->wallet, BigDecimal::of('5'), $this->staff);

    \expect($tx->getType())->toBe(TransactionTypeEnum::REDEEM);
    \expect($tx->getAmount())->toBe('-5.00');
    \expect($tx->getBalanceAfter())->toBe('5.00');
    \expect($this->wallet->fresh()->getRewardsBalance())->toBe('5.00');
    \expect($this->wallet->fresh()->getLifetimeRedeemed())->toBe('5.00');
});

\test('redeem rejects an amount greater than the balance', function (): void {
    $this->service->logPurchase($this->wallet, BigDecimal::of('100'), $this->staff);

    $this->service->redeem($this->wallet, BigDecimal::of('11'), $this->staff);
})->throws(\Illuminate\Validation\ValidationException::class);

\test('redeem rejects zero', function (): void {
    $this->service->redeem($this->wallet, BigDecimal::of('0'), $this->staff);
})->throws(\Illuminate\Validation\ValidationException::class);

\test('redeem rejects a negative amount', function (): void {
    $this->service->redeem($this->wallet, BigDecimal::of('-1'), $this->staff);
})->throws(\Illuminate\Validation\ValidationException::class);

\test('redeem exactly the full balance is allowed', function (): void {
    $this->service->logPurchase($this->wallet, BigDecimal::of('100'), $this->staff);

    $this->service->redeem($this->wallet, BigDecimal::of('10'), $this->staff);

    \expect($this->wallet->fresh()->getRewardsBalance())->toBe('0.00');
});

\test('manualAdd credits the wallet and requires a note', function (): void {
    $this->service->manualAdd($this->wallet, BigDecimal::of('5'), 'Birthday gift', $this->staff);

    \expect($this->wallet->fresh()->getRewardsBalance())->toBe('5.00');
    \expect($this->wallet->fresh()->getLifetimeEarned())->toBe('5.00');
});

\test('manualAdd rejects an empty note', function (): void {
    $this->service->manualAdd($this->wallet, BigDecimal::of('5'), '   ', $this->staff);
})->throws(\Illuminate\Validation\ValidationException::class);

\test('manualAdd rejects zero and negative amounts', function (): void {
    $this->service->manualAdd($this->wallet, BigDecimal::of('0'), 'note', $this->staff);
})->throws(\Illuminate\Validation\ValidationException::class);

\test('manualSubtract debits the wallet and requires a note', function (): void {
    $this->service->manualAdd($this->wallet, BigDecimal::of('20'), 'Top-up', $this->staff);

    $tx = $this->service->manualSubtract($this->wallet, BigDecimal::of('5'), 'Goodwill credit', $this->staff);

    \expect($tx->getType())->toBe(TransactionTypeEnum::MANUAL_SUBTRACT);
    \expect($this->wallet->fresh()->getRewardsBalance())->toBe('15.00');
    \expect($this->wallet->fresh()->getLifetimeRedeemed())->toBe('5.00');
});

\test('manualSubtract rejects going below zero', function (): void {
    $this->service->manualSubtract($this->wallet, BigDecimal::of('1'), 'oops', $this->staff);
})->throws(\Illuminate\Validation\ValidationException::class);

\test('manualSubtract rejects an empty note', function (): void {
    $this->service->manualSubtract($this->wallet, BigDecimal::of('1'), '', $this->staff);
})->throws(\Illuminate\Validation\ValidationException::class);

\test('manualSet replaces the balance exactly and requires a note', function (): void {
    $this->service->manualAdd($this->wallet, BigDecimal::of('50'), 'Top-up', $this->staff);

    $tx = $this->service->manualSet($this->wallet, BigDecimal::of('7'), 'Audit correction', $this->staff);

    \expect($tx->getType())->toBe(TransactionTypeEnum::MANUAL_SET);
    \expect($tx->getBalanceAfter())->toBe('7.00');
    \expect($this->wallet->fresh()->getRewardsBalance())->toBe('7.00');
});

\test('manualSet to 0 is allowed', function (): void {
    $this->service->manualAdd($this->wallet, BigDecimal::of('10'), 'Top-up', $this->staff);

    $this->service->manualSet($this->wallet, BigDecimal::of('0'), 'Audit', $this->staff);

    \expect($this->wallet->fresh()->getRewardsBalance())->toBe('0.00');
});

\test('manualSet to a negative value is rejected', function (): void {
    $this->service->manualSet($this->wallet, BigDecimal::of('-1'), 'note', $this->staff);
})->throws(\Illuminate\Validation\ValidationException::class);

\test('manualSet rejects an empty note', function (): void {
    $this->service->manualSet($this->wallet, BigDecimal::of('0'), '', $this->staff);
})->throws(\Illuminate\Validation\ValidationException::class);

\test('every balance change creates exactly one transaction row', function (): void {
    $countBefore = RewardTransaction::query()->count();

    $this->service->logPurchase($this->wallet, BigDecimal::of('100'), $this->staff);
    $this->service->redeem($this->wallet, BigDecimal::of('10'), $this->staff);
    $this->service->manualAdd($this->wallet, BigDecimal::of('5'), 'note', $this->staff);
    $this->service->manualSubtract($this->wallet, BigDecimal::of('1'), 'note', $this->staff);
    $this->service->manualSet($this->wallet, BigDecimal::of('50'), 'note', $this->staff);

    \expect(RewardTransaction::query()->count())->toBe($countBefore + 5);
});

\test('preventing negative balance: redeem + logPurchase cannot underflow', function (): void {
    $this->service->logPurchase($this->wallet, BigDecimal::of('5'), $this->staff);

    try {
        $this->service->redeem($this->wallet, BigDecimal::of('100'), $this->staff);
        \expect(true)->toBeFalse('Redeem should have thrown.');
    } catch (\Illuminate\Validation\ValidationException) {
        \expect($this->wallet->fresh()->getRewardsBalance())->toBe('0.50');
    }
});
