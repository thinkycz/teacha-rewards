<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionTypeEnum;
use Database\Factories\RewardTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Thinkycz\LaravelCore\Models\BaseModel;
use Thinkycz\LaravelCore\Support\Typer;

/**
 * @property int $id
 * @property string $uuid
 * @property int $reward_wallet_id
 * @property int|null $user_id
 * @property string $type
 * @property string|null $purchase_amount
 * @property string|null $cashback_rate
 * @property string $amount
 * @property string $balance_before
 * @property string $balance_after
 * @property string|null $note
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class RewardTransaction extends BaseModel
{
    /**
     * @use HasFactory<RewardTransactionFactory>
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
     * Matches against the transaction note and the related wallet's
     * first name and phone so staff can search "Anička" and see her full
     * history.
     *
     * @param Builder<static> $builder
     */
    public static function scopeSearch(Builder $builder, string $search): void
    {
        $like = '%' . $search . '%';
        $builder->where(static function (Builder $query) use ($like): void {
            $query->where($query->qualifyColumn('note'), 'LIKE', $like)
                ->orWhereHas('wallet', static function (Builder $wallet) use ($like): void {
                    $wallet->where($wallet->qualifyColumn('first_name'), 'LIKE', $like)
                        ->orWhere($wallet->qualifyColumn('phone'), 'LIKE', $like)
                        ->orWhere($wallet->qualifyColumn('wallet_number'), 'LIKE', $like);
                });
        });
    }

    /**
     * Wallet relationship.
     *
     * @return BelongsTo<RewardWallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(RewardWallet::class, 'reward_wallet_id');
    }

    /**
     * User (staff/admin who created the transaction) relationship.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Type getter.
     */
    public function getType(): TransactionTypeEnum
    {
        return TransactionTypeEnum::from($this->assertString('type'));
    }

    /**
     * Purchase amount getter.
     */
    public function getPurchaseAmount(): string|null
    {
        return $this->assertNullableString('purchase_amount');
    }

    /**
     * Cashback rate getter.
     */
    public function getCashbackRate(): string|null
    {
        return $this->assertNullableString('cashback_rate');
    }

    /**
     * Amount getter (signed; positive on earn, negative on redeem/subtract).
     */
    public function getAmount(): string
    {
        return $this->assertString('amount');
    }

    /**
     * Balance before getter.
     */
    public function getBalanceBefore(): string
    {
        return $this->assertString('balance_before');
    }

    /**
     * Balance after getter.
     */
    public function getBalanceAfter(): string
    {
        return $this->assertString('balance_after');
    }

    /**
     * Note getter.
     */
    public function getNote(): string|null
    {
        return $this->assertNullableString('note');
    }

    /**
     * User id getter.
     */
    public function getUserId(): int|null
    {
        $raw = $this->getAttribute('user_id');

        return $raw === null ? null : Typer::assertInt(\is_numeric($raw) ? (int) $raw : 0);
    }

    /**
     * Metadata getter.
     *
     * The `metadata` cast on the model decodes the JSON column into a
     * PHP array on access, so we delegate to `assertNullableArray`
     * rather than calling `json_decode` ourselves.
     *
     * @return array<mixed>|null
     */
    public function getMetadata(): array|null
    {
        return $this->assertNullableArray('metadata');
    }

    /**
     * Get the attributes that should be cast.
     *
     * Every decimal column is cast to `decimal:2` so that Eloquent
     * always returns them as zero-padded strings (`"5.00"`) instead of
     * the PDO driver's int representation of `5.00` → `5`. The
     * `metadata` column is a JSON column.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'purchase_amount' => 'decimal:2',
            'cashback_rate' => 'decimal:2',
        ];
    }
}
