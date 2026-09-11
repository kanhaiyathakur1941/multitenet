<?php

use App\Shared\Exceptions\ApiExceptionRenderer;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('returns a generic message for unexpected server errors when debug is disabled', function () {
    config(['app.debug' => false]);

    $response = app(ApiExceptionRenderer::class)->render(
        new RuntimeException('Database credentials leaked'),
    );

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getData(true))->toBe([
            'success' => false,
            'message' => 'An unexpected error occurred.',
        ]);
});

it('hides http 500 exception details when debug is disabled', function () {
    config(['app.debug' => false]);

    $response = app(ApiExceptionRenderer::class)->render(
        new HttpException(500, 'Connection refused: mysql://root:secret@127.0.0.1'),
    );

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getData(true)['message'])->toBe('An unexpected error occurred.')
        ->and($response->getData(true)['message'])->not->toContain('secret');
});

it('returns the http exception message for client errors when debug is disabled', function () {
    config(['app.debug' => false]);

    $response = app(ApiExceptionRenderer::class)->render(
        new HttpException(400, 'Invalid payload.'),
    );

    expect($response->getStatusCode())->toBe(400)
        ->and($response->getData(true)['message'])->toBe('Invalid payload.');
});
