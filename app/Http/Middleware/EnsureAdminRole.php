<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Thinkycz\LaravelCore\Support\Thrower;

/**
 * Reject any user whose role is not `admin`. The `EnsureStaffRole`
 * middleware must run first; this one is a stricter role check for
 * the admin-only `/staff/settings*` surface.
 */
class EnsureAdminRole
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

        if ($user->getRole() !== UserRoleEnum::ADMIN) {
            Thrower::default()->message('auth', \__('auth.forbidden'))->throw();
        }

        return $next($request);
    }
}
