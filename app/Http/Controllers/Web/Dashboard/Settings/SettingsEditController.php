<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Dashboard\Settings;

use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Services\Settings\SettingsService;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;

/**
 * Admin settings page.
 *
 * Renders the program-mode toggle plus the cashback-mode fields
 * (rate, currency) and the stamps-mode fields
 * (stamps_per_reward, stamps_per_reward_label). All seven values
 * are stored as key/value strings in the `settings` table; the
 * service normalizes them on read.
 */
class SettingsEditController
{
    use ValidatesWebRequests;

    public function __invoke(): Response
    {
        /** @var SettingsService $settings */
        $settings = Resolver::resolve(SettingsService::class);

        $cashback = $settings->get('cashback_rate', '10');
        $currency = $settings->get('currency', 'CZK');
        $programName = $settings->get('program_name', 'Teacha Rewards');
        $storeName = $settings->get('store_name', 'Teacha');
        $programMode = $settings->getProgramMode();
        $stampsPerReward = $settings->getStampsPerReward();
        $stampsRewardLabel = $settings->getStampsRewardLabel();
        $stampIcon = $settings->getStampIcon();

        return Inertia::render('Dashboard/Settings/Index', [
            'settings' => [
                'cashback_rate' => Typer::assertString(\is_scalar($cashback) ? (string) $cashback : '10'),
                'currency' => Typer::assertString(\is_scalar($currency) ? (string) $currency : 'CZK'),
                'program_name' => Typer::assertString(\is_scalar($programName) ? (string) $programName : 'Teacha Rewards'),
                'store_name' => Typer::assertString(\is_scalar($storeName) ? (string) $storeName : 'Teacha'),
                'program_mode' => $programMode,
                'stamps_per_reward' => (string) $stampsPerReward,
                'stamps_per_reward_label' => $stampsRewardLabel,
                'stamp_icon' => $stampIcon,
            ],
        ]);
    }
}
