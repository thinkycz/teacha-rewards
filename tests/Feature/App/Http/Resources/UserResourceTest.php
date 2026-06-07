<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Resources;

use App\Http\Resources\UserResource;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Typer;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_type_is_users(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        $resource = new UserResource($user);
        $request = Request::create('/');

        static::assertSame('users', $resource->toType($request));
    }

    public function test_resource_attributes_include_email_locale_and_verified_at(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        $resource = new UserResource($user);
        $request = Request::create('/');

        $attributes = $resource->toAttributes($request);

        static::assertSame($user->getEmail(), $attributes['email']);
        static::assertSame($user->getLocale(), $attributes['locale']);
        static::assertNotNull($attributes['email_verified_at']);
    }

    public function test_resource_id_matches_user_key(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        $resource = new UserResource($user);
        $request = Request::create('/');

        static::assertSame((string) $user->getKey(), $resource->toId($request));
    }
}
