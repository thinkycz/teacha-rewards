<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Wallet;

use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Show the "create or open my rewards wallet" form.
 *
 * The form is a single screen with two fields (`phone`, `first_name`).
 * On submit (`POST /wallet`), the controller upserts the wallet by
 * phone and redirects to the public wallet page at `/w/{public_token}`.
 */
class WalletCreateController
{
    use ValidatesWebRequests;

    /**
     * Show the create-or-find form.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Wallet/Create');
    }
}
