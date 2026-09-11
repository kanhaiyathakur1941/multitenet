<?php

use App\Models\User;

it('allows tenant admins to access the filament login page', function () {
    $this->get('/admin/login')->assertOk();
});

it('redirects guests from the admin dashboard to login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('allows a tenant admin to access the admin dashboard', function () {
    $admin = User::factory()->tenantAdmin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

it('allows a manager to access the admin dashboard', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get('/admin')
        ->assertOk();
});

it('forbids customers from accessing the admin dashboard', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)
        ->get('/admin')
        ->assertForbidden();
});

it('allows a super admin to access the admin dashboard', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->get('/admin')
        ->assertOk();
});
