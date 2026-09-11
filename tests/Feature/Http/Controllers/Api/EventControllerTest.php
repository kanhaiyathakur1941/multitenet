<?php

use App\Models\Event;
use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function eventPayload(array $overrides = []): array
{
    return [
        'title' => 'Summer Launch',
        'description' => 'A product launch event.',
        'location' => 'Austin',
        'start_date' => now()->addWeek()->toDateTimeString(),
        'end_date' => now()->addWeek()->addHours(2)->toDateTimeString(),
        'capacity' => 50,
        'status' => 'published',
        ...$overrides,
    ];
}

describe('index', function () {
    it('returns 401 when a sanctum token is missing', function () {
        $this->getJson('/api/events')->assertUnauthorized();
    });

    it('lists only the current tenant events for a manager', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenantA)->create();
        $managerB = User::factory()->manager()->for($tenantB)->create();

        $own = Event::factory()->for($tenantA)->for($manager, 'creator')->create(['title' => 'Alpha Gala']);
        Event::factory()->for($tenantB)->for($managerB, 'creator')->create(['title' => 'Beta Gala']);

        Sanctum::actingAs($manager);

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.id', $own->id)
            ->assertJsonPath('data.items.0.title', 'Alpha Gala');
    });

    it('lists only published events for a customer', function () {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();
        $customer = User::factory()->customer()->for($tenant)->create();

        $published = Event::factory()->published()->for($tenant)->for($manager, 'creator')->create(['title' => 'Open Night']);
        Event::factory()->draft()->for($tenant)->for($manager, 'creator')->create(['title' => 'Secret Draft']);

        Sanctum::actingAs($customer);

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.id', $published->id);
    });

    it('filters events by status for a manager', function () {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();

        Event::factory()->published()->for($tenant)->for($manager, 'creator')->create();
        Event::factory()->draft()->for($tenant)->for($manager, 'creator')->create();

        Sanctum::actingAs($manager);

        $this->getJson('/api/events?status=draft')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.status', 'draft');
    });

    it('searches events by title', function () {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();

        Event::factory()->for($tenant)->for($manager, 'creator')->create(['title' => 'Jazz Festival']);
        Event::factory()->for($tenant)->for($manager, 'creator')->create(['title' => 'Comedy Night']);

        Sanctum::actingAs($manager);

        $this->getJson('/api/events?search=Jazz')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.title', 'Jazz Festival');
    });
});

describe('store', function () {
    it('creates an event for a manager', function () {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();

        Sanctum::actingAs($manager);

        $this->postJson('/api/events', eventPayload(['title' => 'New Conference']))
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'New Conference')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.created_by.id', $manager->id);

        $this->assertDatabaseHas('events', [
            'title' => 'New Conference',
            'tenant_id' => $tenant->id,
            'created_by' => $manager->id,
        ]);
    });

    it('returns 403 when a customer creates an event', function () {
        $customer = User::factory()->customer()->create();

        Sanctum::actingAs($customer);

        $this->postJson('/api/events', eventPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('events', 0);
    });

    it('returns 422 when required event fields are missing', function () {
        $manager = User::factory()->manager()->create();

        Sanctum::actingAs($manager);

        $this->postJson('/api/events', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'location', 'start_date', 'end_date', 'capacity']);
    });

    it('returns 422 when the end date is before the start date', function () {
        $manager = User::factory()->manager()->create();

        Sanctum::actingAs($manager);

        $this->postJson('/api/events', eventPayload([
            'start_date' => now()->addWeek()->toDateTimeString(),
            'end_date' => now()->addDay()->toDateTimeString(),
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    });
});

describe('show', function () {
    it('returns an event for the owning tenant', function () {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();
        $event = Event::factory()->for($tenant)->for($manager, 'creator')->create(['title' => 'Own Event']);

        Sanctum::actingAs($manager);

        $this->getJson('/api/events/'.$event->id)
            ->assertOk()
            ->assertJsonPath('data.id', $event->id)
            ->assertJsonPath('data.title', 'Own Event');
    });

    it('returns 404 when tenant A requests a tenant B event', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $managerA = User::factory()->manager()->for($tenantA)->create();
        $managerB = User::factory()->manager()->for($tenantB)->create();
        $eventB = Event::factory()->for($tenantB)->for($managerB, 'creator')->create();

        Sanctum::actingAs($managerA);

        $this->getJson('/api/events/'.$eventB->id)
            ->assertNotFound()
            ->assertJsonPath('success', false);
    });

    it('returns 403 when a customer views a draft event', function () {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();
        $customer = User::factory()->customer()->for($tenant)->create();
        $event = Event::factory()->draft()->for($tenant)->for($manager, 'creator')->create();

        Sanctum::actingAs($customer);

        $this->getJson('/api/events/'.$event->id)
            ->assertForbidden();
    });
});

describe('update', function () {
    it('returns 403 when a customer updates an event', function () {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();
        $customer = User::factory()->customer()->for($tenant)->create();
        $event = Event::factory()->published()->for($tenant)->for($manager, 'creator')->create();

        Sanctum::actingAs($customer);

        $this->putJson('/api/events/'.$event->id, eventPayload(['title' => 'Hijacked Title']))
            ->assertForbidden();

        expect($event->fresh()->title)->not->toBe('Hijacked Title');
    });

    it('updates an event for a tenant admin', function () {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin()->for($tenant)->create();
        $event = Event::factory()->for($tenant)->for($admin, 'creator')->create();

        Sanctum::actingAs($admin);

        $this->putJson('/api/events/'.$event->id, eventPayload(['title' => 'Updated Title', 'status' => 'cancelled']))
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.status', 'cancelled');

        expect($event->fresh()->title)->toBe('Updated Title');
    });
});

describe('destroy', function () {
    it('returns 403 when a customer deletes an event', function () {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();
        $customer = User::factory()->customer()->for($tenant)->create();
        $event = Event::factory()->published()->for($tenant)->for($manager, 'creator')->create();

        Sanctum::actingAs($customer);

        $this->deleteJson('/api/events/'.$event->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted($event);
    });

    it('soft deletes an event for a manager', function () {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();
        $event = Event::factory()->for($tenant)->for($manager, 'creator')->create();

        Sanctum::actingAs($manager);

        $this->deleteJson('/api/events/'.$event->id)
            ->assertOk()
            ->assertJsonPath('message', 'Event deleted successfully.');

        $this->assertSoftDeleted($event);
    });

    it('returns 404 when tenant A deletes a tenant B event', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $managerA = User::factory()->manager()->for($tenantA)->create();
        $managerB = User::factory()->manager()->for($tenantB)->create();
        $eventB = Event::factory()->for($tenantB)->for($managerB, 'creator')->create();

        Sanctum::actingAs($managerA);

        $this->deleteJson('/api/events/'.$eventB->id)->assertNotFound();

        $this->assertDatabaseHas('events', ['id' => $eventB->id, 'deleted_at' => null]);
    });
});
