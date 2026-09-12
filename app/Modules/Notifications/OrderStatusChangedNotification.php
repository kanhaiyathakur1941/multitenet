<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Models\Order;
use App\Modules\Notifications\Concerns\UsesEventFlowChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification
{
    use UsesEventFlowChannels;

    public function __construct(
        public int $orderId,
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
        $order = $this->order();

        return [
            'type' => 'order_status_changed',
            'order_id' => $order->id,
            'previous_status' => $this->previousStatus,
            'status' => $order->status->value,
            'message' => "Order #{$order->id} status changed to {$order->status->value}.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order();

        return (new MailMessage)
            ->subject('Order status updated — EventFlow')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("Your order #{$order->id} status changed from {$this->previousStatus} to {$order->status->value}.")
            ->line('Thank you for using EventFlow.');
    }

    public function toLog(object $notifiable): string
    {
        $order = $this->order();

        return "Order #{$order->id} status changed from {$this->previousStatus} to {$order->status->value} for {$notifiable->email}.";
    }

    private function order(): Order
    {
        return Order::query()->withoutTenant()->findOrFail($this->orderId);
    }
}
