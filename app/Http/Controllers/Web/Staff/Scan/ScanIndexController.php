<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Staff\Scan;

use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mobile-first staff scanner landing page.
 *
 * The cashier opens this on the phone or tablet, grants camera access,
 * and scans the customer's QR code. The scanner UI is the
 * `html5-qrcode` library mounted on the `Scan/Index` Vue page; on a
 * successful scan we navigate to `Scan/Show/{token}` which renders the
 * customer's wallet summary with the action buttons.
 */
class ScanIndexController
{
    use ValidatesWebRequests;

    public function __invoke(): Response
    {
        return Inertia::render('Staff/Scan/Index');
    }
}
