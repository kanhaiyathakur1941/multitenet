<?php

it('returns a 404 envelope for unknown api routes', function () {
    $response = $this->getJson('/api/this-route-does-not-exist');

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Resource not found.',
        ])
        ->assertJsonMissingPath('trace');
});

it('returns a 401 envelope when a sanctum token is missing', function () {
    $response = $this->getJson('/api/auth/me');

    $response->assertUnauthorized()
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
});
