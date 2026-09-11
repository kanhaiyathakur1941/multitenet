<?php

use App\Models\Event;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns 403 when a super admin lists tenant events', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    Event::factory()->for($tenant)->for($manager, 'creator')->create();
    $superAdmin = User::factory()->superAdmin()->create();

    Sanctum::actingAs($superAdmin);

    $this->getJson('/api/events')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

it('returns 403 when a super admin lists tenant products', function () {
    $tenant = Tenant::factory()->create();
    Product::factory()->for($tenant)->create();
    $superAdmin = User::factory()->superAdmin()->create();

    Sanctum::actingAs($superAdmin);

    $this->getJson('/api/products')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

it('returns 403 when a super admin requests the dashboard', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    Sanctum::actingAs($superAdmin);

    $this->getJson('/api/dashboard')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});
