<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Dashboard\Settings;

use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Services\Settings\SettingsService;
use App\Validation\Web\Staff\SettingsValidity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Thinkycz\LaravelCore\Support\Resolver;

/**
 * Admin settings update.
 *
 * Writes the four settings through `SettingsService::set`. The
 * `cashback_rate` value is stored as the raw string and re-rounded
 * on read; the form does its own rounding so the cashier sees the
 * "10.00" form they're entering.
 */
class SettingsUpdateController
{
    use ValidatesWebRequests;

    public function __invoke(Request $request): RedirectResponse
    {
        $validity = SettingsValidity::inject();

        $validated = $this->validateRequest($request, [
            'cashback_rate' => $validity->cashbackRate()->toArray(),
            'currency' => $validity->currency()->toArray(),
            'program_name' => $validity->programName()->toArray(),
            'store_name' => $validity->storeName()->toArray(),
        ]);

        /** @var SettingsService $settings */
        $settings = Resolver::resolve(SettingsService::class);

        $settings->set('cashback_rate', $validated->assertString('cashback_rate'));
        $settings->set('currency', $validated->assertString('currency'));
        $settings->set('program_name', $validated->assertString('program_name'));
        $settings->set('store_name', $validated->assertString('store_name'));

        Inertia::flash('success', \__('Settings updated.'));

        return \redirect()->route('dashboard.settings.edit');
    }
}
