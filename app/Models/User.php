<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRoleEnum;
use App\Http\Resources\UserResource;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Carbon;
use Laravel\Ai\Models\Conversation;
use Thinkycz\LaravelCore\Models\BaseUser;

class User extends BaseUser implements MustVerifyEmail
{
    /**
     * Explicit mass-assignment allowlist.
     *
     * Overrides the permissive `BaseUser::$guarded = ['id']` so only the
     * columns actually written through `create()`/`update()` are
     * fillable: `email`, `password`, `locale`, `name`, and `role`.
     * Sensitive attributes such as `email_verified_at` and `remember_token`
     * are set through `forceFill()`/`save()` (bypassing the guard) by
     * their dedicated methods, so they remain intentionally non-fillable
     * here.
     *
     * The PHPDoc type matches the Eloquent parent's `array<int, string>`
     * declaration; using `list<string>` here triggered a PHPStan
     * `property.phpDocType` mismatch on the override.
     *
     * @var array<int, string>
     */
    protected $fillable = ['email', 'password', 'locale', 'name', 'role'];

    /**
     * Get the user's AI conversations.
     *
     * The `->orderBy()` chain is intentionally omitted so the static
     * return type stays `HasMany<...>`. Callers that need a specific
     * order append their own `->orderBy(...)` (e.g. the sidebar payload
     * in `ConversationRepository::recentForSidebar`).
     *
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_id');
    }

    /**
     * Email getter.
     */
    public function getEmail(): string
    {
        return $this->assertString('email');
    }

    /**
     * Locale getter.
     */
    public function getLocale(): string
    {
        return $this->assertString('locale');
    }

    /**
     * Name getter.
     */
    public function getName(): string
    {
        return $this->assertString('name');
    }

    /**
     * Role getter.
     */
    public function getRole(): UserRoleEnum
    {
        return UserRoleEnum::from($this->assertString('role'));
    }

    /**
     * Whether the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->getRole() === UserRoleEnum::ADMIN;
    }

    /**
     * Whether the user is a staff member.
     */
    public function isStaff(): bool
    {
        return $this->getRole() === UserRoleEnum::STAFF;
    }

    /**
     * EmailVerifiedAt getter.
     */
    public function getEmailVerifiedAt(): Carbon|null
    {
        return $this->assertNullableCarbon('email_verified_at');
    }

    /**
     * @inheritDoc
     */
    public function markEmailAsUnverified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => null,
        ])->save();
    }

    /**
     * Me resource.
     */
    public function meResource(): JsonApiResource
    {
        return new UserResource($this);
    }

    /**
     * VND json:api resource.
     */
    public function resource(): JsonApiResource
    {
        return $this->meResource();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRoleEnum::class,
        ];
    }
}
