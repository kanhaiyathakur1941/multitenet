<?php

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns tenant dashboard statistics for a manager', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    $customer = User::factory()->customer()->for($tenant)->create();

    Event::factory()->count(2)->published()->for($tenant)->for($manager, 'creator')->create([
        'start_date' => now()->subWeek(),
        'end_date' => now()->subWeek()->addHours(2),
    ]);
    Event::factory()->draft()->for($tenant)->for($manager, 'creator')->create();
    Product::factory()->count(3)->for($tenant)->create();
    Order::factory()->for($tenant)->for($customer)->create([
        'status' => OrderStatus::Confirmed,
        'subtotal' => 100.00,
        'tax' => 10.00,
        'total' => 110.00,
    ]);
    Order::factory()->for($tenant)->for($customer)->create([
        'status' => OrderStatus::Cancelled,
        'total' => 50.00,
    ]);

    $upcoming = Event::factory()->published()->for($tenant)->for($manager, 'creator')->create([
        'title' => 'Future Summit',
        'start_date' => now()->addWeek(),
        'end_date' => now()->addWeek()->addHours(2),
    ]);

    Sanctum::actingAs($manager);

    $this->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total_events', 4)
        ->assertJsonPath('data.published_events', 3)
        ->assertJsonPath('data.total_products', 3)
        ->assertJsonPath('data.total_customers', 1)
        ->assertJsonPath('data.total_orders', 2)
        ->assertJsonPath('data.total_revenue', '110.00')
        ->assertJsonPath('data.upcoming_events.0.id', $upcoming->id)
        ->assertJsonPath('data.upcoming_events.0.title', 'Future Summit')
        ->assertJsonCount(2, 'data.recent_orders');
});

it('returns tenant dashboard statistics for a tenant admin', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->tenantAdmin()->for($tenant)->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/dashboard')->assertOk();
});

it('returns 403 when a customer requests the dashboard', function () {
    $customer = User::factory()->customer()->create();

    Sanctum::actingAs($customer);

    $this->getJson('/api/dashboard')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

it('returns 401 when a sanctum token is missing', function () {
    $this->getJson('/api/dashboard')->assertUnauthorized();
});

it('scopes dashboard statistics to the current tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $managerA = User::factory()->manager()->for($tenantA)->create();
    $managerB = User::factory()->manager()->for($tenantB)->create();

    Event::factory()->count(3)->for($tenantA)->for($managerA, 'creator')->create();
    Event::factory()->count(5)->for($tenantB)->for($managerB, 'creator')->create();
    Product::factory()->count(2)->for($tenantA)->create();
    Product::factory()->count(7)->for($tenantB)->create();

    Sanctum::actingAs($managerA);

    $this->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonPath('data.total_events', 3)
        ->assertJsonPath('data.total_products', 2);
});
