<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\EventRegistration;
use App\Modules\Notifications\EventRegistrationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class EventRegistrationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $registrationId) {}

    public function handle(): void
    {
        $registration = EventRegistration::query()
            ->withoutTenant()
            ->with([
                'user',
                'event' => fn ($query) => $query->withoutTenant(),
            ])
            ->findOrFail($this->registrationId);

        $registration->user->notify(new EventRegistrationNotification($registration->id));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('EventRegistrationJob failed.', [
            'registration_id' => $this->registrationId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
