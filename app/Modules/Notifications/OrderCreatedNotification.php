<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Models\Order;
use App\Modules\Notifications\Concerns\UsesEventFlowChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesEventFlowChannels;

    public function __construct(public Order $order) {}

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
            'type' => 'order_created',
            'order_id' => $this->order->id,
            'total' => $this->order->total,
            'status' => $this->order->status->value,
            'message' => "Your order #{$this->order->id} was created successfully.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order confirmed — EventFlow')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("Your order #{$this->order->id} has been confirmed.")
            ->line('Total: '.$this->order->total)
            ->line('Payment reference: '.($this->order->payment_transaction_id ?? 'N/A'))
            ->line('Thank you for shopping with EventFlow.');
    }

    public function toLog(object $notifiable): string
    {
        return "Order #{$this->order->id} created for {$notifiable->email}.";
    }
}
