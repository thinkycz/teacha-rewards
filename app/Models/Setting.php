<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Thinkycz\LaravelCore\Models\BaseModel;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 */
class Setting extends BaseModel
{
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
     * @param Builder<static> $builder
     */
    public static function scopeSearch(Builder $builder, string $search): void
    {
        $like = '%' . $search . '%';
        $builder->getQuery()->where($builder->qualifyColumn('key'), 'LIKE', $like);
    }

    /**
     * Setting key getter.
     *
     * Named `getKeyValue` (not `getKey`) to avoid clashing with
     * `BaseModel::getKey(): int`, which exposes the model's primary key.
     */
    public function getKeyValue(): string
    {
        return $this->assertString('key');
    }

    /**
     * Value getter.
     */
    public function getValue(): string
    {
        return $this->assertString('value');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }
}
