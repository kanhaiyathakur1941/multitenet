<?php

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('registers a customer for a published event', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $event = Event::factory()->published()->for($tenant)->for($manager, 'creator')->create();

    Sanctum::actingAs($customer);

    $this->postJson('/api/events/'.$event->id.'/register')
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.event_id', $event->id)
        ->assertJsonPath('data.user_id', $customer->id)
        ->assertJsonPath('data.event.id', $event->id);

    $this->assertDatabaseHas('event_registrations', [
        'event_id' => $event->id,
        'user_id' => $customer->id,
        'tenant_id' => $tenant->id,
    ]);
});

it('returns 422 when the customer is already registered', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $event = Event::factory()->published()->for($tenant)->for($manager, 'creator')->create();

    EventRegistration::factory()->create([
        'tenant_id' => $tenant->id,
        'event_id' => $event->id,
        'user_id' => $customer->id,
    ]);

    Sanctum::actingAs($customer);

    $this->postJson('/api/events/'.$event->id.'/register')
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('errors.event.0', 'You are already registered for this event.');

    $this->assertDatabaseCount('event_registrations', 1);
});

it('returns 422 when the event is at capacity', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    $firstCustomer = User::factory()->customer()->for($tenant)->create();
    $secondCustomer = User::factory()->customer()->for($tenant)->create();
    $event = Event::factory()->published()->for($tenant)->for($manager, 'creator')->create([
        'capacity' => 1,
    ]);

    EventRegistration::factory()->create([
        'tenant_id' => $tenant->id,
        'event_id' => $event->id,
        'user_id' => $firstCustomer->id,
    ]);

    Sanctum::actingAs($secondCustomer);

    $this->postJson('/api/events/'.$event->id.'/register')
        ->assertUnprocessable()
        ->assertJsonPath('errors.event.0', 'This event is at capacity.');

    $this->assertDatabaseCount('event_registrations', 1);
});

it('returns 403 when a customer registers for a draft event', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $event = Event::factory()->draft()->for($tenant)->for($manager, 'creator')->create();

    Sanctum::actingAs($customer);

    $this->postJson('/api/events/'.$event->id.'/register')
        ->assertForbidden();

    $this->assertDatabaseCount('event_registrations', 0);
});

it('returns 403 when a manager registers for an event', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    $event = Event::factory()->published()->for($tenant)->for($manager, 'creator')->create();

    Sanctum::actingAs($manager);

    $this->postJson('/api/events/'.$event->id.'/register')
        ->assertForbidden();
});

it('returns 404 when tenant A registers for a tenant B event', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $customerA = User::factory()->customer()->for($tenantA)->create();
    $managerB = User::factory()->manager()->for($tenantB)->create();
    $eventB = Event::factory()->published()->for($tenantB)->for($managerB, 'creator')->create();

    Sanctum::actingAs($customerA);

    $this->postJson('/api/events/'.$eventB->id.'/register')
        ->assertNotFound();

    $this->assertDatabaseCount('event_registrations', 0);
});

it('returns 401 when a sanctum token is missing', function () {
    $this->postJson('/api/events/1/register')->assertUnauthorized();
});
