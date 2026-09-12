<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Models\Order;
use App\Modules\Notifications\Concerns\UsesEventFlowChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification
{
    use UsesEventFlowChannels;

    public function __construct(public int $orderId) {}

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
            'type' => 'order_created',
            'order_id' => $order->id,
            'total' => $order->total,
            'status' => $order->status->value,
            'message' => "Your order #{$order->id} was created successfully.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order();

        return (new MailMessage)
            ->subject('Order confirmed — EventFlow')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("Your order #{$order->id} has been confirmed.")
            ->line('Total: '.$order->total)
            ->line('Payment reference: '.($order->payment_transaction_id ?? 'N/A'))
            ->line('Thank you for shopping with EventFlow.');
    }

    public function toLog(object $notifiable): string
    {
        $order = $this->order();

        return "Order #{$order->id} created for {$notifiable->email}.";
    }

    private function order(): Order
    {
        return Order::query()->withoutTenant()->findOrFail($this->orderId);
    }
}
