<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Staff;

use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Typer;

/**
 * Renders the printable "store QR" sheet staff can hand to a printer
 * to stick on the entrance counter. The QR code itself is rendered
 * client-side via the `qrcode` package (already used on the customer
 * wallet page) so we keep a single rendering pipeline and don't
 * need a server-side QR dependency.
 */
class StoreQrPrintController
{
    public function __invoke(Request $request, SettingsService $settings): Response
    {
        $storeName = $settings->get('store_name', 'Teacha');
        $programName = $settings->get('program_name', 'Teacha Rewards');

        // Use the public origin (not the staff URL) so a customer
        // scanning the QR lands on the customer wallet flow, not the
        // staff admin.
        $base = $request->getSchemeAndHttpHost();
        $walletUrl = $base . '/wallet';

        return Inertia::render('Staff/StoreQrPrint', [
            'store_name' => Typer::assertString($storeName),
            'program_name' => Typer::assertString($programName),
            'wallet_url' => $walletUrl,
        ]);
    }
}
