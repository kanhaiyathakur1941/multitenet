<?php

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    config(['eventflow.payments.driver' => 'fake']);
});

it('shows the customer login page at the site root', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Sign in to browse events');
});

it('redirects the legacy portal login url to the site root', function () {
    $this->get('/portal/login')
        ->assertRedirect('/');
});

it('allows a customer to sign in and view the dashboard', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();

    $this->post(route('portal.login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertRedirect(route('portal.dashboard'));

    $this->actingAs($customer)
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSee($customer->name);
});

it('rejects staff users at the customer login', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->tenantAdmin()->for($tenant)->create();

    $this->post(route('portal.login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('forbids staff users from the customer portal', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->tenantAdmin()->for($tenant)->create();

    $this->actingAs($admin)
        ->get(route('portal.dashboard'))
        ->assertForbidden();
});

it('lets a customer place an order from the portal cart', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $product = Product::factory()->for($tenant)->create([
        'price' => 10.00,
        'stock' => 5,
    ]);

    $this->actingAs($customer)
        ->post(route('portal.cart.store', $product), ['quantity' => 2])
        ->assertRedirect(route('portal.cart.index'));

    $this->actingAs($customer)
        ->post(route('portal.cart.checkout'))
        ->assertRedirect();

    $this->assertDatabaseHas('orders', [
        'user_id' => $customer->id,
        'tenant_id' => $tenant->id,
        'total' => 22.00,
    ]);
});
