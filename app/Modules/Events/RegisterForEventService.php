<?php

declare(strict_types=1);

namespace App\Modules\Events;

use App\Enums\EventStatus;
use App\Jobs\EventRegistrationJob;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class RegisterForEventService
{
    public function register(Event $event, User $user): EventRegistration
    {
        try {
            $registration = DB::transaction(function () use ($event, $user): EventRegistration {
                $locked = Event::query()
                    ->whereKey($event->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status !== EventStatus::Published) {
                    throw ValidationException::withMessages([
                        'event' => ['This event is not open for registration.'],
                    ]);
                }

                $alreadyRegistered = EventRegistration::query()
                    ->where('event_id', $locked->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($alreadyRegistered) {
                    throw ValidationException::withMessages([
                        'event' => ['You are already registered for this event.'],
                    ]);
                }

                $registeredCount = EventRegistration::query()
                    ->where('event_id', $locked->id)
                    ->count();

                if ($registeredCount >= $locked->capacity) {
                    Log::info('Event registration rejected because the event is at capacity.', [
                        'event_id' => $locked->id,
                        'tenant_id' => $locked->tenant_id,
                    ]);

                    throw ValidationException::withMessages([
                        'event' => ['This event is at capacity.'],
                    ]);
                }

                $registration = EventRegistration::query()->create([
                    'tenant_id' => $locked->tenant_id,
                    'event_id' => $locked->id,
                    'user_id' => $user->id,
                ]);

                return $registration->load('event');
            });

            EventRegistrationJob::dispatch($registration->id);

            return $registration;
        } catch (UniqueConstraintViolationException|QueryException $exception) {
            if (! $this->isDuplicateRegistration($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'event' => ['You are already registered for this event.'],
            ]);
        }
    }

    private function isDuplicateRegistration(QueryException $exception): bool
    {
        if ($exception instanceof UniqueConstraintViolationException) {
            return true;
        }

        $message = $exception->getMessage();

        return str_contains($message, 'event_registrations_event_id_user_id_unique')
            || str_contains($message, 'UNIQUE constraint failed: event_registrations.event_id');
    }
}
