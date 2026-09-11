<?php

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('allows tenant admins to create products', function () {
    $user = User::factory()->tenantAdmin()->create();

    expect(Gate::forUser($user)->allows('create', Product::class))->toBeTrue();
});

it('forbids managers and customers from creating products', function (string $state) {
    $user = User::factory()->{$state}()->create();

    expect(Gate::forUser($user)->denies('create', Product::class))->toBeTrue();
})->with(['manager', 'customer']);

it('allows a customer to view an active product', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $product = Product::factory()->active()->for($tenant)->create();

    expect(Gate::forUser($customer)->allows('view', $product))->toBeTrue();
});

it('forbids a customer from viewing an inactive product', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $product = Product::factory()->inactive()->for($tenant)->create();

    expect(Gate::forUser($customer)->denies('view', $product))->toBeTrue();
});
