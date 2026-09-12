<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Models\EventRegistration;
use App\Modules\Notifications\Concerns\UsesEventFlowChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRegistrationNotification extends Notification
{
    use UsesEventFlowChannels;

    public function __construct(public int $registrationId) {}

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
        $registration = $this->registration();

        return [
            'type' => 'event_registration',
            'event_id' => $registration->event_id,
            'event_title' => $registration->event?->title,
            'message' => "You are registered for {$registration->event?->title}.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $registration = $this->registration();
        $eventTitle = $registration->event?->title ?? 'your event';

        return (new MailMessage)
            ->subject('Event registration confirmed — EventFlow')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("You are registered for {$eventTitle}.")
            ->line('We look forward to seeing you there.');
    }

    public function toLog(object $notifiable): string
    {
        $registration = $this->registration();

        return "User {$notifiable->email} registered for event #{$registration->event_id}.";
    }

    private function registration(): EventRegistration
    {
        return EventRegistration::query()
            ->withoutTenant()
            ->with(['event' => fn ($query) => $query->withoutTenant()])
            ->findOrFail($this->registrationId);
    }
}
