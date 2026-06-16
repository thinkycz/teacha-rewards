<?php

declare(strict_types=1);

\test('GET /offline returns 200', function (): void {
    $response = $this->get('/offline');

    $response->assertOk();
});
