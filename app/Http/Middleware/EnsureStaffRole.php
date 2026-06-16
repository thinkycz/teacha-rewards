<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Thinkycz\LaravelCore\Support\Thrower;

/**
 * Reject anonymous users and any user whose role is not `admin` or
 * `staff`. The existing `EnsureInertiaUserIsAuthenticated` middleware
 * must run first; this one is a stricter role check for the `/staff/*`
 * surface.
 */
class EnsureStaffRole
{
    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            Thrower::default()->message('auth', \__('auth.unauthenticated'))->throw();
        }

        if (! $user->isAdmin() && ! $user->isStaff()) {
            Thrower::default()->message('auth', \__('auth.forbidden'))->throw();
        }

        return $next($request);
    }
}
