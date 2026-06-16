<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Pwa;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Offline page rendered by the Inertia route when the customer
 * loses connectivity. The service worker also serves a static
 * `public/offline.html` for navigations that never reach the server.
 */
class OfflineController
{
    /**
     * Render the offline fallback page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Pwa/Offline');
    }
}
