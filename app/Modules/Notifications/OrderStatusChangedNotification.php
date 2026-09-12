<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Models\Order;
use App\Modules\Notifications\Concerns\UsesEventFlowChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesEventFlowChannels;

    public function __construct(
        public Order $order,
        public string $previousStatus,
    ) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return $this->eventFlowChannels();
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order status updated — EventFlow')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("Your order #{$this->order->id} status changed from {$this->previousStatus} to {$this->order->status->value}.")
            ->line('Thank you for using EventFlow.');
    }

    public function toLog(object $notifiable): string
    {
        return "Order #{$this->order->id} status changed from {$this->previousStatus} to {$this->order->status->value} for {$notifiable->email}.";
    }
}
