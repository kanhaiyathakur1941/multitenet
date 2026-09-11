<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Models\Order;
use App\Modules\Notifications\Channels\LogNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

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
            'type' => 'order_created',
            'order_id' => $this->order->id,
            'total' => $this->order->total,
            'status' => $this->order->status->value,
            'message' => "Your order #{$this->order->id} was created successfully.",
        ];
    }

    public function toLog(object $notifiable): string
    {
        return "Order #{$this->order->id} created for {$notifiable->email}.";
    }
}
