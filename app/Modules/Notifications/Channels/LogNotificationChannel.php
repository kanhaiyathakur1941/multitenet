<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

final class LogNotificationChannel
{
    /**
     * @param  mixed  $notifiable
     */
    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toLog')) {
            return;
        }

        Log::info($notification->toLog($notifiable));
    }
}
