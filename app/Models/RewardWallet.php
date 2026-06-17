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
 * @property int $stamps_count
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
     * Matches against first name, phone (display), normalized phone,
     * wallet number, and the public token (the value encoded in the
     * barcode / QR the customer carries) - the columns the staff
     * search field actually presents to the cashier, including a
     * freshly scanned barcode.
     *
     * @param Builder<static> $builder
     */
    public static function scopeSearch(Builder $builder, string $search): void
    {
        $like = '%' . $search . '%';
        $builder->where(static function (Builder $query) use ($like): void {
            $query->where($query->qualifyColumn('first_name'), 'LIKE', $like)
                ->orWhere($query->qualifyColumn('phone'), 'LIKE', $like)
                ->orWhere($query->qualifyColumn('phone_normalized'), 'LIKE', $like)
                ->orWhere($query->qualifyColumn('wallet_number'), 'LIKE', $like)
                ->orWhere($query->qualifyColumn('public_token'), 'LIKE', $like);
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
     * Stamps count getter.
     *
     * Integer counter used when the store is in `program_mode = stamps`.
     * Stays at zero in cashback mode so flipping the mode toggle is
     * non-destructive: existing cashback balances are preserved and a
     * new stamps program starts with empty cards.
     */
    public function getStampsCount(): int
    {
        return $this->assertInt('stamps_count');
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
     * The three decimal columns are cast to `decimal:2` so that Eloquent
     * always returns them as zero-padded strings (e.g. `"0.00"`,
     * `"12.50"`) instead of the PDO driver's int representation of
     * `0.00` → `0`. Without this cast, `assertString('rewards_balance')`
     * panics on every read of a default-valued row.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'rewards_balance' => 'decimal:2',
            'stamps_count' => 'integer',
            'lifetime_earned' => 'decimal:2',
            'lifetime_redeemed' => 'decimal:2',
        ];
    }
}
