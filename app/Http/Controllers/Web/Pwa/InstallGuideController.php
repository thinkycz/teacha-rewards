<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Pwa;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the static "How to install Teacha on your home screen"
 * guide for iOS users (whose Safari does not fire
 * `beforeinstallprompt`).
 */
class InstallGuideController
{
    public function __invoke(): Response
    {
        return Inertia::render('Pwa/InstallGuide');
    }
}
