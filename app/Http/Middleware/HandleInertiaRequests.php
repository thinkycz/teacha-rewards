<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Thinkycz\LaravelCore\Support\Config;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => Config::inject()->assertString('app.name'),
                'locale' => Config::inject()->assertString('app.locale'),
            ],
            'auth' => [
                'user' => fn(): array|null => $this->user(),
            ],
            'flash' => [
                'success' => static fn(): string|null => Typer::assertNullableString($request->session()->get('success')),
                'error' => static fn(): string|null => Typer::assertNullableString($request->session()->get('error')),
            ],
        ];
    }

    /**
     * Resolve the authenticated user for shared Inertia props.
     *
     * @return array<string, mixed>|null
     */
    protected function user(): array|null
    {
        $user = Resolver::resolveAuthManager()->guard('users')->user();

        if ($user instanceof User === false) {
            return null;
        }

        return [
            'id' => $user->getKey(),
            'email' => $user->getEmail(),
            'locale' => $user->getLocale(),
            'email_verified_at' => $user->getEmailVerifiedAt()?->toJSON(),
        ];
    }
}
