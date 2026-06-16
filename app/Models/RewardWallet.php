<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WalletStatusEnum;
use Database\Factories\RewardWalletFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Thinkycz\LaravelCore\Models\BaseModel;

/**
 * @property int $id
 * @property string $uuid
 * @property string $public_token
 * @property string $wallet_number
 * @property string $first_name
 * @property string $phone
 * @property string $phone_normalized
 * @property string $rewards_balance
 * @property string $lifetime_earned
 * @property string $lifetime_redeemed
 * @property string $status
 * @property Carbon|null $last_used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @use HasFactory<RewardWalletFactory>
 */
class RewardWallet extends BaseModel
{
    /**
     * @use HasFactory<RewardWalletFactory>
     */
    use HasFactory;

    /**
     * Base select query.
     *
     * @param Builder<static> $builder
     */
    public static function querySelect(Builder $builder): void
    {
        $builder->getQuery()->select($builder->qualifyColumn('*'));
    }

    /**
     * Search scope.
     *
     * Matches against first name, phone (display), normalized phone, and
     * wallet number — the columns the staff search field actually
     * presents to the cashier.
     *
     * @param Builder<static> $builder
     */
    public static function scopeSearch(Builder $builder, string $search): void
    {
        $like = '%' . $search . '%';
        $builder->getQuery()->where(static function (Builder $query) use ($like): void {
            $query->where($query->qualifyColumn('first_name'), 'LIKE', $like)
                ->orWhere($query->qualifyColumn('phone'), 'LIKE', $like)
                ->orWhere($query->qualifyColumn('phone_normalized'), 'LIKE', $like)
                ->orWhere($query->qualifyColumn('wallet_number'), 'LIKE', $like);
        });
    }

    /**
     * Transactions relationship.
     *
     * Ordering is intentionally left to the call site. Returning a
     * chained `->orderByDesc(...)` here would change the static return
     * type to `Query\Builder` and break PHPStan's `HasMany<...>`
     * expectation.
     *
     * @return HasMany<RewardTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(RewardTransaction::class, 'reward_wallet_id');
    }

    /**
     * Public token getter.
     */
    public function getPublicToken(): string
    {
        return $this->assertString('public_token');
    }

    /**
     * Wallet number getter.
     */
    public function getWalletNumber(): string
    {
        return $this->assertString('wallet_number');
    }

    /**
     * First name getter.
     */
    public function getFirstName(): string
    {
        return $this->assertString('first_name');
    }

    /**
     * Phone (display) getter.
     */
    public function getPhone(): string
    {
        return $this->assertString('phone');
    }

    /**
     * Phone (E.164 normalized) getter.
     */
    public function getPhoneNormalized(): string
    {
        return $this->assertString('phone_normalized');
    }

    /**
     * Rewards balance getter.
     *
     * Returned as a string (the raw decimal from the DB) to avoid float
     * drift. Services convert to `BigDecimal` via `brick/math` for
     * arithmetic; the model stays at the persistence boundary.
     */
    public function getRewardsBalance(): string
    {
        return $this->assertString('rewards_balance');
    }

    /**
     * Lifetime earned getter.
     */
    public function getLifetimeEarned(): string
    {
        return $this->assertString('lifetime_earned');
    }

    /**
     * Lifetime redeemed getter.
     */
    public function getLifetimeRedeemed(): string
    {
        return $this->assertString('lifetime_redeemed');
    }

    /**
     * Status getter.
     */
    public function getStatus(): WalletStatusEnum
    {
        return WalletStatusEnum::from($this->assertString('status'));
    }

    /**
     * Whether the wallet is active.
     */
    public function isActive(): bool
    {
        return $this->getStatus() === WalletStatusEnum::ACTIVE;
    }

    /**
     * Last used at getter.
     */
    public function getLastUsedAt(): Carbon|null
    {
        return $this->assertNullableCarbon('last_used_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }
}
