<?php

use App\Models\Event;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('allows managers and tenant admins to create events', function (string $state) {
    $user = User::factory()->{$state}()->create();

    expect(Gate::forUser($user)->allows('create', Event::class))->toBeTrue();
})->with(['manager', 'tenantAdmin']);

it('forbids customers and super admins from creating events', function (string $state) {
    $user = User::factory()->{$state}()->create();

    expect(Gate::forUser($user)->denies('create', Event::class))->toBeTrue();
})->with(['customer', 'superAdmin']);

it('allows a customer to view a published event', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $event = Event::factory()->published()->for($tenant)->for($customer, 'creator')->create();

    expect(Gate::forUser($customer)->allows('view', $event))->toBeTrue();
});

it('forbids a customer from viewing a draft event', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $event = Event::factory()->draft()->for($tenant)->for($customer, 'creator')->create();

    expect(Gate::forUser($customer)->denies('view', $event))->toBeTrue();
});

it('allows a manager to view a draft event', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    $event = Event::factory()->draft()->for($tenant)->for($manager, 'creator')->create();

    expect(Gate::forUser($manager)->allows('view', $event))->toBeTrue();
});

it('allows managers and tenant admins to update and delete events', function (string $ability) {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    $event = Event::factory()->for($tenant)->for($manager, 'creator')->create();

    expect(Gate::forUser($manager)->allows($ability, $event))->toBeTrue();
})->with(['update', 'delete']);

it('allows a customer to register for a published event', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $event = Event::factory()->published()->for($tenant)->for($customer, 'creator')->create();

    expect(Gate::forUser($customer)->allows('register', $event))->toBeTrue();
});

it('forbids a manager from registering for an event', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    $event = Event::factory()->published()->for($tenant)->for($manager, 'creator')->create();

    expect(Gate::forUser($manager)->denies('register', $event))->toBeTrue();
});
