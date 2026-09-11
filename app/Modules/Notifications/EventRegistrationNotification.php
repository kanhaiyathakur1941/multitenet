<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Models\EventRegistration;
use App\Modules\Notifications\Channels\LogNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventRegistrationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public EventRegistration $registration) {}

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
            'type' => 'event_registration',
            'event_id' => $this->registration->event_id,
            'event_title' => $this->registration->event?->title,
            'message' => "You are registered for {$this->registration->event?->title}.",
        ];
    }

    public function toLog(object $notifiable): string
    {
        return "User {$notifiable->email} registered for event #{$this->registration->event_id}.";
    }
}
