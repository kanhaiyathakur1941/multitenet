<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Concerns;

use App\Modules\Notifications\Channels\LogNotificationChannel;
use App\Modules\Notifications\Channels\SafeMailNotificationChannel;

trait UsesEventFlowChannels
{
    /**
     * @return list<string|class-string>
     */
    protected function eventFlowChannels(): array
    {
        $channels = ['database', LogNotificationChannel::class];

        if (config('eventflow.notifications.mail_enabled')) {
            $channels[] = SafeMailNotificationChannel::class;
        }

        return $channels;
    }
}
