<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Web\Concerns\ThrottlesWebRequests;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Thinkycz\LaravelCore\Support\Resolver;

class VerifyEmailController
{
    use ThrottlesWebRequests;

    /**
     * Show the email verification notice.
     */
    public function create(): Response
    {
        return Inertia::render('auth/VerifyEmail');
    }

    /**
     * Resend an email verification message.
     */
    public function store(Request $request): SymfonyResponse
    {
        $this->hit($this->limit());

        $user = User::mustAuth();

        if (!$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return Resolver::resolveRedirector()
            ->to('/verify-email')
            ->with('success', \__('Verification email sent.'));
    }
}
