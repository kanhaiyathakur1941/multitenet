<?php

use App\Enums\OrderStatus;
use App\Jobs\OrderStatusChangedJob;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Orders\OrderService;
use Illuminate\Support\Facades\Queue;

it('stores a database notification when the job is handled', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $order = Order::factory()->for($tenant)->for($customer)->create([
        'status' => OrderStatus::Confirmed,
    ]);

    (new OrderStatusChangedJob($order->id, OrderStatus::Pending->value))->handle();

    $notification = $customer->notifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->data['type'])->toBe('order_status_changed')
        ->and($notification->data['previous_status'])->toBe('pending')
        ->and($notification->data['status'])->toBe('confirmed');
});

it('dispatches a job when order status is updated', function () {
    Queue::fake([OrderStatusChangedJob::class]);

    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $order = Order::factory()->for($tenant)->for($customer)->create([
        'status' => OrderStatus::Confirmed,
    ]);

    app(OrderService::class)->updateStatus($order, OrderStatus::Processing);

    Queue::assertPushed(
        OrderStatusChangedJob::class,
        fn (OrderStatusChangedJob $job): bool => $job->orderId === $order->id
            && $job->previousStatus === OrderStatus::Confirmed->value,
    );
});
