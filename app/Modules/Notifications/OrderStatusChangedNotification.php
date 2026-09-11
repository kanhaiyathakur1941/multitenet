<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Models\Order;
use App\Modules\Notifications\Channels\LogNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $previousStatus,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', LogNotificationChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_status_changed',
            'order_id' => $this->order->id,
            'previous_status' => $this->previousStatus,
            'status' => $this->order->status->value,
            'message' => "Order #{$this->order->id} status changed to {$this->order->status->value}.",
        ];
    }

    public function toLog(object $notifiable): string
    {
        return "Order #{$this->order->id} status changed from {$this->previousStatus} to {$this->order->status->value} for {$notifiable->email}.";
    }
}
