<?php

use App\Jobs\EventRegistrationJob;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

it('stores a database notification when the job is handled', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    $event = Event::factory()->published()->for($tenant)->for($manager, 'creator')->create();
    $registration = EventRegistration::factory()->create([
        'tenant_id' => $tenant->id,
        'event_id' => $event->id,
        'user_id' => $customer->id,
    ]);

    (new EventRegistrationJob($registration->id))->handle();

    $notification = $customer->notifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->data['type'])->toBe('event_registration')
        ->and($notification->data['event_id'])->toBe($event->id);
});

it('is dispatched after a customer registers for an event', function () {
    Queue::fake([EventRegistrationJob::class]);

    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $manager = User::factory()->manager()->for($tenant)->create();
    $event = Event::factory()->published()->for($tenant)->for($manager, 'creator')->create();

    Sanctum::actingAs($customer);

    $this->postJson('/api/events/'.$event->id.'/register')->assertCreated();

    Queue::assertPushed(EventRegistrationJob::class);
});
