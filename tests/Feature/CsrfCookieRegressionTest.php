<?php

declare(strict_types=1);

/**
 * Regression tests for the Inertia + Laravel CSRF integration.
 *
 * The Inertia v3 client reads the `XSRF-TOKEN` cookie and sets it
 * as the `X-XSRF-TOKEN` header on every XHR. Laravel's
 * `PreventRequestForgery` middleware validates that header against
 * the session token. If the XSRF cookie is missing, the POST
 * fails with TokenMismatchException.
 *
 * Two failure modes to guard against:
 *   1. The session cookie is set with `secure: true` on a plain
 *      `http://` dev URL, so the browser rejects it and the
 *      XSRF cookie never lands. (Local Herd / Valet dev.)
 *   2. The XSRF cookie isn't queued onto the response at all
 *      (e.g. the AddQueuedCookiesToResponse middleware is
 *      misconfigured or removed).
 */
\test('GET / sets the XSRF-TOKEN cookie on the response', function (): void {
    $response = $this->get('/');

    $cookies = $response->headers->getCookies();
    $names = \array_map(static fn ($cookie): string => $cookie->getName(), $cookies);

    \expect(\in_array('XSRF-TOKEN', $names, true))->toBeTrue(
        'XSRF-TOKEN cookie must be set on the first GET so the Inertia client can echo it back on the next POST',
    );
});

\test('XSRF-TOKEN cookie is not flagged Secure when APP_URL is http', function (): void {
    $response = $this->get('/');

    $cookies = $response->headers->getCookies();
    $xsrf = null;
    foreach ($cookies as $cookie) {
        if ($cookie->getName() === 'XSRF-TOKEN') {
            $xsrf = $cookie;
            break;
        }
    }

    if ($xsrf === null) {
        $this->markTestSkipped('XSRF-TOKEN cookie not set; see the other regression test.');
    }

    // On http:// APP_URL the XSRF cookie must NOT be Secure, otherwise
    // the browser will refuse to store / send it on subsequent
    // requests, breaking POST /register etc.
    \expect($xsrf->isSecure())->toBeFalse();
});
