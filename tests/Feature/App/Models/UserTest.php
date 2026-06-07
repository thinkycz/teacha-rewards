<?php

declare(strict_types=1);

namespace Tests\Feature\App\Models;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Typer;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_getter_returns_email(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        static::assertSame($user->getEmail(), $user->assertString('email'));
    }

    public function test_locale_getter_returns_locale(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        static::assertSame($user->getLocale(), $user->assertString('locale'));
    }

    public function test_email_verified_at_getter_returns_carbon_or_null(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        static::assertNotNull($user->getEmailVerifiedAt());

        $unverified = Typer::assertInstance(UserFactory::new()->unverified()->createOne(), User::class);

        static::assertNull($unverified->getEmailVerifiedAt());
    }

    public function test_mark_email_as_unverified_clears_timestamp(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        static::assertTrue($user->markEmailAsUnverified());

        $user->refresh();

        static::assertNull($user->getEmailVerifiedAt());
    }

    public function test_me_resource_returns_user_resource(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        $resource = $user->meResource();

        static::assertSame($user, $resource->resource);
    }

    public function test_resource_delegates_to_me_resource(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        static::assertSame($user->meResource()->resource, $user->resource()->resource);
    }

    public function test_database_tokens_relationship_is_defined(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        $this->assertDatabaseCount('database_tokens', 0);
    }
}
