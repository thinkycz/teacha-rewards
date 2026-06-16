<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Marketing;

use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Config;

/**
 * Public marketing landing page.
 *
 * Replaces the boilerplate's `GET /` redirect-to-dashboard closure.
 * `GET /` always renders this marketing page, even for authenticated
 * staff. Staff navigate to `/staff` for their dashboard.
 */
class MarketingIndexController
{
    use ValidatesWebRequests;

    /**
     * Show the marketing landing page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Marketing/Index', [
            'programName' => Config::inject()->assertString('app.name'),
        ]);
    }
}
