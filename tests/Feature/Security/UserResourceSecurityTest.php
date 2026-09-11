<?php

use App\Http\Resources\UserResource;
use App\Models\User;

it('does not expose sensitive user attributes in api resources', function () {
    $user = User::factory()->customer()->create([
        'password' => 'password',
        'remember_token' => 'remember-me',
    ]);

    $payload = UserResource::make($user->load(['role', 'tenant']))->resolve();

    expect($payload)
        ->not->toHaveKey('password')
        ->not->toHaveKey('remember_token')
        ->and($payload)->toHaveKeys(['id', 'name', 'email', 'role', 'tenant']);
});
