<?php

use App\Jobs\OrderCreatedJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

it('stores a database notification when the job is handled', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $order = Order::factory()->for($tenant)->for($customer)->create();

    (new OrderCreatedJob($order->id))->handle();

    $notification = $customer->notifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->data['type'])->toBe('order_created')
        ->and($notification->data['order_id'])->toBe($order->id);
});

it('is dispatched after an order is created', function () {
    Queue::fake([OrderCreatedJob::class]);

    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $product = Product::factory()->for($tenant)->create(['stock' => 5]);

    Sanctum::actingAs($customer);

    $this->postJson('/api/orders', [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertCreated();

    Queue::assertPushed(OrderCreatedJob::class, fn (OrderCreatedJob $job): bool => $job->orderId > 0);
});
