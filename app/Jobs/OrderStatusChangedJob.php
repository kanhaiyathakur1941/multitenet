<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Modules\Notifications\OrderStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderStatusChangedJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $previousStatus,
    ) {}

    public function handle(): void
    {
        $order = Order::query()->withoutTenant()->with('user')->findOrFail($this->orderId);

        $order->user->notify(new OrderStatusChangedNotification($order, $this->previousStatus));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('OrderStatusChangedJob failed.', [
            'order_id' => $this->orderId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
