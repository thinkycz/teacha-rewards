<?php

declare(strict_types=1);

\test('GET /wallet returns 200', function (): void {
    $response = $this->get('/wallet');

    $response->assertOk();
});
