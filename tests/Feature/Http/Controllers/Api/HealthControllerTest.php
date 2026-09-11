<?php

it('returns a 200 envelope for the health endpoint', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'EventFlow API is running.',
            'data' => [
                'status' => 'ok',
            ],
        ]);
});
