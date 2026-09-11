<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Modules\Notifications\OrderCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderCreatedJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId) {}

    public function handle(): void
    {
        $order = Order::query()->withoutTenant()->with('user')->findOrFail($this->orderId);

        $order->user->notify(new OrderCreatedNotification($order));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('OrderCreatedJob failed.', [
            'order_id' => $this->orderId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
