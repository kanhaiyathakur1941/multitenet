<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('login', function () {
    it('returns a token and user for valid credentials', function () {
        $user = User::factory()->customer()->create([
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logged in successfully.')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonPath('data.user.role.slug', 'customer')
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('password');

        expect($response->json('data.token'))->toBeString()->not->toBeEmpty();

        $this->assertDatabaseCount('personal_access_tokens', 1);
    });

    it('returns 422 when the password is invalid', function () {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

    it('returns 422 when the email is unknown', function () {
        $this->postJson('/api/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

    it('returns 422 when credentials are missing', function () {
        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });
});

describe('me', function () {
    it('returns the authenticated user', function () {
        $user = User::factory()->manager()->create([
            'name' => 'Alex Manager',
            'email' => 'alex@example.com',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', 'Alex Manager')
            ->assertJsonPath('data.user.role.slug', 'manager')
            ->assertJsonPath('data.user.tenant.id', $user->tenant_id)
            ->assertJsonMissingPath('data.token')
            ->assertJsonMissingPath('data.user.password');
    });

    it('returns 401 when a sanctum token is missing', function () {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    });
});

describe('logout', function () {
    it('revokes the current token', function () {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $token = $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password',
        ])->json('data.token');

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logged out successfully.');

        expect($user->fresh()->tokens()->count())->toBe(0);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    });

    it('returns 401 when a sanctum token is missing', function () {
        $this->postJson('/api/auth/logout')
            ->assertUnauthorized();
    });
});
