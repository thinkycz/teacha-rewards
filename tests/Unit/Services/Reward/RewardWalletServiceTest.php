<?php

declare(strict_types=1);

use App\Models\RewardWallet;
use App\Services\Reward\RewardWalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use libphonenumber\NumberParseException;

\beforeEach(function (): void {
    $this->service = $this->app->make(RewardWalletService::class);
});

\test('normalizePhone strips spaces and produces E.164', function (): void {
    \expect($this->service->normalizePhone('+420 601 234 567'))->toBe('+420601234567');
});

\test('normalizePhone accepts a local Czech number with no +', function (): void {
    \expect($this->service->normalizePhone('601 234 567'))->toBe('+420601234567');
});

\test('normalizePhone rejects an empty string', function (): void {
    $this->service->normalizePhone('   ');
})->throws(NumberParseException::class);

\test('normalizePhone rejects a string that is not a phone number', function (): void {
    $this->service->normalizePhone('not-a-phone');
})->throws(NumberParseException::class);

\test('findOrCreateByPhone creates a wallet on first call', function (): void {
    $wallet = $this->service->findOrCreateByPhone('+420 601 234 567', 'Anička');

    \expect($wallet)->toBeInstanceOf(RewardWallet::class);
    \expect($wallet->getFirstName())->toBe('Anička');
    \expect($wallet->getPhoneNormalized())->toBe('+420601234567');
});

\test('findOrCreateByPhone returns the same wallet on the second call', function (): void {
    $first = $this->service->findOrCreateByPhone('+420 601 234 567', 'Anička');
    $second = $this->service->findOrCreateByPhone('+420 601 234 567', 'Anička');

    \expect($second->getKey())->toBe($first->getKey());
});

\test('findOrCreateByPhone does NOT update first_name when it is already set', function (): void {
    $first = $this->service->findOrCreateByPhone('+420 601 234 567', 'Anička');
    $second = $this->service->findOrCreateByPhone('+420 601 234 567', 'Anežka');

    \expect($second->getFirstName())->toBe('Anička');
});

\test('findOrCreateByPhone updates first_name only when it is currently empty', function (): void {
    $first = $this->service->findOrCreateByPhone('+420 601 234 567', '   ');
    $second = $this->service->findOrCreateByPhone('+420 601 234 567', 'Anička');

    \expect($second->getFirstName())->toBe('Anička');
});

\test('createWallet generates a 32-char URL-safe public_token', function (): void {
    $wallet = $this->service->createWallet('+420601234567', '+420 601 234 567', 'Anička');

    $token = $wallet->getPublicToken();

    \expect(\strlen($token))->toBe(32);
    \expect($token)->toMatch('/^[A-Za-z0-9]+$/');
});

\test('createWallet generates a wallet number in T-XXXX-XXXX format', function (): void {
    $wallet = $this->service->createWallet('+420601234567', '+420 601 234 567', 'Anička');

    \expect($wallet->getWalletNumber())->toMatch('/^T-[A-Z0-9]{4}-[A-Z0-9]{4}$/');
});

\test('getByPublicToken returns the matching wallet', function (): void {
    $created = $this->service->createWallet('+420601234567', '+420 601 234 567', 'Anička');

    $looked = $this->service->getByPublicToken($created->getPublicToken());

    \expect($looked->getKey())->toBe($created->getKey());
});

\test('getByPublicToken throws ModelNotFoundException for a bad token', function (): void {
    $this->service->getByPublicToken('not-a-real-token');
})->throws(ModelNotFoundException::class);

\test('public_token is unique across calls', function (): void {
    $tokens = [];

    for ($i = 0; $i < 20; $i++) {
        $wallet = $this->service->createWallet('+420 111 222 ' . \str_pad((string) $i, 3, '0', \STR_PAD_LEFT), '+420 111 222 ' . $i, 'Customer ' . $i);
        $tokens[] = $wallet->getPublicToken();
    }

    \expect(\count(\array_unique($tokens)))->toBe(20);
});
