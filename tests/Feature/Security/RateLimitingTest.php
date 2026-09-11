<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    config([
        'eventflow.rate_limits.login.max_attempts' => 2,
        'eventflow.rate_limits.api.max_attempts' => 2,
    ]);

    Cache::flush();
});

describe('login rate limiting', function () {
    it('returns 429 after too many login attempts for the same email and ip', function () {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $payload = [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ];

        $this->postJson('/api/auth/login', $payload)->assertUnprocessable();
        $this->postJson('/api/auth/login', $payload)->assertUnprocessable();

        $this->postJson('/api/auth/login', $payload)
            ->assertTooManyRequests()
            ->assertJson([
                'success' => false,
                'message' => 'Too many requests.',
            ]);
    });
});

describe('api rate limiting', function () {
    it('returns 429 after too many authenticated api requests', function () {
        Sanctum::actingAs(User::factory()->customer()->create());

        $this->getJson('/api/auth/me')->assertOk();
        $this->getJson('/api/auth/me')->assertOk();

        $this->getJson('/api/auth/me')
            ->assertTooManyRequests()
            ->assertJson([
                'success' => false,
                'message' => 'Too many requests.',
            ]);
    });

    it('rate limits unauthenticated api requests by ip', function () {
        $this->getJson('/api/health')->assertOk();
        $this->getJson('/api/health')->assertOk();

        $this->getJson('/api/health')->assertTooManyRequests();
    });
});
