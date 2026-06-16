<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Wallet;

use App\Http\Controllers\Web\Concerns\ThrottlesWebRequests;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Services\Reward\RewardWalletService;
use App\Validation\Web\Wallet\StoreWalletValidity;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use libphonenumber\NumberParseException;
use Thinkycz\LaravelCore\Http\RequestSignature;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Thrower;

/**
 * "Create or open my rewards wallet" submission.
 *
 * - Rate-limited to 10/min per IP and 3/min per phone (per the plan).
 * - On success, redirects to the public wallet page at
 *   `/w/{public_token}` with a flash message that differs for
 *   first-time and returning customers.
 */
class WalletStoreController
{
    use ThrottlesWebRequests;
    use ValidatesWebRequests;

    /**
     * Handle the form submission.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $validity = StoreWalletValidity::inject();

        $validated = $this->validateRequest($request, [
            'phone' => $validity->phone()->toArray(),
            'first_name' => $validity->firstName()->toArray(),
        ]);

        $phone = $validated->assertString('phone');
        $firstName = $validated->assertString('first_name');

        // Per-IP limit (10/min) and per-phone limit (3/min). The
        // ThrottlesWebRequests::hit() helper returns a closure that the
        // trait registers for cleanup; here we just need to consume
        // the token once for each limit.
        $this->hit(Limit::perMinutes(1, 10)->by(
            RequestSignature::default('wallet.create')->hash(),
        ));
        $this->hit(Limit::perMinutes(1, 3)->by(
            RequestSignature::default('wallet.create')->data('phone', $phone)->hash(),
        ));

        /** @var RewardWalletService $service */
        $service = Resolver::resolve(RewardWalletService::class);

        // `findOrCreateByPhone` normalizes the phone (E.164) and
        // applies the first_name-only-if-empty rule. We catch the
        // `NumberParseException` so the throttle token isn't consumed
        // for an obviously invalid phone the validity rules missed.
        try {
            $wallet = $service->findOrCreateByPhone($phone, $firstName);
        } catch (NumberParseException) {
            Thrower::default()->message('phone', \__('reward.invalid_phone'))->throw();
        }

        $wasExisting = \trim($wallet->getFirstName()) !== '' && $wallet->getFirstName() !== \trim($firstName);

        Inertia::flash(
            'success',
            $wasExisting
                ? \__('reward.wallet.opened_existing')
                : \__('reward.wallet.ready'),
        );

        return \redirect()->route('wallet.show', ['token' => $wallet->getPublicToken()]);
    }
}

