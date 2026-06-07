<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    /**
     * Show the dashboard.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard');
    }
}
