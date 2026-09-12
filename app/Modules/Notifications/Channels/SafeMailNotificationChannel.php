<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Channels;

use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SafeMailNotificationChannel
{
    public function __construct(private MailChannel $mailChannel) {}

    /**
     * @param  mixed  $notifiable
     */
    public function send($notifiable, Notification $notification): void
    {
        try {
            $this->mailChannel->send($notifiable, $notification);
        } catch (Throwable $exception) {
            Log::error('Mail notification failed.', [
                'notification' => $notification::class,
                'notifiable_id' => $notifiable->getKey(),
                'notifiable_email' => $notifiable->routeNotificationFor('mail') ?? null,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
