<?php

declare(strict_types=1);

use App\Validation\Web\Wallet\MobilePhoneRule;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;

/*
 * Tests for the custom `MobilePhoneRule`.
 *
 * The rule mirrors `RewardWalletService::parsePhone`:
 *  - no `+` prefix → default to Czech, must be a mobile;
 *  - explicit `+XXX` prefix → use that country, must be a mobile.
 *
 * The factory is set up with a translator so the rule's
 * `validation.phone` failure message resolves to a real string and
 * the test can assert against it.
 */

\beforeEach(function (): void {
    $loader = new ArrayLoader();
    $loader->addMessages('en', 'validation', ['phone' => 'validation.phone']);
    $translator = new Translator($loader, 'en');
    $this->factory = new ValidationFactory($translator);
});

\describe('MobilePhoneRule', function (): void {
    \test('accepts a Czech mobile without a + prefix', function (): void {
        $errors = $this->factory->make(
            ['phone' => '730969399'],
            ['phone' => [new MobilePhoneRule()]],
        )->errors();

        \expect($errors->get('phone'))->toBe([]);
    });

    \test('accepts a Czech mobile with a +420 prefix', function (): void {
        $errors = $this->factory->make(
            ['phone' => '+420 730 969 399'],
            ['phone' => [new MobilePhoneRule()]],
        )->errors();

        \expect($errors->get('phone'))->toBe([]);
    });

    \test('accepts a Slovak mobile with a +421 prefix', function (): void {
        $errors = $this->factory->make(
            ['phone' => '+421 911 123 456'],
            ['phone' => [new MobilePhoneRule()]],
        )->errors();

        \expect($errors->get('phone'))->toBe([]);
    });

    \test('accepts a German mobile with a +49 prefix', function (): void {
        $errors = $this->factory->make(
            ['phone' => '+49 151 1234 5678'],
            ['phone' => [new MobilePhoneRule()]],
        )->errors();

        \expect($errors->get('phone'))->toBe([]);
    });

    \test('rejects a string that is not a phone number', function (): void {
        $errors = $this->factory->make(
            ['phone' => 'not-a-phone'],
            ['phone' => ['required', new MobilePhoneRule()]],
        )->errors();

        \expect($errors->get('phone'))->toContain('validation.phone');
    });

    \test('rejects a non-string value', function (): void {
        $errors = $this->factory->make(
            ['phone' => 12345],
            ['phone' => [new MobilePhoneRule()]],
        )->errors();

        \expect($errors->get('phone'))->toContain('validation.phone');
    });
});
