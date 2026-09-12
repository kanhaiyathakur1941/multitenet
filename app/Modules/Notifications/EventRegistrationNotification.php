<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Models\EventRegistration;
use App\Modules\Notifications\Concerns\UsesEventFlowChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRegistrationNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesEventFlowChannels;

    public function __construct(public EventRegistration $registration) {}

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
            'type' => 'event_registration',
            'event_id' => $this->registration->event_id,
            'event_title' => $this->registration->event?->title,
            'message' => "You are registered for {$this->registration->event?->title}.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventTitle = $this->registration->event?->title ?? 'your event';

        return (new MailMessage)
            ->subject('Event registration confirmed — EventFlow')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("You are registered for {$eventTitle}.")
            ->line('We look forward to seeing you there.');
    }

    public function toLog(object $notifiable): string
    {
        return "User {$notifiable->email} registered for event #{$this->registration->event_id}.";
    }
}
