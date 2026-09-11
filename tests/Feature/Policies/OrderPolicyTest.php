<?php

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('allows customers to create orders', function () {
    $user = User::factory()->customer()->create();

    expect(Gate::forUser($user)->allows('create', Order::class))->toBeTrue();
});

it('forbids managers from creating orders', function () {
    $user = User::factory()->manager()->create();

    expect(Gate::forUser($user)->denies('create', Order::class))->toBeTrue();
});

it('allows a customer to view their own order', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $order = Order::factory()->for($tenant)->for($customer)->create();

    expect(Gate::forUser($customer)->allows('view', $order))->toBeTrue();
});

it('forbids a customer from viewing another customers order', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $otherCustomer = User::factory()->customer()->for($tenant)->create();
    $order = Order::factory()->for($tenant)->for($otherCustomer)->create();

    expect(Gate::forUser($customer)->denies('view', $order))->toBeTrue();
});

it('allows a manager to view any tenant order', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $order = Order::factory()->for($tenant)->for($customer)->create();

    expect(Gate::forUser($manager)->allows('view', $order))->toBeTrue();
});
